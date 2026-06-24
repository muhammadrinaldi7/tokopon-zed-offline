<?php

namespace App\Livewire\Admin\Warehouse;

use App\Models\ProductSerialNumber;
use Livewire\Component;
use Livewire\Attributes\Layout;

class CheckSerialNumber extends Component
{
    public $keyword = '';
    public $result = null;
    public $hasSearched = false;

    #[Layout('layouts.z')]
    public function render()
    {
        return view('livewire.admin.warehouse.check-serial-number');
    }

    public function search()
    {
        $this->validate([
            'keyword' => 'required|string|min:3'
        ], [
            'keyword.required' => 'Silakan masukkan Serial Number (IMEI).',
            'keyword.min' => 'Masukkan minimal 3 karakter.'
        ]);

        $this->hasSearched = true;

        $this->result = ProductSerialNumber::with('warehouse')
            ->where('serial_number', trim($this->keyword))
            ->first();

        // Reset the input so scanner can scan the next one easily
        $this->keyword = '';
    }
}
