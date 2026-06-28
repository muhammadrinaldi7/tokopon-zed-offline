<?php

namespace App\Livewire\Admin\Warranty;

use App\Models\WarrantyClaim;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\DeviceInspection;
use App\Services\AccurateService;

class ClaimManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';

    public $showModal = false;
    public $selectedClaimId = null;
    public $resolution_notes = '';
    public $replacement_imei = '';

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
    public $bank_no = '';

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
        $this->bank_no = '';
        $this->cancelReplacementProduct();
        $this->originalInspection = null;
        $this->claimInspection = null;
        $this->viewingQcDetails = null;
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

    public function approveReplacement()
    {
        $this->validate([
            'replacement_imei' => 'required|string|min:3',
            'replacement_item_no' => 'required_if:replacement_type,different',
            'bank_no' => 'required_if:replacement_type,different',
            'original_price' => 'required|numeric|min:0'
        ]);

        $claim = WarrantyClaim::with(['warranty.orderItem.order', 'warranty.orderItem.variant'])->findOrFail($this->selectedClaimId);
        
        $originalPrice = $this->original_price;
        $newItemNo = $this->replacement_type === 'different' ? $this->replacement_item_no : null;
        $newPrice = $this->replacement_type === 'different' ? $this->replacement_price : $originalPrice;
        $priceDifference = $newPrice - $originalPrice; // Positive if upgrade, negative if downgrade

        // 1. Integrasi API Accurate
        try {
            $accurateService = app(AccurateService::class);
            // $newPrice digunakan sbg targetPrice di service
            $accurateService->processWarrantyReplacement($claim, $this->replacement_imei, $newItemNo, $newPrice, $priceDifference, $this->replacement_type, $this->bank_no, $originalPrice);
        } catch (\Exception $e) {
            $this->addError('replacement_imei', 'Gagal memproses Accurate: ' . $e->getMessage());
            return;
        }

        // 2. Update Database Lokal
        $claim->status = $priceDifference < 0 ? 'waiting_refund' : 'completed'; // Jika downgrade, tunggu kasir proses refund
        if ($priceDifference < 0) {
            $claim->refund_amount = abs($priceDifference);
        }
        $claim->resolved_at = Carbon::now();
        $claim->resolution = 'replaced';
        $noteType = $this->replacement_type === 'same' ? 'Ganti Unit' : ($priceDifference > 0 ? 'Upgrade Unit' : 'Downgrade Unit');
        $claim->resolution_notes = "{$noteType} ke IMEI: {$this->replacement_imei}" . 
                                   ($newItemNo ? " (Barang Baru: {$this->replacement_product_name})" : "") .
                                   " | {$this->resolution_notes}";
        $claim->approved_by = Auth::id();
        $claim->save();

        // Nonaktifkan Garansi Lama
        $oldWarranty = $claim->warranty;
        $oldWarranty->status = 'replaced';
        $oldWarranty->save();

        // Buat Garansi Baru untuk IMEI Baru (Meneruskan masa aktif yang lama)
        $newWarranty = $oldWarranty->replicate();
        $newWarranty->serial_number = $this->replacement_imei;
        $newWarranty->status = 'active';
        $newWarranty->device_inspection_id = null; // Butuh QC baru nanti
        
        // Update data varian jika berbeda
        if ($this->replacement_type === 'different' && $newItemNo) {
            $newVariant = \App\Models\ProductVariant::whereHas('accurateData', function($q) use ($newItemNo) {
                $q->where('item_no', $newItemNo);
            })->first();
            
            if (!$newVariant) {
                $newVariant = \App\Models\SecondProductVariant::whereHas('accurateData', function($q) use ($newItemNo) {
                    $q->where('item_no', $newItemNo);
                })->first();
            }
            
            // Catatan: Model Warranty bawaan tidak memiliki product_variant_id. 
            // Cukup biarkan order_item_id menunjuk ke order asli.
        }
        
        $newWarranty->save();
        
        $this->closeReplacementForm();
        $this->closeModal(); // Pastikan modal utama juga tertutup agar state bersih
        $this->dispatch('toast', title: 'Berhasil', message: 'Unit berhasil diganti' . ($claim->status === 'waiting_refund' ? '. Sisa saldo menunggu proses refund.' : '!'), type: 'success');
        $this->reset(['replacement_imei', 'replacement_type', 'replacement_item_no', 'replacement_price', 'replacement_product_name', 'search_product_query', 'product_results', 'bank_no']);
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
        $this->bank_no = '';
    }
    
    public function closeRefundForm()
    {
        $this->showRefundForm = false;
    }

    public function openReplacementForm()
    {
        $this->showReplacementForm = true;
        $this->replacement_type = 'same';
        $this->bank_no = '';
        
        $claim = WarrantyClaim::with('warranty.orderItem')->find($this->selectedClaimId);
        $this->original_price = $claim->warranty->orderItem->price_at_checkout ?? 0;
        
        $this->cancelReplacementProduct();
    }

    public function closeReplacementForm()
    {
        $this->showReplacementForm = false;
        $this->replacement_imei = '';
        $this->replacement_type = 'same';
        $this->bank_no = '';
        $this->cancelReplacementProduct();
        $this->resetValidation(['replacement_imei', 'bank_no']);
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
