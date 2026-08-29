<?php

namespace App\Livewire\Admin\Warranty;

use App\Models\WarrantyClaim;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\DeviceInspection;
use App\Services\AccurateService;
use App\Models\Order;
use App\Models\OrderItem;

class ClaimManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';

    public $showModal = false;
    public $selectedClaimId = null;
    public $resolution_notes = '';
    public $replacement_imei = '';

    public $showReplacementConfirmModal = false;

    public $originalInspection = null;
    public $claimInspection = null;

    public $viewingQcDetails = null; // 'original' or 'claim'
    public $showReplacementForm = false;
    public $showServiceForm = false;
    public $showRejectForm = false;
    public $showRefundForm = false;

    // Replacement Type (Upgrade/Downgrade)
    public $replacement_type = 'same'; // 'same' or 'different'
    public $replacement_item_no = null;
    public $replacement_price = 0;
    public $original_price = 0;
    public $replacement_product_name = '';
    public $search_product_query = '';
    public $product_results = [];
    public $bank_no = '10.02.103';
    public $manual_note = '';

    public $is_editing_replacement_price = false;

    public $search_imei_query = '';
    public $imei_results = [];

    protected $listeners = ['refreshClaims' => '$refresh'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedSearchProductQuery()
    {
        if (strlen($this->search_product_query) > 2) {
            $query = \App\Models\ProductAccurate::where('name', 'like', '%' . $this->search_product_query . '%');

            if ($this->selectedClaimId) {
                $claim = WarrantyClaim::with('warranty.policy.businessUnit')->find($this->selectedClaimId);
                $businessUnitId = $claim->warranty->policy->business_unit_id ?? null;

                if ($businessUnitId) {
                    $query->where('business_unit_id', $businessUnitId);
                }
            }

            $this->product_results = $query->limit(10)->get()->toArray();
        } else {
            $this->product_results = [];
        }
    }

    public function selectReplacementProduct($itemNo, $name, $price)
    {
        $this->replacement_item_no = $itemNo;
        $this->replacement_product_name = $name;
        $this->replacement_price = $price;
        $this->search_product_query = '';
        $this->product_results = [];
    }

    public function cancelReplacementProduct()
    {
        $this->replacement_item_no = null;
        $this->replacement_product_name = '';
        $this->replacement_price = 0;
        $this->search_product_query = '';
        $this->product_results = [];
    }

    public function openProcessModal($id)
    {
        $this->selectedClaimId = $id;
        $this->resolution_notes = '';
        $this->replacement_imei = '';
        $this->search_imei_query = '';
        $this->imei_results = [];
        $this->resetValidation();
        $this->viewingQcDetails = null;

        $claim = WarrantyClaim::with('warranty')->find($id);
        if ($claim) {
            $this->originalInspection = $claim->warranty->device_inspection_id ? DeviceInspection::with(['media', 'qcTemplate'])->find($claim->warranty->device_inspection_id) : null;
            $this->claimInspection = $claim->receiving_inspection_id ? DeviceInspection::with(['media', 'qcTemplate'])->find($claim->receiving_inspection_id) : null;
        }

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedClaimId = null;
        $this->resolution_notes = '';
        $this->replacement_imei = '';
        $this->replacement_type = 'same';
        $this->search_imei_query = '';
        $this->imei_results = [];
        $this->cancelReplacementProduct();
        $this->originalInspection = null;
        $this->claimInspection = null;
        $this->viewingQcDetails = null;
        $this->showRefundForm = false;
    }

    public function viewQcDetails($type)
    {
        $this->viewingQcDetails = $type;
    }

    public function closeQcDetails()
    {
        $this->viewingQcDetails = null;
    }

    public function updateStatus($status)
    {
        $this->validate([
            'resolution_notes' => 'nullable|string|max:500'
        ]);

        $claim = WarrantyClaim::findOrFail($this->selectedClaimId);

        $claim->status = $status;
        if ($this->resolution_notes) {
            $claim->resolution_notes = $this->resolution_notes;
        }

        if (in_array($status, ['approved', 'rejected'])) {
            $claim->approved_by = Auth::id();
        }

        if ($status === 'completed') {
            $claim->resolved_at = Carbon::now();
            $claim->resolution = 'repaired';
            $claim->warranty->increment('claims_used');
        }

        $claim->save();

        $this->closeModal();
        $this->dispatch('toast', title: 'Berhasil', message: 'Status klaim diperbarui menjadi ' . strtoupper($status), type: 'success');
    }

    public function confirmReplacement()
    {
        $validator = \Illuminate\Support\Facades\Validator::make([
            'replacement_imei' => $this->replacement_imei,
            'replacement_item_no' => $this->replacement_item_no,
            'bank_no' => $this->bank_no,
            'original_price' => $this->original_price,
            'replacement_type' => $this->replacement_type,
        ], [
            'replacement_imei' => 'required|string|min:3',
            'replacement_item_no' => 'required_if:replacement_type,different',
            'bank_no' => 'required',
            'original_price' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            $this->setErrorBag($validator->getMessageBag());
            $this->dispatch('toast', title: 'Validasi Gagal', message: 'Mohon periksa kembali form, pastikan IMEI dan Bank sudah terisi.', type: 'warning');
            return;
        }

        $this->showReplacementConfirmModal = true;
        \Illuminate\Support\Facades\Log::info("confirmReplacement: showReplacementConfirmModal set to TRUE, selectedClaimId=" . $this->selectedClaimId);
    }

    public function cancelReplacementConfirm()
    {
        $this->showReplacementConfirmModal = false;
        $this->resetValidation(['replacement_imei']);
    }

    public function toggleEditReplacementPrice()
    {
        $this->is_editing_replacement_price = !$this->is_editing_replacement_price;
    }

    public function approveReplacement()
    {
        // Tetap ada validasi untuk berjaga-jaga
        $this->validate([
            'replacement_imei' => 'required|string|min:3',
            'replacement_item_no' => 'required_if:replacement_type,different',
            'bank_no' => 'required',
            'original_price' => 'required|numeric|min:0'
        ]);

        $claim = WarrantyClaim::with(['warranty.orderItem.order', 'warranty.orderItem.variant'])->findOrFail($this->selectedClaimId);

        $originalPrice = $this->original_price;
        $newItemNo = $this->replacement_type === 'different' ? $this->replacement_item_no : null;
        $newPrice = $this->replacement_type === 'different' ? $this->replacement_price : $originalPrice;
        $priceDifference = $newPrice - $originalPrice; // Positive if upgrade, negative if downgrade

        // Memulai Transaksi Database Lokal DULUAN agar jika ada error sintaks/enum (seperti Error 1265), Accurate TIDAK terlanjur tereksekusi.
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // 1. Update Database Lokal (Tahap 1: Validasi DB & Status Claim)
            if ($priceDifference < 0) {
                $claim->status = 'waiting_refund'; // Downgrade: tunggu kasir proses refund
                $claim->refund_amount = abs($priceDifference);
            } elseif ($priceDifference > 0) {
                $claim->status = 'waiting_payment'; // Upgrade: tunggu Finance konfirmasi pelunasan
                $claim->refund_amount = null;
            } else {
                $claim->status = 'completed'; // 1:1 sama persis
                $claim->refund_amount = null;
            }

            if ($claim->status === 'completed') {
                $claim->resolved_at = Carbon::now();
            }
            $claim->resolution = 'replaced';
            $noteType = $this->replacement_type === 'same' ? 'Ganti Unit' : ($priceDifference > 0 ? 'Upgrade Unit' : 'Downgrade Unit');
            
            $finalNotes = "{$noteType} ke IMEI: {$this->replacement_imei}" .
                ($newItemNo ? " (Barang Baru: {$this->replacement_product_name})" : "");
                
            if (!empty($this->manual_note)) {
                $finalNotes .= "\nCatatan Tambahan: {$this->manual_note}";
            }
                
            $claim->resolution_notes = $finalNotes . " | {$this->resolution_notes}";
            $claim->approved_by = Auth::id();
            $claim->save();

            $warranty = $claim->warranty;
            $oldSn = $warranty->serial_number;

            // Simpan original SN jika belum ada
            if (!$warranty->original_serial_number) {
                $warranty->original_serial_number = $oldSn;
            }

            // Catat log perpindahan SN
            $reason = $this->replacement_type === 'same' 
                ? 'replacement_same' 
                : ($priceDifference > 0 ? 'replacement_upgrade' : 'replacement_downgrade');
                
            \App\Models\WarrantySerialLog::create([
                'warranty_id' => $warranty->id,
                'warranty_claim_id' => $claim->id,
                'old_serial_number' => $oldSn,
                'new_serial_number' => $this->replacement_imei,
                'reason' => $reason,
                'changed_by' => Auth::id(),
                'notes' => $finalNotes,
            ]);

            \App\Models\WarrantyReplacement::create([
                'warranty_claim_id' => $claim->id,
                'old_imei' => $oldSn,
                'new_imei' => $this->replacement_imei,
                'processed_by' => Auth::id(),
                'system_notes' => $finalNotes,
            ]);

            // Update SN langsung
            $warranty->serial_number = $this->replacement_imei;
            $warranty->claims_used = ($warranty->claims_used ?? 0) + 1;
            $warranty->replacement_count = ($warranty->replacement_count ?? 0) + 1;
            $warranty->device_inspection_id = null; // Butuh QC ulang
            $warranty->status = 'active';

            // Atur masa aktif berdasarkan kebijakan
            $policy = $warranty->policy;
            if ($policy && $policy->replacement_type === 'reset') {
                $warranty->activated_at = Carbon::now();
                $warranty->expires_at = Carbon::now()->addDays($policy->duration_days);
            }

            // Simpan sementara agar terpicu query-nya dan mendeteksi error jika ada sebelum ke Accurate
            $warranty->save();

            // 2. Integrasi API Accurate (Dijalankan di DALAM try-catch transaksi DB)
            $accurateService = app(AccurateService::class);
            $accurateResult = $accurateService->processWarrantyReplacement($claim, $this->replacement_imei, $newItemNo, $newPrice, $priceDifference, $this->replacement_type, $this->bank_no, $originalPrice);

            // Update data varian jika berbeda (tidak perlu ubah claims_used atau expires_at lagi)
            if ($this->replacement_type === 'different' && $newItemNo) {
                $newVariant = \App\Models\ProductVariant::whereHas('accurateData', function ($q) use ($newItemNo) {
                    $q->where('item_no', $newItemNo);
                })->first();

                if (!$newVariant) {
                    $newVariant = \App\Models\SecondProductVariant::whereHas('accurateData', function ($q) use ($newItemNo) {
                        $q->where('item_no', $newItemNo);
                    })->first();
                }

                // Catatan: Model Warranty bawaan tidak memiliki product_variant_id. 
                // Cukup biarkan order_item_id menunjuk ke order asli.
            }

            $warranty->save();

            // 3. Buat Rekaman Order POS agar Nota/Receipt bisa dicetak
            $newInvoiceNo = $accurateResult['sales_invoice']['number'] ?? null;

            $newOrderNumber = 'WR-' . $claim->claim_number;
            $order = Order::create([
                'business_unit_id' => $claim->warranty->policy?->business_unit_id ?? (Auth::user()->getActiveBusinessUnitId() ?? 1),
                'user_id' => $claim->customer_user_id,
                'order_number' => $newOrderNumber,
                'accurate_invoice_no' => $newInvoiceNo,
                'order_date' => Carbon::now()->format('Y-m-d'),
                'total_amount' => $newPrice,
                'shipping_cost' => 0,
                'discount_amount' => 0,
                'mdr_percentage' => 0,
                'mdr_amount' => 0,
                'grand_total' => $newPrice, // Total nilai barang
                'order_status' => 'COMPLETED',
                'order_channel' => 'POS',
                'handled_by' => Auth::id(),
                'sales_id' => $claim->warranty->orderItem->order->sales_id ?? Auth::id(),
                'shipping_address_snapshot' => ['type' => 'POS', 'store' => Auth::user()->branch->name ?? 'Toko', 'is_warranty_replacement' => true],
                'notes' => "Ganti Unit Klaim Garansi #{$claim->claim_number}. Pengganti untuk IMEI: {$claim->serial_number}",
                'branch_id' => Auth::user()->branch_id,
            ]);

            // Simpan referensi Accurate Documents
            if (isset($accurateResult['sales_return']) && $accurateResult['sales_return']['id']) {
                \App\Models\OrderAccurateDoc::create([
                    'order_id' => $order->id,
                    'doc_type' => 'SALES_RETURN',
                    'accurate_id' => $accurateResult['sales_return']['id'],
                    'doc_number' => $accurateResult['sales_return']['number'],
                    'status' => 'SUCCESS'
                ]);
            }
            if (isset($accurateResult['sales_invoice']) && $accurateResult['sales_invoice']['id']) {
                \App\Models\OrderAccurateDoc::create([
                    'order_id' => $order->id,
                    'doc_type' => 'SALES_INVOICE',
                    'accurate_id' => $accurateResult['sales_invoice']['id'],
                    'doc_number' => $accurateResult['sales_invoice']['number'],
                    'status' => 'SUCCESS'
                ]);
            }
            if (isset($accurateResult['sales_receipt']) && $accurateResult['sales_receipt']['id']) {
                \App\Models\OrderAccurateDoc::create([
                    'order_id' => $order->id,
                    'doc_type' => 'SALES_RECEIPT',
                    'accurate_id' => $accurateResult['sales_receipt']['id'],
                    'doc_number' => $accurateResult['sales_receipt']['number'],
                    'status' => 'SUCCESS'
                ]);
            }

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $claim->warranty->orderItem->product_id, // Asumsi id produk sama, atau beda jika downgrade
                'product_variant_type' => $this->replacement_type === 'different' && $newItemNo ?
                    get_class($newVariant ?? $claim->warranty->orderItem->variant) :
                    $claim->warranty->orderItem->product_variant_type,
                'product_variant_id' => $this->replacement_type === 'different' && $newItemNo ?
                    ($newVariant->id ?? $claim->warranty->orderItem->product_variant_id) :
                    $claim->warranty->orderItem->product_variant_id,
                'product_name' => $this->replacement_type === 'different' && $this->replacement_product_name ?
                    $this->replacement_product_name :
                    $claim->warranty->orderItem->product_name,
                'serial_number' => $this->replacement_imei,
                'qty' => 1,
                'price_at_checkout' => $newPrice,
                'vendor_name_snapshot' => $claim->warranty->orderItem->vendor_name_snapshot,
                'discount_amount' => 0,
                'promo_discount_amount' => 0,
                'subtotal' => $newPrice,
            ]);

            // Tautkan garansi ke OrderItem baru agar datanya rapi
            $warranty->order_item_id = $orderItem->id;
            $warranty->save();

            \Illuminate\Support\Facades\DB::commit();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->addError('replacement_imei', 'Gagal menyimpan data ke database lokal: ' . $e->getMessage());
            $this->showReplacementConfirmModal = false;
            return;
        }

        $this->showReplacementConfirmModal = false;
        $this->closeReplacementForm();
        $this->closeModal(); // Pastikan modal utama juga tertutup agar state bersih
        $this->dispatch('toast', title: 'Berhasil', message: 'Unit berhasil diganti' . ($claim->status === 'waiting_refund' ? '. Sisa saldo menunggu proses refund.' : '!'), type: 'success');
        $this->reset(['replacement_imei', 'search_imei_query', 'imei_results', 'replacement_type', 'replacement_item_no', 'replacement_price', 'replacement_product_name', 'search_product_query', 'product_results', 'manual_note']);
    }

    public function processRefundCash($claimId)
    {
        $this->validate([
            'bank_no' => 'required'
        ]);

        $claim = WarrantyClaim::with(['warranty.orderItem.order'])->findOrFail($claimId);

        if ($claim->status !== 'waiting_refund' || !$claim->refund_amount) {
            $this->addError('bank_no', 'Klaim ini tidak memiliki nominal refund yang valid.');
            return;
        }

        try {
            $accurateService = app(AccurateService::class);
            $accurateService->processDowngradeRefund($claim, $this->bank_no, $claim->refund_amount);

            $claim->status = 'completed';
            $claim->save();

            $this->closeRefundForm();
            $this->closeModal(); // Pastikan modal utama tertutup
            $this->dispatch('toast', title: 'Berhasil', message: 'Refund tunai berhasil diproses ke Accurate!', type: 'success');
            $this->reset(['bank_no', 'showRefundForm', 'selectedClaimId']);
        } catch (\Exception $e) {
            $this->addError('bank_no', 'Gagal memproses refund ke Accurate: ' . $e->getMessage());
        }
    }

    public function openRefundForm()
    {
        $this->showRefundForm = true;
    }

    public function closeRefundForm()
    {
        $this->showRefundForm = false;
    }

    public function confirmPaymentReceived($claimId)
    {
        $claim = WarrantyClaim::findOrFail($claimId);

        if ($claim->status !== 'waiting_payment') {
            $this->dispatch('alert', type: 'error', message: 'Status klaim tidak valid untuk pelunasan.');
            return;
        }

        $claim->status = 'completed';
        $claim->resolved_at = Carbon::now();
        $claim->save();

        $this->dispatch('alert', type: 'success', message: 'Pelunasan berhasil dikonfirmasi. Klaim selesai.');
    }

    public function openReplacementForm()
    {
        $this->showReplacementForm = true;
        $this->replacement_type = 'same';

        $claim = WarrantyClaim::with('warranty.orderItem')->find($this->selectedClaimId);
        
        if ($claim->warranty && $claim->warranty->orderItem) {
            $item = $claim->warranty->orderItem;
            $this->original_price = $item->price_at_checkout - ($item->discount_amount ?? 0) - ($item->promo_discount_amount ?? 0);
        } else {
            $this->original_price = 0;
        }

        $this->cancelReplacementProduct();
    }

    public function closeReplacementForm()
    {
        $this->showReplacementForm = false;
        $this->replacement_imei = '';
        $this->search_imei_query = '';
        $this->imei_results = [];
        $this->replacement_type = 'same';
        $this->manual_note = '';
        $this->cancelReplacementProduct();
        $this->resetValidation(['replacement_imei']);
    }

    public function openServiceForm()
    {
        $this->showServiceForm = true;
    }

    public function closeServiceForm()
    {
        $this->showServiceForm = false;
    }

    public function approveService()
    {
        $this->updateStatus('approved');
        $this->closeServiceForm();
    }

    public function openRejectForm()
    {
        $this->showRejectForm = true;
    }

    public function closeRejectForm()
    {
        $this->showRejectForm = false;
    }

    public function rejectClaim()
    {
        $this->updateStatus('rejected');
        $this->closeRejectForm();
    }

    public function updatedSearchImeiQuery()
    {
        if (strlen($this->search_imei_query) > 2) {
            // Tentukan Target SKU/Item No
            $targetItemNo = null;

            if ($this->replacement_type === 'different') {
                $targetItemNo = $this->replacement_item_no;
            } else {
                // Untuk "same", kita cari SKU dari produk asli
                if ($this->selectedClaimId) {
                    $claim = \App\Models\WarrantyClaim::with('warranty.orderItem.variant')->find($this->selectedClaimId);
                    if ($claim && $claim->warranty && $claim->warranty->orderItem) {
                        $variant = $claim->warranty->orderItem->variant;
                        if ($variant) {
                            if (isset($variant->item_no)) {
                                $targetItemNo = $variant->item_no;
                            } elseif ($variant->accurateData) {
                                $targetItemNo = $variant->accurateData->item_no;
                            } elseif (method_exists($variant, 'accurateData') && $variant->accurateData()->first()) {
                                $targetItemNo = $variant->accurateData()->first()->item_no;
                            }
                        }
                    }
                }
            }

            $query = \App\Models\ProductSerialNumber::with('productAccurate')
                ->where('serial_number', 'like', '%' . $this->search_imei_query . '%')
                ->whereNotIn('serial_number', function ($q) {
                    $q->select('serial_number')->from('warranties')->where('status', 'active')->whereNotNull('serial_number');
                });

            if ($targetItemNo) {
                $query->where('item_no', $targetItemNo);
            }

            $this->imei_results = $query->limit(5)
                ->get()
                ->map(function ($sn) {
                    return [
                        'serial_number' => $sn->serial_number,
                        'product_name' => $sn->productAccurate->name ?? 'Produk Tidak Ditemukan',
                        'item_no' => $sn->item_no,
                    ];
                })
                ->toArray();
        } else {
            $this->imei_results = [];
        }
    }

    public function selectImei($imei)
    {
        $this->replacement_imei = $imei;
        $this->search_imei_query = '';
        $this->imei_results = [];
    }

    public function render()
    {
        $claims = WarrantyClaim::with(['warranty.policy', 'customer', 'approvedBy', 'claimedBy'])
            ->when($this->search, function ($query) {
                $query->where('claim_number', 'like', '%' . $this->search . '%')
                    ->orWhere('serial_number', 'like', '%' . $this->search . '%')
                    ->orWhereHas('customer', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Ambil data Bank untuk opsi Upgrade/Downgrade berdasarkan Business Unit aktif
        $banks = collect();
        $selectedClaimObj = null;
        if ($this->selectedClaimId) {
            $selectedClaimObj = $claims->firstWhere('id', $this->selectedClaimId)
                ?? WarrantyClaim::with(['warranty.policy.businessUnit', 'customer', 'warranty.orderItem.order', 'warranty.orderItem.variant'])->find($this->selectedClaimId);

            if ($selectedClaimObj) {
                $businessUnitCode = $selectedClaimObj->warranty->policy->businessUnit->code ?? 'syihab';

                $banks = \App\Models\AccurateGlAccount::where('account_type', 'CASH_BANK')
                    ->where('database_source', $businessUnitCode)
                    ->get();
            }
        }

        return view('livewire.admin.warranty.claim-management', [
            'claims' => $claims,
            'banks' => $banks,
            'selectedClaimObj' => $selectedClaimObj
        ])->layout('layouts.z');
    }
}
