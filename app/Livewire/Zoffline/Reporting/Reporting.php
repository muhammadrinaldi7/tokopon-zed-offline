<?php

namespace App\Livewire\Zoffline\Reporting;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.z')]
class Reporting extends Component
{
    public function navigateToSales()
    {
        return $this->redirectRoute('admin.reporting.sales', navigate: true);
    }

    public function navigateToStock()
    {
        return $this->redirectRoute('admin.reporting.stock', navigate: true);
    }

    public function navigateToPromo()
    {
        return $this->redirectRoute('admin.reporting.promo', navigate: true);
    }

    public function navigateToProducts()
    {
        return $this->redirectRoute('admin.reporting.products', navigate: true);
    }

    public function navigateToLaporanStok()
    {
        return $this->redirectRoute('admin.reporting.laporan-stok', navigate: true);
    }

    public function navigateToStaff()
    {
        return $this->redirectRoute('admin.reporting.staff', navigate: true);
    }

    public function render()
    {
        return view('livewire.zoffline.reporting.reporting');
    }
}
