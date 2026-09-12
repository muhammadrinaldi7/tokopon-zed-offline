<?php

namespace App\Livewire\Zoffline\Inbound;

use Livewire\Component;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\DeviceInspection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.z', ['title' => 'Inbound QC - TokoPun'])]
class Scan extends Component
{
    public PurchaseOrder $po;

    // UI States
    public $activeItemNo = null; // Item yang sedang discan SKU nya
    public $activeItemRowId = null; // ID item yang sedang discan
    public $barcodeInput = '';
    public $errorMessage = '';
    public $successMessage = '';

    // Detailed QC State

    public $scannedImei = '';
    public $activeItemId = null;

    // Inline Editing IMEI State
    public $editingInspectionId = null;
    public $editingImei = '';
    public $editErrorMessage = '';

    // Warehouse Selection & Confirmation Modal
    public $selectedWarehouseId = null;
    public $showConfirmModal = false;

    // Migration / Copy from Another PO State
    public $showMigrateModal = false;
    public $sourcePoId = null;
    public $itemMappings = []; // [target_item_id => source_item_id]
    public $migrateOption = 'move'; // 'move' or 'copy'
    public $resetPushedStatus = true;
    public $migrateErrorMessage = '';

    /**
     * Validasi format IMEI atau Serial Number dengan Luhn Checksum Algorithm
     */
    public static function validateImeiOrSn(string $input): array
    {
        $clean = strtoupper(trim(preg_replace('/[\s\-\.]/', '', $input)));
        if (empty($clean)) {
            return [
                'valid' => false,
                'clean' => '',
                'type' => 'empty',
                'message' => 'IMEI atau Serial Number tidak boleh kosong.'
            ];
        }

        if (!preg_match('/^[A-Z0-9]+$/', $clean)) {
            return [
                'valid' => false,
                'clean' => $clean,
                'type' => 'invalid_chars',
                'message' => 'Karakter tidak valid. Hanya angka dan huruf kapital tanpa simbol yang diperbolehkan.'
            ];
        }

        $length = strlen($clean);

        // Jika angka murni (Standar IMEI GSM)
        if (ctype_digit($clean)) {
            // Standar IMEI GSM Internasional: 15 digit (dengan Luhn Check Digit di digit ke-15)
            if ($length === 15) {
                $sum = 0;
                $parity = $length % 2;
                for ($i = 0; $i < $length; $i++) {
                    $digit = (int)$clean[$i];
                    if ($i % 2 === $parity) {
                        $digit *= 2;
                        if ($digit > 9) $digit -= 9;
                    }
                    $sum += $digit;
                }

                if ($sum % 10 !== 0) {
                    return [
                        'valid' => false,
                        'clean' => $clean,
                        'type' => 'imei_invalid_luhn',
                        'message' => "Format IMEI 15 digit tidak valid (Luhn Checksum gagal). Ada kemungkinan angka salah ketik atau tertukar. Mohon periksa kembali fisik HP."
                    ];
                }

                return [
                    'valid' => true,
                    'clean' => $clean,
                    'type' => 'imei_valid',
                    'message' => 'IMEI 15 Digit Valid (Luhn Passed)'
                ];
            }

            if ($length === 14) {
                // 14 digit IMEI tanpa check digit
                return [
                    'valid' => true,
                    'clean' => $clean,
                    'type' => 'imei_14_digit',
                    'message' => 'IMEI 14 Digit (Tanpa Check Digit)'
                ];
            }

            if ($length < 8 || $length > 18) {
                return [
                    'valid' => false,
                    'clean' => $clean,
                    'type' => 'invalid_length',
                    'message' => "Panjang Serial Number/IMEI tidak wajar ({$length} digit). Standar IMEI adalah 15 digit angka."
                ];
            }

            return [
                'valid' => true,
                'clean' => $clean,
                'type' => 'numeric_sn',
                'message' => "Serial Number Numerik ({$length} digit)"
            ];
        }

        // Alfanumerik (misal Apple SN, laptop, aksesoris)
        if ($length >= 6 && $length <= 20) {
            return [
                'valid' => true,
                'clean' => $clean,
                'type' => 'alphanumeric_sn',
                'message' => "Serial Number Alfanumerik ({$length} karakter)"
            ];
        }

        return [
            'valid' => false,
            'clean' => $clean,
            'type' => 'invalid_sn',
            'message' => "Panjang Serial Number alfanumerik ({$length} karakter) tidak wajar (standar 6-20 karakter)."
        ];
    }

