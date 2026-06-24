<div class="space-y-6">
    {{-- Header --}}
    {{-- <div>
        <h1 class="text-2xl font-bold text-gray-800">List Branch</h1>
        <p class="text-gray-500 mt-1">Daftar Branch.</p>
    </div> --}}

    {{-- Tabs --}}
    <div class="flex gap-4 border-b border-gray-200 mb-6 px-2">
        @foreach ($businessUnits as $bu)
            <button wire:click="$set('activeTab', {{ $bu->id }})"
                class="px-4 py-3 font-bold text-sm transition-colors border-b-2 {{ $activeTab == $bu->id ? 'border-[#1c69d4] text-[#1c69d4]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                {{ $bu->name }}
            </button>
        @endforeach
    </div>

    {{-- Main Settings Box --}}
    <div class="bg-white rounded-2xl shadow-sm border border-neutral-100-sm border border-gray-100 p-6 max-w-3xl">
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
                    @forelse ($warehouse->where('business_unit_id', $activeTab) as $item)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-900">{{ $item->name }}</span>
                                    <div class="flex gap-1">
                                        @if ($item->businessUnit)
                                            <span
                                                class="bg-blue-100 text-blue-800 text-[10px] font-bold px-1.5 py-0.5 rounded">{{ $item->businessUnit->name }}</span>
                                        @else
                                            <span
                                                class="bg-gray-100 text-gray-800 text-[10px] font-bold px-1.5 py-0.5 rounded">Unassigned</span>
                                        @endif
                                    </div>
                                </div>
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
    <div class="bg-white rounded-2xl shadow-sm border border-neutral-100-sm border border-gray-100 p-6 max-w-3xl">
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
                    @forelse ($branch->where('business_unit_id', $activeTab) as $item)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-900">{{ $item->name }}</span>
                                    <div class="flex gap-1">
                                        @if ($item->businessUnit)
                                            <span
                                                class="bg-blue-100 text-blue-800 text-[10px] font-bold px-1.5 py-0.5 rounded">{{ $item->businessUnit->name }}</span>
                                        @else
                                            <span
                                                class="bg-gray-100 text-gray-800 text-[10px] font-bold px-1.5 py-0.5 rounded">Unassigned</span>
                                        @endif
                                    </div>
                                </div>
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
