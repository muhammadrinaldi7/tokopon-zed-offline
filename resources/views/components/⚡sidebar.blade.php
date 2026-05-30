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
    <aside
        class="hidden lg:flex fixed top-0 left-0 z-50 h-screen w-20 bg-white border-r border-gray-100 flex-col items-center py-6 shadow-xs">

        {{-- Logo --}}
        <a href="/" wire:navigate
            class="w-10 h-10 rounded-xl bg-linear-to-br from-neutral-900 via-neutral-600 to-neutral-800 flex items-center justify-center text-white font-bold text-xl shadow-md mb-8 shrink-0">
            <img src="{{ asset('assets/png/zlogo.png') }}" alt="Zpos Logo" class="w-8 h-8">
        </a>

        {{-- Menu Navigasi --}}
        <nav class="flex-1 flex flex-col gap-4 w-full items-center">

            {{-- Item: POS --}}
            <a href="{{ route('zoffline.pos') }}" wire:navigate
                class="group relative flex items-center justify-center w-12 h-12 rounded-2xl text-gray-500 hover:text-[#4E44DB] hover:bg-blue-50 transition-all duration-200">
                <img src="{{ asset('assets/png/pos2.png') }}" class="w-8 h-auto" alt="">
                <span
                    class="absolute left-full ml-4 px-3 py-1.5 bg-gray-800 text-white text-xs font-bold rounded-lg opacity-0 pointer-events-none group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-200 whitespace-nowrap shadow-md z-50">
                    Point of Sale
                    <div class="absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-2 bg-gray-800 rotate-45"></div>
                </span>
            </a>

            {{-- Item: Tukar Tambah --}}
            @can('trade-in')
                <a href="{{ route('zoffline.trade-in') }}" wire:navigate
                    class="group relative flex items-center justify-center w-12 h-12 rounded-2xl text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 transition-all duration-200">
                    <img src="{{ asset('assets/png/trd.png') }}" class="w-8 h-auto" alt="">

                    <span
                        class="absolute left-full ml-4 px-3 py-1.5 bg-gray-800 text-white text-xs font-bold rounded-lg opacity-0 pointer-events-none group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-200 whitespace-nowrap shadow-md z-50">
                        Tukar Tambah
                        <div class="absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-2 bg-gray-800 rotate-45"></div>
                    </span>
                </a>
            @endcan

            @can('sell-phone')
                {{-- Item: Jual HP --}}
                <a href="{{ route('zoffline.sell-phone') }}" wire:navigate
                    class="group relative flex items-center justify-center w-12 h-12 rounded-2xl text-gray-500 hover:text-violet-600 hover:bg-violet-50 transition-all duration-200">
                    <img src="{{ asset('assets/png/sellphone.png') }}" class="w-8 h-auto" alt="">
                    <span
                        class="absolute left-full ml-4 px-3 py-1.5 bg-gray-800 text-white text-xs font-bold rounded-lg opacity-0 pointer-events-none group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-200 whitespace-nowrap shadow-md z-50">
                        Jual HP Bekas
                        <div class="absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-2 bg-gray-800 rotate-45"></div>
                    </span>
                </a>
            @endcan

            {{-- Item: Cek Stock --}}
            <a href="{{ route('zoffline.cekstock') }}" wire:navigate
                class="group relative flex items-center justify-center w-12 h-12 rounded-2xl text-gray-500 hover:text-violet-600 hover:bg-violet-50 transition-all duration-200">
                <img src="{{ asset('assets/png/stok.png') }}" class="w-8 h-auto" alt="">
                <span
                    class="absolute left-full ml-4 px-3 py-1.5 bg-gray-800 text-white text-xs font-bold rounded-lg opacity-0 pointer-events-none group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-200 whitespace-nowrap shadow-md z-50">
                    Cek Stock
                    <div class="absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-2 bg-gray-800 rotate-45"></div>
                </span>
            </a>

            {{-- Item: Riwayat Kasir --}}
            <a href="{{ route('zoffline.riwayat-kasir') }}" wire:navigate
                class="group relative flex items-center justify-center w-12 h-12 rounded-2xl text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200">
                <img src="{{ asset('assets/png/rk.png') }}" class="w-8 h-auto" alt="">
                <span
                    class="absolute left-full ml-4 px-3 py-1.5 bg-gray-800 text-white text-xs font-bold rounded-lg opacity-0 pointer-events-none group-hover:opacity-100 -translate-x-2 group-hover:translate-x-0 transition-all duration-200 whitespace-nowrap shadow-md z-50">
                    Shift
                    <div class="absolute top-1/2 -left-1 -translate-y-1/2 w-2 h-2 bg-gray-800 rotate-45"></div>
                </span>
            </a>
        </nav>

        {{-- Profil dengan Dropdown (Alpine.js) --}}
        <div x-data="{ open: false }" class="relative mt-4" @click.outside="open = false">

            {{-- Tombol Profil --}}
            <button @click="open = !open" class="group relative focus:outline-none flex flex-col items-center">
                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-bold border-2 transition-all"
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
                        <span class="block text-[11px] text-gray-500">{{ auth()->user()->roles->first()->name }}</span>
                    </div>
                @endauth

                {{-- Menu Item --}}
                @if (auth()->check() && !auth()->user()->hasRole('user'))
                    <a href="{{ route('admin.dashboard') }}" wire:navigate
                        class="block px-4 py-2 text-sm text-gray-600 hover:text-[#4E44DB] hover:bg-blue-50 transition-colors">
                        Dashboard Admin
                    </a>
                @endif
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
            <img src="{{ asset('assets/png/pos2.png') }}" class="w-8 h-auto" alt="">
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

        <a href="{{ route('zoffline.cekstock') }}" wire:navigate
            class="flex flex-col items-center justify-center w-16 h-full gap-1 text-gray-400 hover:text-violet-600 transition-colors">
            <img src="{{ asset('assets/png/stok.png') }}" class="w-8 h-auto" alt="">
            <span class="text-[10px] font-medium">Cek Stock</span>
        </a>

        <a href="{{ route('zoffline.riwayat-kasir') }}" wire:navigate
            class="flex flex-col items-center justify-center w-16 h-full gap-1 text-gray-400 hover:text-blue-600 transition-colors">
            <img src="{{ asset('assets/png/rk.png') }}" class="w-8 h-auto" alt="">
            <span class="text-[10px] font-medium">Shift</span>
        </a>

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
