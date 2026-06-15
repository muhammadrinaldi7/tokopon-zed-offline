<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Message;
use App\Models\Conversation;
use App\Events\MessageSent;

new class extends Component {
    public $unreadCount = 0;

    #[On('echo-private:user.{userId},MessageSent')]
    public function updateUnreadCount($event)
    {
        $this->unreadCount = Message::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();
    }

    public function mount()
    {
        $this->unreadCount = Message::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();
    }
};
?>
<aside
    class="w-[280px] lg:w-64 bg-white border-r border-gray-100 flex flex-col h-screen shrink-0 fixed lg:sticky top-0 left-0 z-30 transform transition-transform duration-300 ease-in-out lg:translate-x-0 shadow-2xl lg:shadow-none"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
    <div class="p-8 flex flex-col items-center border-b border-gray-50 relative">
        <button @click="sidebarOpen = false"
            class="lg:hidden absolute top-4 right-4 p-2 text-gray-400 hover:text-gray-600 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        {{-- Avatar --}}
        <div
            class="w-16 h-16 rounded-full bg-[#4E44DB] text-white flex items-center justify-center text-xl font-bold mb-3 shadow-md shadow-[#4E44DB]/20">
            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 2)) }}
        </div>
        <h3 class="font-bold text-gray-800 text-sm">{{ auth()->user()->name ?? 'Admin User' }}</h3>
        <p class="text-[10px] text-gray-400 mt-0.5 capitalize">Role:
            {{ auth()->user()->roles->pluck('name')->first() ?? 'Member' }}</p>
    </div>

    <nav class="flex-1 py-6 px-4 space-y-1.5 overflow-y-auto">
        @php
            $activeClass = 'bg-[#eff2ff] text-[#4E44DB] font-semibold';
            $inactiveClass = 'text-gray-500 hover:bg-gray-50 hover:text-gray-700 font-medium';
            $activeIconClass = 'opacity-90';
            $inactiveIconClass = 'opacity-70 text-gray-400 font-normal';
        @endphp

        <a href="/admin/dashboard" wire:navigate
            class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.dashboard') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 {{ request()->routeIs('admin.dashboard') ? $activeIconClass : $inactiveIconClass }}"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Dashboard
        </a>

        @role('cs')
            <a href="/admin/cs-chat" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.cs-chat') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.cs-chat') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <span class="flex-1">Chat CS</span>
                @if ($unreadCount > 0)
                    <span
                        class="ml-auto bg-red-500 text-white text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center">
                        {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                    </span>
                @endif
            </a>
        @endrole


        <a href="#"
            class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-colors cursor-pointer {{ request()->is('admin/services') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 {{ request()->is('admin/services') ? $activeIconClass : $inactiveIconClass }}"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Status Servis
        </a>

        <a href="{{ route('admin.promos.index') }}" wire:navigate
            class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.promos.*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 {{ request()->routeIs('admin.promos.*') ? $activeIconClass : $inactiveIconClass }}"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            Promo & Voucher
        </a>

        <a href="{{ route('admin.manual-discount.index') }}" wire:navigate
            class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.manual-discount.*') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 {{ request()->routeIs('admin.manual-discount.*') ? $activeIconClass : $inactiveIconClass }}"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Promo Internal Diskon
        </a>

        <a href="#"
            class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-colors cursor-pointer {{ request()->is('admin/tips') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 {{ request()->is('admin/tips') ? $activeIconClass : $inactiveIconClass }}"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            Tips Gadget
        </a>

        @hasanyrole('admin|super admin')
            <div class="px-4 mt-8 mb-2">
                <p class="text-[10px] font-bold tracking-wider text-gray-400 uppercase">Quality Control</p>
            </div>

            <a href="{{ route('admin.qc.device-search') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.qc.device*') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.qc.device*') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Cari Device (IMEI)
            </a>

            <a href="{{ route('admin.qc.templates') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.qc.templates') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.qc.templates') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                QC Templates
            </a>
        @endhasanyrole

        @hasanyrole('admin|super admin')
            <div class="px-4 mt-8 mb-2">
                <p class="text-[10px] font-bold tracking-wider text-gray-400 uppercase">Administrator</p>
            </div>

            <a href="{{ route('admin.orders.management') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.orders.management') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.orders.management') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                Kelola Pesanan
            </a>

            <a href="{{ route('admin.sales-orders.index') }}" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.sales-orders.*') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.sales-orders.*') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Pesanan & Tagihan (SO)
            </a>

            <a href="/admin/users" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.users') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.users') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Kelola Pengguna
            </a>

            <a href="/admin/roles" wire:navigate
                class="flex items-center gap-3 px-4 py-3 rounded-2xl text-sm transition-colors cursor-pointer {{ request()->routeIs('admin.roles') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 {{ request()->routeIs('admin.roles') ? $activeIconClass : $inactiveIconClass }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Kelola Role & Akses
            </a>
        @endhasanyrole
    </nav>

    <div class="p-6">
        <form action="{{ route('logout') }}" method="POST" class="w-full">
            @csrf
            <button type="submit"
                class="flex items-center gap-3 px-4 py-3 rounded-2xl text-red-500 hover:bg-red-50 font-semibold text-sm transition-colors w-full cursor-pointer">
                <svg class="w-5 h-5 opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Keluar
            </button>
        </form>
    </div>
</aside>