    public function mount(PurchaseOrder $po)
    {
        $this->po = $po->load('items.inspections');
        $this->selectedWarehouseId = Auth::user()->warehouse_id;
    }

    public function setActiveItem($itemNo)
    {
        $this->errorMessage = '';
        $this->successMessage = '';

        // Cari item yang belum penuh
        $item = $this->po->items->where('item_no', $itemNo)
            ->filter(function ($i) {
                return $i->quantity_received < $i->quantity_ordered;
            })->first();

        // Jika semua sudah penuh, ambil yang pertama untuk tampilkan error
        if (!$item) {
            $item = $this->po->items->where('item_no', $itemNo)->first();
            if ($item && $item->quantity_received >= $item->quantity_ordered) {
                $this->errorMessage = "Item {$item->item_name} sudah mencapai kuantitas pesanan.";
                return;
            }
        }

        if (!$item) return;

        // Cek apakah produk ini membutuhkan SN
        $productAccurate = \App\Models\ProductAccurate::where('item_no', $itemNo)
            ->where('database_source', $this->po->database_source)
            ->first();

        $hasSn = $productAccurate ? $productAccurate->has_sn : true; // Default true jika tidak ada data

        if (!$hasSn) {
            $item->increment('quantity_received');
            $this->po->refresh();
            $this->successMessage = "1 {$item->item_name} berhasil ditambahkan.";
            return;
        }

        $this->activeItemNo = $itemNo;
        $this->activeItemRowId = $item->id;
        $this->barcodeInput = '';
    }

    public function setActiveItemByRow($id)
    {
        $this->errorMessage = '';
        $this->successMessage = '';

        $item = $this->po->items->where('id', $id)->first();
        if (!$item) return;

        if ($item->quantity_received >= $item->quantity_ordered) {
            $this->errorMessage = "Item {$item->item_name} sudah mencapai kuantitas pesanan.";
            return;
        }

        // Cek apakah produk ini membutuhkan SN
        $productAccurate = \App\Models\ProductAccurate::where('item_no', $item->item_no)
            ->where('database_source', $this->po->database_source)
            ->first();

        $hasSn = $productAccurate ? $productAccurate->has_sn : true; // Default true jika tidak ada data

        if (!$hasSn) {
            $item->increment('quantity_received');
            $this->po->refresh();
            $this->successMessage = "1 {$item->item_name} berhasil ditambahkan.";
            return;
        }

        $this->activeItemNo = $item->item_no;
        $this->activeItemRowId = $item->id;
        $this->barcodeInput = '';
    }

    public function processScan()
    {
        $this->errorMessage = '';
        $this->successMessage = '';
        $rawBarcode = trim($this->barcodeInput);
        if (empty($rawBarcode)) return;

        // 1. Cek apakah ini barcode SKU (Item No)
        $item = $this->po->items->where('item_no', $rawBarcode)->first();
        if ($item) {
            $this->setActiveItem($rawBarcode);
            return;
        }

        // 2. Jika bukan SKU, maka ini IMEI (Serial Number). Harus ada activeItemNo
        if (!$this->activeItemNo) {
            $this->errorMessage = "Scan SKU (Barcode Produk) terlebih dahulu sebelum scan IMEI.";
            $this->barcodeInput = '';
            return;
        }

        // 3. Validasi Format & Luhn Checksum IMEI/SN
        $validation = self::validateImeiOrSn($rawBarcode);
        if (!$validation['valid']) {
            $this->errorMessage = $validation['message'];
            return;
        }

        $barcode = $validation['clean'];

        // 4. Proses IMEI
        $activeItem = $this->po->items->where('id', $this->activeItemRowId)->first();
        if (!$activeItem) {
            $activeItem = $this->po->items->where('item_no', $this->activeItemNo)->first();
        }
        if (!$activeItem) return;

        if ($activeItem->quantity_received >= $activeItem->quantity_ordered) {
            $this->errorMessage = "Target kuantitas sudah terpenuhi. Silakan scan SKU produk lain.";
            $this->barcodeInput = '';
            $this->activeItemNo = null;
            $this->activeItemRowId = null;
            return;
        }

        // Cek duplikasi IMEI di tabel device_inspections (untuk semua inbound PO agar tidak dobel scan)
        $exists = DeviceInspection::where('imei', $barcode)->where('inspectable_type', PurchaseOrderItem::class)->exists();
        if ($exists) {
            $this->errorMessage = "IMEI {$barcode} sudah discan sebelumnya.";
            $this->barcodeInput = '';
            return;
        }

        // Tampilkan Form QC Inline
        $this->scannedImei = $barcode;
        $this->activeItemId = $activeItem->id;
        $this->barcodeInput = '';
    }

