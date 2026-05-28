<?php

namespace App\Livewire\Admin\Qc;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin', ['title' => 'Cari Device'])]
class DeviceSearch extends Component
{
    public $imei = '';

    public function search()
    {
        $this->validate([
            'imei' => 'required|string|min:3',
        ]);

        return $this->redirectRoute('admin.qc.device-passport', ['imei' => $this->imei], navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.qc.device-search');
    }
}
