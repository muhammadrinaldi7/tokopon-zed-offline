<div class="mb-6 space-y-4">
    {{-- Top Bar & Page Title --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-2xl shadow-xs border border-gray-100">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('zoffline') }}" wire:navigate
                class="w-11 h-11 bg-gray-50 hover:bg-neutral-800 text-gray-600 hover:text-white rounded-xl flex items-center justify-center transition-all duration-200 border border-gray-200 shadow-xs group">
                <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                        ZED E-Commerce Hub
                    </span>
                    <span class="text-xs text-gray-400">• Mobile App & Live Chat</span>
                </div>
                <h1 class="text-xl font-black text-gray-900 tracking-tight mt-0.5">
                    {{ $title ?? 'Manajemen Toko Online' }}
                </h1>
            </div>
        </div>

        {{-- Segmented Sub-Navigation Tabs --}}
        <div class="flex flex-wrap items-center gap-1 bg-gray-100/80 p-1.5 rounded-xl border border-gray-200/50 text-xs font-bold">
            <a href="{{ route('zoffline.cs-chat') }}" wire:navigate
                class="px-3.5 py-2 rounded-lg transition-all duration-150 flex items-center gap-1.5 {{ request()->routeIs('zoffline.cs-chat') ? 'bg-white text-indigo-600 shadow-sm border border-gray-200/40' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50' }}">
                <span>💬</span>
                <span>Live Chat CS</span>
            </a>

            <a href="{{ route('zoffline.ecommerce.flash-sale') }}" wire:navigate
                class="px-3.5 py-2 rounded-lg transition-all duration-150 flex items-center gap-1.5 {{ request()->routeIs('zoffline.ecommerce.flash-sale') ? 'bg-white text-indigo-600 shadow-sm border border-gray-200/40' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50' }}">
                <span>⚡</span>
                <span>Flash Sale</span>
            </a>

            <a href="{{ route('zoffline.ecommerce.banners') }}" wire:navigate
                class="px-3.5 py-2 rounded-lg transition-all duration-150 flex items-center gap-1.5 {{ request()->routeIs('zoffline.ecommerce.banners') ? 'bg-white text-indigo-600 shadow-sm border border-gray-200/40' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50' }}">
                <span>🎨</span>
                <span>Banner Promo</span>
            </a>

            <a href="{{ route('zoffline.ecommerce.products') }}" wire:navigate
                class="px-3.5 py-2 rounded-lg transition-all duration-150 flex items-center gap-1.5 {{ request()->routeIs('zoffline.ecommerce.products') ? 'bg-white text-indigo-600 shadow-sm border border-gray-200/40' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50' }}">
                <span>📦</span>
                <span>Kurasi Produk</span>
            </a>

            <a href="{{ route('zoffline.ecommerce.closing-analytics') }}" wire:navigate
                class="px-3.5 py-2 rounded-lg transition-all duration-150 flex items-center gap-1.5 {{ request()->routeIs('zoffline.ecommerce.closing-analytics') ? 'bg-white text-indigo-600 shadow-sm border border-gray-200/40' : 'text-gray-600 hover:text-gray-900 hover:bg-white/50' }}">
                <span>📈</span>
                <span>Analitik Closing</span>
            </a>
        </div>
    </div>
</div>
