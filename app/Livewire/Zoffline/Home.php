<?php

namespace App\Livewire\Zoffline;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.z')]
class Home extends Component
{
    public function navigateToTradeIn()
    {
        return redirect()->route('zoffline.trade-in');
    }
    public function navigateToSellPhone()
    {
        return redirect()->route('zoffline.sell-phone');
    }
    public function navigateToZPos()
    {
        return redirect()->route('zoffline.pos');
    }
    public function render()
    {
        return view('livewire.zoffline.home');
    }
}
