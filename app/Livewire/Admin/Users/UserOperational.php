<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.admin', ['title' => 'Kelola Pengguna Operasional - TokoPun'])]
class UserOperational extends Component
{
    use WithPagination;

    public $search = '';
    public $isEditModalOpen = false;
    public $editingUser = null;
    public $selectedRoles = [];

    // Create User
    public $isCreateModalOpen = false;
    public $createName = '';
    public $createEmail = '';
    public $createPassword = '';
    public $createPasswordConfirmation = '';
    public $selectedCreateRoles = [];

    public function mount()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user->hasAnyRole(['admin', 'superadmin'])) {
            return redirect('/admin/dashboard');
        }
    }

    public function with()
    {
        return [
            'users' => User::with(['roles', 'branch', 'warehouse'])
                // 1. Filter Role: Ambil yang BUKAN customer atau user
                ->whereHas('roles', function ($q) {
                    $q->whereNotIn('name', ['customer', 'user']);
                })
                // 2. Pencarian: Bungkus di dalam closure agar operator OR tidak bocor
                ->when($this->search, function ($q) {
                    $q->where(function ($query) {
                        $query->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('email', 'like', '%' . $this->search . '%');
                    });
                })
                // 3. Pengurutan dan Paginasi
                ->orderByDesc('id')
                ->paginate(15),

            'availableRoles' => Role::all() // Catatan: Jika role banyak, pertimbangkan select('id', 'name')
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function editUser($userId)
    {
        $this->editingUser = User::with('roles')->findOrFail($userId);
        $this->selectedRoles = $this->editingUser->roles->pluck('name')->toArray();
        $this->isEditModalOpen = true;
    }

    public function saveRoles()
    {
        if ($this->editingUser) {
            // Protect against locking out current admin user
            if (Auth::id() === $this->editingUser->id && !in_array('admin', $this->selectedRoles) && !in_array('superadmin', $this->selectedRoles)) {
                // To display error to user, typical JS dispatching
                $this->dispatch('admin-alert', type: 'error', message: 'Anda tidak bisa menghapus role admin dari akun Anda sendiri.');
                return;
            }

            $this->editingUser->syncRoles($this->selectedRoles);
            $this->isEditModalOpen = false;
            $this->editingUser = null;

            $this->dispatch('admin-alert', type: 'success', message: 'Role dan Permission berhasil diperbarui!');
        }
    }

    public function closeModal()
    {
        $this->isEditModalOpen = false;
        $this->editingUser = null;
    }

    public function openCreateModal()
    {
        $this->resetCreateForm();
        $this->isCreateModalOpen = true;
    }

    public function closeCreateModal()
    {
        $this->isCreateModalOpen = false;
        $this->resetCreateForm();
    }

    public function storeUser()
    {
        $this->validate([
            'createName' => 'required|string|max:255',
            'createEmail' => 'required|email|unique:users,email',
            'createPassword' => 'required|string|min:8|same:createPasswordConfirmation',
            'createPasswordConfirmation' => 'required',
            'selectedCreateRoles' => 'required|array|min:1',
        ], [
            'createName.required' => 'Nama wajib diisi.',
            'createEmail.required' => 'Email wajib diisi.',
            'createEmail.unique' => 'Email sudah terdaftar.',
            'createPassword.required' => 'Password wajib diisi.',
            'createPassword.min' => 'Password minimal 8 karakter.',
            'createPassword.same' => 'Konfirmasi password tidak sesuai.',
            'selectedCreateRoles.required' => 'Pilih minimal satu role.',
            'selectedCreateRoles.min' => 'Pilih minimal satu role.',
        ]);

        $user = User::create([
            'name' => $this->createName,
            'email' => $this->createEmail,
            'password' => bcrypt($this->createPassword),
        ]);

        $user->syncRoles($this->selectedCreateRoles);

        $this->isCreateModalOpen = false;
        $this->resetCreateForm();

        $this->dispatch('admin-alert', type: 'success', message: 'Staff baru "' . $user->name . '" berhasil ditambahkan!');
    }

    private function resetCreateForm()
    {
        $this->createName = '';
        $this->createEmail = '';
        $this->createPassword = '';
        $this->createPasswordConfirmation = '';
        $this->selectedCreateRoles = [];
    }
    // Di dalam Class Index.php Anda
    #[On('refresh-user-table')]
    public function refreshTable() {}
    public function render()
    {
        return view('livewire.admin.users.user-operational');
    }
}
