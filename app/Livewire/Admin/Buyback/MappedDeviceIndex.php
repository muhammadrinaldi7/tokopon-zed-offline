<?php

namespace App\Livewire\Admin\Buyback;

use App\Models\BuybackTier;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('layouts.admin', ['title' => 'Perangkat Ter-mapping'])]
class MappedDeviceIndex extends Component
{
    use WithPagination;

    public $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = BuybackTier::withCount('devices');

        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $tiers = $query->orderBy('name')->paginate(15);

        return view('livewire.admin.buyback.mapped-device-index', compact('tiers'));
    }
}
