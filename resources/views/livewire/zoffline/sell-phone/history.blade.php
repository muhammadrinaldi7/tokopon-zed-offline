<div class="max-w-7xl mx-auto p-2  md:p-6 min-h-screen">
    {{-- Header Navigation --}}
    <div class="flex gap-2">
        <a href="/"
            class="bg-neutral-500 text-white px-3 flex justify-center items-center rounded-md hover:bg-neutral-600 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                class="size-6 md:size-8 rotate-180">
                <path fill-rule="evenodd"
                    d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z"
                    clip-rule="evenodd" />
            </svg>
        </a>
        <div
            class="w-full flex gap-4 items-center bg-linear-to-r from-[#0097FF] via-[#4E44DB] to-[#013559] py-3 px-6 rounded-md shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-8 text-white">
                <path
                    d="M2.25 2.25a.75.75 0 0 0 0 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 0 0-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 0 0 0-1.5H5.378A2.25 2.25 0 0 1 7.5 15h11.218a.75.75 0 0 0 .674-.421 60.358 60.358 0 0 0 2.96-7.228.75.75 0 0 0-.525-.965A60.864 60.864 0 0 0 5.68 4.509l-.232-.867A1.875 1.875 0 0 0 3.636 2.25H2.25ZM3.75 20.25a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM16.5 20.25a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" />
            </svg>
            <h1 class="text-white text-xl md:text-4xl font-bold">Sell Phone</h1>
        </div>
    </div>
    <div class="mb-8 mt-2 flex gap-2  items-center">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
            class="size-5 md:size-6 text-gray-500">
            <path fill-rule="evenodd"
                d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z"
                clip-rule="evenodd" />
        </svg>

        <p class="text-gray-500 text-xs md:text-sm">Pantau status jual HP Anda.</p>
    </div>
    <div class="space-y-4">
        @forelse($sells as $item)
            <a href="{{ route('sell-phone.show', $item) }}" wire:navigate
                class="block bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md hover:border-gray-200 transition">
                <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-16 h-16 bg-gray-50 rounded-xl flex items-center justify-center p-2 border border-gray-100 shrink-0">
                            <img src="{{ $item->getFirstMediaUrl('photos', 'thumb') }}"
                                class="object-contain max-h-full max-w-full">
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Barang
                                Dijual</p>
                            <h3 class="font-bold text-gray-900">{{ $item->phone_brand }} {{ $item->phone_model }}
                            </h3>
                        </div>
                    </div>

                    <div class="flex flex-col md:items-end justify-center mt-2 md:mt-0">
                        @php
                            $statusColors = [
                                'PENDING' => 'bg-amber-100 text-amber-800',
                                'OFFERED' => 'bg-blue-100 text-blue-800',
                                'WAITING_FOR_DEVICE' => 'bg-purple-100 text-purple-800',
                                'INSPECTING' => 'bg-indigo-100 text-indigo-800',
                                'PAYING' => 'bg-teal-100 text-teal-800',
                                'COMPLETED' => 'bg-emerald-100 text-emerald-800',
                                'CANCELLED' => 'bg-rose-100 text-rose-800',
                            ];
                            $statusLabels = [
                                'PENDING' => 'Menunggu Taksiran Admin',
                                'OFFERED' => 'Penawaran Tersedia',
                                'WAITING_FOR_DEVICE' => 'Menunggu HP Lama Anda via Kurir',
                                'INSPECTING' => 'Inspeksi Fisik Oleh Admin',
                                'PAYING' => 'Menunggu Pembayaran Akhir',
                                'COMPLETED' => 'Selesai',
                                'CANCELLED' => 'Dibatalkan',
                            ];
                        @endphp
                        <span
                            class="px-3 py-1 md:py-1.5 text-[11px] font-bold rounded-lg {{ $statusColors[$item->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $statusLabels[$item->status] ?? $item->status }}
                        </span>
                        @if ($item->appraised_value)
                            <p class="text-sm font-bold text-emerald-600 mt-2">Nilai Taksiran: Rp
                                {{ number_format($item->appraised_value, 0, ',', '.') }}</p>
                            @if($item->is_price_adjusted)
                                <p class="text-[10px] font-bold text-amber-500 mt-1 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Nominal disesuaikan Admin
                                </p>
                            @endif
                        @else
                            <p class="text-xs text-gray-400 mt-2 italic">Belum ada taksiran harga</p>
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <div
                class="bg-white rounded-2xl p-10 shadow-sm border border-gray-100 text-center flex flex-col items-center justify-center">
                <img src="{{ asset('assets/png/tradein.png') }}" class="w-70 h-auto" alt="">
                <h3 class="font-bold text-gray-900 text-lg">Kamu belum menjual HP</h3>
                <p class="text-gray-500 mt-1 text-xs md:text-sm">Yuk jual HP lamamu sekarang.</p>
                {{-- <a href="{{ route('products.index') }}" wire:navigate
                    class="inline-block mt-4 bg-[#4E44DB] text-white px-6 py-2.5 rounded-xl font-bold hover:bg-[#3f36b8] transition">Mulai
                    Ajukan</a> --}}
            </div>
        @endforelse
    </div>

</div>
