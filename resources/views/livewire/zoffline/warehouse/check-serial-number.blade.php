<div class=" flex flex-col items-center justify-center p-6">
    <div class="max-w-7xl mx-auto w-full">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Lacak Lokasi Serial Number</h1>
            <p class="text-gray-500 text-sm mt-1">Pindai atau ketik IMEI/SN untuk melihat lokasi gudang dan status
                barang.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Panel Pencarian -->
            <div class="md:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6">
                        <form wire:submit="search">
                            <div class="mb-4 text-center">
                                <div
                                    class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-50 text-blue-600 mb-3">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z">
                                        </path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-800">Scan Barcode SN</h3>
                                <p class="text-xs text-gray-500 mt-1">Arahkan scanner ke kardus atau ketik manual</p>
                            </div>

                            <div class="mb-4">
                                <input type="text" wire:model="keyword" id="keyword" autofocus
                                    class="w-full text-center text-lg tracking-wider font-mono px-4 py-3 border @error('keyword') border-red-500 @else border-gray-300 @enderror rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                                    placeholder="Cth: 354421... / Tembak Scanner" autocomplete="off">
                                @error('keyword')
                                    <span class="text-xs text-red-500 mt-1 block text-center">{{ $message }}</span>
                                @enderror
                            </div>

                            <button type="submit"
                                class="w-full bg-neutral-800 hover:-translate-y-1 hover:shadow-md transition-all duration-300 text-white font-medium py-3 px-4 rounded-lg transition-colors flex justify-center items-center gap-2">
                                <svg wire:loading wire:target="search"
                                    class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Cari Lokasi SN
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Panel Hasil -->
            <div class="md:col-span-2">
                @if ($hasSearched)
                    @if ($result)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden h-full">
                            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Serial Number Ditemukan
                                </h3>

                                @php
                                    $statusColors = [
                                        'Available' => 'bg-green-100 text-green-800 border-green-200',
                                        'Sold' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'Reserved' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                        'Unavailable' => 'bg-red-100 text-red-800 border-red-200',
                                    ];
                                    $colorClass =
                                        $statusColors[$result->status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                @endphp
                                <span class="px-3 py-1 text-xs font-bold rounded-full border {{ $colorClass }}">
                                    {{ strtoupper($result->status) }}
                                </span>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <p class="text-sm text-gray-500 font-medium mb-1">Serial Number / IMEI</p>
                                        <p class="text-xl font-mono font-bold text-gray-900">
                                            {{ $result->serial_number }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-sm text-gray-500 font-medium mb-1">Nomor Item (SKU)</p>
                                        <p class="text-lg font-medium text-gray-800">{{ $result->item_no }}</p>
                                    </div>

                                    <div class="md:col-span-2">
                                        <p class="text-sm text-gray-500 font-medium mb-1">Nama Produk</p>
                                        @if ($result->variant && $result->variant->product)
                                            <p class="text-lg font-bold text-gray-900">
                                                {{ $result->variant->product->name }}
                                                @if ($result->variant->name && $result->variant->name !== 'Default')
                                                    - {{ $result->variant->name }}
                                                @endif
                                            </p>
                                        @else
                                            <p class="text-lg font-medium text-gray-500 italic">Nama produk tidak
                                                ditemukan
                                                di katalog lokal</p>
                                        @endif
                                    </div>

                                    <div
                                        class="bg-blue-50 rounded-lg p-4 md:col-span-2 border border-blue-100 flex items-start gap-3">
                                        <div class="mt-1">
                                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                                </path>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm text-blue-600 font-medium mb-1">Lokasi Gudang Fisik</p>
                                            <p class="text-xl font-bold text-gray-900">
                                                {{ $result->warehouse ? $result->warehouse->name : 'Gudang Tidak Diketahui' }}
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="md:col-span-2 flex items-center justify-between mt-2 pt-4 border-t border-gray-100">
                                        <p class="text-xs text-gray-400">
                                            Update terakhir: {{ $result->updated_at->format('d M Y H:i') }}
                                        </p>

                                        <a href="{{ route('admin.warehouse.sn-history', ['sn' => urlencode($result->serial_number)]) }}"
                                            wire:navigate
                                            class="px-5 py-2.5 bg-neutral-800 hover:bg-black text-white font-bold rounded-xl text-sm transition-colors flex items-center gap-2 shadow-sm">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Lihat Riwayat Perjalanan
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div
                            class="bg-white rounded-xl shadow-sm border border-red-200 overflow-hidden h-full flex flex-col items-center justify-center p-8 text-center">
                            <div
                                class="w-20 h-20 rounded-full bg-red-50 text-red-500 flex items-center justify-center mb-4">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                    </path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Serial Number Tidak Ditemukan</h3>
                            <p class="text-gray-500 max-w-md">
                                SN/IMEI yang Anda masukkan tidak terdaftar di database lokal.
                                Pastikan Anda sudah menekan "Sync Serial Number" jika ini adalah barang baru dari
                                Accurate.
                            </p>
                            <button type="button" onclick="document.getElementById('keyword').focus()"
                                class="mt-6 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors text-sm">
                                Coba Scan Lagi
                            </button>
                        </div>
                    @endif
                @else
                    <div
                        class="bg-gray-50 rounded-xl border border-dashed border-gray-300 h-full flex flex-col items-center justify-center p-8 text-center min-h-[300px]">
                        <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <p class="text-gray-500">Hasil pencarian akan muncul di sini</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
