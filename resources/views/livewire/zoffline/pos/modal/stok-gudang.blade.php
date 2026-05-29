{{-- MODAL CEK STOK GUDANG --}}
@if ($showStockModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm transition-opacity"
        wire:transition.fade>

        <div class="bg-white rounded-xl shadow-xl w-full max-w-md overflow-hidden transform transition-all"
            wire:click.away="closeStockModal">

            {{-- Header Modal --}}
            <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="text-sm font-bold text-gray-800">Ketersediaan Stok</h3>
                    <p class="text-[11px] text-gray-500 mt-0.5">{{ $stockModalItemTitle }}</p>
                </div>
                <button wire:click="closeStockModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            {{-- Body Modal --}}
            <div class="p-5 max-h-[60vh] overflow-y-auto">
                @if (count($stockModalData) > 0)
                    <ul class="space-y-2.5">
                        @foreach ($stockModalData as $data)
                            <li
                                class="flex justify-between items-center p-3 rounded-lg border 
                            {{ $data['is_current_user_warehouse'] ? 'bg-indigo-50/50 border-indigo-200' : 'bg-white border-gray-100' }}">

                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 {{ $data['is_current_user_warehouse'] ? 'text-indigo-600' : 'text-gray-400' }}"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    {{-- Warna teks beda untuk gudang user saat ini --}}
                                    <span
                                        class="text-sm font-semibold {{ $data['is_current_user_warehouse'] ? 'text-indigo-700' : 'text-gray-700' }}">
                                        {{ $data['warehouse_name'] }}
                                        @if ($data['is_current_user_warehouse'])
                                            <span
                                                class="ml-1 text-[10px] font-normal bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded">Gudang
                                                Anda</span>
                                        @endif
                                    </span>
                                </div>

                                <div class="text-right">
                                    <span
                                        class="text-sm font-bold {{ $data['is_current_user_warehouse'] ? 'text-indigo-700' : 'text-gray-900' }}">
                                        {{ $data['stock'] }}
                                    </span>
                                    <span
                                        class="text-[11px] {{ $data['is_current_user_warehouse'] ? 'text-indigo-500' : 'text-gray-500' }}">Unit</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center py-6">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <p class="text-sm text-gray-500">Stok tidak tersedia di semua gudang.</p>
                    </div>
                @endif
            </div>

        </div>
    </div>
@endif