    #[On('qc-inspection-saved')]
    public function handleQcSaved($verdict = 'pass')
    {
        $activeItem = $this->po->items->where('id', $this->activeItemRowId)->first();
        if (!$activeItem) {
            $activeItem = $this->po->items->where('item_no', $this->activeItemNo)->first();
        }
        if ($activeItem) {
            $activeItem->increment('quantity_received');
            $this->po->refresh();
        }
        $this->scannedImei = '';
        $this->activeItemId = null;
    }

    public function startEditImei($inspectionId)
    {
        $this->errorMessage = '';
        $this->editErrorMessage = '';
        /** @var DeviceInspection|null $inspection */
        $inspection = DeviceInspection::where('id', $inspectionId)->first();
        if ($inspection) {
            if ($inspection->is_pushed) {
                $this->dispatch('toast', title: 'Peringatan', message: 'Item ini sudah disinkronkan ke Accurate dan tidak dapat diedit di sini.', type: 'warning');
                return;
            }
            $this->editingInspectionId = $inspectionId;
            $this->editingImei = $inspection->imei;
        }
    }

    public function cancelEditImei()
    {
        $this->editingInspectionId = null;
        $this->editingImei = '';
        $this->editErrorMessage = '';
    }

    public function saveEditImei()
    {
        $this->editErrorMessage = '';
        if (!$this->editingInspectionId) return;

        /** @var DeviceInspection|null $inspection */
        $inspection = DeviceInspection::where('id', $this->editingInspectionId)->first();
        if (!$inspection) {
            $this->cancelEditImei();
            return;
        }

        if ($inspection->is_pushed) {
            $this->dispatch('toast', title: 'Peringatan', message: 'Item ini sudah disinkronkan ke Accurate.', type: 'warning');
            $this->cancelEditImei();
            return;
        }

        $validation = self::validateImeiOrSn($this->editingImei);
        if (!$validation['valid']) {
            $this->editErrorMessage = $validation['message'];
            return;
        }

        $newImei = $validation['clean'];

        // Cek duplikasi jika nomor berubah
        if ($newImei !== $inspection->imei) {
            $exists = DeviceInspection::where('imei', $newImei)
                ->where('inspectable_type', PurchaseOrderItem::class)
                ->where('id', '!=', $inspection->id)
                ->exists();

            if ($exists) {
                $this->editErrorMessage = "IMEI {$newImei} sudah terdaftar pada data inspeksi lain.";
                return;
            }
        }

        $oldImei = $inspection->imei;
        $inspection->imei = $newImei;
        $inspection->save();

        $this->cancelEditImei();
        $this->po->refresh();
        $this->dispatch('toast', title: 'Berhasil', message: "IMEI {$oldImei} berhasil dikoreksi menjadi {$newImei}.", type: 'success');
    }

    public function deleteQc($inspectionId)
    {
        /** @var DeviceInspection|null $inspection */
        $inspection = DeviceInspection::where('id', $inspectionId)->first();
        if ($inspection) {
            /** @var PurchaseOrderItem|null $item */
            $item = PurchaseOrderItem::where('id', $inspection->inspectable_id)->first();
            $inspection->delete();
            if ($item) {
                $item->decrement('quantity_received');
            }
            $this->po->refresh();
        }
    }

