<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserAccurateCustomer;
use App\Models\UserAccurateVendor;
use App\Models\UserBankAccount;
use App\Models\BusinessUnit;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

new #[Layout('layouts.admin', ['title' => 'Kelola Pengguna & Customer - TokoPun'])] class extends Component {
    use WithPagination;

    // ─── Filter & Pencarian ────────────────────────────────────
    public $search = '';
    public $filterRole = '';
    public $filterAccurateStatus = '';

    // ─── State Modal Multi-Tab ─────────────────────────────────
    public $isEditModalOpen = false;
    public $activeTab = 'profile'; // profile | accurate_customer | accurate_vendor | roles_bank
    public $editingUserId = null;

    // ─── Tab 1: Profil & Akun ──────────────────────────────────
    public $editName = '';
    public $editEmail = '';
    public $editPhone = '';
    public $editIdentity = '';
    public $editNpwp = '';
    public $editDomisili = '';
    public $editPassword = '';
    public $syncProfileToAccurate = false;

    // ─── Tab 2: Accurate Customer ──────────────────────────────
    public $manualCustBuId = '';
    public $manualCustAccurateId = '';
    public $manualCustAccurateNo = '';
    public $pullCustBuCode = '';
    public $pullCustNo = '';

    // ─── Tab 3: Accurate Vendor ────────────────────────────────
    public $manualVendBuId = '';
    public $manualVendAccurateId = '';
    public $manualVendAccurateNo = '';
    public $pullVendBuCode = '';
    public $pullVendNo = '';

    // ─── Tab 4: Roles ──────────────────────────────────────────
    public $selectedRoles = [];

    public function mount()
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        if (!$user->hasAnyRole(['admin', 'superadmin'])) {
            return redirect('/admin/dashboard');
        }
    }

    public function with()
    {
        $query = User::with([
            'roles',
            'profile',
            'accurateCustomers.businessUnit',
            'accurateVendors.businessUnit',
        ]);

        if (!empty(trim((string) $this->search))) {
            $term = trim((string) $this->search);
            $cleanId = ltrim($term, '#');
            $query->where(function ($q) use ($term, $cleanId) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhere('email', 'like', '%' . $term . '%')
                    ->orWhere('identity', 'like', '%' . $term . '%')
                    ->orWhereHas('profile', function ($pq) use ($term) {
                        $pq->where('phone_number', 'like', '%' . $term . '%')
                           ->orWhere('full_name', 'like', '%' . $term . '%');
                    })
                    ->orWhereHas('accurateCustomers', function ($cq) use ($term) {
                        $cq->where('accurate_customer_no', 'like', '%' . $term . '%')
                           ->orWhere('accurate_customer_id', 'like', '%' . $term . '%');
                    })
                    ->orWhereHas('accurateVendors', function ($vq) use ($term) {
                        $vq->where('accurate_vendor_no', 'like', '%' . $term . '%')
                           ->orWhere('accurate_vendor_id', 'like', '%' . $term . '%');
                    });

                if (is_numeric($cleanId)) {
                    $q->orWhere('id', (int) $cleanId);
                }
            });
        }

        if (!empty($this->filterRole)) {
            if ($this->filterRole === '__no_role__') {
                $query->doesntHave('roles');
            } else {
                $query->role($this->filterRole);
            }
        }

        if ($this->filterAccurateStatus === 'unmapped_customer') {
            $query->doesntHave('accurateCustomers');
        } elseif ($this->filterAccurateStatus === 'mapped_customer') {
            $query->has('accurateCustomers');
        } elseif ($this->filterAccurateStatus === 'mapped_vendor') {
            $query->has('accurateVendors');
        }

        $editingUser = $this->editingUserId
            ? User::with([
                'roles',
                'profile',
                'accurateCustomers.businessUnit',
                'accurateVendors.businessUnit',
                'bankAccounts',
            ])->find($this->editingUserId)
            : null;

        return [
            'users' => $query->orderByDesc('id')->paginate(15),
            'availableRoles' => Role::all(),
            'businessUnits' => BusinessUnit::orderBy('id')->get(),
            'editingUser' => $editingUser,
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterRole()
    {
        $this->resetPage();
    }

    public function updatingFilterAccurateStatus()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterRole = '';
        $this->filterAccurateStatus = '';
        $this->resetPage();
    }

    // ─── Buka / Tutup Modal ────────────────────────────────────
    public function editUser($userId, $tab = 'profile')
    {
        $user = User::with(['roles', 'profile', 'accurateCustomers', 'accurateVendors', 'bankAccounts'])->findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->activeTab = $tab;

        // Tab 1: Profil
        $this->editName = $user->name ?? '';
        $this->editEmail = $user->email ?? '';
        $this->editPhone = $user->profile->phone_number ?? '';
        $this->editIdentity = $user->identity ?? '';
        $this->editNpwp = $user->npwp ?? '';
        $this->editDomisili = $user->profile->domisili ?? '';
        $this->editPassword = '';
        $this->syncProfileToAccurate = false;

        // Tab 2 & 3: Reset form mapping
        $firstBu = BusinessUnit::orderBy('id')->first();
        $this->manualCustBuId = $firstBu?->id ?? '';
        $this->manualCustAccurateId = '';
        $this->manualCustAccurateNo = '';
        $this->pullCustBuCode = $firstBu?->code ?? 'syihab';
        $this->pullCustNo = '';

        $this->manualVendBuId = $firstBu?->id ?? '';
        $this->manualVendAccurateId = '';
        $this->manualVendAccurateNo = '';
        $this->pullVendBuCode = $firstBu?->code ?? 'second';
        $this->pullVendNo = '';

        // Tab 4: Roles
        $this->selectedRoles = $user->roles->pluck('name')->toArray();

        $this->resetValidation();
        $this->isEditModalOpen = true;
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetValidation();
    }

    public function closeModal()
    {
        $this->isEditModalOpen = false;
        $this->editingUserId = null;
        $this->editPassword = '';
        $this->resetValidation();
    }

    // ─── TAB 1: Simpan Profil & Akun ───────────────────────────
    public function saveProfile()
    {
        if (!$this->editingUserId) return;
        $user = User::with(['profile', 'accurateCustomers.businessUnit'])->findOrFail($this->editingUserId);

        $this->editEmail = strtolower(trim((string) $this->editEmail));
        $this->editName = trim((string) $this->editName);
        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $this->editPhone);

        $this->validate([
            'editName' => 'required|string|min:2|max:255',
            'editEmail' => 'required|email:rfc,filter|unique:users,email,' . $user->id,
            'editPhone' => 'nullable|string|max:25',
            'editIdentity' => 'nullable|string|max:50',
            'editNpwp' => 'nullable|string|max:50',
            'editDomisili' => 'nullable|string|max:500',
            'editPassword' => 'nullable|string|min:6|max:100',
        ], [
            'editName.required' => 'Nama wajib diisi.',
            'editEmail.required' => 'Email wajib diisi.',
            'editEmail.email' => 'Format email tidak valid (wajib menggunakan domain lengkap, contoh: nama@gmail.com).',
            'editEmail.unique' => 'Email sudah dipakai pengguna lain.',
            'editPassword.min' => 'Password baru minimal 6 karakter.',
        ]);

        $updateData = [
            'name' => $this->editName,
            'email' => $this->editEmail,
            'identity' => trim((string) $this->editIdentity) ?: null,
            'npwp' => trim((string) $this->editNpwp) ?: null,
        ];

        if (!empty($this->editPassword)) {
            $updateData['password'] = Hash::make($this->editPassword);
        }

        $user->update($updateData);

        UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'full_name' => $this->editName,
                'phone_number' => $cleanPhone ?: null,
                'domisili' => trim((string) $this->editDomisili) ?: null,
            ]
        );

        $syncNote = '';
        if ($this->syncProfileToAccurate && $user->accurateCustomers->isNotEmpty()) {
            $accurateService = app(AccurateService::class);
            $user->refresh()->load('profile');
            $syncedBus = [];
            $failedBus = [];
            foreach ($user->accurateCustomers as $mapping) {
                $buCode = $mapping->businessUnit?->code;
                if (!$buCode) continue;
                try {
                    $accurateService->updateCustomer($user, $buCode);
                    $syncedBus[] = strtoupper($buCode);
                } catch (\Throwable $e) {
                    $failedBus[] = strtoupper($buCode) . ' (' . $e->getMessage() . ')';
                }
            }
            if (!empty($syncedBus)) {
                $syncNote .= ' | Update Accurate: ' . implode(', ', $syncedBus);
            }
            if (!empty($failedBus)) {
                $syncNote .= ' | Gagal Accurate: ' . implode('; ', $failedBus);
            }
        }

        $this->editPassword = '';
        $this->dispatch('admin-alert', type: 'success', message: 'Profil pengguna berhasil diperbarui!' . $syncNote);
    }

    // ─── TAB 2: Accurate Customer ──────────────────────────────
    public function editManualCustomerMapping($mappingId)
    {
        $mapping = UserAccurateCustomer::find($mappingId);
        if (!$mapping) return;

        $this->manualCustBuId = $mapping->business_unit_id;
        $this->manualCustAccurateId = $mapping->accurate_customer_id;
        $this->manualCustAccurateNo = $mapping->accurate_customer_no;
    }

    public function saveManualCustomerMapping()
    {
        if (!$this->editingUserId) return;

        $this->validate([
            'manualCustBuId' => 'required|exists:business_units,id',
            'manualCustAccurateId' => 'required|string|max:100',
            'manualCustAccurateNo' => 'required|string|max:100',
        ], [
            'manualCustBuId.required' => 'Pilih Business Unit terlebih dahulu.',
            'manualCustAccurateId.required' => 'Accurate Customer ID wajib diisi.',
            'manualCustAccurateNo.required' => 'Accurate Customer No wajib diisi.',
        ]);

        UserAccurateCustomer::updateOrCreate(
            [
                'user_id' => $this->editingUserId,
                'business_unit_id' => $this->manualCustBuId,
            ],
            [
                'accurate_customer_id' => trim((string) $this->manualCustAccurateId),
                'accurate_customer_no' => trim((string) $this->manualCustAccurateNo),
            ]
        );

        $this->manualCustAccurateId = '';
        $this->manualCustAccurateNo = '';
        $this->dispatch('admin-alert', type: 'success', message: 'Mapping Accurate Customer berhasil disimpan!');
    }

    public function deleteCustomerMapping($mappingId)
    {
        $mapping = UserAccurateCustomer::where('id', $mappingId)
            ->where('user_id', $this->editingUserId)
            ->first();

        if ($mapping) {
            $mapping->delete();
            $this->dispatch('admin-alert', type: 'success', message: 'Mapping Accurate Customer dihapus.');
        }
    }

    public function autoSyncCustomer($buCode)
    {
        if (!$this->editingUserId) return;
        $user = User::with(['profile', 'addresses'])->findOrFail($this->editingUserId);

        try {
            $accurateService = app(AccurateService::class);
            $accurateService->syncCustomer($user, $buCode);
            $this->dispatch('admin-alert', type: 'success', message: "Customer berhasil disinkronkan / ditautkan ke Accurate (" . strtoupper($buCode) . ")!");
        } catch (\Throwable $e) {
            $this->dispatch('admin-alert', type: 'error', message: "Gagal Sync Accurate (" . strtoupper($buCode) . "): " . $e->getMessage());
        }
    }

    public function pullCustomCustomer()
    {
        if (!$this->editingUserId) return;

        $this->validate([
            'pullCustBuCode' => 'required|string',
            'pullCustNo' => 'required|string|min:2|max:100',
        ], [
            'pullCustBuCode.required' => 'Pilih Business Unit.',
            'pullCustNo.required' => 'Masukkan Nomor Pelanggan yang ada di Accurate.',
        ]);

        $user = User::findOrFail($this->editingUserId);

        try {
            $accurateService = app(AccurateService::class);
            $mapping = $accurateService->pullAndLinkCustomerByNo($user, $this->pullCustNo, $this->pullCustBuCode);
            $this->pullCustNo = '';
            $this->dispatch('admin-alert', type: 'success', message: "Berhasil menarik & menautkan {$mapping->accurate_customer_no} (ID: {$mapping->accurate_customer_id})!");
        } catch (\Throwable $e) {
            $this->dispatch('admin-alert', type: 'error', message: 'Gagal tarik customer: ' . $e->getMessage());
        }
    }

    // ─── TAB 3: Accurate Vendor ────────────────────────────────
    public function editManualVendorMapping($mappingId)
    {
        $mapping = UserAccurateVendor::find($mappingId);
        if (!$mapping) return;

        $this->manualVendBuId = $mapping->business_unit_id;
        $this->manualVendAccurateId = $mapping->accurate_vendor_id;
        $this->manualVendAccurateNo = $mapping->accurate_vendor_no;
    }

    public function saveManualVendorMapping()
    {
        if (!$this->editingUserId) return;

        $this->validate([
            'manualVendBuId' => 'required|exists:business_units,id',
            'manualVendAccurateId' => 'required|string|max:100',
            'manualVendAccurateNo' => 'required|string|max:100',
        ], [
            'manualVendBuId.required' => 'Pilih Business Unit terlebih dahulu.',
            'manualVendAccurateId.required' => 'Accurate Vendor ID wajib diisi.',
            'manualVendAccurateNo.required' => 'Accurate Vendor No wajib diisi.',
        ]);

        UserAccurateVendor::updateOrCreate(
            [
                'user_id' => $this->editingUserId,
                'business_unit_id' => $this->manualVendBuId,
            ],
            [
                'accurate_vendor_id' => trim((string) $this->manualVendAccurateId),
                'accurate_vendor_no' => trim((string) $this->manualVendAccurateNo),
            ]
        );

        $this->manualVendAccurateId = '';
        $this->manualVendAccurateNo = '';
        $this->dispatch('admin-alert', type: 'success', message: 'Mapping Accurate Vendor berhasil disimpan!');
    }

    public function deleteVendorMapping($mappingId)
    {
        $mapping = UserAccurateVendor::where('id', $mappingId)
            ->where('user_id', $this->editingUserId)
            ->first();

        if ($mapping) {
            $mapping->delete();
            $this->dispatch('admin-alert', type: 'success', message: 'Mapping Accurate Vendor dihapus.');
        }
    }

    public function autoSyncVendor($buCode)
    {
        if (!$this->editingUserId) return;
        $user = User::with(['profile', 'addresses'])->findOrFail($this->editingUserId);

        try {
            $accurateService = app(AccurateService::class);
            $accurateService->syncVendor($user, $buCode);
            $this->dispatch('admin-alert', type: 'success', message: "Vendor berhasil disinkronkan / ditautkan ke Accurate (" . strtoupper($buCode) . ")!");
        } catch (\Throwable $e) {
            $this->dispatch('admin-alert', type: 'error', message: "Gagal Sync Vendor (" . strtoupper($buCode) . "): " . $e->getMessage());
        }
    }

    public function pullCustomVendor()
    {
        if (!$this->editingUserId) return;

        $this->validate([
            'pullVendBuCode' => 'required|string',
            'pullVendNo' => 'required|string|min:2|max:100',
        ], [
            'pullVendBuCode.required' => 'Pilih Business Unit.',
            'pullVendNo.required' => 'Masukkan Nomor Pemasok yang ada di Accurate.',
        ]);

        $user = User::findOrFail($this->editingUserId);

        try {
            $accurateService = app(AccurateService::class);
            $mapping = $accurateService->pullAndLinkVendorByNo($user, $this->pullVendNo, $this->pullVendBuCode);
            $this->pullVendNo = '';
            $this->dispatch('admin-alert', type: 'success', message: "Berhasil menarik & menautkan Vendor {$mapping->accurate_vendor_no} (ID: {$mapping->accurate_vendor_id})!");
        } catch (\Throwable $e) {
            $this->dispatch('admin-alert', type: 'error', message: 'Gagal tarik vendor: ' . $e->getMessage());
        }
    }

    // ─── TAB 4: Role & Rekening Bank ───────────────────────────
    public function saveRoles()
    {
        if (!$this->editingUserId) return;
        $user = User::findOrFail($this->editingUserId);

        if (auth()->id() === $user->id && !in_array('admin', $this->selectedRoles) && !in_array('superadmin', $this->selectedRoles)) {
            $this->dispatch('admin-alert', type: 'error', message: 'Anda tidak bisa menghapus role admin dari akun Anda sendiri.');
            return;
        }

        $user->syncRoles($this->selectedRoles);
        $this->dispatch('admin-alert', type: 'success', message: 'Role dan Permission berhasil diperbarui!');
    }

    public function setPrimaryBank($bankId)
    {
        if (!$this->editingUserId) return;
        UserBankAccount::where('user_id', $this->editingUserId)->update(['is_primary' => false]);
        UserBankAccount::where('id', $bankId)->where('user_id', $this->editingUserId)->update(['is_primary' => true]);
        $this->dispatch('admin-alert', type: 'success', message: 'Rekening utama berhasil diperbarui.');
    }

    public function deleteBankAccount($bankId)
    {
        if (!$this->editingUserId) return;
        UserBankAccount::where('id', $bankId)->where('user_id', $this->editingUserId)->delete();
        $this->dispatch('admin-alert', type: 'success', message: 'Rekening bank berhasil dihapus.');
    }
};
?>

