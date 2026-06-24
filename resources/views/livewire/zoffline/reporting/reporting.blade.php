<div class="min-h-[calc(100vh-120px)] flex flex-col items-center justify-center w-full px-4 sm:px-6 lg:px-8 py-6">
    <div class="max-w-7xl mx-auto w-full mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Pusat Laporan</h1>
        <p class="text-gray-500">Lihat semua data dan laporan aktivitas toko</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 max-w-7xl mx-auto gap-6 w-full">
        <!-- Card 1: Penjualan -->
        <div wire:click="navigateToSales"
            class="w-full h-64 bg-white rounded-2xl relative flex flex-col justify-between overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 ease-out">
            <div class="rounded-full w-16 h-16 bg-blue-50 flex items-center justify-center text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-800">Laporan Penjualan</h2>
                <p class="text-neutral-500 text-sm mt-2 line-clamp-2">Rekapitulasi transaksi penjualan</p>
            </div>
        </div>

        <!-- Card 2: Stok -->
        <div wire:click="navigateToStock"
            class="w-full h-64 bg-white rounded-2xl relative flex flex-col justify-between overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 ease-out">
            <div class="rounded-full w-16 h-16 bg-green-50 flex items-center justify-center text-green-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-800">Laporan Stok</h2>
                <p class="text-neutral-500 text-sm mt-2 line-clamp-2">Ketersediaan barang secara umum</p>
            </div>
        </div>

        <!-- Card 3: Promo -->
        <div wire:click="navigateToPromo"
            class="w-full h-64 bg-white rounded-2xl relative flex flex-col justify-between overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 ease-out">
            <div class="rounded-full w-16 h-16 bg-red-50 flex items-center justify-center text-red-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-800">Laporan Promo</h2>
                <p class="text-neutral-500 text-sm mt-2 line-clamp-2">Efektivitas promo dan diskon</p>
            </div>
        </div>

        <!-- Card 4: Kinerja Produk -->
        <div wire:click="navigateToProducts"
            class="w-full h-64 bg-white rounded-2xl relative flex flex-col justify-between overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 ease-out">
            <div class="rounded-full w-16 h-16 bg-purple-50 flex items-center justify-center text-purple-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-800">Kinerja Produk</h2>
                <p class="text-neutral-500 text-sm mt-2 line-clamp-2">Analisis produk paling laku</p>
            </div>
        </div>

        <!-- Card 5: Stok by SN -->
        <div wire:click="navigateToLaporanStok"
            class="w-full h-64 bg-white rounded-2xl relative flex flex-col justify-between overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 ease-out">
            <div class="rounded-full w-16 h-16 bg-orange-50 flex items-center justify-center text-orange-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-800">Stok by SN</h2>
                <p class="text-neutral-500 text-sm mt-2 line-clamp-2">Detail stok per Serial Number</p>
            </div>
        </div>

        <!-- Card 6: Report Sales -->
        <div wire:click="navigateToStaff"
            class="w-full h-64 bg-white rounded-2xl relative flex flex-col justify-between overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 ease-out">
            <div class="rounded-full w-16 h-16 bg-indigo-50 flex items-center justify-center text-indigo-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-800">Report Sales</h2>
                <p class="text-neutral-500 text-sm mt-2 line-clamp-2">Kinerja penjualan per Staff/Sales</p>
            </div>
        </div>
    </div>
</div>
