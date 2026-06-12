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
            <h1 class="text-2xl font-bold text-gray-900">Master Karyawan Accurate</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola kaitan akun login POS dan pantau sinkronisasi staf dari
                database Accurate Online.</p>
        </div>
        <div class="flex items-center gap-3">
            <select wire:model.live="filterBusinessUnitId"
                class="w-full md:w-auto bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all appearance-none cursor-pointer">
                <option value="">Semua Unit Usaha</option>
                @foreach ($businessUnits as $bu)
                    <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                @endforeach
            </select>

            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama, nik, atau jabatan..."
                class="w-full md:w-72 bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all">

            <select wire:model="syncBusinessUnitId"
                class="w-full md:w-auto bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all appearance-none cursor-pointer">
                <option value="">Semua Unit Usaha</option>
                @foreach ($businessUnits as $bu)
                    <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                @endforeach
            </select>

            <button wire:click="syncEmployees" wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-[#4E44DB] text-white px-4 py-2.5 rounded-xl text-sm font-bold hover:bg-[#3c34af] transition-all shadow-md shadow-[#4E44DB]/20 shrink-0 disabled:opacity-50">
                <svg wire:loading.class="animate-spin" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.253 8H18" />
                </svg>
                <span wire:loading.remove wire:target="syncEmployees">Sinkron Accurate</span>
                <span wire:loading wire:target="syncEmployees">Menyelaraskan...</span>
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50/50 text-gray-500 font-semibold border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Karyawan</th>
                        <th class="px-6 py-4">No. Karyawan (NIK)</th>
                        <th class="px-6 py-4">Unit Usaha</th>
                        <th class="px-6 py-4">Jabatan</th>
                        <th class="px-6 py-4">Status Kerja</th>
                        <th class="px-6 py-4">Akun Login POS Lokal</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($employeesList as $employee)
                        <tr class="hover:bg-gray-50/50 transition-colors" wire:key="emp-{{ $employee->id }}">
                            <td class="px-6 py-4 font-medium text-gray-900 flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-linear-to-br from-[#4E44DB] to-[#766bf2] text-white flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ strtoupper(substr($employee->name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $employee->name }}</p>
                                    <p class="text-xs text-gray-400 font-normal">
                                        {{ $employee->email ?? ($employee->phone_number ?? '-') }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-gray-700">{{ $employee->employee_no ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-md bg-gray-100 text-gray-700 text-xs font-medium">
                                    {{ $employee->businessUnit->name ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-md bg-gray-100 text-gray-700 text-xs font-medium">
                                    {{ $employee->position ?? 'Staff' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if ($employee->is_active)
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-700">
                                        Aktif
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold bg-red-100 text-red-700">
                                        Suspended (Resign)
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                @if ($employee->user)
                                    <div class="flex flex-col">
                                        <span
                                            class="text-sm font-semibold text-indigo-600">{{ $employee->user->name }}</span>
                                        <span class="text-[10px] text-gray-400">{{ $employee->user->email }}</span>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Belum punya hak login POS</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="editUser({{ $employee->id }})"
                                    class="inline-flex flex-row items-center justify-center gap-2 px-3 py-1.5 text-xs font-bold text-[#4E44DB] bg-[#eff2ff] hover:bg-[#4E44DB] hover:text-white rounded-lg transition-colors cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    Atur Akun POS
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <p>Tidak ada data karyawan ditemukan. Silahkan klik tombol sinkronisasi.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30">
            {{ $employeesList->links() }}
        </div>
    </div>

    <!-- Link User Account Modal (Reused Edit Role Modal) -->
    @if ($isEditModalOpen && $editingEmployee)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" wire:click="closeModal">
            </div>

            <!-- Modal Box -->
            <div
                class="relative bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Hubungkan Akun Login POS</h3>
                    <button wire:click="closeModal"
                        class="text-gray-400 hover:text-gray-600 transition-colors p-1 bg-gray-50 rounded-full hover:bg-gray-100 border border-gray-200 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="saveRoles">
                    <div class="p-6">
                        <div class="mb-5 p-4 bg-gray-50 rounded-2xl flex items-center gap-4 border border-gray-100">
                            <div
                                class="w-10 h-10 rounded-full bg-linear-to-br from-[#4E44DB] to-[#766bf2] text-white flex items-center justify-center font-bold text-sm shrink-0">
                                {{ strtoupper(substr($editingEmployee->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ $editingEmployee->name }}</p>
                                <p class="text-xs text-gray-500">{{ $editingEmployee->position ?? 'Karyawan Staff' }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="block text-sm font-semibold text-gray-700">Pilih Akun Login POS</label>

                            <select wire:model="selectedUserId"
                                class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all appearance-none cursor-pointer">
                                <option value="">-- Putus Hubungan / Bukan User Kasir --</option>
                                @foreach ($availableUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>

                            <p
                                class="text-xs text-indigo-600 mt-4 flex items-center gap-2 bg-indigo-50 p-3 rounded-xl border border-indigo-100/50">
                                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Menghubungkan karyawan dengan user login berguna agar sistem POS otomatis mencatat nama
                                salesman Accurate ini pada struk kasir penjualan.
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
                            Simpan Kaitan Akun
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
