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
                        {{-- @php
                            dd($searchResults);
                        @endphp --}}
                        @foreach ($searchResults as $result)
                            @php
                                $isSelected =
                                    $selectedProductId == $result['id'] && $selectedProductType == $result['type'];
                            @endphp
                            <div wire:click="selectProduct({{ $result['id'] }}, '{{ $result['type'] }}')"
                                class="p-4 cursor-pointer transition flex justify-between items-center group {{ $isSelected ? 'bg-blue-100 border-l-4 border-blue-600' : 'hover:bg-blue-50' }}">
                                <div>
                                    <h4 class="font-bold text-gray-900 group-hover:text-blue-700 transition">
                                        {{ $result['name'] }}
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 uppercase">
                                            {{ $result['business_unit_name'] ?? 'Unknown' }}
                                        </span>
                                    </h4>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ !empty($result['ram']) ? $result['ram'] . ' / ' . $result['storage'] : $result['storage'] }}
                                        - {{ $result['color'] }} {{ $result['price'] }} Stock Jual :
                                        {{ $result['allStock'] }}
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
                                    Global</p>
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
                                    {{-- Modifikasi: Diubah menjadi flex-col agar SN bisa ditaruh di bawahnya --}}
                                    <li
                                        class="flex flex-col p-4 rounded-xl border transition-all shadow-sm
                                {{ $data['is_current_user_warehouse'] ? 'bg-blue-50 border-blue-200 ring-1 ring-blue-500/20' : 'bg-white border-gray-100' }}">

                                        {{-- Baris Utama: Informasi Gudang dan Jumlah Unit --}}
                                        <div class="flex justify-between items-center w-full">
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
                                        </div>

                                        {{-- GANTI DARI SINI: List Serial Number per Gudang --}}
                                        @if (!empty($data['sns']) && count($data['sns']) > 0)
                                            <div
                                                class="mt-3 pt-3 border-t border-dashed {{ $data['is_current_user_warehouse'] ? 'border-blue-200' : 'border-gray-100' }} flex justify-end">
                                                <button
                                                    wire:click="openSnModal('{{ $data['warehouse_name'] }}', {{ json_encode($data['sns']) }})"
                                                    type="button"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg transition shadow-sm border 
            {{ $data['is_current_user_warehouse']
                ? 'bg-blue-600 text-white hover:bg-blue-700 border-blue-600'
                : 'bg-white text-gray-700 hover:bg-gray-50 border-gray-200' }}">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    Cek SN
                                                </button>
                                            </div>
                                        @endif

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
                                <p class="text-gray-500 text-sm">Varian produk ini belum memiliki data ketersediaan
                                    stok
                                    di seluruh gudang.</p>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                {{-- View Empty State default bawaan anda --}}
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
    {{-- MODAL KHUSUS SERIAL NUMBER --}}
    @if ($showSnModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto outline-none focus:outline-none">

            {{-- Backdrop Efek Blur --}}
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" wire:click="closeSnModal">
            </div>

            {{-- Konten Modal --}}
            <div
                class="relative w-full max-w-md mx-auto bg-white rounded-2xl shadow-xl border border-gray-100 z-10 overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-150">

                {{-- Header Modal --}}
                <div class="flex items-center justify-between p-4 border-b border-gray-100 bg-gray-50">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Daftar Serial Number</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Gudang: <span
                                class="font-semibold text-blue-600">{{ $modalWarehouseName }}</span></p>
                    </div>
                    <button wire:click="closeSnModal" type="button"
                        class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body Modal (List SN) --}}
                <div class="p-5 max-h-[300px] overflow-y-auto space-y-2">
                    @foreach ($modalSns as $index => $snData)
                        @php
                            $sn = is_array($snData) ? $snData['serial_number'] : $snData;
                            $hpp = is_array($snData) ? $snData['hpp'] : 0;
                            $vendor = is_array($snData) ? $snData['vendor_name'] : 'Tidak ada';
                        @endphp
                        <div
                            class="flex items-center justify-between p-3 bg-gray-50 border border-gray-200/60 rounded-xl font-mono text-sm text-gray-700 hover:bg-blue-50/40 hover:border-blue-200 transition group">
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="text-xs text-gray-400 font-sans font-medium">{{ $index + 1 }}.</span>
                                    <span class="font-semibold tracking-wide">{{ $sn }}</span>
                                </div>
                                @if (is_array($snData))
                                    @can('view_modal_vendor')
                                        <div class="flex flex-col gap-1.5 text-xs text-gray-500 font-sans pl-5 mt-1">
                                            <span class="flex items-center gap-1.5" title="Vendor">
                                                <svg class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                </svg>
                                                <span class="truncate">{{ $vendor }}</span>
                                            </span>
                                            <span class="flex items-center gap-1.5" title="HPP">
                                                <svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ \App\Utils\Format::rupiah($hpp) }}
                                            </span>
                                        </div>
                                    @endcan
                                @endif
                            </div>

                            {{-- Tombol Klik Otomatis Salin SN --}}
                            <button type="button"
                                onclick="navigator.clipboard.writeText('{{ $sn }}'); alert('SN {{ $sn }} berhasil disalin!')"
                                class="p-1 text-gray-400 hover:text-blue-600 rounded-md hover:bg-white border border-transparent hover:border-gray-200 transition shadow-none hover:shadow-sm self-start"
                                title="Salin Nomor Seri">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>

                {{-- Footer Modal --}}
                <div class="p-4 border-t border-gray-100 bg-gray-50 flex justify-end">
                    <button wire:click="closeSnModal" type="button"
                        class="px-4 py-2 text-xs font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition shadow-sm">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
