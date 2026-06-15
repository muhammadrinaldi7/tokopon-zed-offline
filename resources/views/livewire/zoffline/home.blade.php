<div class="min-h-screen flex flex-col items-center justify-center p-6">

    {{-- Cards Container --}}
    <div class="grid grid-cols-1 md:grid-cols-4 max-w-7xl gap-6  w-full">

        {{-- Card 1: Zpos --}}
        @can('view-pos')
            <div wire:click="navigateToZPos"
                class="md:nth-[3n+1]:col-span-2 w-full h-70 md:h-80 bg-white rounded-2xl relative flex flex-col justify-between overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 ease-out">

                {{-- Ikon tetap di atas --}}
                <div class="rounded-full w-20 h-20 bg-[#DFE7FF] flex items-center justify-center text-black">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-auto" viewBox="0 0 24 24">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="1.2">
                            <path d="M14.5 10.5a2.5 2.5 0 1 1-5 0a2.5 2.5 0 0 1 5 0" />
                            <path
                                d="M16 3.5c2.48 0 4.19.384 5.133.676c.543.169.867.683.867 1.251v9.755c0 1.115-1.228 1.954-2.324 1.748c-.94-.178-2.165-.32-3.676-.32c-4.75 0-5.89 1.805-12.855.27A1.47 1.47 0 0 1 2 15.437V5.421c0-.976.92-1.687 1.878-1.497C10.197 5.177 11.421 3.5 16 3.5" />
                            <path
                                d="M2 7.5c1.951 0 3.705-1.595 3.929-3.246M18.5 4c0 2.04 1.765 3.969 3.5 3.969m0 5.531c-1.9 0-3.74 1.31-3.898 3.098M6 16.996a4 4 0 0 0-4-4m17 6.737a18.5 18.5 0 0 0-3-.233c-4.294 0-5.638 1.66-11 .703" />
                        </g>
                    </svg>
                </div>

                {{-- Teks dibungkus div agar kumpul di bawah --}}
                <div>
                    <h1 class="text-2xl ">Transaksi <br> Penjualan</h1>
                    {{-- line-clamp-2 ditambahkan agar aman jika suatu saat teksnya panjang --}}
                    <p class="text-neutral-500 text-sm mt-3 line-clamp-2">Transaksi Mudah dan Cepat</p>
                </div>

            </div>
        @endcan

        @can('sell-phone')
            {{-- Card 3: Sell Phone (Jual HP) --}}
            <div wire:click="navigateToSellPhone" {{-- Ubah justify-end menjadi justify-between di sini --}}
                class="md:nth-[3n+1]:col-span-2 w-full h-70 md:h-80 bg-white rounded-2xl relative flex flex-col justify-between overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 ease-out">

                {{-- Ikon akan tertahan di atas --}}
                <div class="rounded-full w-20 h-20 bg-[#FFC4C4] flex items-center justify-center text-black">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-auto" viewBox="0 0 2048 2048">
                        <path d="M0 0h2048v2048H0z" fill="none" />
                        <path fill="currentColor"
                            d="M534 1664q-20 52-20 107v10q0 5 1 11H384v-128zm965 256h293v128H837l-147-148q-24-25-37-57t-13-67t13-67t38-58l813-814l538 539zm5-902l-389 390l357 358l389-390zm-187 902l65-64l-358-357l-242 242q-14 14-14 35t14 35l108 109zm-767 0q21 41 47 68t60 60H128q-27 0-50-10t-40-27t-28-41t-10-50V128q0-27 10-50t27-40t41-28t50-10h1024q27 0 50 10t40 27t28 41t10 50v752l-128 128V128H128v1792z" />
                    </svg>
                </div>

                {{-- Bungkus Teks jadi satu div agar ngumpul di bawah --}}
                <div>
                    <h1 class="text-2xl ">Transaksi<br>Pembelian</h1>
                    {{-- Tambahkan line-clamp-2 agar rapi jika kepanjangan --}}
                    <p class="text-neutral-500 text-sm mt-3 line-clamp-2">Catatan aktivitas pembelian</p>
                </div>
            </div>
        @endcan
        @can('sell-phone')
            {{-- Card 3: Sell Phone (Jual HP) --}}
            <div wire:click="navigateToSellPhone" {{-- Ubah justify-end menjadi justify-between di sini --}}
                class="md:nth-[3n+1]:col-span-2 w-full h-70 md:h-80 bg-white rounded-2xl relative flex flex-col justify-between overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 ease-out">

                {{-- Ikon akan tertahan di atas --}}
                <div class="rounded-full w-20 h-20 bg-[#D4F1FF] flex items-center justify-center text-black">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-auto" viewBox="0 0 24 24">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <g fill="none">
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="1.5" d="M15 5H9" />
                            <path fill="currentColor"
                                d="M21.426 14.412a.75.75 0 1 0-.931 1.176zm-17.92 1.176a.75.75 0 1 0-.932-1.176zm8.494 5.1l.494.564a.75.75 0 0 0 0-1.129zm-1.5-1.313l.494-.564a.75.75 0 0 0-1.244.564zm0 2.625h-.75a.75.75 0 0 0 1.244.564zm3.924-2.183a.75.75 0 0 0 .152 1.493zm6.07-4.23c.574.455.756.856.756 1.163h1.5c0-.95-.567-1.738-1.324-2.338zM2.75 16.75c0-.307.182-.708.755-1.162l-.931-1.176c-.757.6-1.324 1.388-1.324 2.338zm9.744 3.373l-1.5-1.312l-.988 1.128l1.5 1.313zm-1.5 2.441l1.5-1.312l-.988-1.129l-1.5 1.313zM21.25 16.75c0 .457-.425 1.112-1.719 1.76c-1.23.617-3.009 1.095-5.107 1.307l.152 1.493c2.215-.225 4.186-.736 5.627-1.459c1.379-.69 2.547-1.723 2.547-3.101zm-11.5 2.625v1.268h1.5v-1.268zm0 1.268V22h1.5v-1.357zm.794-.748c-2.343-.139-4.371-.605-5.788-1.248c-.71-.322-1.232-.672-1.565-1.017c-.33-.342-.441-.637-.441-.88h-1.5c0 .744.35 1.393.862 1.922c.509.526 1.21.972 2.024 1.341c1.63.74 3.851 1.233 6.32 1.38z" />
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="1.5"
                                d="M5.502 17q-.002-.468-.002-1V8c0-2.828 0-4.243.879-5.121C7.257 2 8.672 2 11.5 2h1c2.828 0 4.243 0 5.121.879c.879.878.879 2.293.879 5.121v8q0 .532-.002 1" />
                        </g>
                    </svg>


                </div>

                {{-- Bungkus Teks jadi satu div agar ngumpul di bawah --}}
                <div>
                    <h1 class="text-2xl ">Klaim Garansi <br> Retur</h1>
                    {{-- Tambahkan line-clamp-2 agar rapi jika kepanjangan --}}
                    <p class="text-neutral-500 text-sm mt-3 line-clamp-2">Ajukan klaim garansi untuk produk yang bermasalah
                    </p>
                </div>
            </div>
        @endcan
        {{-- Card 4: cekstok --}}
        @can('view-stock')
            <div wire:click="navigateToCekStock"
                class="md:nth-[3n+1]:col-span-2 w-full h-70 md:h-80 bg-white rounded-2xl relative flex flex-col justify-between overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 ease-out">
                {{-- Ikon akan tertahan di atas --}}
                <div class="rounded-full w-20 h-20 bg-[#FFD9B7] flex items-center justify-center text-black">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-auto" viewBox="0 0 15 15">
                        <path d="M0 0h15v15H0z" fill="none" />
                        <path fill="none" stroke="currentColor" stroke-linejoin="round"
                            d="M.5 3.498L7.5.5l7 2.998m-14 0l7 2.998m-7-2.998V3.5m14-.002l-7 2.998m7-2.998V11.5l-7 3m7-11.002L7.5 6.5v8m0-8.004V14.5m0-8.004L.5 3.5m7 11l-7-3v-8" />
                    </svg>

                </div>

                {{-- Bungkus Teks jadi satu div agar ngumpul di bawah --}}
                <div>
                    <h1 class="text-2xl ">Cek <br> Stock</h1>
                    {{-- Tambahkan line-clamp-2 agar rapi jika kepanjangan --}}
                    <p class="text-neutral-500 text-sm mt-3 line-clamp-2">Informasi stok barang yang tersedia
                    </p>
                </div>
            </div>
        @endcan
        @can('view-riwayat-kasir')
            {{-- Card 5: shift --}}
            <div wire:click="navigateToShift"
                class="md:nth-[3n+1]:col-span-2 w-full h-70 md:h-80 bg-white rounded-2xl relative flex flex-col justify-between overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 ease-out">
                {{-- Ikon akan tertahan di atas --}}
                <div class="rounded-full w-20 h-20 bg-[#FFF1D8] flex items-center justify-center text-black">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-auto" viewBox="0 0 16 16">
                        <path d="M0 0h16v16H0z" fill="none" />
                        <path fill="currentColor"
                            d="M12.5 2A2.5 2.5 0 0 1 15 4.5v10a.5.5 0 0 1-.686.464l-2.31-.926l-2.31.926a.5.5 0 0 1-.28.027l-.092-.027l-2.31-.926l-2.31.926a.5.5 0 0 1-.678-.378l-.007-.086V7.36l-.985-.328l-2.33.932a.5.5 0 0 1-.678-.378L.017 7.5v-3a2.5 2.5 0 0 1 2.5-2.5h10zm0 1h-8l.019.024c.303.413.482.923.482 1.48v9.26l1.81-.725a.5.5 0 0 1 .28-.027l.091.027l2.31.925l2.32-.925a.5.5 0 0 1 .28-.027l.092.027l1.81.725v-9.26c0-.78-.595-1.42-1.36-1.49l-.144-.007zm-3 6a.5.5 0 0 1 0 1h-3a.5.5 0 0 1 0-1zm3-2a.5.5 0 0 1 0 1h-6a.5.5 0 0 1 0-1zm-10-4l-.144.007a1.503 1.503 0 0 0-1.36 1.49v2.26l1.81-.725a.5.5 0 0 1 .258-.03l.086.02l.842.28v-1.81c0-.78-.595-1.42-1.36-1.49l-.144-.007zm10 2a.5.5 0 0 1 0 1h-6a.5.5 0 0 1 0-1z" />
                    </svg>
                </div>

                {{-- Bungkus Teks jadi satu div agar ngumpul di bawah --}}
                <div>
                    <h1 class="text-2xl ">Closing <br> Kasir</h1>
                    {{-- Tambahkan line-clamp-2 agar rapi jika kepanjangan --}}
                    <p class="text-neutral-500 text-sm mt-3 line-clamp-2">Rekap dan penutupan kasir harian
                    </p>
                </div>
            </div>
        @endcan
        @can('view_dashboard')
            {{-- Card 5: shift --}}
            <div wire:click="navigateToDashboard"
                class="md:nth-[3n+1]:col-span-2 w-full h-70 md:h-80 bg-white rounded-2xl relative flex flex-col justify-between overflow-hidden p-6 lg:p-8 group cursor-pointer shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-200 ease-out">
                {{-- Ikon akan tertahan di atas --}}
                <div class="rounded-full w-20 h-20 bg-[#FFF1D8] flex items-center justify-center text-black">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-auto" viewBox="0 0 24 24">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <path fill="currentColor"
                            d="M13 9V3h8v6zM3 13V3h8v10zm10 8V11h8v10zM3 21v-6h8v6zm2-10h4V5H5zm10 8h4v-6h-4zm0-12h4V5h-4zM5 19h4v-2H5zm4-2" />
                    </svg>

                </div>

                {{-- Bungkus Teks jadi satu div agar ngumpul di bawah --}}
                <div>
                    <h1 class="text-2xl ">Dashboard</h1>
                    {{-- Tambahkan line-clamp-2 agar rapi jika kepanjangan --}}
                    <p class="text-neutral-500 text-sm mt-3 line-clamp-2">Omzet, grafik, dan analitik toko
                    </p>
                </div>
            </div>
        @endcan
    </div>
</div>
