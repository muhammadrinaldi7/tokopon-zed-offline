<?php

namespace App\Livewire\Zoffline\Warranty;

use App\Models\Warranty;
use App\Models\WarrantyClaim as WarrantyClaimModel;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

#[Layout('layouts.z')]
class WarrantyClaim extends Component
{
    public $searchQuery = '';
    public $foundWarranties;
    public $selectedWarrantyId = null;

    public function mount()
    {
        $this->foundWarranties = collect();
    }
    
    // Form fields
    public $issue_description = '';
    public $customer_name = '';
    public $customer_phone = '';
    
    public $isSubmitted = false;

    public function searchWarranties()
    {
        $this->validate([
            'searchQuery' => 'required|string|min:3'
        ], [
            'searchQuery.required' => 'Serial Number wajib diisi',
            'searchQuery.min' => 'Serial Number minimal 3 karakter'
        ]);

        $this->foundWarranties = Warranty::with(['policy', 'orderItem.order.user', 'orderItem.variant'])
            ->where('serial_number', $this->searchQuery)
            ->get();

        $this->selectedWarrantyId = null;
        $this->isSubmitted = false;
        
        if ($this->foundWarranties->count() === 0) {
            $this->addError('searchQuery', 'Tidak ada garansi ditemukan untuk Serial Number ini.');
        }
    }

    public function selectWarranty($id)
    {
        $this->selectedWarrantyId = $id;
        
        // Auto-fill customer info if available
        $warranty = $this->foundWarranties->firstWhere('id', $id);
        if ($warranty && $warranty->orderItem && $warranty->orderItem->order && $warranty->orderItem->order->user) {
            $user = $warranty->orderItem->order->user;
            $this->customer_name = $user->name;
            $this->customer_phone = $user->phone_number;
        }
    }

    public function submitClaim()
    {
        $this->validate([
            'selectedWarrantyId' => 'required|exists:warranties,id',
            'issue_description' => 'required|string|min:10',
            'customer_name' => 'required|string|max:255',
        ]);

        $warranty = Warranty::find($this->selectedWarrantyId);

        // Check if expired
        if ($warranty->expires_at < Carbon::now()) {
            $this->addError('issue_description', 'Garansi ini sudah kedaluwarsa.');
            return;
        }

        // Check if max claims reached
        if ($warranty->claims_used >= $warranty->policy->max_claims) {
            $this->addError('issue_description', 'Batas maksimal klaim untuk garansi ini sudah tercapai.');
            return;
        }

        // Generate claim number
        $claimNumber = 'CLM-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $claim = WarrantyClaimModel::create([
            'claim_number' => $claimNumber,
            'warranty_id' => $warranty->id,
            'customer_user_id' => $warranty->customer_user_id,
            'serial_number' => $warranty->serial_number,
            'issue_description' => $this->issue_description,
            'status' => 'pending',
            'claimed_by' => Auth::id(),
            'claimed_at' => Carbon::now(),
        ]);

        $this->isSubmitted = true;
        $this->dispatch('toast', title: 'Klaim Berhasil', message: 'Klaim garansi berhasil diajukan dengan nomor: ' . $claimNumber, type: 'success');
    }

    public function resetForm()
    {
        $this->searchQuery = '';
        $this->foundWarranties = collect();
        $this->selectedWarrantyId = null;
        $this->issue_description = '';
        $this->customer_name = '';
        $this->customer_phone = '';
        $this->isSubmitted = false;
    }

    public function render()
    {
        return view('livewire.zoffline.warranty.warranty-claim');
    }
}
