<div class="px-6 py-8 w-full max-w-7xl mx-auto" x-data="{ alert: null }"
    @admin-alert.window="
        alert = $event.detail;
        setTimeout(() => alert = null, 3000);
    ">

    <!-- Alpine Notification Setup -->
    <div x-show="alert" x-transition.opacity.duration.300ms style="display: none;"
        class="mb-6 px-4 py-3 rounded-xl border flex items-center gap-3 text-sm font-medium shadow-sm transition-all"
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
            <h1 class="text-2xl font-bold text-gray-900">Manajemen Pengguna</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola peran (Roles) dan akses (Permissions) untuk semua akun
                terdaftar.</p>
        </div>
        <div class="flex items-center gap-3">
            <select wire:model.live="filterBusinessUnitId"
                class="w-full md:w-auto bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all appearance-none cursor-pointer">
                <option value="">Semua Unit Usaha</option>
                @foreach ($businessUnits as $bu)
                    <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                @endforeach
            </select>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau email..."
                class="w-full md:w-72 bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all">
            <button wire:click="openCreateModal"
                class="flex items-center gap-2 bg-[#4E44DB] text-white px-4 py-2.5 rounded-xl text-sm font-bold hover:bg-[#3c34af] transition-all shadow-md shadow-[#4E44DB]/20 shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Staff
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50/50 text-gray-500 font-semibold border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Pengguna</th>
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4">Unit Usaha</th>
                        <th class="px-6 py-4">Role / Akses</th>
                        <th class="px-6 py-4">Branch/Warehouse</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50/50 transition-colors" wire:key="user-{{ $user->id }}">
                            <td class="px-6 py-4 font-medium text-gray-900 flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-linear-to-br from-[#4E44DB] to-[#766bf2] text-white flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                {{ $user->name }}
                            </td>
                            <td class="px-6 py-4">{{ $user->email }}</td>
                            <td class="px-6 py-4 text-gray-700 font-medium">
                                {{ $user->businessUnit->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($user->roles as $role)
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold 
                                            {{ in_array($role->name, ['admin', 'superadmin']) ? 'bg-purple-100 text-purple-700' : 'bg-emerald-100 text-emerald-700' }}">
                                            {{ ucfirst($role->name) }}
                                        </span>
                                    @empty
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                            User Biasa
                                        </span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ $user->branch->name ?? 'N/A' }} / {{ $user->warehouse->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 flex flex-col gap-2 text-right">
                                <button wire:click="editUser({{ $user->id }})"
                                    class="inline-flex flex-row items-center justify-center gap-2 px-3 py-1.5 text-xs font-bold text-[#4E44DB] bg-[#eff2ff] hover:bg-[#4E44DB] hover:text-white rounded-lg transition-colors cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    Kelola Role
                                </button>
                                <button wire:click="$dispatch('open-branch-modal', { userId: {{ $user->id }} })"
                                    class="inline-flex flex-row items-center justify-center gap-2 px-3 py-1.5 text-xs font-bold text-[#4E44DB] bg-[#eff2ff] hover:bg-[#4E44DB] hover:text-white rounded-lg transition-colors cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    Kelola Branch
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
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

    <!-- Edit Role Modal -->
    @if ($isEditModalOpen && $editingUser)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" wire:click="closeModal">
            </div>

            <!-- Modal Box -->
            <div
                class="relative bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Kelola Role Pengguna</h3>
                    <button wire:click="closeModal"
                        class="text-gray-400 hover:text-gray-600 transition-colors p-1 bg-gray-50 rounded-full hover:bg-gray-100 border border-gray-200 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="saveRoles">
                    <div class="p-6 max-h-[75vh] overflow-y-auto">
                        <div class="mb-5 p-4 bg-gray-50 rounded-2xl flex items-center gap-4 border border-gray-100">
                            <div
                                class="w-10 h-10 rounded-full bg-linear-to-br from-gray-400 to-gray-500 text-white flex items-center justify-center font-bold text-sm shrink-0">
                                {{ strtoupper(substr($editingUser->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ $editingUser->name }}</p>
                                <p class="text-xs text-gray-500">{{ $editingUser->email }}</p>
                            </div>
                        </div>

                        <div class="space-y-4 mb-6">
                            {{-- Unit Usaha --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Penugasan Unit Usaha <span class="text-rose-500">*</span></label>
                                <select wire:model.live="createBusinessUnitId"
                                    class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all appearance-none cursor-pointer">
                                    <option value="">-- Pilih Unit Usaha --</option>
                                    @foreach ($businessUnits as $bu)
                                        <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                {{-- Cabang / Branch --}}
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Cabang</label>
                                    <select wire:model="createBranchId"
                                        class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all appearance-none cursor-pointer">
                                        <option value="">-- Pilih Cabang --</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                {{-- Gudang / Warehouse --}}
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Gudang</label>
                                    <select wire:model="createWarehouseId"
                                        class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all appearance-none cursor-pointer">
                                        <option value="">-- Pilih Gudang --</option>
                                        @foreach ($warehouses as $warehouse)
                                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-1 border-t border-gray-100 pt-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Pilih Role
                                (Multi-select)</label>

                            @foreach ($availableRoles as $role)
                                <label
                                    class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:bg-gray-50 hover:border-[#4E44DB] cursor-pointer transition-colors mb-2 group">
                                    <input type="checkbox" wire:model="selectedRoles" value="{{ $role->name }}"
                                        class="w-5 h-5 text-[#4E44DB] border-gray-300 rounded focus:ring-[#4E44DB] focus:ring-opacity-20 cursor-pointer">
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-gray-800 capitalize group-hover:text-[#4E44DB] transition-colors">
                                            {{ $role->name }}</p>
                                        <p class="text-[10px] text-gray-500">Berikan akses fitur terkait panel atau
                                            tugas {{ $role->name }}.</p>
                                    </div>
                                </label>
                            @endforeach

                            <p
                                class="text-xs text-indigo-600 mt-4 flex items-center gap-2 bg-indigo-50 p-3 rounded-xl border border-indigo-100/50">
                                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Jangan centang apapun diatas jika pengguna adalah User Biasa (Pembeli standard).
                            </p>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 rounded-b-3xl">
                        <button type="button" wire:click="closeModal"
                            class="px-5 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:text-gray-900 transition-colors shadow-sm cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2.5 text-sm font-bold text-white bg-[#4E44DB] rounded-xl hover:bg-[#3c34af] shadow-md shadow-[#4E44DB]/20 transition-all flex items-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Create Staff Modal --}}
    @if ($isCreateModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" wire:click="closeCreateModal"></div>

            <div class="relative bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Tambah Staff Baru</h3>
                    <button wire:click="closeCreateModal"
                        class="text-gray-400 hover:text-gray-600 transition-colors p-1 bg-gray-50 rounded-full hover:bg-gray-100 border border-gray-200 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="storeUser">
                    <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                        {{-- Name --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Lengkap <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="createName" placeholder="Contoh: Ahmad Fauzi"
                                class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all"
                                required>
                            @error('createName')
                                <span class="text-xs text-rose-500 font-medium mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email <span class="text-rose-500">*</span></label>
                            <input type="email" wire:model="createEmail" placeholder="staff@tokopun.com"
                                class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all"
                                required>
                            @error('createEmail')
                                <span class="text-xs text-rose-500 font-medium mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password <span class="text-rose-500">*</span></label>
                                <input type="password" wire:model="createPassword" placeholder="Min. 8 karakter"
                                    class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all"
                                    required>
                                @error('createPassword')
                                    <span class="text-xs text-rose-500 font-medium mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Konfirmasi <span class="text-rose-500">*</span></label>
                                <input type="password" wire:model="createPasswordConfirmation" placeholder="Ulangi password"
                                    class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all"
                                    required>
                                @error('createPasswordConfirmation')
                                    <span class="text-xs text-rose-500 font-medium mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Location Assigments --}}
                        <div class="pt-2 border-t border-gray-100">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Penugasan Lokasi</label>
                            
                            <div class="space-y-3 mb-4">
                                {{-- Unit Usaha --}}
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Unit Usaha <span class="text-rose-500">*</span></label>
                                    <select wire:model.live="createBusinessUnitId" required
                                        class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all appearance-none cursor-pointer">
                                        <option value="">-- Pilih Unit Usaha --</option>
                                        @foreach ($businessUnits as $bu)
                                            <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    {{-- Cabang / Branch --}}
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Cabang</label>
                                        <select wire:model="createBranchId" required
                                            class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all appearance-none cursor-pointer">
                                            <option value="">-- Pilih Cabang --</option>
                                            @foreach ($branches as $branch)
                                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    {{-- Gudang / Warehouse --}}
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Gudang</label>
                                        <select wire:model="createWarehouseId" required
                                            class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all appearance-none cursor-pointer">
                                            <option value="">-- Pilih Gudang --</option>
                                            @foreach ($warehouses as $warehouse)
                                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Role Selection --}}
                        <div class="pt-4 border-t border-gray-100">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Assign Role <span class="text-rose-500">*</span></label>
                            @error('selectedCreateRoles')
                                <span class="text-xs text-rose-500 font-medium mb-2 block">{{ $message }}</span>
                            @enderror
                            <div class="space-y-2">
                                @foreach ($availableRoles as $role)
                                    @if (!in_array($role->name, ['customer', 'user']))
                                        <label
                                            class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:bg-gray-50 hover:border-[#4E44DB] cursor-pointer transition-colors group">
                                            <input type="checkbox" wire:model="selectedCreateRoles"
                                                value="{{ $role->name }}"
                                                class="w-5 h-5 text-[#4E44DB] border-gray-300 rounded focus:ring-[#4E44DB] focus:ring-opacity-20 cursor-pointer">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-800 capitalize group-hover:text-[#4E44DB] transition-colors">
                                                    {{ $role->name }}</p>
                                                <p class="text-[10px] text-gray-500">Akses fitur panel
                                                    {{ $role->name }}.</p>
                                            </div>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 rounded-b-3xl">
                        <button type="button" wire:click="closeCreateModal"
                            class="px-5 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:text-gray-900 transition-colors shadow-sm cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2.5 text-sm font-bold text-white bg-[#4E44DB] rounded-xl hover:bg-[#3c34af] shadow-md shadow-[#4E44DB]/20 transition-all flex items-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            Buat Akun Staff
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <livewire:admin.settings.warehouse.manage-branch-warehouse-modal />
</div>