    public function openConfirmModal()
    {
        $this->errorMessage = '';
        $this->successMessage = '';

        $received = $this->po->items->sum('quantity_received');
        if ($received === 0) {
            $this->dispatch('toast', title: 'Peringatan', message: 'Belum ada item yang di-scan untuk diterima.', type: 'warning');
            return;
        }

        $hasUnpushed = false;
        foreach ($this->po->items as $item) {
            if (($item->quantity_received - $item->quantity_pushed) > 0) {
                $hasUnpushed = true;
                break;
            }
        }

        if (!$hasUnpushed) {
            $this->dispatch('toast', title: 'Info', message: 'Semua item yang discan sudah berhasil dikirim ke Accurate sebelumnya.', type: 'info');
            return;
        }

        if (!$this->selectedWarehouseId) {
            $this->selectedWarehouseId = Auth::user()->warehouse_id 
                ?? \App\Models\Warehouse::where('business_unit_id', Auth::user()->getActiveBusinessUnitId() ?? 2)->first()?->id;
        }

        $this->showConfirmModal = true;
    }

    public function closeConfirmModal()
    {
        $this->showConfirmModal = false;
    }

    public function completeReceiveItem()
    {
        // Validasi
        $ordered = $this->po->items->sum('quantity_ordered');
        $received = $this->po->items->sum('quantity_received');

        if ($received === 0) {
            $this->dispatch('admin-alert', type: 'error', message: 'Tidak ada item yang di-scan.');
            $this->showConfirmModal = false;
            return;
        }

        // Jika tidak 100%, tandai PARTIAL, jika 100% COMPLETED. 
        // Accurate akan mencatat apa yang dipush.
        $status = ($received < $ordered) ? 'PARTIAL' : 'COMPLETED';

        try {
            $service = app(AccurateService::class);
            list($host, $token, $secretKey) = $service->getCredentials($this->po->database_source);

            $targetWarehouse = \App\Models\Warehouse::find($this->selectedWarehouseId) ?? Auth::user()->warehouse;
            if (!$targetWarehouse) {
                $this->dispatch('toast', title: 'Peringatan', message: 'Gudang tujuan penerimaan belum dipilih atau user belum memiliki penempatan gudang.', type: 'warning');
                return;
            }
            $targetWarehouseName = $targetWarehouse->name;

            // Kita tidak perlu memisahkan item menjadi chunk yang berbeda (chunking) 
            // karena kita sekarang mengirimkan purchaseOrderDetailId.
            $detailItems = [];

            foreach ($this->po->items as $item) {
                $qtyToPush = $item->quantity_received - $item->quantity_pushed;

                if ($qtyToPush > 0) {
                    $serialNumbers = [];
                    foreach ($item->inspections as $ins) {
                        if (!$ins->is_pushed) {
                            $serialNumbers[] = [
                                'serialNumberNo' => $ins->imei,
                                'quantity' => 1
                            ];
                        }
                    }

                    $detailData = [
                        'itemNo' => $item->item_no,
                        'unitPrice' => (float)$item->unit_price,
                        'quantity' => (float)$qtyToPush,
                        'purchaseOrderNumber' => $this->po->po_number,
                        'warehouseName' => $targetWarehouseName
                    ];

                    if ($item->accurate_detail_id) {
                        $detailData['purchaseOrderDetailId'] = $item->accurate_detail_id;
                    }

                    if (!empty($serialNumbers)) {
                        $detailData['detailSerialNumber'] = $serialNumbers;
                    }

                    $detailItems[] = $detailData;
                }
            }

            if (empty($detailItems)) {
                $this->dispatch('toast', title: 'Info', message: 'Semua item yang discan sudah berhasil dikirim ke Accurate sebelumnya.', type: 'info');
                $this->showConfirmModal = false;
                return;
            }

            $baseSj = 'SJ-' . $this->po->po_number;
            $successCount = 0;
            $errorMessages = [];
            $allSuccess = true;

            $suffix = '-' . date('His');
            $maxBaseLen = 30 - strlen($suffix);
            $receiveNumber = substr($baseSj, 0, $maxBaseLen) . $suffix;

            $payload = [
                'receiveNumber' => $receiveNumber,
                'vendorNo' => $this->po->vendor->vendor_no ?? '',
                'warehouseName' => $targetWarehouseName,
                'detailItem' => $detailItems,
                'branchName' => Auth::user()->branch->name ?? null
            ];

            $timestamp = now()->toIso8601String();
            $signature = hash_hmac('sha256', $timestamp, $secretKey);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization'   => 'Bearer ' . $token,
                'X-Api-Timestamp' => $timestamp,
                'X-Api-Signature' => $signature,
                'Content-Type'    => 'application/json',
            ])->post($host . '/receive-item/save.do', $payload);

