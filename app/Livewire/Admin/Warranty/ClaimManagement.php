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
use Livewire\Attributes\Computed;

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
    public $selected_sales_id = null;
    public $search_sales_query = '';

    public $is_editing_replacement_price = false;

    public $search_imei_query = '';
    public $imei_results = [];

    protected $listeners = ['refreshClaims' => '$refresh'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function salesResults()
    {
        if (strlen($this->search_sales_query) < 2) return [];

        $user = Auth::user();
        $businessUnitId = $user->getActiveBusinessUnitId() ?? 1;

        return \App\Models\Employe::active()
            ->where('business_unit_id', $businessUnitId)
            ->with('branch')
            ->where(function ($q) {
                $q->where('branch_id', Auth::user()->branch_id)
                    ->orWhereNull('branch_id');
            })
            ->where('name', 'like', '%' . $this->search_sales_query . '%')
            ->take(10)
            ->get();
    }

    public function selectSales($salesId)
    {
        $this->selected_sales_id = $salesId;
        $this->search_sales_query = \App\Models\Employe::find($salesId)->name ?? '';
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
        $this->is_editing_replacement_price = false;
        $this->selected_sales_id = null;
        $this->search_sales_query = '';
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
            'selected_sales_id' => $this->selected_sales_id
        ], [
            'replacement_imei' => 'required|string|min:3',
            'replacement_item_no' => 'required_if:replacement_type,different',
            'bank_no' => 'required',
            'original_price' => 'required|numeric|min:0',
            'selected_sales_id' => 'required'
        ], [
            'selected_sales_id.required' => 'Mohon pilih Salesperson untuk unit pengganti.'
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
        $this->validate([
            'replacement_imei' => 'required|min:3',
            'replacement_type' => 'required|in:same,different',
            'replacement_item_no' => 'required_if:replacement_type,different',
            'replacement_price' => 'required_if:replacement_type,different|numeric|min:0',
            'bank_no' => 'required',
            'original_price' => 'required|numeric|min:0',
            'selected_sales_id' => 'required'
        ], [
            'selected_sales_id.required' => 'Mohon pilih Salesperson untuk unit pengganti.'
        ]);

        $claim = WarrantyClaim::with(['warranty.orderItem.order', 'warranty.orderItem.variant'])->findOrFail($this->selectedClaimId);

        // Cek apakah sudah ada request approval yang pending
        $existing = \App\Models\ApprovalRequest::where('approvable_type', WarrantyClaim::class)
            ->where('approvable_id', $claim->id)
            ->where('request_type', 'WARRANTY_REPLACEMENT')
            ->where('status', 'PENDING')
            ->first();

        if ($existing) {
            $this->addError('replacement_imei', 'Klaim ini sedang menunggu persetujuan (Approval PENDING).');
            return;
        }

        $payload = [
            'replacement_imei' => $this->replacement_imei,
            'replacement_type' => $this->replacement_type,
            'replacement_item_no' => $this->replacement_item_no,
            'replacement_price' => $this->replacement_price,
            'bank_no' => $this->bank_no,
            'original_price' => $this->original_price,
            'selected_sales_id' => $this->selected_sales_id,
            'manual_note' => $this->manual_note,
            'resolution_notes' => $this->resolution_notes,
            'replacement_product_name' => $this->replacement_product_name,
            'branch_id' => Auth::user()->branch_id,
            'business_unit_id' => Auth::user()->getActiveBusinessUnitId() ?? 1,
            'branch_name' => Auth::user()->branch->name ?? 'Toko'
        ];

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $approval = \App\Models\ApprovalRequest::create([
                'request_type' => 'WARRANTY_REPLACEMENT',
                'approvable_type' => WarrantyClaim::class,
                'approvable_id' => $claim->id,
                'requested_by' => Auth::id(),
                'payload' => $payload,
                'reason' => "Pengajuan Ganti Unit Garansi ke IMEI: {$this->replacement_imei}",
                'status' => 'PENDING',
                'current_level' => 0
            ]);

            \App\Http\Controllers\ApprovalController::sendTelegramNotification($approval);

            \Illuminate\Support\Facades\DB::commit();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->addError('replacement_imei', 'Gagal mengajukan approval: ' . $e->getMessage());
            $this->showReplacementConfirmModal = false;
            return;
        }

        $this->showReplacementConfirmModal = false;
        $this->closeReplacementForm();
        $this->closeModal(); // Pastikan modal utama juga tertutup agar state bersih
        $this->dispatch('toast', title: 'Berhasil', message: 'Pengajuan Ganti Unit berhasil dikirim ke Admin untuk di-approve.', type: 'success');
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
