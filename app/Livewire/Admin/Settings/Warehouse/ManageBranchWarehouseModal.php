<?php

namespace App\Livewire\Admin\Settings\Warehouse;

use App\Models\Branch;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Attributes\On;
use Livewire\Component;

class ManageBranchWarehouseModal extends Component
{
    public $isOpen = false;
    public $branches;
    public $warehouses;
    public $user_id;
    public $name;
    public $email;
    public $branch_id;
    public $warehouse_id;

    // Listener untuk mendengarkan perintah buka modal dari komponen Index
    #[On('open-branch-modal')]
    public function openModal($userId)
    {
        $user = User::findOrFail($userId);

        $this->user_id = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->branch_id = $user->branch_id;
        $this->warehouse_id = $user->warehouse_id;

        $this->isOpen = true;
    }
    public function loadAll()
    {
        $this->branches = Branch::all();
        $this->warehouses = Warehouse::all();
    }
    public function mount()
    {
        $this->loadAll();
    }

    public function closeModal()
    {
        $this->reset(['isOpen', 'user_id', 'name', 'email', 'branch_id', 'warehouse_id']);
    }

    public function saveBranch()
    {
        $user = User::findOrFail($this->user_id);

        $user->update([
            'branch_id' => $this->branch_id ?: null,
            'warehouse_id' => $this->warehouse_id ?: null,
        ]);

        // Kirim notifikasi toast (sesuai komponen toast Alpine Anda)
        $this->dispatch('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Penempatan Cabang & Gudang berhasil diperbarui.'
        ]);

        // Perintahkan komponen Index utama untuk me-refresh tabel
        $this->dispatch('refresh-user-table');

        // Tutup modal
        $this->closeModal();
    }
    public function render()
    {
        return view('livewire.admin.settings.warehouse.manage-branch-warehouse-modal');
    }
}
