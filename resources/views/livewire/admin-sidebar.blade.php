<aside :class="(sidebarCollapsed ? 'lg:w-20' : 'lg:w-64') + ' ' + (sidebarOpen ? 'translate-x-0' : '-translate-x-full')"
    class="w-[280px] bg-[#f7f7f7] border-r border-gray-200 flex flex-col h-screen shrink-0 fixed lg:sticky top-0 left-0 z-40 transform transition-transform duration-300 ease-in-out lg:translate-x-0 shadow-sm">
    <div class="p-8 flex flex-col items-center border-b border-gray-200 relative">
        <button @click="sidebarOpen = false"
            class="lg:hidden absolute top-4 right-4 p-2 text-gray-400 hover:text-gray-600 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        {{-- Avatar --}}
        <div :class="sidebarCollapsed ? 'w-10 h-10 text-sm mb-1' : 'w-16 h-16 text-xl mb-3'"
            class="rounded-lg bg-[#1c69d4] text-white flex items-center justify-center font-bold shadow-none transition-all duration-300">
            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 2)) }}
        </div>
        <h3 x-show="!sidebarCollapsed" class="font-bold text-[#262626] text-sm whitespace-nowrap">
            {{ auth()->user()->name ?? 'Admin User' }}</h3>
        <p x-show="!sidebarCollapsed"
            class="text-[10px] text-[#6b6b6b] mt-0.5 uppercase tracking-wider font-bold whitespace-nowrap">
            {{ auth()->user()->roles->pluck('name')->first() ?? 'Member' }}</p>
    </div>

    <nav class="flex-1 py-6 px-4 space-y-1.5 overflow-y-auto no-scrollbar overflow-x-hidden">
        @php
            $activeClass = 'bg-[#1c69d4] text-white font-bold rounded-lg';
            $inactiveClass = 'text-[#262626] hover:bg-[#ebebeb] hover:text-[#1a1a1a] font-light rounded-lg';
            $activeIconClass = 'opacity-100 text-white';
            $inactiveIconClass = 'opacity-70 text-[#262626] font-normal';
        @endphp

        <a href="/admin/dashboard" wire:navigate
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.dashboard') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 {{ request()->routeIs('admin.dashboard') ? $activeIconClass : $inactiveIconClass }}"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Dashboard</span>
        </a>

        @can('access-cs-chat')
            <a href="/admin/cs-chat" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.cs-chat') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.cs-chat') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span x-show="!sidebarCollapsed" class="flex-1 whitespace-nowrap transition-opacity">Chat CS</span>
                @if ($unreadCount > 0)
                    <span
                        class="ml-auto bg-red-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center">
                        {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                    </span>
                @endif
            </a>
        @endcan

        <a href="#"
            class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->is('admin/services') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 {{ request()->is('admin/services') ? $activeIconClass : $inactiveIconClass }}"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Status Servis</span>
        </a>

        @can('manage-promos')
            <a href="{{ route('admin.promos.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.promos.*') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.promos.*') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                Promo & Vouchers
            </a>
        @endcan

        @canany(['view-pos', 'view-stock'])
            <div class="px-4 mt-8 mb-2" x-show="!sidebarCollapsed">
                <p class="text-[13px] font-bold tracking-[1.5px] text-gray-400 uppercase">Toko & Katalog</p>
            </div>

            @can('view-pos')
                <a href="{{ route('zoffline.pos') }}" wire:navigate
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('zoffline.pos') ? $activeClass : $inactiveClass }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('zoffline.pos') ? $activeIconClass : $inactiveIconClass }}"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Kasir (POS)</span>
                </a>
            @endcan

            @can('view-stock')
                <a href="{{ route('zoffline.cekstock') }}" wire:navigate
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('zoffline.cekstock') ? $activeClass : $inactiveClass }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('zoffline.cekstock') ? $activeIconClass : $inactiveIconClass }}"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Zm3.75 11.625a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>

                    <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Cek Stock</span>
                </a>
            @endcan
            @can('view-warehouse-stocks')
                <a href="{{ route('admin.check-serial-number') }}" wire:navigate
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.check-serial-number') ? $activeClass : $inactiveClass }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('admin.check-serial-number') ? $activeIconClass : $inactiveIconClass }}"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m5.231 13.481L15 17.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v16.5c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Zm3.75 11.625a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                    <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Cek Lokasi SN</span>
                </a>
            @endcan
        @endcanany

        @can('view-catalog-menu')
            <div x-data="{ openProducts: {{ request()->routeIs('admin.products', 'admin.second-products', 'admin.products.variants', 'admin.second-products.variants', 'admin.categories', 'admin.brands', 'admin.accurate-products', 'admin.accurate-sync-sn', 'admin.accurate-customers', 'admin.warehouse-stocks', 'admin.check-serial-number') ? 'true' : 'false' }} }">
                <button @click="openProducts = !openProducts" type="button"
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.products', 'admin.second-products', 'admin.products.variants', 'admin.second-products.variants', 'admin.categories', 'admin.brands', 'admin.accurate-products', 'admin.accurate-sync-sn', 'admin.accurate-customers', 'admin.warehouse-stocks', 'admin.check-serial-number') ? $activeClass : $inactiveClass }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.products', 'admin.second-products', 'admin.products.variants', 'admin.second-products.variants', 'admin.categories', 'admin.brands', 'admin.accurate-products', 'admin.accurate-sync-sn', 'admin.accurate-customers', 'admin.warehouse-stocks', 'admin.check-serial-number') ? $activeIconClass : $inactiveIconClass }}"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Katalog Pusat</span>
                    </div>
                    <svg x-show="!sidebarCollapsed" :class="{ 'rotate-180': openProducts }"
                        class="w-4 h-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="openProducts && !sidebarCollapsed" style="display: none;" class="pl-12 mt-1 mb-2 space-y-1">
                    @can('manage-new-catalog')
                        <a href="{{ route('admin.products') }}" wire:navigate
                            class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.products', 'admin.products.variants') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                            Katalog Baru
                        </a>
                    @endcan
                    @can('manage-second-catalog')
                        <a href="{{ route('admin.second-products') }}" wire:navigate
                            class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.second-products', 'admin.second-products.variants') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                            Katalog Second
                        </a>
                    @endcan
                    @can('manage-categories')
                        <a href="{{ route('admin.categories') }}" wire:navigate
                            class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.categories') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                            Master Kategori
                        </a>
                    @endcan
                    @can('manage-brands')
                        <a href="{{ route('admin.brands') }}" wire:navigate
                            class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.brands') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                            Master Merek
                        </a>
                    @endcan
                    @can('manage-accurate-products')
                        <a href="{{ route('admin.accurate-products') }}" wire:navigate
                            class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.accurate-products') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                            Produk Accurate
                        </a>
                        <a href="{{ route('admin.accurate-sync-sn') }}" wire:navigate
                            class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.accurate-sync-sn') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                            Sync Serial Number
                        </a>
                    @endcan
                    @can('manage-accurate-customers')
                        <a href="{{ route('admin.accurate-customers') }}" wire:navigate
                            class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.accurate-customers') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                            Pelanggan Accurate
                        </a>
                    @endcan
                    @can('view-warehouse-stocks')
                        <a href="{{ route('admin.warehouse-stocks') }}" wire:navigate
                            class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.warehouse-stocks') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                            Stok Gudang
                        </a>
                    @endcan
                </div>
            </div>
        @endcan

        @can('manage-orders')
            <a href="/admin/orders" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.orders.management') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.orders.management') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Pesanan</span>
            </a>
        @endcan

        @can('view-reporting')

            <div x-data="{ openReporting: {{ request()->routeIs('admin.reporting.*') ? 'true' : 'false' }} }">
                <button @click="openReporting = !openReporting" type="button"
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.reporting.*') ? $activeClass : $inactiveClass }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.reporting.*') ? $activeIconClass : $inactiveIconClass }}"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Reporting</span>
                    </div>
                    <svg x-show="!sidebarCollapsed" :class="{ 'rotate-180': openReporting }"
                        class="w-4 h-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="openReporting && !sidebarCollapsed" style="display: none;"
                    class="pl-12 mt-1 mb-2 space-y-1">
                    @can('reporting-dashboard')
                        <a href="{{ route('admin.reporting.index') }}" wire:navigate
                            class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.reporting.index') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                            Dashboard
                        </a>
                    @endcan
                    @can('reporting-sales')
                        <a href="{{ route('admin.reporting.sales') }}" wire:navigate
                            class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.reporting.sales') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                            Laporan Penjualan
                        </a>
                        <a href="{{ route('admin.reporting.promo') }}" wire:navigate
                            class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.reporting.promo') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                            Laporan Promo
                        </a>
                    @endcan
                    @can('reporting-products')
                        <a href="{{ route('admin.reporting.products') }}" wire:navigate
                            class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.reporting.products') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                            Kinerja Produk
                        </a>
                        <a href="{{ route('admin.reporting.stock') }}" wire:navigate
                            class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.reporting.stock') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                            Laporan Stok (Rekap)
                        </a>
                        <a href="{{ route('admin.reporting.laporan-stok') }}" wire:navigate
                            class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.reporting.laporan-stok') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                            Laporan Stok (Serial Number)
                        </a>
                    @endcan
                    @can('reporting-staff')
                        <a href="{{ route('admin.reporting.staff') }}" wire:navigate
                            class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.reporting.staff') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                            Analisa Sales
                        </a>
                    @endcan
                </div>
            </div>
        @endcan

        @can('manage-trade-in')
            <a href="{{ route('admin.trade-ins.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.trade-ins.*') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.trade-ins.*') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Tukar Tambah</span>
            </a>

            <a href="{{ route('admin.sell-phones.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.sell-phones.*') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.sell-phones.*') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Pembelian HP</span>
            </a>
        @endcan

        @can('manage-buyback')
            <div x-data="{ openBuyback: {{ request()->routeIs('admin.buyback.*') ? 'true' : 'false' }} }">
                <button @click="openBuyback = !openBuyback" type="button"
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.buyback.*') ? $activeClass : $inactiveClass }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.buyback.*') ? $activeIconClass : $inactiveIconClass }}"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Master Harga</span>
                    </div>
                    <svg x-show="!sidebarCollapsed" :class="{ 'rotate-180': openBuyback }"
                        class="w-4 h-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="openBuyback && !sidebarCollapsed" style="display: none;" class="pl-12 mt-1 mb-2 space-y-1">
                    <a href="{{ route('admin.buyback.index') }}" wire:navigate
                        class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.buyback.index', 'admin.buyback.create') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                        Daftar Perangkat
                    </a>
                    <a href="{{ route('admin.buyback.tiers') }}" wire:navigate
                        class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.buyback.tiers') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                        Buyback Tiers
                    </a>
                </div>
            </div>
        @endcan

        @can('manage-qc')
            <div x-data="{ openQc: {{ request()->routeIs('admin.qc.*') ? 'true' : 'false' }} }">
                <button @click="openQc = !openQc" type="button"
                    class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.qc.*') ? $activeClass : $inactiveClass }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 {{ request()->routeIs('admin.qc.*') ? $activeIconClass : $inactiveIconClass }}"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">QC & Inspeksi</span>
                    </div>
                    <svg x-show="!sidebarCollapsed" :class="{ 'rotate-180': openQc }"
                        class="w-4 h-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="openQc && !sidebarCollapsed" style="display: none;" class="pl-12 mt-1 mb-2 space-y-1">
                    <a href="{{ route('admin.qc.templates') }}" wire:navigate
                        class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.qc.templates') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                        Templates
                    </a>
                    <a href="{{ route('admin.qc.inbound') }}" wire:navigate
                        class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.qc.inbound') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                        Antrean Inbound
                    </a>
                    <a href="{{ route('admin.qc.device-search') }}" wire:navigate
                        class="block px-4 py-2 rounded-lg text-xs transition-colors cursor-pointer {{ request()->routeIs('admin.qc.device-search') ? 'bg-[#1c69d4]/10 text-[#1c69d4] font-bold' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-800 font-medium' }}">
                        Pemeriksaan Perangkat
                    </a>
                </div>
            </div>
        @endcan

        @can('manage-users')
            <div class="px-4 mt-8 mb-2" x-show="!sidebarCollapsed">
                <p class="text-[13px] font-bold tracking-[1.5px] text-gray-400 uppercase">Administrator</p>
            </div>

            <a href="/admin/users" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.users') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.users') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Kelola Pengguna</span>
            </a>
            <a href={{ route('admin.user.operational') }} wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.user.operational') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.user.operational') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">User Operational</span>
            </a>
            <a href={{ route('admin.user.employes') }} wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.user.employes') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.user.employes') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Karyawan</span>
            </a>

            <a href={{ route('admin.user.vendors') }} wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.user.vendors') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.user.vendors') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Vendor</span>
            </a>

            <a href="/admin/roles" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.roles') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.roles') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Kelola Role & Akses</span>
            </a>
        @endcan

        @can('manage-settings')
            <div class="px-4 mt-8 mb-2" x-show="!sidebarCollapsed">
                <p class="text-[13px] font-bold tracking-[1.5px] text-gray-400 uppercase">Sistem</p>
            </div>

            <a href="{{ route('admin.settings.business-units') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.settings.business-units') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.settings.business-units') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Unit Usaha</span>
            </a>

            <a href="{{ route('admin.settings.payment-methods') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.settings.payment-methods') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.settings.payment-methods') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">List Bank</span>
            </a>

            <a href="{{ route('admin.settings.shipping') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.settings.shipping') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.settings.shipping') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                </svg>
                <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Pengaturan Pengiriman</span>
            </a>

            <a href="{{ route('admin.settings.catalog') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.settings.catalog') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.settings.catalog') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Pengaturan Katalog</span>
            </a>

            <a href="{{ route('admin.settings.warehouse') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.settings.warehouse') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.settings.warehouse') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Pengaturan Gudang</span>
            </a>

            <a href="{{ route('admin.settings.pos') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.settings.pos') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.settings.pos') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Pengaturan POS</span>
            </a>

            <a href="{{ route('admin.adjustment.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.adjustment.index') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.adjustment.index') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Penyesuaian Stok</span>
            </a>
        @endcan
    </nav>

    <div class="p-6">
        <form action="{{ route('logout') }}" method="POST" class="w-full">
            @csrf
            <button type="submit"
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-red-500 hover:bg-red-50 font-semibold text-sm transition-colors w-full cursor-pointer">
                <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span x-show="!sidebarCollapsed" class="whitespace-nowrap transition-opacity">Keluar</span>
            </button>
        </form>
    </div>
</aside>
