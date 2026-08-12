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

    public function navigateToIncomeStatement()
    {
        return $this->redirectRoute('reporting.income-statement', navigate: true);
    }

    public function navigateToClosingKasir()
    {
        return $this->redirectRoute('reporting.closing-kasir', navigate: true);
    }

    public function navigateToInvoiceReport()
    {
        return $this->redirectRoute('reporting.pembayaran', navigate: true);
    }

    public function navigateToCancellation()
    {
        return $this->redirectRoute('reporting.pembatalan', navigate: true);
    }

    public function navigateToSalesOrder()
    {
        return $this->redirectRoute('reporting.sales-order', navigate: true);
    }

    public function navigateToWarranty()
    {
        return $this->redirectRoute('reporting.warranty', navigate: true);
    }

    public function navigateToReturnReport()
    {
        return $this->redirectRoute('reporting.return-report', navigate: true);
    }

    public function render()
    {
        return view('livewire.zoffline.reporting.reporting');
    }
}
