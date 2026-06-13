<div class="space-y-6">
    <div class="bg-gradient-to-br from-indigo-600 to-blue-700 rounded-2xl shadow-lg border border-indigo-500 overflow-hidden relative">
        <div class="absolute top-0 right-0 p-8 opacity-10">
            <svg class="w-32 h-32 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
            </svg>
        </div>
        
        <div class="p-8 relative z-10 text-white">
            <h2 class="text-3xl font-black mb-2 flex items-center gap-3">
                <span class="text-yellow-300">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </span>
                Upsell & Add-ons
            </h2>
            <p class="text-indigo-100 text-lg">Jangan lewatkan kesempatan! Tawarkan pelindung layar, casing, atau promo bundle kepada pelanggan.</p>
        </div>
    </div>

    {{-- Daftar Addons (Katalog Aksesoris) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Rekomendasi Aksesoris
            </h3>
        </div>
        
        <div class="p-6 text-center py-12">
            <div class="w-20 h-20 bg-indigo-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
            </div>
            <p class="text-gray-500 font-bold mb-2">Area Katalog Aksesoris</p>
            <p class="text-sm text-gray-400">Kasir dapat menggunakan Scanner Barcode di halaman sebelumnya jika customer membeli aksesoris tambahan.</p>
        </div>
    </div>

    {{-- Footer Actions --}}
    <div class="flex justify-between gap-3 pt-4">
        <button wire:click="prevStep"
            class="px-6 py-3 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 font-bold rounded-xl shadow-sm transition-all flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali
        </button>
        <button wire:click="nextStep"
            class="px-8 py-3 bg-[#1c69d4] hover:bg-blue-700 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-2 group">
            Lewati & Lanjut Pembayaran
            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </button>
    </div>
</div>
