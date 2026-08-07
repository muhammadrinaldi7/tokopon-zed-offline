<div>
    {{-- Header & Actions --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.orders.management') }}" wire:navigate
                    class="text-gray-400 hover:text-gray-700 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <h1 class="text-2xl font-extrabold text-gray-900">Kendala & Kesalahan Pesanan</h1>
            </div>
            <p class="text-gray-500 text-sm mt-1 ml-7">Pantau, tindak lanjuti, dan rekap seluruh catatan kendala transaksi pelanggan.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.orders.management') }}" wire:navigate
                class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition text-xs font-bold flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                Kelola Pesanan
            </a>

            <button wire:click="exportExcel" wire:loading.attr="disabled"
                class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition text-xs font-bold flex items-center gap-2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                <svg wire:loading.remove wire:target="exportExcel" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <svg wire:loading wire:target="exportExcel" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span>Export Excel (.xlsx)</span>
            </button>
        </div>
    </div>

    {{-- 4 Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Total Issues --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold">Total Kendala</p>
                <h3 class="text-xl font-extrabold text-gray-900 mt-0.5">{{ number_format($totalIssues, 0, ',', '.') }}</h3>
            </div>
        </div>

        {{-- Open Issues --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 relative">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                @if ($openIssues > 0)
                    <span class="absolute top-2 right-2 w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"></span>
                @endif
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold">Perlu Diselesaikan (OPEN)</p>
                <h3 class="text-xl font-extrabold text-rose-600 mt-0.5">{{ number_format($openIssues, 0, ',', '.') }}</h3>
            </div>
        </div>

        {{-- Resolved Issues --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="text-xs text-gray-500 font-semibold">Terselesaikan (RESOLVED)</p>
                <h3 class="text-xl font-extrabold text-emerald-600 mt-0.5">{{ number_format($resolvedIssues, 0, ',', '.') }}</h3>
            </div>
        </div>

        {{-- Top Issue Category --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
            </div>
            <div class="overflow-hidden">
                <p class="text-xs text-gray-500 font-semibold truncate">Kategori Terbanyak</p>
                <h3 class="text-sm font-extrabold text-purple-700 mt-0.5 truncate" title="{{ $topCategory }}">
                    {{ $topCategory }}
                </h3>
                <p class="text-[11px] text-gray-400 mt-0.5">({{ $topCategoryCount }} Catatan)</p>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            {{-- Search --}}
            <div class="lg:col-span-2 relative">
                <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari No. Order, Customer, Pelapor, Catatan..."
                    class="w-full pl-10 pr-4 py-2 bg-gray-50 border-gray-200 rounded-xl text-xs focus:ring-[#1c69d4]/20 focus:border-[#1c69d4]">
            </div>

            {{-- Status Filter --}}
            <div>
                <select wire:model.live="statusFilter"
                    class="w-full px-3 py-2 bg-gray-50 border-gray-200 rounded-xl text-xs focus:ring-[#1c69d4]/20 focus:border-[#1c69d4]">
                    <option value="">Semua Status</option>
                    <option value="OPEN">Belum Selesai (OPEN)</option>
                    <option value="RESOLVED">Terselesaikan (RESOLVED)</option>
                </select>
            </div>

            {{-- Category Filter --}}
            <div>
                <select wire:model.live="categoryFilter"
                    class="w-full px-3 py-2 bg-gray-50 border-gray-200 rounded-xl text-xs focus:ring-[#1c69d4]/20 focus:border-[#1c69d4]">
                    <option value="">Semua Kategori</option>
                    <option value="SALAH_METODE_BAYAR">Salah Metode Bayar</option>
                    <option value="SALAH_DISKON">Salah Input Diskon</option>
                    <option value="SALAH_ITEM">Salah Input Item</option>
                </select>
            </div>

            {{-- Reset Filter Button --}}
            <div class="flex items-center">
                <button type="button" wire:click="resetFilter"
                    class="w-full px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Reset Filter</span>
                </button>
            </div>

            {{-- Date From --}}
            <div class="sm:col-span-1">
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Dari Tanggal:</label>
                <input type="date" wire:model.live="dateFrom"
                    class="w-full px-3 py-1.5 bg-gray-50 border-gray-200 rounded-xl text-xs focus:ring-[#1c69d4]/20 focus:border-[#1c69d4]">
            </div>

            {{-- Date To --}}
            <div class="sm:col-span-1">
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Sampai Tanggal:</label>
                <input type="date" wire:model.live="dateTo"
                    class="w-full px-3 py-1.5 bg-gray-50 border-gray-200 rounded-xl text-xs focus:ring-[#1c69d4]/20 focus:border-[#1c69d4]">
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-4 font-bold">Waktu & Order</th>
                        <th class="px-6 py-4 font-bold">Pelanggan & Lokasi</th>
                        <th class="px-6 py-4 font-bold">Pelapor & Kategori</th>
                        <th class="px-6 py-4 font-bold">Rincian Catatan</th>
                        <th class="px-6 py-4 font-bold text-center">Status</th>
                        <th class="px-6 py-4 font-bold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 align-top">
                    @forelse ($issues as $issue)
                        @php
                            $isResolved = $issue->status === 'RESOLVED';
                            $order = $issue->order;
                            $categoryInfo = [
                                'SALAH_METODE_BAYAR' => ['label' => 'Salah Metode Bayar', 'class' => 'bg-rose-50 text-rose-700 border-rose-200'],
                                'SALAH_DISKON' => ['label' => 'Salah Input Diskon', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
                                'SALAH_ITEM' => ['label' => 'Salah Input Item', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
                            ];
                            $cat = $categoryInfo[$issue->category] ?? ['label' => $issue->category, 'class' => 'bg-gray-100 text-gray-700 border-gray-200'];
                        @endphp
                        <tr class="hover:bg-gray-50/60 transition-colors {{ $isResolved ? 'opacity-85 bg-gray-50/30' : '' }}">
                            {{-- Waktu & Order --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-gray-900 text-sm">
                                        #{{ $order->order_number ?? 'Order Terhapus' }}
                                    </span>
                                    @if ($order)
                                        <button wire:click="viewReceipt({{ $order->id }})"
                                            class="text-gray-400 hover:text-[#1c69d4] transition"
                                            title="Lihat Struk Pesanan">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $issue->created_at->format('d M Y, H:i') }}
                                </p>
                                <p class="text-[11px] text-gray-400 font-medium">
                                    ({{ $issue->created_at->diffForHumans() }})
                                </p>
                            </td>

                            {{-- Pelanggan & Lokasi --}}
                            <td class="px-6 py-4">
                                <p class="font-bold text-gray-800 text-sm">
                                    {{ $order->user->name ?? 'User Terhapus' }}
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $order->user->profile->phone_number ?? '-' }}
                                </p>
                                <p class="text-[11px] font-semibold text-gray-400 mt-1">
                                    Toko: {{ $order->branch->name ?? ($order->shipping_address_snapshot['store'] ?? '-') }}
                                </p>
                            </td>

                            {{-- Pelapor & Kategori --}}
                            <td class="px-6 py-4">
                                <span class="inline-block text-[11px] font-bold px-2.5 py-0.5 rounded-md border {{ $cat['class'] }}">
                                    {{ $cat['label'] }}
                                </span>
                                <p class="text-xs text-gray-600 font-medium mt-1.5 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ $issue->user->name ?? 'User Terhapus' }}
                                </p>
                            </td>

                            {{-- Rincian Catatan --}}
                            <td class="px-6 py-4 max-w-md">
                                <div class="text-xs text-gray-800 leading-relaxed whitespace-pre-line bg-gray-50/80 p-2.5 rounded-xl border border-gray-100">
                                    {{ $issue->comment }}
                                </div>
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4 text-center">
                                @if ($isResolved)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-extrabold px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        SELESAI
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[11px] font-extrabold px-3 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 animate-pulse">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        OPEN
                                    </span>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    @if ($isResolved)
                                        <button wire:click="toggleStatus({{ $issue->id }})"
                                            class="text-xs font-bold text-gray-500 hover:text-gray-800 hover:bg-gray-100 px-2.5 py-1.5 rounded-lg transition"
                                            title="Buka kembali kendala ini">
                                            Buka Lagi
                                        </button>
                                    @else
                                        <button wire:click="toggleStatus({{ $issue->id }})"
                                            class="text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-3 py-1.5 rounded-lg transition shadow-2xs"
                                            title="Tandai kendala ini sudah selesai">
                                            ✓ Selesaikan
                                        </button>
                                    @endif

                                    @if (Auth::user()->hasRole('admin'))
                                        <button wire:click="deleteIssue({{ $issue->id }})"
                                            wire:confirm="Yakin ingin menghapus catatan kendala ini?"
                                            class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                            title="Hapus Catatan">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-gray-500 font-medium">Tidak ada catatan kendala yang sesuai dengan filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($issues->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                {{ $issues->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL: Receipt (Struk) Khusus View Admin --}}
    @include('livewire.zoffline.pos.modal.riwayat-receipt')
</div>
