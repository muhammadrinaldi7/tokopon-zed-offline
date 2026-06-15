<?php

namespace App\Livewire\Admin\ManualDiscount;

use App\Models\ManualDiscountPreset;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin', ['title' => 'Pengaturan Diskon Manual (Internal)'])]
class Index extends Component
{
    use WithPagination;

    public $search = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function delete($id)
    {
        ManualDiscountPreset::find($id)?->delete();
        $this->dispatch('toast', title: 'Berhasil', message: 'Preset diskon berhasil dihapus.', type: 'success');
    }

    public function toggleActive($id)
    {
        $preset = ManualDiscountPreset::find($id);
        if ($preset) {
            $preset->update(['is_active' => !$preset->is_active]);
            $this->dispatch('toast', title: 'Berhasil', message: 'Status preset berhasil diubah.', type: 'success');
        }
    }

    public function render()
    {
        $query = ManualDiscountPreset::with('brand')->latest();

        if ($this->search) {
            $query->where('amount', 'like', '%' . $this->search . '%')
                ->orWhereHas('brand', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
        }

        return view('livewire.admin.manual-discount.index', [
            'presets' => $query->paginate(20)
        ]);
    }
}