            if ($response->successful() && isset($response->json()['s']) && $response->json()['s'] === true) {
                $successCount++;
            } else {
                $allSuccess = false;
                $err = 'Error dari Accurate.';
                if (isset($response->json()['d']) && is_array($response->json()['d'])) {
                    $err = implode(', ', $response->json()['d']);
                }
                $errorMessages[] = $err;
                Log::error('Accurate Receive Item Error: ' . $response->body());
            }

            if ($successCount > 0) {
                // Update tracking status loka
                $this->po->update(['status' => $status]);
                foreach ($this->po->items as $item) {
                    if ($item->quantity_received > $item->quantity_pushed) {
                        $item->quantity_pushed = $item->quantity_received;
                        $item->save();

                        foreach ($item->inspections as $ins) {
                            if (!$ins->is_pushed) {
                                $ins->is_pushed = true;
                                $ins->save();
                            }
                        }
                    }
                }
            }

            $this->showConfirmModal = false;

            if ($allSuccess) {
                $this->dispatch('toast', title: 'Berhasil', message: 'Sinkronisasi Penerimaan Barang ke Accurate berhasil.', type: 'success');
            } else if ($successCount > 0) {
                $this->dispatch('toast', title: 'Berhasil Sebagian', message: 'Sebagian data berhasil dikirim, namun ada error: ' . implode(' | ', $errorMessages), type: 'warning');
            } else {
                $this->dispatch('toast', title: 'Gagal', message: 'Gagal kirim ke Accurate: ' . implode(' | ', $errorMessages), type: 'error');
            }
        } catch (\Exception $e) {
            $this->errorMessage = "Error: " . $e->getMessage();
            $this->dispatch('toast', title: 'Error', message: $e->getMessage(), type: 'error');
        }
    }

    public function openMigrateModal()
    {
        $this->migrateErrorMessage = '';
        $this->sourcePoId = null;
        $this->itemMappings = [];
        $this->migrateOption = 'move';
        $this->resetPushedStatus = true;
        $this->showMigrateModal = true;
    }

    public function closeMigrateModal()
    {
        $this->showMigrateModal = false;
        $this->sourcePoId = null;
        $this->itemMappings = [];
        $this->migrateErrorMessage = '';
    }

    public function updatedSourcePoId($poId)
    {
        $this->migrateErrorMessage = '';
        $this->itemMappings = [];

        if (!$poId) {
            return;
        }

        /** @var PurchaseOrder|null $sourcePo */
        $sourcePo = PurchaseOrder::with(['items.inspections', 'items.productAccurate'])->where('id', $poId)->first();
        if (!$sourcePo) {
            return;
        }

        // Smart Auto-Matching
        foreach ($this->po->items as $targetItem) {
            $bestMatchId = null;

            // 1. Coba cari yang nama item persis sama
            $exactMatch = $sourcePo->items->first(function ($sItem) use ($targetItem) {
                return strtolower(trim($sItem->item_name)) === strtolower(trim($targetItem->item_name)) && $sItem->inspections->count() > 0;
            });

            if ($exactMatch) {
                $bestMatchId = $exactMatch->id;
            } else {
                // 2. Coba cari yang mengandung kata kunci sama dan punya data inspeksi
                $cleanTargetName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $targetItem->item_name));
                $fuzzyMatch = $sourcePo->items->first(function ($sItem) use ($cleanTargetName) {
                    $cleanSourceName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $sItem->item_name));
                    return $sItem->inspections->count() > 0 && (
                        str_contains($cleanTargetName, $cleanSourceName) || str_contains($cleanSourceName, $cleanTargetName)
                    );
                });

                if ($fuzzyMatch) {
                    $bestMatchId = $fuzzyMatch->id;
                }
            }

            $this->itemMappings[$targetItem->id] = $bestMatchId;
        }
    }

    public function executeMigration()
    {
        $this->migrateErrorMessage = '';

        if (!$this->sourcePoId) {
            $this->migrateErrorMessage = 'Silakan pilih PO asal yang akan disalin.';
            return;
        }

        /** @var PurchaseOrder|null $sourcePo */
        $sourcePo = PurchaseOrder::with(['items.inspections'])->where('id', $this->sourcePoId)->first();
        if (!$sourcePo) {
            $this->migrateErrorMessage = 'PO asal tidak ditemukan.';
            return;
        }

        // Validasi minimal ada 1 mapping yang dipilih
        $validMappings = array_filter($this->itemMappings, function ($sourceItemId) {
            return !empty($sourceItemId);
        });

        if (empty($validMappings)) {
            $this->migrateErrorMessage = 'Pilih minimal satu item sumber untuk dipindahkan.';
            return;
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($sourcePo, $validMappings) {
                $totalMigrated = 0;

                foreach ($validMappings as $targetItemId => $sourceItemId) {
                    /** @var PurchaseOrderItem|null $targetItem */
                    $targetItem = PurchaseOrderItem::where('id', $targetItemId)->first();
                    /** @var PurchaseOrderItem|null $sourceItem */
                    $sourceItem = PurchaseOrderItem::with('inspections')->where('id', $sourceItemId)->first();

                    if (!$targetItem || !$sourceItem) {
                        continue;
                    }

                    $inspections = $sourceItem->inspections;
                    if ($inspections->isEmpty()) {
                        continue;
                    }

                    foreach ($inspections as $ins) {
                        if ($this->migrateOption === 'move') {
                            $ins->inspectable_id = $targetItem->id;
                            if ($this->resetPushedStatus) {
                                $ins->is_pushed = false;
                            }
                            $ins->save();
                            $totalMigrated++;
                        } else {
                            // Copy mode
                            DeviceInspection::create([
                                'inspectable_type'          => PurchaseOrderItem::class,
                                'inspectable_id'            => $targetItem->id,
                                'second_product_variant_id' => $ins->second_product_variant_id,
                                'qc_template_id'            => $ins->qc_template_id,
                                'imei'                      => $ins->imei,
                                'label'                     => $ins->label ?? 'QC Inbound PO Grosir',
                                'checklist_results'         => $ins->checklist_results,
                                'passed_count'              => $ins->passed_count,
                                'failed_count'              => $ins->failed_count,
                                'total_items'               => $ins->total_items,
                                'verdict'                   => $ins->verdict,
                                'notes'                     => $ins->notes,
                                'is_pushed'                 => false,
                                'inspected_by'              => Auth::id() ?? $ins->inspected_by,
                                'inspected_at'              => now(),
                            ]);
                            $totalMigrated++;
                        }
                    }

                    // Update quantity_received
                    $targetItem->quantity_received = $targetItem->inspections()->count();
                    $targetItem->save();

                    if ($this->migrateOption === 'move') {
                        $sourceItem->quantity_received = $sourceItem->inspections()->count();
                        $sourceItem->save();
                    }
                }

                $this->po->refresh();
                if ($this->migrateOption === 'move') {
                    $sourcePo->refresh();
                }
            });

            $this->closeMigrateModal();
            $this->dispatch('toast', title: 'Berhasil Migrasi Data', message: 'Data hasil scan IMEI berhasil dipindahkan ke PO ini dan siap dipush ke Accurate.', type: 'success');
        } catch (\Exception $e) {
            Log::error('Inbound PO Migration Error: ' . $e->getMessage());
            $this->migrateErrorMessage = 'Terjadi kesalahan saat migrasi data: ' . $e->getMessage();
        }
    }

    public function render()
    {
        $buId = Auth::user()->getActiveBusinessUnitId() ?? Auth::user()->business_unit_id ?? 2;
        $availableWarehouses = \App\Models\Warehouse::where('business_unit_id', $buId)
            ->whereNotNull('warehouse_id')
            ->orderBy('name')
            ->get();

        $availableSourcePos = PurchaseOrder::where('id', '!=', $this->po->id)
            ->whereHas('items.inspections')
            ->orderBy('id', 'desc')
            ->take(30)
            ->get();

        /** @var PurchaseOrder|null $sourcePoObj */
        $sourcePoObj = $this->sourcePoId 
            ? PurchaseOrder::with(['items.inspections', 'items.productAccurate'])->where('id', $this->sourcePoId)->first() 
            : null;

        return view('livewire.zoffline.inbound.scan', [
            'availableWarehouses' => $availableWarehouses,
            'availableSourcePos'  => $availableSourcePos,
            'sourcePoObj'         => $sourcePoObj,
            'po'                  => $this->po
        ]);
    }
}
