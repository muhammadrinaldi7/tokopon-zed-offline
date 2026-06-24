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
         1. DESKTOP NAVBAR (Hanya tampil di layar besar / lg) 
         ========================================== --}}
    <nav class="hidden lg:block fixed top-0 inset-x-0 z-50 w-full h-20 px-6 pointer-events-none">
        <div class="max-w-7xl mx-auto w-full h-full flex flex-row items-center justify-between pointer-events-auto">

            {{-- Kiri: Home --}}
            <div>
                <a href="/" wire:navigate
                    class="group relative flex items-center justify-center w-12 h-12 rounded-xl shadow-sm transition-all duration-500 {{ request()->is('/') ? 'bg-neutral-800 text-white' : 'bg-white text-neutral-500 hover:text-white hover:bg-neutral-800 hover:-translate-y-1 hover:scale-105' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-auto" viewBox="0 0 24 24">
                        <path d="M0 0h24v24H0z" fill="none" />
                        <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M9 20h3m3 0h-3m0 0v-3m0 0h7a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2z" />
                    </svg>
                    <span
                        class="absolute top-full mt-4 px-3 py-1.5 bg-neutral-800 text-white text-xs font-bold rounded-lg opacity-0 pointer-events-none group-hover:opacity-100 translate-y-2 group-hover:translate-y-0 transition-all duration-200 whitespace-nowrap shadow-md z-50">
                        Home
                        <div class="absolute -top-1 left-1/2 -translate-x-1/2 w-2 h-2 bg-neutral-800 rotate-45"></div>
                    </span>
                </a>
            </div>

            {{-- Kanan: Profil dengan Dropdown (Alpine.js) --}}
            <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                <button @click="open = !open" class="group relative focus:outline-none flex flex-col items-center">
                    <div class="w-12 h-12 rounded-xl bg-white shadow-sm flex items-center justify-center text-gray-600 font-bold border-2 transition-all"
                        :class="open ? 'border-[#4E44DB]' : 'border-transparent group-hover:border-gray-300'">
                        {{ auth()->check() ? substr(auth()->user()->name, 0, 1) : 'A' }}
                    </div>
                </button>

                <div x-show="open" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2 lg:translate-y-2 lg:translate-x-0"
                    x-transition:enter-end="opacity-100 translate-y-0 lg:translate-x-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 lg:translate-x-0"
                    x-transition:leave-end="opacity-0 translate-y-2 lg:translate-y-2 lg:translate-x-0"
                    class="absolute top-full right-0 mt-4 w-48 bg-white border border-gray-100 rounded-2xl shadow-xl z-50 py-2"
                    style="display: none;">

                    @auth
                        <div class="px-4 py-2 border-b border-gray-100 mb-1">
                            <span class="block text-sm font-bold text-gray-800 truncate">{{ auth()->user()->name }}</span>
                            <span
                                class="block text-[11px] text-gray-500">{{ auth()->user()->businessUnit->name ?? '' }}</span>
                            <span class="block text-[11px] text-gray-500">{{ auth()->user()->roles->first()->name }}</span>
                        </div>
                    @endauth

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
                        class="block w-full text-left px-4 py-2 text-sm text-red-500 hover:bg-red-50 font-medium transition-colors">
                        Keluar Akun
                    </button>
                </div>
            </div>
    </nav>

    {{-- ==========================================
         2. MOBILE & TABLET BOTTOM BAR (Sembunyi di lg) 
         ========================================== --}}
    <nav
        class="lg:hidden fixed bottom-0 left-0 w-full z-50 bg-white/90 backdrop-blur-lg border-t border-gray-100 flex items-center justify-around h-18 p-2 shadow-[0_-10px_40px_rgba(0,0,0,0.05)]">

        {{-- Kiri: Home --}}
        <a href="/" wire:navigate
            class="flex flex-col items-center justify-center w-14 h-full gap-1 {{ request()->is('/') ? 'text-[#4E44DB]' : 'text-gray-400 hover:text-[#4E44DB] transition-colors' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-auto" viewBox="0 0 24 24">
                <path d="M0 0h24v24H0z" fill="none" />
                <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M9 20h3m3 0h-3m0 0v-3m0 0h7a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2z" />
            </svg>
            <span class="text-[10px] font-semibold">Home</span>
        </a>

        {{-- Profil dengan Dropdown (Alpine.js) --}}
        <div x-data="{ open: false }" class="relative flex flex-col items-center justify-center w-16 h-full"
            @click.outside="open = false">

            <button @click="open = !open" class="flex flex-col items-center gap-1 focus:outline-none transition-colors"
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
                @can('sell-phone-history')
                    {{-- Item: Jual HP --}}
                    <a href="{{ route('zoffline.sell-phone-history') }}" wire:navigate
                        class="block px-4 py-2 text-sm text-left text-gray-600 hover:text-[#4E44DB] hover:bg-blue-50 transition-colors">
                        Riwayat Pembelian
                    </a>
                @endcan
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