<div class="px-6 py-8 w-full max-w-7xl mx-auto" x-data="{ alert: null }"
    @admin-alert.window="
        alert = $event.detail;
        setTimeout(() => alert = null, 5000);
    ">

    <!-- Alpine Notification Setup -->
    <div x-show="alert" x-transition.opacity.duration.300ms style="display: none;"
        class="fixed top-5 right-5 z-[120] max-w-md px-4 py-3 rounded-2xl border flex items-center gap-3 text-sm font-medium shadow-xl transition-all"
        :class="alert?.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'">
        <svg x-show="alert?.type === 'success'" class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <svg x-show="alert?.type === 'error'" class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span x-text="alert?.message" class="leading-snug"></span>
        <button @click="alert = null" class="ml-auto text-gray-400 hover:text-gray-600 cursor-pointer">&times;</button>
    </div>

    <!-- Header -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manajemen Pengguna & Customer</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola profil customer, role akses, rekening bank, serta mapping Accurate Customer & Vendor.</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
            <div class="md:col-span-5">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama, email, No. HP, #ID, atau No. Accurate..."
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all">
            </div>
            <div class="md:col-span-3">
                <select wire:model.live="filterAccurateStatus"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB]">
                    <option value="">Semua Status Accurate</option>
                    <option value="unmapped_customer">Belum Mapping Customer Accurate</option>
                    <option value="mapped_customer">Sudah Mapping Customer Accurate</option>
                    <option value="mapped_vendor">Punya Mapping Vendor Accurate</option>
                </select>
            </div>
            <div class="md:col-span-3">
                <select wire:model.live="filterRole"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB]">
                    <option value="">Semua Role</option>
                    <option value="__no_role__">Tanpa Role (User Biasa)</option>
                    @foreach($availableRoles as $r)
                        <option value="{{ $r->name }}">{{ ucfirst($r->name) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-1">
                <button type="button" wire:click="resetFilters"
                    class="w-full py-2.5 px-3 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition cursor-pointer">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50/70 text-gray-500 font-semibold border-b border-gray-100 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-4">Pengguna</th>
                        <th class="px-5 py-4">Kontak (Email & HP)</th>
                        <th class="px-5 py-4">Relasi Accurate</th>
                        <th class="px-5 py-4">Role</th>
                        <th class="px-5 py-4">Daftar</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50/60 transition-colors" wire:key="user-{{ $user->id }}">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-linear-to-br from-[#4E44DB] to-[#766bf2] text-white flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 leading-tight">{{ $user->name }}</div>
                                        <div class="text-[11px] text-gray-400 font-mono mt-0.5">
                                            ID #{{ $user->id }}
                                            @if($user->identity)
                                                • NIK: {{ $user->identity }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-xs font-medium text-gray-800">{{ $user->email }}</div>
                                <div class="text-[11px] text-gray-500 font-mono mt-0.5">
                                    {{ $user->profile?->phone_number ?: 'Belum ada No. HP' }}
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-col gap-1.5">
                                    {{-- Accurate Customers --}}
                                    <div class="flex flex-wrap items-center gap-1">
                                        @forelse($user->accurateCustomers as $ac)
                                            <button type="button" wire:click="editUser({{ $user->id }}, 'accurate_customer')"
                                                title="ID: {{ $ac->accurate_customer_id }} | No: {{ $ac->accurate_customer_no }}"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 cursor-pointer">
                                                <span>C:{{ strtoupper($ac->businessUnit->code ?? 'BU') }}</span>
                                                <span class="font-mono font-normal text-blue-600">{{ $ac->accurate_customer_no }}</span>
                                            </button>
                                        @empty
                                            <button type="button" wire:click="editUser({{ $user->id }}, 'accurate_customer')"
                                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 cursor-pointer">
                                                Belum Mapping Customer
                                            </button>
                                        @endforelse
                                    </div>
                                    {{-- Accurate Vendors --}}
                                    @if($user->accurateVendors->isNotEmpty())
                                        <div class="flex flex-wrap items-center gap-1">
                                            @foreach($user->accurateVendors as $av)
                                                <button type="button" wire:click="editUser({{ $user->id }}, 'accurate_vendor')"
                                                    title="Vendor ID: {{ $av->accurate_vendor_id }} | No: {{ $av->accurate_vendor_no }}"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 hover:bg-purple-100 cursor-pointer">
                                                    <span>V:{{ strtoupper($av->businessUnit->code ?? 'BU') }}</span>
                                                    <span class="font-mono font-normal text-purple-600">{{ $av->accurate_vendor_no }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold
                                            {{ in_array($role->name, ['admin', 'superadmin']) ? 'bg-purple-100 text-purple-700' : 'bg-emerald-100 text-emerald-700' }}">
                                            {{ ucfirst($role->name) }}
                                        </span>
                                    @empty
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-gray-100 text-gray-500">
                                            Customer
                                        </span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-4 text-xs text-gray-500 whitespace-nowrap">
                                {{ $user->created_at?->format('d M Y') }}
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <button wire:click="editUser({{ $user->id }}, 'profile')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-[#4E44DB] bg-[#eff2ff] hover:bg-[#4E44DB] hover:text-white rounded-lg transition-colors cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    Edit / Kelola
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center text-gray-400">
                                <p>Tidak ada pengguna yang ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Modal Manajemen Pengguna (Multi-Tab) -->
    @if($isEditModalOpen && $editingUser)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs transition-opacity" wire:click="closeModal"></div>

            <!-- Modal Box -->
            <div class="relative bg-white rounded-3xl w-full max-w-3xl shadow-2xl overflow-hidden my-auto max-h-[90vh] flex flex-col">
                <!-- Modal Header -->
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/60 shrink-0">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-linear-to-br from-[#4E44DB] to-[#766bf2] text-white flex items-center justify-center font-bold text-sm shrink-0">
                            {{ strtoupper(substr($editingUser->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-gray-900">{{ $editingUser->name }}</h3>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-gray-200/80 text-gray-700">ID #{{ $editingUser->id }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $editingUser->email }} • {{ $editingUser->profile?->phone_number ?: 'Belum ada No. HP' }}
                            </p>
                        </div>
                    </div>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-700 transition-colors p-2 bg-white rounded-xl hover:bg-gray-100 border border-gray-200 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Navigation Tabs -->
                <div class="px-6 pt-3 border-b border-gray-100 bg-white flex gap-1 overflow-x-auto shrink-0">
                    <button type="button" wire:click="setTab('profile')"
                        class="px-4 py-2.5 text-xs font-bold border-b-2 transition-colors whitespace-nowrap cursor-pointer {{ $activeTab === 'profile' ? 'border-[#4E44DB] text-[#4E44DB]' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                        Profil & Akun
                    </button>
                    <button type="button" wire:click="setTab('accurate_customer')"
                        class="px-4 py-2.5 text-xs font-bold border-b-2 transition-colors whitespace-nowrap flex items-center gap-1.5 cursor-pointer {{ $activeTab === 'accurate_customer' ? 'border-[#4E44DB] text-[#4E44DB]' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                        Accurate Customer
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $editingUser->accurateCustomers->count() > 0 ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $editingUser->accurateCustomers->count() }}
                        </span>
                    </button>
                    <button type="button" wire:click="setTab('accurate_vendor')"
                        class="px-4 py-2.5 text-xs font-bold border-b-2 transition-colors whitespace-nowrap flex items-center gap-1.5 cursor-pointer {{ $activeTab === 'accurate_vendor' ? 'border-[#4E44DB] text-[#4E44DB]' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                        Accurate Vendor
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-purple-100 text-purple-700">
                            {{ $editingUser->accurateVendors->count() }}
                        </span>
                    </button>
                    <button type="button" wire:click="setTab('roles_bank')"
                        class="px-4 py-2.5 text-xs font-bold border-b-2 transition-colors whitespace-nowrap flex items-center gap-1.5 cursor-pointer {{ $activeTab === 'roles_bank' ? 'border-[#4E44DB] text-[#4E44DB]' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
                        Role & Bank
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-600">
                            {{ $editingUser->bankAccounts->count() }}
                        </span>
                    </button>
                </div>

                <!-- Tab Contents (Scrollable) -->
                <div class="p-6 overflow-y-auto flex-1 space-y-6">

                    {{-- ═══════════════════════════════════════════════
                         TAB 1: PROFIL & AKUN
                    ═══════════════════════════════════════════════ --}}
                    @if($activeTab === 'profile')
                        <form wire:submit="saveProfile" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Nama Lengkap <span class="text-rose-500">*</span></label>
                                    <input type="text" wire:model="editName" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] outline-none">
                                    @error('editName') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Email <span class="text-rose-500">*</span></label>
                                    <input type="email" wire:model="editEmail" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] outline-none">
                                    @error('editEmail') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">No. HP / WhatsApp</label>
                                    <input type="text" wire:model="editPhone" placeholder="08xxxxxxxxxx" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-mono focus:bg-white focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] outline-none">
                                    @error('editPhone') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">NIK / Identitas</label>
                                    <input type="text" wire:model="editIdentity" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-mono focus:bg-white focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">NPWP</label>
                                    <input type="text" wire:model="editNpwp" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-mono focus:bg-white focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Reset Password Baru <span class="text-gray-400 font-normal">(Opsional)</span></label>
                                    <input type="password" wire:model="editPassword" placeholder="Kosongkan jika tidak diubah" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] outline-none">
                                    @error('editPassword') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Domisili / Alamat Singkat</label>
                                <textarea wire:model="editDomisili" rows="2" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:bg-white focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] outline-none"></textarea>
                            </div>

                            @if($editingUser->accurateCustomers->isNotEmpty())
                                <label class="flex items-center gap-2.5 p-3 rounded-xl bg-blue-50/70 border border-blue-100 cursor-pointer">
                                    <input type="checkbox" wire:model="syncProfileToAccurate" class="w-4 h-4 text-[#4E44DB] rounded border-gray-300">
                                    <span class="text-xs font-semibold text-blue-900">Sekalian perbarui Nama, Email & No. HP ke Accurate Online (untuk semua BU yang terhubung)</span>
                                </label>
                            @endif

                            <div class="pt-2 flex justify-end">
                                <button type="submit" wire:loading.attr="disabled"
                                    class="px-5 py-2.5 text-xs font-bold text-white bg-[#4E44DB] hover:bg-[#3c34af] rounded-xl shadow-sm transition cursor-pointer disabled:opacity-50">
                                    <span wire:loading.remove wire:target="saveProfile">Simpan Profil</span>
                                    <span wire:loading wire:target="saveProfile">Menyimpan...</span>
                                </button>
                            </div>
                        </form>
                    @endif

                    {{-- ═══════════════════════════════════════════════
                         TAB 2: ACCURATE CUSTOMER
                    ═══════════════════════════════════════════════ --}}
                    @if($activeTab === 'accurate_customer')
                        {{-- Daftar Mapping Saat Ini --}}
                        <div>
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2.5">Mapping `user_accurate_customers` Saat Ini</h4>
                            <div class="border border-gray-200 rounded-2xl overflow-hidden">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-gray-50 text-gray-500 border-b border-gray-200">
                                        <tr>
                                            <th class="px-4 py-2.5">Business Unit</th>
                                            <th class="px-4 py-2.5">Accurate Customer ID</th>
                                            <th class="px-4 py-2.5">Accurate Customer No</th>
                                            <th class="px-4 py-2.5 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse($editingUser->accurateCustomers as $ac)
                                            <tr>
                                                <td class="px-4 py-2.5 font-bold text-gray-800">{{ $ac->businessUnit->name ?? 'BU #' . $ac->business_unit_id }}</td>
                                                <td class="px-4 py-2.5 font-mono text-gray-700">{{ $ac->accurate_customer_id }}</td>
                                                <td class="px-4 py-2.5 font-mono font-bold text-blue-700">{{ $ac->accurate_customer_no }}</td>
                                                <td class="px-4 py-2.5 text-right space-x-2">
                                                    <button type="button" wire:click="editManualCustomerMapping({{ $ac->id }})" class="text-blue-600 hover:underline font-semibold cursor-pointer">Edit</button>
                                                    <button type="button" wire:click="deleteCustomerMapping({{ $ac->id }})" wire:confirm="Hapus mapping customer Accurate ini?" class="text-rose-600 hover:underline font-semibold cursor-pointer">Hapus</button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-4 py-6 text-center text-gray-400">Belum ada mapping Accurate Customer.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Aksi Cepat 1-Klik Auto Sync / Self-Healing per BU --}}
                        <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-100">
                            <h4 class="text-xs font-bold text-blue-900 uppercase tracking-wider mb-1">1. Auto Sync / Tarik Standar dari Accurate</h4>
                            <p class="text-[11px] text-blue-700 mb-3">Mencoba mendaftarkan ke Accurate dengan format standar (<span class="font-mono">PREFIX_CUSTOMER_{{ str_pad($editingUser->id, 5, '0', STR_PAD_LEFT) }}</span>). Jika sudah ada di Accurate, otomatis menarik ID-nya dan menyimpannya ke database.</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($businessUnits as $bu)
                                    <button type="button" wire:click="autoSyncCustomer('{{ $bu->code }}')" wire:loading.attr="disabled"
                                        class="px-3.5 py-2 text-xs font-bold bg-white hover:bg-blue-600 text-blue-700 hover:text-white border border-blue-200 rounded-xl transition shadow-2xs cursor-pointer disabled:opacity-50">
                                        Sync / Tarik {{ $bu->name }} ({{ strtoupper($bu->code) }})
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Tarik berdasarkan Nomor Custom di Accurate --}}
                        <form wire:submit="pullCustomCustomer" class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-100 space-y-3">
                            <div>
                                <h4 class="text-xs font-bold text-emerald-900 uppercase tracking-wider">2. Tarik dari Accurate Berdasarkan Nomor Custom</h4>
                                <p class="text-[11px] text-emerald-700 mt-0.5">Gunakan ini jika di Accurate pelanggan sudah terdaftar dengan nomor lain (misal <span class="font-mono">C.00123</span>) agar ID-nya otomatis ditarik.</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-2.5">
                                <div class="md:col-span-4">
                                    <select wire:model="pullCustBuCode" class="w-full bg-white border border-emerald-200 rounded-xl px-3 py-2 text-xs font-semibold">
                                        @foreach($businessUnits as $bu)
                                            <option value="{{ $bu->code }}">{{ $bu->name }} ({{ strtoupper($bu->code) }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="md:col-span-5">
                                    <input type="text" wire:model="pullCustNo" placeholder="Nomor Pelanggan di Accurate..." class="w-full bg-white border border-emerald-200 rounded-xl px-3 py-2 text-xs font-mono">
                                    @error('pullCustNo') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="md:col-span-3">
                                    <button type="submit" wire:loading.attr="disabled" class="w-full py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition cursor-pointer disabled:opacity-50">
                                        Tarik & Tautkan
                                    </button>
                                </div>
                            </div>
                        </form>

                        {{-- Form Tambah / Edit Manual (Pengganti phpMyAdmin) --}}
                        <form wire:submit="saveManualCustomerMapping" class="p-4 rounded-2xl bg-gray-50 border border-gray-200 space-y-3">
                            <div>
                                <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider">3. Input / Edit Manual Mapping (Pengganti phpMyAdmin)</h4>
                                <p class="text-[11px] text-gray-500 mt-0.5">Simpan langsung ke tabel <span class="font-mono">user_accurate_customers</span> tanpa memanggil API Accurate.</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-2.5">
                                <div class="md:col-span-4">
                                    <select wire:model="manualCustBuId" class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-semibold">
                                        <option value="">-- Pilih BU --</option>
                                        @foreach($businessUnits as $bu)
                                            <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('manualCustBuId') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="md:col-span-3">
                                    <input type="text" wire:model="manualCustAccurateId" placeholder="Accurate ID (angka)" class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-mono">
                                    @error('manualCustAccurateId') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="md:col-span-3">
                                    <input type="text" wire:model="manualCustAccurateNo" placeholder="Customer No" class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-mono">
                                    @error('manualCustAccurateNo') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <button type="submit" class="w-full py-2 px-3 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-xl transition cursor-pointer">
                                        Simpan
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif

                    {{-- ═══════════════════════════════════════════════
                         TAB 3: ACCURATE VENDOR
                    ═══════════════════════════════════════════════ --}}
                    @if($activeTab === 'accurate_vendor')
                        <div>
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2.5">Mapping `user_accurate_vendors` Saat Ini</h4>
                            <div class="border border-gray-200 rounded-2xl overflow-hidden">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-gray-50 text-gray-500 border-b border-gray-200">
                                        <tr>
                                            <th class="px-4 py-2.5">Business Unit</th>
                                            <th class="px-4 py-2.5">Accurate Vendor ID</th>
                                            <th class="px-4 py-2.5">Accurate Vendor No</th>
                                            <th class="px-4 py-2.5 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse($editingUser->accurateVendors as $av)
                                            <tr>
                                                <td class="px-4 py-2.5 font-bold text-gray-800">{{ $av->businessUnit->name ?? 'BU #' . $av->business_unit_id }}</td>
                                                <td class="px-4 py-2.5 font-mono text-gray-700">{{ $av->accurate_vendor_id }}</td>
                                                <td class="px-4 py-2.5 font-mono font-bold text-purple-700">{{ $av->accurate_vendor_no }}</td>
                                                <td class="px-4 py-2.5 text-right space-x-2">
                                                    <button type="button" wire:click="editManualVendorMapping({{ $av->id }})" class="text-blue-600 hover:underline font-semibold cursor-pointer">Edit</button>
                                                    <button type="button" wire:click="deleteVendorMapping({{ $av->id }})" wire:confirm="Hapus mapping vendor Accurate ini?" class="text-rose-600 hover:underline font-semibold cursor-pointer">Hapus</button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-4 py-6 text-center text-gray-400">Belum ada mapping Accurate Vendor.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl bg-purple-50/60 border border-purple-100">
                            <h4 class="text-xs font-bold text-purple-900 uppercase tracking-wider mb-1">1. Auto Sync / Tarik Vendor Standar</h4>
                            <p class="text-[11px] text-purple-700 mb-3">Untuk kebutuhan SellPhone / Trade-In. Otomatis mendaftarkan atau menarik ID vendor yang sudah ada di Accurate.</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($businessUnits as $bu)
                                    <button type="button" wire:click="autoSyncVendor('{{ $bu->code }}')" wire:loading.attr="disabled"
                                        class="px-3.5 py-2 text-xs font-bold bg-white hover:bg-purple-600 text-purple-700 hover:text-white border border-purple-200 rounded-xl transition shadow-2xs cursor-pointer disabled:opacity-50">
                                        Sync Vendor {{ $bu->name }} ({{ strtoupper($bu->code) }})
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <form wire:submit="pullCustomVendor" class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-100 space-y-3">
                            <div>
                                <h4 class="text-xs font-bold text-emerald-900 uppercase tracking-wider">2. Tarik Vendor dari Accurate Berdasarkan Nomor Custom</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-2.5">
                                <div class="md:col-span-4">
                                    <select wire:model="pullVendBuCode" class="w-full bg-white border border-emerald-200 rounded-xl px-3 py-2 text-xs font-semibold">
                                        @foreach($businessUnits as $bu)
                                            <option value="{{ $bu->code }}">{{ $bu->name }} ({{ strtoupper($bu->code) }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="md:col-span-5">
                                    <input type="text" wire:model="pullVendNo" placeholder="Nomor Pemasok di Accurate..." class="w-full bg-white border border-emerald-200 rounded-xl px-3 py-2 text-xs font-mono">
                                    @error('pullVendNo') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="md:col-span-3">
                                    <button type="submit" wire:loading.attr="disabled" class="w-full py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition cursor-pointer disabled:opacity-50">
                                        Tarik & Tautkan
                                    </button>
                                </div>
                            </div>
                        </form>

                        <form wire:submit="saveManualVendorMapping" class="p-4 rounded-2xl bg-gray-50 border border-gray-200 space-y-3">
                            <div>
                                <h4 class="text-xs font-bold text-gray-800 uppercase tracking-wider">3. Input / Edit Manual Mapping Vendor</h4>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-2.5">
                                <div class="md:col-span-4">
                                    <select wire:model="manualVendBuId" class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-semibold">
                                        <option value="">-- Pilih BU --</option>
                                        @foreach($businessUnits as $bu)
                                            <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('manualVendBuId') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="md:col-span-3">
                                    <input type="text" wire:model="manualVendAccurateId" placeholder="Accurate Vendor ID" class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-mono">
                                    @error('manualVendAccurateId') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="md:col-span-3">
                                    <input type="text" wire:model="manualVendAccurateNo" placeholder="Vendor No" class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-mono">
                                    @error('manualVendAccurateNo') <p class="text-[11px] text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <button type="submit" class="w-full py-2 px-3 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-xl transition cursor-pointer">
                                        Simpan
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif

                    {{-- ═══════════════════════════════════════════════
                         TAB 4: ROLE & REKENING BANK
                    ═══════════════════════════════════════════════ --}}
                    @if($activeTab === 'roles_bank')
                        <form wire:submit="saveRoles" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider">Kelola Role (Multi-select)</h4>
                                <button type="submit" class="px-4 py-1.5 text-xs font-bold text-white bg-[#4E44DB] hover:bg-[#3c34af] rounded-xl transition cursor-pointer">
                                    Simpan Role
                                </button>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                @foreach($availableRoles as $role)
                                    <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-gray-200 hover:bg-gray-50 hover:border-[#4E44DB] cursor-pointer transition">
                                        <input type="checkbox" wire:model="selectedRoles" value="{{ $role->name }}"
                                            class="w-4 h-4 text-[#4E44DB] border-gray-300 rounded cursor-pointer">
                                        <span class="text-xs font-semibold text-gray-800 capitalize">{{ $role->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </form>

                        <div class="border-t border-gray-100 pt-4">
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2.5">Rekening Bank Terdaftar (`user_bank_accounts`)</h4>
                            <div class="border border-gray-200 rounded-2xl overflow-hidden">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-gray-50 text-gray-500 border-b border-gray-200">
                                        <tr>
                                            <th class="px-4 py-2.5">Bank</th>
                                            <th class="px-4 py-2.5">No. Rekening</th>
                                            <th class="px-4 py-2.5">Atas Nama</th>
                                            <th class="px-4 py-2.5 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse($editingUser->bankAccounts as $bank)
                                            <tr>
                                                <td class="px-4 py-2.5 font-bold text-gray-800">
                                                    {{ $bank->bank_name }}
                                                    @if($bank->is_primary)
                                                        <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded bg-emerald-100 text-emerald-700 font-bold">Utama</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2.5 font-mono text-gray-700">{{ $bank->account_number }}</td>
                                                <td class="px-4 py-2.5 text-gray-700">{{ $bank->account_name }}</td>
                                                <td class="px-4 py-2.5 text-right space-x-2">
                                                    @if(!$bank->is_primary)
                                                        <button type="button" wire:click="setPrimaryBank({{ $bank->id }})" class="text-blue-600 hover:underline font-semibold cursor-pointer">Set Utama</button>
                                                    @endif
                                                    <button type="button" wire:click="deleteBankAccount({{ $bank->id }})" wire:confirm="Hapus rekening bank ini?" class="text-rose-600 hover:underline font-semibold cursor-pointer">Hapus</button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-4 py-5 text-center text-gray-400">Belum ada rekening bank terdaftar.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3.5 border-t border-gray-100 bg-gray-50 flex justify-end shrink-0">
                    <button type="button" wire:click="closeModal" class="px-5 py-2 text-xs font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-100 transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
