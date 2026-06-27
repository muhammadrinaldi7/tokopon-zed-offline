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

    protected $listeners = ['refreshClaims' => '$refresh'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
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
            'replacement_imei' => 'required|string|min:3'
        ]);

        $claim = WarrantyClaim::with(['warranty.orderItem.order'])->findOrFail($this->selectedClaimId);
        
        // 1. Integrasi API Accurate
        try {
            $accurateService = app(AccurateService::class);
            $accurateService->processWarrantyReplacement($claim, $this->replacement_imei);
        } catch (\Exception $e) {
            $this->addError('replacement_imei', 'Gagal memproses Accurate: ' . $e->getMessage());
            return;
        }

        // 2. Update Database Lokal
        $claim->status = 'completed'; // Langsung selesai jika ganti unit
        $claim->resolved_at = Carbon::now();
        $claim->resolution = 'replaced';
        $claim->resolution_notes = 'Ganti Unit ke IMEI: ' . $this->replacement_imei . ' | ' . $this->resolution_notes;
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
        $newWarranty->save();

        $this->closeModal();
        $this->dispatch('toast', title: 'Retur Sukses', message: 'Unit berhasil diganti dan disinkronisasi ke Accurate.', type: 'success');
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

        return view('livewire.admin.warranty.claim-management', [
            'claims' => $claims
        ])->layout('layouts.z');
    }
}
