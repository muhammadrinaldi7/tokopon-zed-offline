<?php

namespace App\Livewire\Zoffline\Pos\Traits;

use Livewire\Attributes\Computed;

trait WithCustomerAndSales
{
    // ─── Customer ──────────────────────────────────────────────
    public $isNewCustomer = false;
    public $searchCustomer = '';
    public $selectedCustomerId = null;
    public $customerName = '';
    public $customerPhone = '';
    public $customerEmail = '';

    #[Computed]
    public function displayCustomerName()
    {
        if ($this->selectedCustomerId) {
            return \App\Models\User::find($this->selectedCustomerId)->name ?? 'Pelanggan';
        }
        return $this->customerName ?: 'Pelanggan Baru';
    }

    #[Computed]
    public function displayCustomerPhone()
    {
        if ($this->selectedCustomerId) {
            return \App\Models\User::with('profile')->find($this->selectedCustomerId)->profile->phone_number ?? '';
        }
        return $this->customerPhone;
    }

    #[Computed]
    public function displayCustomerEmail()
    {
        if ($this->selectedCustomerId) {
            return \App\Models\User::find($this->selectedCustomerId)->email ?? '';
        }
        return $this->customerEmail;
    }

    // SALES
    public $selectedSales = []; // Array untuk menampung lebih dari 1 sales
    public $searchSales = '';

    // ─── Customer Actions ──────────────────────────────────────

    public function selectCustomer($id)
    {
        $this->selectedCustomerId = $id;
        $customer = \App\Models\User::with('profile')->find($id);
        if ($customer) {
            $this->customerName = $customer->name;
            $this->customerPhone = $customer->profile->phone_number ?? '';
            $this->customerEmail = $customer->email ?? '';
        }
        $this->searchCustomer = '';
    }

    public function clearSelectedCustomer()
    {
        $this->selectedCustomerId = null;
        $this->customerName = '';
        $this->customerPhone = '';
        $this->customerEmail = '';
        $this->isNewCustomer = false;
    }

    // ─── Sales Actions ──────────────────────────────────────

    public function selectSales($id)
    {
        $sales = \App\Models\Employe::find($id);
        if ($sales) {
            $this->selectedSales = [[
                'id' => $sales->id,
                'name' => $sales->name,
                'employee_no' => $sales->employee_no
            ]];
        }
        $this->searchSales = '';
    }

    public function removeSales($id)
    {
        $this->selectedSales = array_values(array_filter($this->selectedSales, function ($s) use ($id) {
            return $s['id'] != $id;
        }));
    }
}
