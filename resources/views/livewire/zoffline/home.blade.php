<div class="min-h-screen flex flex-col items-center justify-center p-6">

    {{-- Header Section: Left-Aligned dengan Aksen --}}
    <div class="mb-14  mt-10 md:mt-0 max-w-7xl w-full flex flex-col items-start">


        {{-- Main Heading --}}
        <h1 class="text-4xl md:text-5xl font-extrabold text-neutral-900 tracking-tight mb-4">
            Pilih Layanan
        </h1>

        {{-- Subtitle --}}
        <p class="text-sm md:text-base text-gray-500 max-w-2xl leading-relaxed">
            Kelola transaksi kasir, lakukan tukar tambah, atau jual HP bekasmu dengan proses yang cepat, aman, dan
            terpercaya.
        </p>

    </div>

    {{-- Cards Container --}}
    <div class="grid grid-cols-1 md:grid-cols-3 max-w-7xl gap-6  w-full">

        {{-- Card 1: Zpos --}}
        @can('view-pos')
            <div wire:click="navigateToZPos"
                class="w-full h-70 md:h-80 bg-linear-to-br from-neutral-950 via-neutral-800/90 to-neutral-900 rounded-2xl relative flex flex-col justify-end overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-700 ease-out border border-neutral-800 hover:border-neutral-700">

                {{-- Gambar & Logo --}}
                <div class="absolute inset-0 w-full h-full pointer-events-none">
                    <img src="{{ asset('assets/png/zlogo.png') }}" alt="Zpos Logo"
                        class="absolute right-4 md:right-6 top-6 md:top-8 w-40 md:w-40 lg:w-50 h-auto transition-all duration-700 ease-out group-hover:scale-110 group-hover:-translate-y-2 z-10 drop-shadow-xl">
                </div>

                {{-- Text Content --}}
                <div class="relative z-20">
                    <h2 class="text-3xl md:text-4xl text-white font-bold leading-none tracking-tight">
                        Zpos
                    </h2>
                </div>
            </div>
        @endcan
        @can('trade-in')
            {{-- Card 2: Trade-In (Tukar Tambah) --}}
            <div wire:click="navigateToTradeIn"
                class="w-full h-70 md:h-80 bg-emerald-500 rounded-2xl relative flex flex-col justify-end overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300">

                {{-- Decorative PNGs --}}
                <div class="absolute inset-0 w-full h-full pointer-events-none">
                    {{-- trade1 diturunkan ke top-4 dan md:top-6 --}}
                    <img src="{{ asset('assets/png/trade1.png') }}" alt="phonerepair"
                        class="absolute w-35 md:w-30 lg:w-40 right-2 md:right-4 top-4 md:top-6 h-auto transition-all duration-500 ease-in-out group-hover:scale-110 z-10 drop-shadow-xl">

                    {{-- trade2 menyesuaikan turun ke top-10 dan md:top-16 --}}
                    <img src="{{ asset('assets/png/trade2.png') }}" alt="phonerepair"
                        class="absolute w-20 md:w-15 lg:w-20 right-25 md:right-25 lg:right-30 top-15 md:top-16 h-auto transition-all duration-500 ease-in-out group-hover:scale-110 group-hover:rotate-180 z-10 drop-shadow-lg">
                </div>

                {{-- Text Content --}}
                <div class="relative z-20">
                    <h2 class="text-3xl md:text-4xl text-white font-bold leading-none tracking-tight">
                        Trade-In <br> Mobile
                    </h2>
                </div>
            </div>
        @endcan

        @can('sell-phone')
            {{-- Card 3: Sell Phone (Jual HP) --}}
            <div wire:click="navigateToSellPhone"
                class="w-full h-70 md:h-80 bg-violet-600 rounded-2xl relative flex flex-col justify-end overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300">

                {{-- Decorative PNGs --}}
                <div class="absolute inset-0 w-full h-full pointer-events-none">
                    {{-- sell1 diturunkan ke top-4 dan md:top-6 --}}
                    <img src="{{ asset('assets/png/sell1.png') }}" alt="phonerepair"
                        class="absolute w-35 md:w-30 lg:w-40 right-2 md:right-4 top-4 md:top-6 h-auto transition-all duration-500 ease-in-out group-hover:scale-110 z-10 drop-shadow-xl">

                    {{-- sell2 menyesuaikan turun ke top-12 dan md:top-16 --}}
                    <img src="{{ asset('assets/png/sell2.png') }}" alt="phonerepair"
                        class="absolute w-20 md:w-15 lg:w-22 right-25 lg:right-30 top-15 md:top-16 h-auto transition-all duration-500 ease-in-out group-hover:scale-110 group-hover:-rotate-12 group-hover:-translate-x-4 z-10 drop-shadow-lg">

                    {{-- sell3 menyesuaikan turun ke top-32 dan md:top-40 --}}
                    <img src="{{ asset('assets/png/sell3.png') }}" alt="phonerepair"
                        class="absolute w-8 md:w-6 lg:w-7 right-25 lg:right-30 top-40 lg:top-50 h-auto transition-all duration-500 ease-in-out group-hover:scale-110 z-10 group-hover:animate-bounce">

                    {{-- sell4 menyesuaikan turun ke top-36 dan md:top-44 --}}
                    <img src="{{ asset('assets/png/sell4.png') }}" alt="phonerepair"
                        class="absolute w-7 md:w-5 lg:w-6 right-33 md:right-30 lg:right-37 top-44 lg:top-55 h-auto transition-all duration-500 ease-in-out group-hover:scale-110 z-10 group-hover:animate-bounce"
                        style="animation-delay: 0.1s;">
                </div>

                {{-- Text Content --}}
                <div class="relative z-20">
                    <h2 class="text-3xl md:text-4xl text-white font-bold leading-none tracking-tight">
                        Sell <br> Phones
                    </h2>
                </div>
            </div>
        @endcan
        {{-- Card 4: cekstok --}}
        @can('view-stock')
            <div wire:click="navigateToCekStock"
                class="w-full h-70 md:h-80 bg-olive-700 hover:bg-olive-800 rounded-2xl relative flex flex-col justify-end overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-700 ease-out">
                {{-- Gambar & Logo --}}
                <div class="absolute inset-0 w-full h-full pointer-events-none">
                    <img src="{{ asset('assets/png/stok.png') }}" alt="stok Logo"
                        class="absolute right-4 md:right-6 top-6 md:top-8 w-35 md:w-35 lg:w-40 h-auto transition-all invert duration-700 ease-out group-hover:scale-110 group-hover:-translate-y-2 z-10 drop-shadow-xl">
                </div>

                {{-- Text Content --}}
                <div class="relative z-20">
                    <h2 class="text-3xl md:text-4xl text-white font-bold leading-none tracking-tight">
                        Cek Stock
                    </h2>
                </div>
            </div>
        @endcan
        @can('view-riwayat-kasir')
            {{-- Card 5: shift --}}
            <div wire:click="navigateToShift"
                class="w-full h-70 md:h-80 bg-teal-800 hover:bg-teal-900 rounded-2xl relative flex flex-col justify-end overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-700 ease-out">
                {{-- Gambar & Logo --}}
                <div class="absolute inset-0 w-full h-full pointer-events-none">
                    <img src="{{ asset('assets/png/rk.png') }}" alt="shift Logo"
                        class="absolute right-4 md:right-6 top-6 md:top-8 w-35 md:w-35 lg:w-40 invert h-auto transition-all duration-700 ease-out group-hover:scale-110 group-hover:-translate-y-2 z-10 drop-shadow-xl">
                </div>

                {{-- Text Content --}}
                <div class="relative z-20">
                    <h2 class="text-3xl md:text-4xl text-white font-bold leading-none tracking-tight">
                        Shift
                    </h2>
                </div>
            </div>
        @endcan
        @can('view_dashboard')
            {{-- Card 5: shift --}}
            <div wire:click="navigateToDashboard"
                class="w-full h-70 md:h-80 bg-teal-800 hover:bg-teal-900 rounded-2xl relative flex flex-col justify-end overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-lg hover:shadow-2xl hover:-translate-y-1 transition-all duration-700 ease-out">
                {{-- Gambar & Logo --}}
                <div class="absolute inset-0 w-full h-full pointer-events-none">
                    <img src="{{ asset('assets/png/rk.png') }}" alt="shift Logo"
                        class="absolute right-4 md:right-6 top-6 md:top-8 w-35 md:w-35 lg:w-40 invert h-auto transition-all duration-700 ease-out group-hover:scale-110 group-hover:-translate-y-2 z-10 drop-shadow-xl">
                </div>

                {{-- Text Content --}}
                <div class="relative z-20">
                    <h2 class="text-3xl md:text-4xl text-white font-bold leading-none tracking-tight">
                        Dashboard
                    </h2>
                </div>
            </div>
        @endcan
    </div>
</div>
