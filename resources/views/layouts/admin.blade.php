<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Admin Dashboard - TokoPun' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('zlogoblack.svg') }}" sizes="any">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<style>
    /* Hide scrollbar for Chrome, Safari and Opera */
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    /* Hide scrollbar for IE, Edge and Firefox */
    .no-scrollbar {
        -ms-overflow-style: none;
        /* IE and Edge */
        scrollbar-width: none;
        /* Firefox */
    }
</style>

<body class="bg-neutral-50 text-neutral-800 font-sans antialiased overflow-hidden" x-data="{ sidebarOpen: false, sidebarCollapsed: false }">
    <div class="flex h-screen w-full relative overflow-hidden">
        {{-- Overlay for mobile --}}
        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
            class="fixed inset-0 bg-neutral-900/50 z-20 lg:hidden" style="display: none;" x-cloak></div>

        {{-- Sidebar --}}
        <livewire:admin-sidebar />

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col h-full w-full overflow-hidden">
            <x-admin.topbar />

            <main class="flex-1 overflow-y-auto overflow-x-hidden p-4 lg:px-8 pb-8 scroll-smooth">
                {{ $slot }}
            </main>
        </div>
    </div>

    <livewire:confirm-modal />
    <x-toast />
    <livewire:toast-notification />
</body>

</html>
