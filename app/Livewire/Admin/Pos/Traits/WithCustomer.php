<?php

namespace App\Livewire\Admin\Pos\Traits;

use App\Models\Employe;
use App\Models\User;
use Livewire\Attributes\Computed;

trait WithCustomer
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

    #[Computed]
    public function customerResults()
    {
        if (strlen($this->searchCustomer) < 2) return [];

        return User::whereHas('roles', function ($q) {
            $q->where('name', 'user');
        })->where(function ($q) {
            $q->where('name', 'like', '%' . $this->searchCustomer . '%')
                ->orWhere('email', 'like', '%' . $this->searchCustomer . '%')
                ->orWhereHas('profile', function ($q2) {
                    $q2->where('phone_number', 'like', '%' . $this->searchCustomer . '%');
                });
        })->with('profile')->take(5)->get();
    }

    #[Computed]
    public function salesResults()
    {
        if (strlen($this->searchSales) < 2) return [];

        return Employe::where(function ($q) {
            $q->where('name', 'like', '%' . $this->searchSales . '%');
        })->take(5)->get();
    }

    public function selectCustomer($id)
    {
        $this->selectedCustomerId = $id;
        $this->searchCustomer = '';
    }

    public function clearSelectedCustomer()
    {
        $this->selectedCustomerId = null;
        $this->isNewCustomer = false;
    }

    public function selectSales($id)
    {
        $sales = Employe::find($id);
        if ($sales && !collect($this->selectedSales)->contains('id', $id)) {
            $this->selectedSales[] = [
                'id' => $sales->id,
                'name' => $sales->name,
                'employee_no' => $sales->employee_no
            ];
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
