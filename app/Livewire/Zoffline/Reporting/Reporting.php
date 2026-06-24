<?php

namespace App\Livewire\Zoffline\Reporting;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.z')]
class Reporting extends Component
{
    public function navigateToSales()
    {
        return $this->redirectRoute('reporting.sales', navigate: true);
    }

    public function navigateToStock()
    {
        return $this->redirectRoute('reporting.stock', navigate: true);
    }

    public function navigateToPromo()
    {
        return $this->redirectRoute('reporting.promo', navigate: true);
    }

    public function navigateToProducts()
    {
        return $this->redirectRoute('reporting.products', navigate: true);
    }

    public function navigateToLaporanStok()
    {
        return $this->redirectRoute('reporting.laporan-stok', navigate: true);
    }

    public function navigateToStaff()
    {
        return $this->redirectRoute('reporting.staff', navigate: true);
    }

    public function render()
    {
        return view('livewire.zoffline.reporting.reporting');
    }
}
