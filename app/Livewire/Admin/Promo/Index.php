<?php

namespace App\Livewire\Admin\Promo;

use App\Models\Promo;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin', ['title' => 'Manajemen Promo & Voucher'])]
class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $category = ''; // filter

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function toggleStatus(Promo $promo)
    {
        $promo->update(['is_active' => !$promo->is_active]);
        $this->dispatch('toast', title: 'Berhasil', message: 'Status promo diperbarui.', type: 'success');
    }

    public function delete(Promo $promo)
    {
        // Cegah penghapusan jika sudah dipakai
        if ($promo->orders()->count() > 0) {
            $this->dispatch('toast', title: 'Gagal', message: 'Promo tidak bisa dihapus karena sudah memiliki riwayat pemakaian.', type: 'error');
            return;
        }

        $promo->delete();
        $this->dispatch('toast', title: 'Berhasil', message: 'Promo berhasil dihapus.', type: 'success');
    }

    public function render()
    {
        $promos = Promo::with('brand')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('code', 'like', '%' . $this->search . '%');
            })
            ->when($this->category, function ($query) {
                $query->where('category', $this->category);
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.promo.index', [
            'promos' => $promos
        ]);
    }
}
