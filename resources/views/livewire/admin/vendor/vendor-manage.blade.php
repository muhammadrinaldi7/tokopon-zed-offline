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
            <h1 class="text-2xl font-bold text-gray-900">Master Vendor Accurate</h1>
            <p class="text-sm text-gray-500 mt-1">Pantau dan sinkronisasi data vendor/pemasok dari database Accurate Online.</p>
        </div>
        <div class="flex items-center gap-3">
            <select wire:model.live="filterBusinessUnitId"
                class="w-full md:w-48 bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all cursor-pointer">
                <option value="">Semua Unit Usaha</option>
                @foreach ($businessUnits as $bu)
                    <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                @endforeach
            </select>
            
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama, kode, email, atau telepon..."
                class="w-full md:w-72 bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all">

            <button wire:click="syncVendors" wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-[#4E44DB] text-white px-4 py-2.5 rounded-xl text-sm font-bold hover:bg-[#3c34af] transition-all shadow-md shadow-[#4E44DB]/20 shrink-0 disabled:opacity-50">
                <svg wire:loading.class="animate-spin" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.253 8H18" />
                </svg>
                <span wire:loading.remove wire:target="syncVendors">Sinkron Accurate</span>
                <span wire:loading wire:target="syncVendors">Menyelaraskan...</span>
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50/50 text-gray-500 font-semibold border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Vendor / Pemasok</th>
                        <th class="px-6 py-4">No. Vendor</th>
                        <th class="px-6 py-4">ID Accurate</th>
                        <th class="px-6 py-4">Status Sinkronisasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($vendorsList as $vendor)
                        <tr class="hover:bg-gray-50/50 transition-colors" wire:key="vendor-{{ $vendor->id }}">
                            <td class="px-6 py-4 font-medium text-gray-900 flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-linear-to-br from-[#4E44DB] to-[#766bf2] text-white flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ strtoupper(substr($vendor->vendor_name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $vendor->vendor_name }}</p>
                                    <p class="text-xs text-gray-400 font-normal">
                                        {{ $vendor->email ?? ($vendor->phone ?? '-') }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-gray-700">
                                {{ $vendor->vendor_no ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-gray-700">
                                {{ $vendor->accurate_vendor_id ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4">
                                @if ($vendor->accurate_vendor_id)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Tersinkronisasi
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-100 text-amber-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Lokal Saja
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <p>Tidak ada data vendor ditemukan. Silahkan klik tombol sinkronisasi.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30">
            {{ $vendorsList->links() }}
        </div>
    </div>
</div>
