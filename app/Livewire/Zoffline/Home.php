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
        return $this->redirectRoute('zoffline.cekstock', navigate: true);
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
    public function navigateToClosingKasir()
    {
        return $this->redirectRoute('zoffline.pos.closing-kasir', navigate: true);
    }
    public function navigateToDashboard()
    {
        return $this->redirectRoute('admin.dashboard', navigate: true);
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
