<?php

namespace App\Livewire\Admin\Inbound;

use Livewire\Component;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\DeviceInspection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.admin', ['title' => 'Inbound QC - TokoPun'])]
class Scan extends Component
{
    public PurchaseOrder $po;

    // UI States
    public $activeItemNo = null; // Item yang sedang discan SKU nya
    public $barcodeInput = '';
    public $errorMessage = '';
    public $successMessage = '';

    // Detailed QC State
    public $scannedImei = '';
    public $activeItemId = null;

    public function mount(PurchaseOrder $po)
    {
        $this->po = $po->load('items.inspections');
    }

    public function processScan()
    {
        $this->errorMessage = '';
        $this->successMessage = '';
        $barcode = trim($this->barcodeInput);
        if (empty($barcode)) return;

        // 1. Cek apakah ini barcode SKU (Item No)
        $item = $this->po->items->where('item_no', $barcode)->first();
        if ($item) {
            if ($item->quantity_received >= $item->quantity_ordered) {
                $this->errorMessage = "Item {$item->item_name} sudah mencapai kuantitas pesanan.";
                $this->barcodeInput = '';
                return;
            }

            // Cek apakah produk ini membutuhkan SN
            $productAccurate = \App\Models\ProductAccurate::where('item_no', $barcode)
                ->where('database_source', $this->po->database_source)
                ->first();

            $hasSn = $productAccurate ? $productAccurate->has_sn : true; // Default true jika tidak ada data

            if (!$hasSn) {
                $item->increment('quantity_received');
                $this->po->refresh();
                $this->barcodeInput = '';
                $this->successMessage = "1 {$item->item_name} berhasil ditambahkan.";
                return;
            }

            $this->activeItemNo = $barcode;
            $this->barcodeInput = '';
            return;
        }

        // 2. Jika bukan SKU, maka ini IMEI (Serial Number). Harus ada activeItemNo
        if (!$this->activeItemNo) {
            $this->errorMessage = "Scan SKU (Barcode Produk) terlebih dahulu sebelum scan IMEI.";
            $this->barcodeInput = '';
            return;
        }

        // 3. Proses IMEI
        $activeItem = $this->po->items->where('item_no', $this->activeItemNo)->first();
        if (!$activeItem) return;

        if ($activeItem->quantity_received >= $activeItem->quantity_ordered) {
            $this->errorMessage = "Target kuantitas sudah terpenuhi. Silakan scan SKU produk lain.";
            $this->barcodeInput = '';
            $this->activeItemNo = null;
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
        $activeItem = $this->po->items->where('item_no', $this->activeItemNo)->first();
        if ($activeItem) {
            $activeItem->increment('quantity_received');
            $this->po->refresh();
        }
        $this->scannedImei = '';
        $this->activeItemId = null;
    }



    public function deleteQc($inspectionId)
    {
        $inspection = DeviceInspection::find($inspectionId);
        if ($inspection) {
            $item = PurchaseOrderItem::find($inspection->inspectable_id);
            $inspection->delete();
            if ($item) {
                $item->decrement('quantity_received');
            }
            $this->po->refresh();
        }
    }

    public function completeReceiveItem()
    {
        // Validasi
        $ordered = $this->po->items->sum('quantity_ordered');
        $received = $this->po->items->sum('quantity_received');

        if ($received === 0) {
            $this->dispatch('admin-alert', type: 'error', message: 'Tidak ada item yang di-scan.');
            return;
        }

        // Jika tidak 100%, tandai PARTIAL, jika 100% COMPLETED. 
        // Accurate akan mencatat apa yang dipush.
        $status = ($received < $ordered) ? 'PARTIAL' : 'COMPLETED';

        try {
            $service = app(AccurateService::class);
            list($host, $token, $secretKey) = $service->getCredentials($this->po->database_source);

            $detailItem = [];
            foreach ($this->po->items as $item) {
                // Hitung selisih kuantitas yang belum di-push
                $qtyToPush = $item->quantity_received - $item->quantity_pushed;

                if ($qtyToPush > 0) {
                    $serialNumbers = [];
                    foreach ($item->inspections as $ins) {
                        // Hanya push inspection yang belum pernah di-push
                        if (!$ins->is_pushed) {
                            $serialNumbers[] = [
                                'serialNumberNo' => $ins->imei,
                                'quantity' => 1
                            ];
                        }
                    }

                    $detailItemData = [
                        'itemNo' => $item->item_no,
                        'unitPrice' => (float)$item->unit_price,
                        'quantity' => (float)$qtyToPush,
                        'purchaseOrderNumber' => $this->po->po_number,
                    ];

                    if (!empty($serialNumbers)) {
                        $detailItemData['detailSerialNumber'] = $serialNumbers;
                    }

                    $detailItem[] = $detailItemData;
                }
            }

            if (empty($detailItem)) {
                $this->dispatch('toast', title: 'Info', message: 'Semua item yang discan sudah berhasil dikirim ke Accurate sebelumnya.', type: 'info');
                return;
            }

            $baseSj = 'SJ-' . $this->po->po_number;
            $suffix = '-' . date('His');
            
            // Maksimal karakter di Accurate adalah 30.
            // Potong string base agar tidak melampaui batas saat digabung dengan suffix
            $maxBaseLen = 30 - strlen($suffix);
            $receiveNumber = substr($baseSj, 0, $maxBaseLen) . $suffix;

            $payload = [
                // Gunakan timestamp (His) untuk mencegah bentrok SJ ganda di hari yang sama
                'receiveNumber' => $receiveNumber,
                'vendorNo' => $this->po->vendor->vendor_no ?? '',
                'detailItem' => $detailItem,
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
                $this->po->update(['status' => $status]);

                // Update tracking
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

                $this->dispatch('toast', title: 'Berhasil', message: 'Sinkronisasi Penerimaan Barang ke Accurate berhasil.', type: 'success');
            } else {
                $errorMsg = 'Terjadi kesalahan tidak terduga dari Accurate.';
                if (isset($response->json()['d']) && is_array($response->json()['d'])) {
                    $errorMsg = implode(', ', $response->json()['d']);
                }

                Log::error('Accurate Receive Item Error: ' . $response->body());
                $this->dispatch('toast', title: 'Gagal', message: 'Gagal kirim ke Accurate: ' . $errorMsg, type: 'error');
            }
        } catch (\Exception $e) {
            $this->errorMessage = "Error: " . $e->getMessage();
            $this->dispatch('toast', title: 'Error', message: $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.admin.inbound.scan');
    }
}
