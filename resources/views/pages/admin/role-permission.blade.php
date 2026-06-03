<?php
use Livewire\Component;
use Livewire\Attributes\Layout;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Attributes\On;

new #[Layout('layouts.admin', ['title' => 'Kelola Role & Akses - TokoPun'])] class extends Component {
    public $roles = [];
    public $permissions = [];

    // UI State
    public $selectedRoleId = null;
    public $groupedPermissions = [];

    // Form for new role/permission
    public $newRoleName = '';
    public $newPermissionName = '';

    public function mount()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        if (!$user->hasAnyRole(['admin', 'superadmin'])) {
            return redirect('/admin/dashboard');
        }

        $this->loadData();

        if (count($this->roles) > 0) {
            $this->selectedRoleId = $this->roles->first()->id;
        }
    }

    public function loadData()
    {
        // Don't show superadmin as its permissions are usually implicitly bypassed
        $this->roles = Role::where('name', '!=', 'superadmin')->get();
        $this->permissions = Permission::all();

        $this->groupPermissions();
    }

    public function selectRole($id)
    {
        $this->selectedRoleId = $id;
    }

    private function groupPermissions()
    {
        $groups = [
            'Katalog Pusat' => ['icon' => '📦', 'items' => []],
            'Transaksi & Komunikasi' => ['icon' => '🛒', 'items' => []],
            'Master & Pengaturan' => ['icon' => '⚙️', 'items' => []],
            'Lainnya' => ['icon' => '🧩', 'items' => []],
        ];

        foreach ($this->permissions as $p) {
            $name = $p->name;
            if (str_contains($name, 'catalog') || str_contains($name, 'product') || str_contains($name, 'categories') || str_contains($name, 'brands') || str_contains($name, 'accurate') || str_contains($name, 'stock')) {
                $groups['Katalog Pusat']['items'][] = $p;
            } elseif (str_contains($name, 'pos') || str_contains($name, 'order') || str_contains($name, 'chat') || str_contains($name, 'promo')) {
                $groups['Transaksi & Komunikasi']['items'][] = $p;
            } elseif (str_contains($name, 'users') || str_contains($name, 'settings') || str_contains($name, 'buyback') || str_contains($name, 'qc') || str_contains($name, 'trade')) {
                $groups['Master & Pengaturan']['items'][] = $p;
            } else {
                $groups['Lainnya']['items'][] = $p;
            }
        }

        $this->groupedPermissions = array_filter($groups, fn($g) => count($g['items']) > 0);
    }

    public function togglePermission($permissionName)
    {
        $role = Role::findById($this->selectedRoleId);

        if ($role->hasPermissionTo($permissionName)) {
            $role->revokePermissionTo($permissionName);
        } else {
            $role->givePermissionTo($permissionName);
        }

        // Just to trigger a re-render and clear Spatie cache implicitly via relationships
        $this->loadData();
        $this->dispatch('admin-alert', type: 'success', message: 'Akses ' . $permissionName . ' diperbarui!');
    }

    public function confirmDeleteRole($roleId): void
    {
        $this->dispatch('show-confirm', title: 'Hapus Role', message: 'Apakah Anda yakin ingin menghapus role ini?', confirmParams: [$roleId], confirmEvent: 'do-delete-role', type: 'warning', confirmText: 'Ya, Hapus', cancelText: 'Batal');
    }

    public function createRole()
    {
        $this->validate(['newRoleName' => 'required|string|min:2|unique:roles,name']);

        $role = Role::create(['name' => strtolower($this->newRoleName), 'guard_name' => 'web']);

        $this->newRoleName = '';
        $this->loadData();
        $this->selectedRoleId = $role->id;
        $this->dispatch('admin-alert', type: 'success', message: 'Role baru berhasil ditambahkan!');
    }

    public function createPermission()
    {
        $this->validate(['newPermissionName' => 'required|string|min:2|unique:permissions,name']);

        Permission::create(['name' => strtolower(str_replace(' ', '_', $this->newPermissionName)), 'guard_name' => 'web']);

        $this->newPermissionName = '';
        $this->loadData();
        $this->dispatch('admin-alert', type: 'success', message: 'Permission baru berhasil ditambahkan!');
    }

    #[On('do-delete-role')]
    public function deleteRole($roleId)
    {
        $role = Role::findOrFail($roleId);
        if (!in_array($role->name, ['admin', 'cs'])) {
            $role->delete();
            $this->loadData();
            if ($this->selectedRoleId == $roleId) {
                $this->selectedRoleId = count($this->roles) > 0 ? $this->roles->first()->id : null;
            }
            $this->dispatch('admin-alert', type: 'success', message: 'Role berhasil dihapus!');
        } else {
            $this->dispatch('admin-alert', type: 'error', message: 'Role inti sistem (' . $role->name . ') tidak bisa dihapus!');
        }
    }
};
?>

