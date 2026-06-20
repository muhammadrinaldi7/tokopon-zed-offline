<?php

use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component {
    public function confirmLogout(): void
    {
        $this->dispatch('show-confirm', title: 'Logout', message: 'Apakah Anda yakin ingin keluar dari akun?', confirmEvent: 'do-logout', type: 'warning', confirmText: 'Ya, Logout', cancelText: 'Batal');
    }

    #[On('do-logout')]
    public function logout(): void
    {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirect('/', navigate: true);
    }
};
?>

<div>
    {{-- ==========================================
         1. DESKTOP SIDEBAR (Hanya tampil di layar besar / lg) 
         ========================================== --}}
    <aside class="hidden lg:flex fixed top-0 left-0 z-50 h-screen w-20  flex-col items-center py-6">

        {{-- Logo --}}
        {{-- <a href="/" wire:navigate
            class="w-10 h-10 rounded-xl bg-linear-to-br from-neutral-900 via-neutral-600 to-neutral-800 flex items-center justify-center text-white font-bold text-xl shadow-sm mb-8 shrink-0">
            <img src="{{ asset('assets/png/zlogo.png') }}" alt="Zpos Logo" class="w-8 h-8">
        </a> --}}

        {{-- Menu Navigasi --}}
        <nav class="flex-1 flex flex-col gap-4 w-full items-center justify-center">
            <a href="/" wire:navigate
                class="group relative flex items-center justify-center w-12 h-12 rounded-xl  shadow-sm  transition-all duration-500 {{ request()->is('/') ? 'bg-neutral-800 text-white' : 'bg-white text-neutral-500 hover:text-white hover:bg-neutral-800 hover:translate-x-1 hover:scale-105' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-auto" viewBox="0 0 24 24">
                    <path d="M0 0h24v24H0z" fill="none" />
                    <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M9 20h3m3 0h-3m0 0v-3m0 0h7a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2z" />
                </svg>
                <span
                    class="absolute left-full ml-4 px-3 py-1.5 bg-neutral-800 text-white text-xs font-bold rounded-lg opacity-0 pointer-events-none group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-200 whitespace-nowrap shadow-md z-50">
                    Home
                    <div class="absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-2 bg-neutral-800 rotate-45"></div>
                </span>
            </a>
            {{-- Item: POS --}}
            @can('view-pos')
                <a href="{{ route('zoffline.pos') }}" wire:navigate
                    class="group relative flex items-center justify-center w-12 h-12 rounded-xl shadow-sm  transition-all duration-500 {{ request()->is('zoffline/pos') ? 'bg-neutral-800 text-white' : 'bg-white text-neutral-500 hover:text-white hover:bg-neutral-800 hover:translate-x-2 hover:scale-110' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-auto" viewBox="0 0 24 24">
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
                    <span
                        class="absolute left-full ml-4 px-3 py-1.5 bg-neutral-800 text-white text-xs font-bold rounded-lg opacity-0 pointer-events-none group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-200 whitespace-nowrap shadow-md z-50">
                        Transaksi Penjualan
                        <div class="absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-2 bg-neutral-800 rotate-45"></div>
                    </span>
                </a>

                <a href="{{ route('zoffline.pos.riwayat') }}" wire:navigate
                    class="group relative flex items-center justify-center w-12 h-12 rounded-xl shadow-sm transition-all duration-500 {{ request()->routeIs('zoffline.pos.riwayat') ? 'bg-neutral-800 text-white' : 'bg-white text-neutral-500 hover:text-white hover:bg-neutral-800 hover:translate-x-2 hover:scale-110' }}">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span
                        class="absolute left-full ml-4 px-3 py-1.5 bg-neutral-800 text-white text-xs font-bold rounded-lg opacity-0 pointer-events-none group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-200 whitespace-nowrap shadow-md z-50">
                        Riwayat Penjualan
                        <div class="absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-2 bg-neutral-800 rotate-45"></div>
                    </span>
                </a>
            @endcan

            {{-- Item: Tukar Tambah --}}
            {{-- @can('trade-in')
                <a href="{{ route('zoffline.trade-in') }}" wire:navigate
                    class="group relative flex items-center justify-center w-12 h-12 rounded-2xl text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 transition-all duration-200">
                    <img src="{{ asset('assets/png/trd.png') }}" class="w-8 h-auto" alt="">

                    <span
                        class="absolute left-full ml-4 px-3 py-1.5 bg-gray-800 text-white text-xs font-bold rounded-lg opacity-0 pointer-events-none group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-200 whitespace-nowrap shadow-md z-50">
                        Tukar Tambah
                        <div class="absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-2 bg-gray-800 rotate-45"></div>
                    </span>
                </a>
            @endcan --}}

            @can('sell-phone')
                {{-- Item: Jual HP --}}
                <a href="{{ route('zoffline.sell-phone') }}" wire:navigate
                    class="group relative flex items-center justify-center w-12 h-12 rounded-xl shadow-sm  transition-all duration-500 {{ request()->is('zoffline/sell-phone') ? 'bg-neutral-800 text-white' : 'bg-white text-neutral-500 hover:text-white hover:bg-neutral-800 hover:translate-x-2 hover:scale-110' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-auto" viewBox="0 0 2048 2048">
                        <path d="M0 0h2048v2048H0z" fill="none" />
                        <path fill="currentColor"
                            d="M534 1664q-20 52-20 107v10q0 5 1 11H384v-128zm965 256h293v128H837l-147-148q-24-25-37-57t-13-67t13-67t38-58l813-814l538 539zm5-902l-389 390l357 358l389-390zm-187 902l65-64l-358-357l-242 242q-14 14-14 35t14 35l108 109zm-767 0q21 41 47 68t60 60H128q-27 0-50-10t-40-27t-28-41t-10-50V128q0-27 10-50t27-40t41-28t50-10h1024q27 0 50 10t40 27t28 41t10 50v752l-128 128V128H128v1792z" />
                    </svg>
                    <span
                        class="absolute left-full ml-4 px-3 py-1.5 bg-neutral-800 text-white text-xs font-bold rounded-lg opacity-0 pointer-events-none group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-200 whitespace-nowrap shadow-md z-50">
                        Transaksi Pembelian
                        <div class="absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-2 bg-neutral-800 rotate-45"></div>
                    </span>
                </a>
            @endcan

            @can('view-cek-stock')
                {{-- Item: Cek Stock --}}
                <a href="{{ route('zoffline.cekstock') }}" wire:navigate
                    class="group relative flex items-center justify-center w-12 h-12 rounded-xl shadow-sm  transition-all duration-500 {{ request()->is('zoffline/cekstock') ? 'bg-neutral-800 text-white' : 'bg-white text-neutral-500 hover:text-white hover:bg-neutral-800 hover:translate-x-2 hover:scale-110' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-auto" viewBox="0 0 15 15">
                        <path d="M0 0h15v15H0z" fill="none" />
                        <path fill="none" stroke="currentColor" stroke-linejoin="round"
                            d="M.5 3.498L7.5.5l7 2.998m-14 0l7 2.998m-7-2.998V3.5m14-.002l-7 2.998m7-2.998V11.5l-7 3m7-11.002L7.5 6.5v8m0-8.004V14.5m0-8.004L.5 3.5m7 11l-7-3v-8" />
                    </svg>
                    <span
                        class="absolute left-full ml-4 px-3 py-1.5 bg-neutral-800 text-white text-xs font-bold rounded-lg opacity-0 pointer-events-none group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-200 whitespace-nowrap shadow-md z-50">
                        Cek Stock
                        <div class="absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-2 bg-neutral-800 rotate-45"></div>
                    </span>
                </a>
            @endcan

            {{-- Item: Buka Shift --}}
            @can('view-pos')
                <a href="{{ route('zoffline.pos.open-shift') }}" wire:navigate
                    class="group relative flex items-center justify-center w-12 h-12 rounded-xl shadow-sm transition-all duration-500 {{ request()->routeIs('zoffline.pos.open-shift') ? 'bg-neutral-800 text-white' : 'bg-white text-neutral-500 hover:text-white hover:bg-neutral-800 hover:translate-x-2 hover:scale-110' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                    </svg>
                    <span
                        class="absolute left-full ml-4 px-3 py-1.5 bg-neutral-800 text-white text-xs font-bold rounded-lg opacity-0 pointer-events-none group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-200 whitespace-nowrap shadow-md z-50">
                        Buka Shift
                        <div class="absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-2 bg-neutral-800 rotate-45"></div>
                    </span>
                </a>
            @endcan

            {{-- Item: Riwayat Kasir / Closing Kasir --}}
            @can('view-pos')
                <a href="{{ route('zoffline.pos.closing-kasir') }}" wire:navigate
                    class="group relative flex items-center justify-center w-12 h-12 rounded-xl shadow-sm  transition-all duration-500 {{ request()->routeIs('zoffline.pos.closing-kasir') ? 'bg-neutral-800 text-white' : 'bg-white text-neutral-500 hover:text-white hover:bg-neutral-800 hover:translate-x-2 hover:scale-110' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-auto" viewBox="0 0 16 16">
                        <path d="M0 0h16v16H0z" fill="none" />
                        <path fill="currentColor"
                            d="M12.5 2A2.5 2.5 0 0 1 15 4.5v10a.5.5 0 0 1-.686.464l-2.31-.926l-2.31.926a.5.5 0 0 1-.28.027l-.092-.027l-2.31-.926l-2.31.926a.5.5 0 0 1-.678-.378l-.007-.086V7.36l-.985-.328l-2.33.932a.5.5 0 0 1-.678-.378L.017 7.5v-3a2.5 2.5 0 0 1 2.5-2.5h10zm0 1h-8l.019.024c.303.413.482.923.482 1.48v9.26l1.81-.725a.5.5 0 0 1 .28-.027l.091.027l2.31.925l2.32-.925a.5.5 0 0 1 .28-.027l.092.027l1.81.725v-9.26c0-.78-.595-1.42-1.36-1.49l-.144-.007zm-3 6a.5.5 0 0 1 0 1h-3a.5.5 0 0 1 0-1zm3-2a.5.5 0 0 1 0 1h-6a.5.5 0 0 1 0-1zm-10-4l-.144.007a1.503 1.503 0 0 0-1.36 1.49v2.26l1.81-.725a.5.5 0 0 1 .258-.03l.086.02l.842.28v-1.81c0-.78-.595-1.42-1.36-1.49l-.144-.007zm10 2a.5.5 0 0 1 0 1h-6a.5.5 0 0 1 0-1z" />
                    </svg>
                    <span
                        class="absolute left-full ml-4 px-3 py-1.5 bg-neutral-800 text-white text-xs font-bold rounded-lg opacity-0 pointer-events-none group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-200 whitespace-nowrap shadow-md z-50">
                        Closing Kasir
                        <div class="absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-2 bg-neutral-800 rotate-45"></div>
                    </span>
                </a>
            @endcan

        </nav>

        {{-- Profil dengan Dropdown (Alpine.js) --}}
        <div x-data="{ open: false }" class="relative mt-4" @click.outside="open = false">

            {{-- Tombol Profil --}}
            <button @click="open = !open" class="group relative focus:outline-none flex flex-col items-center">
                <div class="w-12 h-12 rounded-xl bg-white shadow-sm flex items-center justify-center text-gray-600 font-bold border-2 transition-all"
                    :class="open ? 'border-[#4E44DB]' : 'border-transparent group-hover:border-gray-300'">
                    {{ auth()->check() ? substr(auth()->user()->name, 0, 1) : 'A' }}
                </div>
            </button>

            {{-- Dropdown Pop-up (Muncul di Kanan) --}}
            <div x-show="open" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2 lg:translate-y-0 lg:-translate-x-2"
                x-transition:enter-end="opacity-100 translate-y-0 lg:translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 lg:translate-x-0"
                x-transition:leave-end="opacity-0 translate-y-2 lg:translate-y-0 lg:-translate-x-2"
                class="absolute bottom-0 left-full ml-4 w-48 bg-white border border-gray-100 rounded-2xl shadow-xl z-50 py-2"
                style="display: none;">

                {{-- Info User (Hanya jika Login) --}}
                @auth
                    <div class="px-4 py-2 border-b border-gray-100 mb-1">
                        <span class="block text-sm font-bold text-gray-800 truncate">{{ auth()->user()->name }}</span>
                        <span class="block text-[11px] text-gray-500">{{ auth()->user()->businessUnit->name ?? '' }}</span>
                        <span class="block text-[11px] text-gray-500">{{ auth()->user()->roles->first()->name }}</span>
                    </div>
                @endauth

                {{-- Menu Item --}}
                @can('view_dashboard')
                    <a href="{{ route('admin.dashboard') }}" wire:navigate
                        class="block px-4 py-2 text-sm text-gray-600 hover:text-[#4E44DB] hover:bg-blue-50 transition-colors">
                        Dashboard Admin
                    </a>
                @endcan

                <a href="/profile" wire:navigate
                    class="block px-4 py-2 text-sm text-gray-600 hover:text-[#4E44DB] hover:bg-blue-50 transition-colors">
                    Profil & Pengaturan
                </a>
                <a href="{{ route('zoffline.sell-phone-history') }}" wire:navigate
                    class="block px-4 py-2 text-sm text-gray-600 hover:text-[#4E44DB] hover:bg-blue-50 transition-colors">
                    History Sell Phone
                </a>

                <div class="h-px bg-gray-100 my-1"></div>

                <button wire:click="confirmLogout"
                    class="block px-4 py-2 text-sm text-red-500 hover:bg-red-50 font-medium transition-colors">
                    Keluar Akun
                </button>
            </div>
        </div>

    </aside>

    {{-- ==========================================
         2. MOBILE & TABLET BOTTOM BAR (Sembunyi di lg) 
         ========================================== --}}
    <nav
        class="lg:hidden fixed bottom-0 left-0 w-full z-50 bg-white/90 backdrop-blur-lg border-t border-gray-100 flex items-center justify-around h-18 p-2 shadow-[0_-10px_40px_rgba(0,0,0,0.05)]">

        {{-- POS --}}
        <a href="{{ route('zoffline.pos') }}" wire:navigate
            class="flex flex-col items-center justify-center w-16 h-full gap-1 text-[#4E44DB]">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-auto" viewBox="0 0 24 24">
                <path d="M0 0h24v24H0z" fill="none" />
                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M9 20h3m3 0h-3m0 0v-3m0 0h7a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2z" />
            </svg>
            <span class="text-[10px] font-semibold">POS</span>
        </a>

        @can('trade-in')
            {{-- Tukar Tambah --}}
            <a href="{{ route('zoffline.trade-in') }}" wire:navigate
                class="flex flex-col items-center justify-center w-16 h-full gap-1 text-gray-400 hover:text-emerald-600 transition-colors">
                <img src="{{ asset('assets/png/trd.png') }}" class="w-8 h-auto" alt="">
                <span class="text-[10px] font-medium">Trade-In</span>
            </a>
        @endcan

        @can('sell-phone')
            {{-- Jual HP --}}
            <a href="{{ route('zoffline.sell-phone') }}" wire:navigate
                class="flex flex-col items-center justify-center w-16 h-full gap-1 text-gray-400 hover:text-violet-600 transition-colors">
                <img src="{{ asset('assets/png/sellphone.png') }}" class="w-8 h-auto" alt="">
                <span class="text-[10px] font-medium">Jual HP</span>
            </a>
        @endcan

        @can('view-cek-stock')
            <a href="{{ route('zoffline.cekstock') }}" wire:navigate
                class="flex flex-col items-center justify-center w-16 h-full gap-1 text-gray-400 hover:text-violet-600 transition-colors">
                <img src="{{ asset('assets/png/stok.png') }}" class="w-8 h-auto" alt="">
                <span class="text-[10px] font-medium">Cek Stock</span>
            </a>
        @endcan

        @can('view-riwayat-kasir')
            <a href="{{ route('zoffline.riwayat-kasir') }}" wire:navigate
                class="flex flex-col items-center justify-center w-16 h-full gap-1 text-gray-400 hover:text-blue-600 transition-colors">
                <img src="{{ asset('assets/png/rk.png') }}" class="w-8 h-auto" alt="">
                <span class="text-[10px] font-medium">Shift</span>
            </a>
        @endcan

        {{-- Profil dengan Dropdown (Alpine.js) --}}
        <div x-data="{ open: false }" class="relative flex flex-col items-center justify-center w-16 h-full"
            @click.outside="open = false">

            <button @click="open = !open"
                class="flex flex-col items-center gap-1 focus:outline-none transition-colors"
                :class="open ? 'text-gray-800' : 'text-gray-400 hover:text-gray-800'">
                <div class="w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center font-bold text-xs transition-colors"
                    :class="open ? 'bg-gray-300 text-gray-800' : 'text-gray-500'">
                    {{ auth()->check() ? substr(auth()->user()->name, 0, 1) : 'A' }}
                </div>
                <span class="text-[10px] font-medium">Profil</span>
            </button>

            {{-- Dropdown Pop-up (Muncul ke Atas) --}}
            <div x-show="open" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
                class="absolute bottom-full right-0 mb-4 w-48 bg-white border border-gray-100 rounded-2xl shadow-[0_-5px_25px_rgba(0,0,0,0.1)] z-50 py-2"
                style="display: none;">

                {{-- Info User --}}
                @auth
                    <div class="px-4 py-2 border-b border-gray-100 mb-1 text-left">
                        <span class="block text-sm font-bold text-gray-800 truncate">{{ auth()->user()->name }}</span>
                        <span class="block text-[11px] text-gray-500">{{ auth()->user()->roles->first()->name }}</span>
                    </div>
                @endauth

                {{-- Menu Item --}}
                @if (auth()->check() && !auth()->user()->hasRole('user'))
                    <a href="{{ route('admin.dashboard') }}" wire:navigate
                        class="block px-4 py-2 text-sm text-left text-gray-600 hover:text-[#4E44DB] hover:bg-blue-50 transition-colors">
                        Dashboard Admin
                    </a>
                @endif
                <a href="/profile" wire:navigate
                    class="block px-4 py-2 text-sm text-left text-gray-600 hover:text-[#4E44DB] hover:bg-blue-50 transition-colors">
                    Profil & Pengaturan
                </a>

                <div class="h-px bg-gray-100 my-1"></div>

                <button wire:click="confirmLogout"
                    class="block px-4 py-2 text-sm text-left text-red-500 hover:bg-red-50 font-medium transition-colors">
                    Keluar Akun
                </button>
            </div>
        </div>

    </nav>
</div>
