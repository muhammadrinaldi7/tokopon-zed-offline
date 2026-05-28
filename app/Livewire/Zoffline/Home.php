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

    public function navigateToSellPhone()
    {
        return $this->redirectRoute('zoffline.sell-phone', navigate: true);
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
