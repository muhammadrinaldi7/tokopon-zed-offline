<div class="min-h-screen bg-gray-50 p-4 md:p-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-black text-gray-900 tracking-tight">Daftar Aktivasi Garansi</h1>
                <p class="text-sm text-gray-500 mt-1">Pantau status QC dan Aktivasi Garansi per IMEI untuk seluruh
                    transaksi cabang Anda.</p>
            </div>
            <div>
                <a href="{{ route('zoffline.warranty-activation') }}" wire:navigate
                    class="px-5 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-sm hover:bg-blue-700 transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Aktivasi Baru
                </a>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <div class="flex flex-col gap-4">
                {{-- Baris 1: Pencarian & Status --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-3">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Cari Transaksi /
                            SN</label>
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.500ms="search"
                                class="w-full pl-10 pr-4 py-3 bg-gray-50 border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Ketik No Invoice, Nama, atau IMEI...">
                            <div class="absolute left-3 top-3.5 text-gray-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Status
                            Aktivasi</label>
                        <select wire:model.live="statusFilter"
                            class="w-full px-4 py-3 bg-gray-50 border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 font-medium">
                            <option value="all">Semua Status</option>
                            <option value="inactive">Belum Aktif (Butuh QC)</option>
                            <option value="active">Sudah Aktif</option>
                        </select>
                    </div>
                </div>

                {{-- Baris 2: Cabang & Tanggal --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-4 border-t border-gray-50">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Pilih Cabang (Branch)</label>
                        <select wire:model.live="branchFilter"
                            class="w-full px-4 py-3 bg-gray-50 border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 font-medium">
                            <option value="all">Semua Cabang</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Rentang
                            Waktu</label>
                        <div class="flex items-center gap-2">
                            <input type="date" wire:model.live="dateStart"
                                class="w-full px-3 py-3 bg-gray-50 border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 font-medium">
                            <span class="text-gray-400">-</span>
                            <input type="date" wire:model.live="dateEnd"
                                class="w-full px-3 py-3 bg-gray-50 border-gray-200 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500 font-medium">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead
                        class="bg-gray-50/50 border-b border-gray-100 text-xs uppercase font-bold text-gray-500 tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Tgl Pembelian</th>
                            <th class="px-6 py-4">Pelanggan & Invoice</th>
                            <th class="px-6 py-4">Barang (SN/IMEI)</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($paginatedItems as $item)
                            <tr class="hover:bg-blue-50/30 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500 font-medium">
                                    {{ \Carbon\Carbon::parse($item['order_date'])->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">{{ $item['customer_name'] }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $item['order_number'] }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-900">{{ $item['product_name'] }}</div>
                                    <div
                                        class="text-xs font-mono text-gray-500 mt-1 bg-gray-100 inline-block px-2 py-0.5 rounded">
                                        {{ $item['serial_number'] }}</div>
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    @if ($item['is_activated'])
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-200/50">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                            Aktif
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 text-rose-600 border border-rose-200/50">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Belum Aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    @if (!$item['is_activated'])
                                        <a href="{{ route('zoffline.warranty-activation', ['sn' => $item['serial_number']]) }}"
                                            wire:navigate
                                            class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-200 text-gray-700 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 text-xs font-bold rounded-xl transition-all shadow-sm">
                                            Aktivasi Sekarang
                                        </a>
                                    @else
                                        <button wire:click="viewQc({{ $item['inspection_id'] }})"
                                            class="inline-flex items-center justify-center px-4 py-2 bg-blue-50 border border-blue-200 text-blue-600 hover:bg-blue-600 hover:text-white text-xs font-bold rounded-xl transition-all shadow-sm gap-1.5">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Lihat QC
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div
                                        class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 text-gray-400 mb-4">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-900">Tidak ada data ditemukan</h3>
                                    <p class="text-sm text-gray-500 mt-1">Coba sesuaikan filter atau kata kunci
                                        pencarian Anda.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($paginatedItems->hasPages())
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $paginatedItems->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL QC DETAILS --}}
    @if ($showQcModal && $selectedInspection)
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="if($event.target === $el) $wire.closeQcModal()">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col" @click.stop>
            {{-- Header --}}
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-white z-10">
                <div>
                    <h3 class="text-xl font-black text-gray-900 tracking-tight">Detail QC Unboxing</h3>
                    <p class="text-sm text-gray-500 mt-1">IMEI: <span class="font-mono font-bold text-gray-800">{{ $selectedInspection->imei }}</span></p>
                </div>
                <button wire:click="closeQcModal" class="p-2 text-gray-400 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition-colors">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-6 overflow-y-auto bg-gray-50/50 flex-1">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {{-- Kiri: Foto --}}
                    <div class="space-y-4">
                        <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Foto Fisik & Kelengkapan
                        </h4>
                        
                        <div class="grid grid-cols-2 gap-4">
                            @php
                                $photos = $selectedInspection->getMedia('qc_photos');
                            @endphp
                            @forelse ($photos as $photo)
                                <div class="relative aspect-square rounded-2xl overflow-hidden border border-gray-200 shadow-sm bg-white group cursor-pointer" onclick="window.open('{{ $photo->getUrl() }}', '_blank')">
                                    <img src="{{ $photo->getUrl() }}" alt="{{ $photo->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-3 pt-8 pointer-events-none">
                                        <p class="text-white text-xs font-bold truncate">{{ $photo->name }}</p>
                                    </div>
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors flex items-center justify-center">
                                        <div class="bg-white/90 rounded-full p-2 opacity-0 group-hover:opacity-100 transition-opacity transform scale-75 group-hover:scale-100">
                                            <svg class="w-5 h-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-2 p-8 text-center bg-white rounded-2xl border border-gray-200 border-dashed">
                                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <p class="text-sm text-gray-500">Tidak ada foto terlampir</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Kanan: Checklist --}}
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Hasil Inspeksi
                            </h4>
                            <span class="px-3 py-1 bg-emerald-100 text-emerald-700 font-bold text-xs rounded-full">{{ $selectedInspection->passed_count }}/{{ $selectedInspection->total_items }} Lulus</span>
                        </div>
                        
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            @php
                                $results = collect($selectedInspection->checklist_results)->groupBy('category');
                            @endphp
                            <div class="max-h-[50vh] overflow-y-auto divide-y divide-gray-100">
                                @foreach($results as $category => $items)
                                    <div class="p-4">
                                        <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">{{ $category }}</h5>
                                        <div class="space-y-2.5">
                                            @foreach($items as $item)
                                                <div class="flex items-center justify-between">
                                                    <span class="text-sm font-medium text-gray-700">{{ $item['name'] }}</span>
                                                    @if(($item['type'] ?? 'boolean') === 'boolean')
                                                        @if(!empty($item['value']))
                                                            <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        @else
                                                            <svg class="w-5 h-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                        @endif
                                                    @else
                                                        <span class="text-sm font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded">{{ $item['value'] ?? '-' }}</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="bg-blue-50/50 rounded-2xl p-4 border border-blue-100">
                            <p class="text-xs text-gray-500 mb-1">Diinspeksi oleh:</p>
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-blue-200 flex items-center justify-center text-blue-700 font-bold text-sm">
                                    {{ substr($selectedInspection->inspector->name ?? '?', 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ $selectedInspection->inspector->name ?? 'Unknown' }}</p>
                                    <p class="text-xs text-gray-500">{{ $selectedInspection->created_at->format('d M Y H:i') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
