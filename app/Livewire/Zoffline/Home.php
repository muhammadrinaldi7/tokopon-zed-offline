<?php

namespace App\Livewire\Zoffline;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.z')]
class Home extends Component
{
    public function navigateToTradeIn()
    {
        return $this->redirectRoute('zoffline.trade-in', navigate: true);
    }
    public function navigateToCekStock()
    {
        return $this->redirectRoute('zoffline.cek-stock', navigate: true);
    }

    public function navigateToCekLokasiSN()
    {
        return $this->redirectRoute('zoffline.check-serial-number', navigate: true);
    }

    public function navigateToShift()
    {
        return $this->redirectRoute('zoffline.riwayat-kasir', navigate: true);
    }
    public function navigateToSellPhone()
    {
        return $this->redirectRoute('zoffline.sell-phone', navigate: true);
    }

    public function navigateToWarrantyActivation()
    {
        return $this->redirectRoute('zoffline.warranty-activation', navigate: true);
    }

    public function navigateToKlaimGaransi()
    {
        $this->dispatch('alert', [
            'type' => 'info',
            'message' => 'Fitur Klaim Garansi akan segera hadir!'
        ]);
    }

    public function navigateToAddOnGaransi()
    {
        $this->dispatch('alert', [
            'type' => 'info',
            'message' => 'Fitur Add On Garansi akan segera hadir!'
        ]);
    }
    public function navigateToClosingKasir()
    {
        return $this->redirectRoute('zoffline.pos.closing-kasir', navigate: true);
    }
    public function navigateToRiwayatPenjualan()
    {
        return $this->redirectRoute('zoffline.pos.riwayat', navigate: true);
    }

    public function navigateToRiwayatPembelian()
    {
        return $this->redirectRoute('zoffline.sell-phone-history', navigate: true);
    }

    public function navigateToTarikDataLaporan()
    {
        return $this->redirectRoute('zoffline.reporting', navigate: true);
    }

    public function navigateToZPos()
    {
        return $this->redirectRoute('zoffline.pos', navigate: true);
    }
    public function render()
    {
        return view('livewire.zoffline.home');
    }
}
