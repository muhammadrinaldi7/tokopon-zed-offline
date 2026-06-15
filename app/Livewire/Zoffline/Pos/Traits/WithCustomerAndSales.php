<?php

namespace App\Livewire\Zoffline\Pos\Traits;

trait WithCustomerAndSales
{
    // ─── Customer ──────────────────────────────────────────────
    public $isNewCustomer = false;
    public $searchCustomer = '';
    public $selectedCustomerId = null;
    public $customerName = '';
    public $customerPhone = '';
    public $customerEmail = '';

    // SALES
    public $selectedSales = []; // Array untuk menampung lebih dari 1 sales
    public $searchSales = '';

    // ─── Customer Actions ──────────────────────────────────────

    public function selectCustomer($id)
    {
        $this->selectedCustomerId = $id;
        $this->searchCustomer = '';
        $this->autoAdvanceIfReady();
    }

    public function clearSelectedCustomer()
    {
        $this->selectedCustomerId = null;
        $this->isNewCustomer = false;
    }

    // ─── Sales Actions ──────────────────────────────────────

    public function selectSales($id)
    {
        $sales = \App\Models\Employe::find($id);
        if ($sales && !collect($this->selectedSales)->contains('id', $id)) {
            $this->selectedSales[] = [
                'id' => $sales->id,
                'name' => $sales->name,
                'employee_no' => $sales->employee_no
            ];
        }
        $this->searchSales = '';
        $this->autoAdvanceIfReady();
    }

    public function removeSales($id)
    {
        $this->selectedSales = array_values(array_filter($this->selectedSales, function ($s) use ($id) {
            return $s['id'] != $id;
        }));
    }


    public function autoAdvanceIfReady()
    {
        if ($this->selectedCustomerId && count($this->selectedSales) > 0) {
            $this->nextStep();
        }
    }
}
