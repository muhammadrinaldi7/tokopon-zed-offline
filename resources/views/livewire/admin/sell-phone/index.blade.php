<div class="relative min-h-screen bg-gradient-to-br p-4 sm:p-8">
    {{-- Decorative Background Elements --}}
    <div
        class="absolute top-0 left-0 w-full h-96 bg-gradient-to-br from-blue-600/5 to-purple-600/5 blur-3xl pointer-events-none -z-10">
    </div>

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1
                class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-indigo-700 tracking-tight">
                Manajemen Jual HP (SellPhone)</h1>
            <p class="text-sm text-slate-500 mt-2 font-medium">Kelola penawaran beli HP bekas dari pelanggan secara
                terpusat.</p>
        </div>
    </div>

    <div
        class="bg-white/70 backdrop-blur-xl rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white/50 overflow-hidden transition-all duration-300">
        {{-- Filters --}}
        <div class="p-5 border-b border-slate-100/50 flex flex-col sm:flex-row gap-4 bg-white/40">
            <div class="relative flex-1 group">
                <div
                    class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors duration-300 group-focus-within:text-blue-600">
                    <svg class="w-5 h-5 text-slate-400 group-focus-within:text-blue-600 transition-colors" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari pelanggan atau tipe HP..."
                    class="w-full pl-11 pr-4 py-3 text-sm bg-white/60 border-slate-200/60 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-300 placeholder:text-slate-400">
            </div>
            <select wire:model.live="status"
                class="py-3 px-4 text-sm bg-white/60 border-slate-200/60 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-300 w-full sm:w-56 font-medium text-slate-700 cursor-pointer">
                <option value="">Semua Status</option>
                <option value="PENDING">Menunggu Taksiran</option>
                <option value="OFFERED">Penawaran Dikirim</option>
                <option value="WAITING_FOR_DEVICE">Menunggu Unit Dikirim</option>
                <option value="INSPECTING">Inspeksi Fisik</option>
                <option value="PAYING">Menunggu Pencairan</option>
                <option value="COMPLETED">Selesai</option>
                <option value="CANCELLED">Dibatalkan</option>
            </select>
            <select wire:model.live="status_inspeksi"
                class="py-3 px-4 text-sm bg-white/60 border-slate-200/60 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-300 w-full sm:w-56 font-medium text-slate-700 cursor-pointer">
                <option value="">Semua Status Inspeksi</option>
                <option value="pass">Lulus</option>
                <option value="conditional">Kondisional</option>
                <option value="fail">Gagal</option>
            </select>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-500 bg-slate-50/50 uppercase font-bold tracking-wider">
                    <tr>
                        <th class="px-6 py-5 border-b border-slate-100">ID & Tanggal</th>
                        <th class="px-6 py-5 border-b border-slate-100">Cabang & FL</th>
                        <th class="px-6 py-5 border-b border-slate-100">Pelanggan</th>
                        <th class="px-6 py-5 border-b border-slate-100">HP Ditawarkan</th>
                        <th class="px-6 py-5 border-b border-slate-100">Status & Taksiran</th>
                        <th class="px-6 py-5 border-b border-slate-100 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/60">
                    @forelse ($sellPhones as $item)
                        <tr class="hover:bg-blue-50/30 transition-colors duration-200 group">
                            <td class="px-6 py-5 whitespace-nowrap">
                                <span
                                    class="font-bold text-slate-900 bg-slate-100/80 px-2.5 py-1 rounded-md text-xs tracking-wide group-hover:bg-blue-100 group-hover:text-blue-700 transition-colors">#SPL-{{ $item->id }}</span>
                                <div class="text-xs text-slate-500 mt-2 font-medium flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $item->created_at->format('d M Y, H:i') }}
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="font-bold text-slate-800 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    {{ optional($item->businessUnit)->name ?? 'Global' }}
                                </div>
                                <div class="text-xs text-slate-500 mt-1 font-medium flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    FL: {{ optional($item->handledBy)->name ?? '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="font-bold text-slate-800">{{ optional($item->user)->name }}</div>
                            </td>
                            <td class="px-6 py-5">
                                <div
                                    class="font-bold text-blue-700 bg-blue-50/50 inline-block px-2.5 py-1 rounded-lg border border-blue-100/50">
                                    {{ $item->phone_brand }} {{ $item->phone_model }}</div>
                                <div
                                    class="text-[11px] text-slate-500 mt-2 font-medium bg-slate-50 inline-block px-2 py-0.5 rounded border border-slate-100">
                                    {{ $item->phone_ram ?: '-' }} RAM / {{ $item->phone_storage ?: '-' }} Storage
                                </div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'PENDING' => 'bg-amber-100 text-amber-800 border-amber-200',
                                        'OFFERED' => 'bg-sky-100 text-sky-800 border-sky-200',
                                        'WAITING_FOR_DEVICE' => 'bg-purple-100 text-purple-800 border-purple-200',
                                        'INSPECTING' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                        'PAYING' => 'bg-teal-100 text-teal-800 border-teal-200',
                                        'COMPLETED' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                        'CANCELLED' => 'bg-rose-100 text-rose-800 border-rose-200',
                                    ];
                                @endphp
                                <span
                                    class="px-3 py-1.5 text-[10px] font-black uppercase rounded-lg border tracking-wider {{ $statusColors[$item->status] ?? 'bg-slate-100 text-slate-800 border-slate-200' }} shadow-sm">
                                    {{ str_replace('_', ' ', $item->status) }}
                                </span>
                                @if ($item->appraised_value)
                                    <div class="text-sm font-black text-emerald-600 mt-2.5 flex items-center gap-1">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Rp {{ number_format($item->appraised_value, 0, ',', '.') }}
                                    </div>
                                @else
                                    <div class="text-xs text-slate-400 mt-2.5 italic">Belum ditaksir</div>
                                @endif
                            </td>
                            <td class="px-6 py-5 text-right">
                                <a href="{{ route('admin.sell-phones.show', $item) }}" wire:navigate
                                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-white text-blue-600 border border-blue-100 shadow-sm hover:shadow hover:bg-blue-50 font-bold text-xs rounded-xl transition-all duration-200 focus:ring-2 focus:ring-blue-500/20">
                                    {{ $item->status === 'PENDING' ? 'Taksir Sekarang' : 'Detail' }}
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div
                                        class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-10 h-10 text-slate-300" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <p class="text-base font-bold text-slate-700">Tidak ada pengajuan ditemukan</p>
                                    <p class="text-sm mt-1 text-slate-500 font-medium">Belum ada pelanggan yang
                                        mengajukan penjualan HP di cabang ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sellPhones->hasPages())
            <div class="p-5 border-t border-slate-100/50 bg-slate-50/30">
                {{ $sellPhones->links() }}
            </div>
        @endif
    </div>
</div>
