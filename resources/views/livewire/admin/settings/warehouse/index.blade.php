<div class="space-y-6">
    {{-- Header --}}
    {{-- <div>
        <h1 class="text-2xl font-bold text-gray-800">List Branch</h1>
        <p class="text-gray-500 mt-1">Daftar Branch.</p>
    </div> --}}

    {{-- Main Settings Box --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 max-w-3xl">
        <div class="flex flex-col md:flex-row justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">List Warehouse</h2>
            <button wire:click="synchronizeWarehouse"
                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                Synchronize
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 bg-gray-50 uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-4">Nama</th>
                        {{-- <th class="px-6 py-4 text-right">Aksi</th> --}}
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($warehouse as $item)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-bold text-gray-900">{{ $item['name'] }}</span>
                            </td>
                            {{-- <td class="px-6 py-4 text-right">
                                <a wire:navigate class="text-[#1c69d4] hover:text-[#3f36b8] font-semibold text-sm">
                                    Detail →
                                </a>
                            </td> --}}
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <p class="text-sm font-medium text-gray-400">Tidak ada pengajuan sell phone
                                        ditemukan</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 max-w-3xl">
        <div class="flex flex-col md:flex-row justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">List Branch</h2>
            <button wire:click="synchronizeBranch" wire:loading.attr="disabled"
                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                Synchronize
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 bg-gray-50 uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-4">Nama</th>
                        {{-- <th class="px-6 py-4 text-right">Aksi</th> --}}
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($branch as $item)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-bold text-gray-900">{{ $item['name'] }}</span>
                            </td>
                            {{-- <td class="px-6 py-4 text-right">
                                <a wire:navigate class="text-[#1c69d4] hover:text-[#3f36b8] font-semibold text-sm">
                                    Detail →
                                </a>
                            </td> --}}
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <p class="text-sm font-medium text-gray-400">Tidak ada pengajuan sell phone
                                        ditemukan</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
