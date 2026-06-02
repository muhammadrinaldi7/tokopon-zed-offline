<div class="max-w-7xl mx-auto p-2  md:p-6 min-h-screen">
    <div class="mb-6">
        <h2 class="text-2xl font-black text-gray-800">Cek Ketersediaan Stok</h2>
        <p class="text-gray-500 text-sm">Cari produk berdasarkan Nama atau SKU untuk melihat ketersediaan stok di seluruh
            gudang.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Kolom Kiri: Pencarian Produk --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Pencarian Produk
            </h3>

            <div class="relative mb-4">
                <input type="text" wire:model.live.debounce.300ms="searchQuery"
                    class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-blue-500 focus:border-blue-500 text-sm transition"
                    placeholder="Ketik Nama Produk atau SKU (min. 2 karakter)...">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            @if (strlen($searchQuery) >= 2)
                @if (count($searchResults) > 0)
                    <div class="border border-gray-100 rounded-xl overflow-hidden divide-y divide-gray-100 bg-white">
                        @foreach ($searchResults as $result)
                            <div wire:click="selectProduct({{ $result['id'] }}, '{{ $result['type'] }}')"
                                class="p-4 hover:bg-blue-50 cursor-pointer transition flex justify-between items-center group">
                                <div>
                                    <h4 class="font-bold text-gray-900 group-hover:text-blue-700 transition">
                                        {{ $result['name'] }}
                                        @if ($result['is_second'])
                                            <span
                                                class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">Second</span>
                                        @else
                                            <span
                                                class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">Baru</span>
                                        @endif
                                    </h4>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ !empty($result['ram']) ? $result['ram'] . ' / ' . $result['storage'] : $result['storage'] }} - {{ $result['color'] }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-mono bg-gray-100 text-gray-600 px-2 py-1 rounded">SKU:
                                        {{ $result['sku'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center p-8 bg-gray-50 rounded-xl border border-gray-100 border-dashed">
                        <p class="text-gray-500 text-sm">Tidak ditemukan produk dengan kata kunci tersebut.</p>
                    </div>
                @endif
            @elseif(strlen($searchQuery) > 0)
                <p class="text-xs text-gray-400 mt-2">Ketik minimal 2 karakter untuk mencari...</p>
            @endif
        </div>

        {{-- Kolom Kanan: Hasil Stok --}}
        <div>
            @if ($selectedProduct)
                <div class="bg-white rounded-xl shadow-sm border border-blue-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white relative overflow-hidden">
                        <div class="relative z-10 flex justify-between items-start">
                            <div>
                                <p class="text-blue-100 text-xs uppercase tracking-wider font-bold mb-1">Informasi Stok
                                </p>
                                <h3 class="text-xl font-bold leading-tight">{{ $selectedProduct }}</h3>
                            </div>
                            <button wire:click="resetCheck"
                                class="p-2 bg-white/20 hover:bg-white/30 rounded-lg backdrop-blur-sm transition"
                                title="Tutup Pencarian">
                                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <svg class="absolute -right-6 -bottom-6 w-32 h-32 text-white/10" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path
                                d="M20 7h-4V5c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zM10 5h4v2h-4V5zm10 14H4V9h16v10z" />
                            <path d="M12 12c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z" />
                        </svg>
                    </div>

                    <div class="p-6 bg-gray-50/50">
                        @if (count($stockData) > 0)
                            <ul class="space-y-3">
                                @foreach ($stockData as $data)
                                    <li
                                        class="flex justify-between items-center p-4 rounded-xl border transition-all shadow-sm
                                        {{ $data['is_current_user_warehouse'] ? 'bg-blue-50 border-blue-200 ring-1 ring-blue-500/20' : 'bg-white border-gray-100' }}">

                                        <div class="flex items-center gap-3">
                                            <div
                                                class="{{ $data['is_current_user_warehouse'] ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-400' }} p-2 rounded-lg">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                            </div>
                                            <div>
                                                <span
                                                    class="block text-sm font-bold {{ $data['is_current_user_warehouse'] ? 'text-blue-900' : 'text-gray-700' }}">
                                                    {{ $data['warehouse_name'] }}
                                                    @if ($data['is_current_user_warehouse'])
                                                        <span
                                                            class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-600 text-white uppercase tracking-wider">Lokasi
                                                            Anda</span>
                                                    @endif
                                                </span>
                                                <span class="block text-xs text-gray-500 mt-0.5">Ketersediaan Stok
                                                    Fisik</span>
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            @if ($data['stock'] > 0)
                                                <span
                                                    class="text-lg font-black {{ $data['is_current_user_warehouse'] ? 'text-blue-600' : 'text-emerald-600' }}">{{ $data['stock'] }}</span>
                                                <span class="text-xs text-gray-500 ml-1 font-bold">Unit</span>
                                            @else
                                                <span
                                                    class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-rose-50 text-rose-600 border border-rose-100">Kosong</span>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center py-10 px-6">
                                <div
                                    class="bg-amber-100 text-amber-600 p-3 rounded-full w-14 h-14 mx-auto mb-4 flex items-center justify-center">
                                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <h4 class="text-gray-900 font-bold mb-1">Stok Belum Diatur</h4>
                                <p class="text-gray-500 text-sm">Varian produk ini belum memiliki data ketersediaan stok
                                    di seluruh gudang.</p>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div
                    class="h-full min-h-[300px] border-2 border-dashed border-gray-200 rounded-xl flex flex-col items-center justify-center text-center p-8 bg-gray-50/50">
                    <div class="bg-gray-100 text-gray-400 p-4 rounded-full mb-4">
                        <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <h4 class="text-gray-500 font-bold mb-2">Belum Ada Produk Dipilih</h4>
                    <p class="text-gray-400 text-sm max-w-xs">Gunakan kotak pencarian di sebelah kiri untuk mencari
                        produk, lalu klik salah satu hasilnya untuk melihat ketersediaan stok di seluruh cabang.</p>
                </div>
            @endif
        </div>
    </div>
</div>