<div class="px-6 py-8 w-full max-w-7xl mx-auto" x-data="{ alert: null, activeTab: 'matrix' }"
    @admin-alert.window="
        alert = $event.detail;
        setTimeout(() => alert = null, 3000);
    ">

    <!-- Alpine Notification Setup -->
    <div x-show="alert" x-transition.opacity.duration.300ms style="display: none;"
        class="mb-6 px-4 py-3 rounded-xl border flex items-center gap-3 text-sm font-medium shadow-sm transition-all fixed top-6 left-1/2 -translate-x-1/2 z-50 min-w-[300px]"
        :class="alert?.type === 'success' ? 'bg-emerald-50 border-emerald-100 text-emerald-800' :
            'bg-red-50 border-red-100 text-red-800'">
        <svg x-show="alert?.type === 'success'" class="w-5 h-5 text-emerald-500 shrink-0" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg x-show="alert?.type === 'error'" class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span x-text="alert?.message"></span>
    </div>

    <!-- Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Kelola Role & Akses</h1>
            <p class="text-sm text-gray-500 mt-1">Tentukan hak akses spesifik apa saja yang diizinkan untuk setiap
                kelompok entitas pengguna.</p>
        </div>

        <!-- Tabs -->
        <div
            class="flex p-1 bg-gray-100/80 rounded-xl border border-gray-200 max-w-full overflow-x-auto shrink-0 w-max">
            <button @click="activeTab = 'matrix'"
                :class="activeTab === 'matrix' ? 'bg-white text-gray-900 shadow-xs border-gray-200' :
                    'text-gray-500 hover:text-gray-800 border-transparent'"
                class="px-5 py-2 text-sm font-semibold rounded-lg transition-all border flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Atur Akses
            </button>
            <button @click="activeTab = 'add'"
                :class="activeTab === 'add' ? 'bg-white text-gray-900 shadow-xs border-gray-200' :
                    'text-gray-500 hover:text-gray-800 border-transparent'"
                class="px-5 py-2 text-sm font-semibold rounded-lg transition-all border flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Master Data
            </button>
        </div>
    </div>

    <!-- Role-Centric Permissions Tab -->
    <div x-show="activeTab === 'matrix'" x-transition.opacity>
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Sidebar Roles -->
            <div class="col-span-1 space-y-3">
                <div class="px-2">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Pilih Jabatan / Role</h3>
                </div>

                @foreach ($roles as $role)
                    <button wire:click="selectRole({{ $role->id }})"
                        class="w-full text-left px-4 py-3.5 rounded-2xl border transition-all flex items-center justify-between group {{ $selectedRoleId == $role->id ? 'bg-[#4E44DB] border-[#4E44DB] shadow-md ring-4 ring-[#4E44DB]/10' : 'bg-white border-gray-200 hover:border-[#4E44DB]/40 hover:shadow-sm' }}">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors {{ $selectedRoleId == $role->id ? 'bg-white/20 text-white' : 'bg-indigo-50 text-indigo-600 group-hover:bg-[#4E44DB] group-hover:text-white' }}">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <span
                                    class="font-bold block {{ $selectedRoleId == $role->id ? 'text-white' : 'text-gray-900' }} capitalize">{{ $role->name }}</span>
                                <span
                                    class="text-xs block {{ $selectedRoleId == $role->id ? 'text-indigo-100' : 'text-gray-400' }}">{{ $role->permissions->count() }}
                                    Izin Akses</span>
                            </div>
                        </div>
                        @if (!in_array($role->name, ['admin', 'cs']))
                            <div wire:click.stop="confirmDeleteRole({{ $role->id }})"
                                class="p-2 rounded-lg hover:bg-red-500 hover:text-white text-gray-300 transition-colors"
                                title="Hapus Role">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                        @endif
                    </button>
                @endforeach
            </div>

            <!-- Main Content Permissions -->
            <div class="col-span-1 lg:col-span-3">
                @if ($selectedRoleId)
                    @php
                        $activeRole = $roles->firstWhere('id', $selectedRoleId);
                    @endphp
                    <div
                        class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden flex flex-col h-full min-h-[500px]">
                        <div
                            class="px-8 py-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white relative overflow-hidden">
                            <!-- Background decoration -->
                            <div
                                class="absolute top-0 right-0 -mr-8 -mt-8 w-40 h-40 bg-indigo-50 rounded-full blur-3xl opacity-50 pointer-events-none">
                            </div>

                            <div class="relative z-10">
                                <h2 class="text-xl font-bold text-gray-900 capitalize flex items-center gap-2">
                                    Hak Akses untuk Role: <span
                                        class="text-[#4E44DB] px-2 py-0.5 bg-indigo-50 rounded-md border border-indigo-100">{{ $activeRole->name }}</span>
                                </h2>
                                <p class="text-sm text-gray-500 mt-2">Nyalakan tuas (switch) di bawah ini untuk
                                    memberikan izin akses fitur kepada role ini.</p>
                            </div>
                        </div>

                        <div class="p-8 bg-gray-50/30 flex-1">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                @foreach ($groupedPermissions as $groupName => $group)
                                    <div
                                        class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden flex flex-col transition-all hover:shadow-md hover:border-indigo-100">
                                        <div class="px-6 py-4 border-b border-gray-50 bg-white flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 rounded-full bg-gray-50 border border-gray-100 flex items-center justify-center text-xl shadow-inner shrink-0">
                                                {{ $group['icon'] }}
                                            </div>
                                            <h3 class="font-bold text-gray-800 text-sm tracking-wide">
                                                {{ $groupName }}</h3>
                                        </div>
                                        <div class="p-3 space-y-1 flex-1 bg-gray-50/10">
                                            @foreach ($group['items'] as $perm)
                                                @php
                                                    $hasPerm = $activeRole->hasPermissionTo($perm->name);
                                                    $permDisplayName = ucwords(
                                                        str_replace(['-', '_'], ' ', $perm->name),
                                                    );
                                                @endphp
                                                <div class="flex items-center justify-between p-3.5 rounded-2xl hover:bg-white hover:shadow-sm transition-all border border-transparent hover:border-gray-100 group/item cursor-pointer"
                                                    wire:click="togglePermission('{{ $perm->name }}')">
                                                    <div>
                                                        <p
                                                            class="text-sm font-bold text-gray-700 transition-colors {{ $hasPerm ? 'text-[#00bfa5]' : 'group-hover/item:text-[#4E44DB]' }}">
                                                            {{ $permDisplayName }}</p>
                                                        <p class="text-[10px] font-mono text-gray-400 mt-0.5">
                                                            {{ $perm->name }}</p>
                                                    </div>
                                                    <label
                                                        class="relative inline-flex items-center cursor-pointer shrink-0"
                                                        @click.stop>
                                                        <input type="checkbox" style="display: none;"
                                                            wire:click="togglePermission('{{ $perm->name }}')"
                                                            {{ $hasPerm ? 'checked' : '' }}>
                                                        <div class="w-12 h-6 rounded-full transition-colors shadow-inner border"
                                                            style="{{ $hasPerm ? 'background-color: #00bfa5; border-color: #00bfa5;' : 'background-color: #f3f4f6; border-color: #e5e7eb;' }}">
                                                        </div>
                                                        <div class="absolute left-[2px] top-[2px] bg-white rounded-full h-5 w-5 transition-transform duration-300 shadow-sm border border-gray-200 pointer-events-none flex items-center justify-center"
                                                            style="{{ $hasPerm ? 'transform: translateX(120%); border-color: transparent;' : '' }}">
                                                            @if ($hasPerm)
                                                                <svg class="w-3 h-3 text-[#00bfa5]" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor"
                                                                    stroke-width="3">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                            @endif
                                                        </div>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div
                        class="bg-white rounded-3xl border border-gray-200 shadow-sm h-full flex flex-col items-center justify-center p-12 text-center min-h-[500px]">
                        <div
                            class="w-20 h-20 bg-indigo-50 text-indigo-300 rounded-full flex items-center justify-center mb-6">
                            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800">Pilih Role Terlebih Dahulu</h3>
                        <p class="text-sm text-gray-500 mt-2 max-w-sm">Pilih salah satu role di daftar sebelah kiri
                            untuk melihat dan mengatur hak aksesnya.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Add Form Tab (Master Data) -->
    <div x-show="activeTab === 'add'" class="grid grid-cols-1 md:grid-cols-2 gap-6" style="display: none;"
        x-transition.opacity>
        <!-- Add Role Form -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 flex flex-col justify-between">
            <div>
                <div
                    class="w-14 h-14 bg-indigo-50 border border-indigo-100 rounded-2xl flex items-center justify-center text-[#4E44DB] mb-6">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Tambah Master Role Baru</h3>
                <p class="text-sm text-gray-500 mb-8 leading-relaxed">Role merupakan sebuah jabatan atau grup untuk
                    mengelompokkan
                    pengguna di dalam aplikasi Anda. (Contoh: manager, supervisor, agen).</p>
            </div>

            <form wire:submit="createRole">
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nama Role</label>
                    <input type="text" wire:model="newRoleName"
                        placeholder="Ketik nama role huruf kecil (misal: kurir)..." required
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-5 py-4 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all">
                    @error('newRoleName')
                        <span class="text-red-500 text-xs mt-2 block font-bold">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit"
                    class="w-full px-5 py-4 text-sm font-bold text-white bg-[#4E44DB] rounded-xl hover:bg-[#3c34af] shadow-lg shadow-[#4E44DB]/20 transition-all flex items-center justify-center gap-2 cursor-pointer border border-[#4E44DB]">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Simpan Role Baru
                </button>
            </form>
        </div>

        <!-- Add Permission Form -->
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 flex flex-col justify-between">
            <div>
                <div
                    class="w-14 h-14 bg-emerald-50 border border-emerald-100 rounded-2xl flex items-center justify-center text-emerald-600 mb-6">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Tambah Permission Sistem</h3>
                <p class="text-sm text-gray-500 mb-8 leading-relaxed">Permission adalah variabel titik akses spesifik
                    di dalam code aplikasi. Biasanya dibuat oleh Developer. (Contoh: edit_product, delete_receipt).</p>
            </div>

            <form wire:submit="createPermission">
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Nama Permission</label>
                    <input type="text" wire:model="newPermissionName" placeholder="Contoh: verify-transaction..."
                        required
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-5 py-4 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#00bfa5]/20 focus:border-[#00bfa5] transition-all font-mono">
                    @error('newPermissionName')
                        <span class="text-red-500 text-xs mt-2 block font-bold">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit"
                    class="w-full px-5 py-4 text-sm font-bold text-white bg-[#00bfa5] rounded-xl hover:bg-[#00a68f] shadow-lg shadow-[#00bfa5]/20 transition-all flex items-center justify-center gap-2 cursor-pointer border border-[#00bfa5]">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Daftarkan Permission
                </button>
            </form>
        </div>
    </div>
</div>
