<div class="max-w-7xl mx-auto p-2  md:p-6 min-h-screen">
    <div class="flex gap-2">
        <a href="/"
            class="bg-neutral-500 hover:bg-neutral-600 transition-colors text-white px-3 flex justify-center items-center rounded-md">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                class="size-6 md:size-8 rotate-180">
                <path fill-rule="evenodd"
                    d="M4.5 5.653c0-1.427 1.529-2.33 2.779-1.643l11.54 6.347c1.295.712 1.295 2.573 0 3.286L7.28 19.99c-1.25.687-2.779-.217-2.779-1.643V5.653Z"
                    clip-rule="evenodd" />
            </svg>
        </a>
        <div
            class="w-full flex gap-4  items-center bg-linear-to-r from-[#0097FF] via-[#4E44DB] to-[#013559] py-3 px-6 rounded-md shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-8 text-white">
                <path
                    d="M2.25 2.25a.75.75 0 0 0 0 1.5h1.386c.17 0 .318.114.362.278l2.558 9.592a3.752 3.752 0 0 0-2.806 3.63c0 .414.336.75.75.75h15.75a.75.75 0 0 0 0-1.5H5.378A2.25 2.25 0 0 1 7.5 15h11.218a.75.75 0 0 0 .674-.421 60.358 60.358 0 0 0 2.96-7.228.75.75 0 0 0-.525-.965A60.864 60.864 0 0 0 5.68 4.509l-.232-.867A1.875 1.875 0 0 0 3.636 2.25H2.25ZM3.75 20.25a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM16.5 20.25a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" />
            </svg>

            <div class="flex justify-between items-center w-full">
                <h1 class="text-white text-xl md:text-4xl font-bold">Pesanan Saya</h1>

            </div>
        </div>

    </div>
    {{-- Status Filter --}}
    <div class="flex  gap-2 mb-8 mt-4 overflow-hidden overflow-x-auto no-scrollbar ">
        @foreach (['' => 'Semua', 'PENDING' => 'Menunggu', 'PROCESSING' => 'Diproses', 'SHIPPED' => 'Dikirim', 'COMPLETED' => 'Selesai', 'CANCELLED' => 'Dibatalkan'] as $value => $label)
            <button wire:click="$set('statusFilter', '{{ $value }}')" @class([
                'px-4 py-2 rounded-xl text-sm font-semibold transition-all',
                'bg-[#4E44DB] text-white shadow-md shadow-[#4E44DB]/20' =>
                    $statusFilter === $value,
                'bg-white text-gray-600 border border-gray-200 hover:border-[#4E44DB]/30' =>
                    $statusFilter !== $value,
            ])>
                {{ $label }}
            </button>
        @endforeach
    </div>
    <div class="">
        {{-- Orders List --}}
        @forelse ($orders as $order)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-4 overflow-hidden">
                {{-- Order Header --}}
                <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between bg-gray-50/30">
                    <div>
                        <span class="font-bold text-gray-800 text-sm">{{ $order->order_number }}</span>
                        <span class="text-xs text-gray-400 ml-2">{{ $order->created_at->format('d M Y, H:i') }}</span>
                    </div>
                    @php
                        $statusColors = [
                            'PENDING' => 'bg-amber-50 text-amber-600 border-amber-100',
                            'PROCESSING' => 'bg-blue-50 text-blue-600 border-blue-100',
                            'SHIPPED' => 'bg-purple-50 text-purple-600 border-purple-100',
                            'COMPLETED' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                            'CANCELLED' => 'bg-rose-50 text-rose-600 border-rose-100',
                        ];
                        $statusLabels = [
                            'PENDING' => 'Menunggu Bayar',
                            'PROCESSING' => 'Diproses',
                            'SHIPPED' => 'Dikirim',
                            'COMPLETED' => 'Selesai',
                            'CANCELLED' => 'Dibatalkan',
                        ];
                    @endphp
                    <span
                        class="text-xs font-bold px-3 py-1 rounded-lg border {{ $statusColors[$order->order_status] ?? 'bg-gray-50 text-gray-600' }}">
                        {{ $statusLabels[$order->order_status] ?? $order->order_status }}
                    </span>
                </div>

                {{-- Items Preview (max 2) --}}
                <div class="px-6 py-4">
                    @foreach ($order->items->take(2) as $item)
                        @php
                            $variant = $item->variant;
                            $product = $variant?->product;
                            $imgUrl = $product
                                ? ($product->getFirstMediaUrl('cover', 'thumb') ?:
                                $product->getFirstMediaUrl('gallery', 'thumb'))
                                : '';
                        @endphp
                        <div class="flex gap-3 items-center {{ !$loop->last ? 'mb-3' : '' }}">
                            <div
                                class="w-12 h-12 rounded-lg bg-gray-50 overflow-hidden border border-gray-100 shrink-0 flex items-center justify-center">
                                @if ($imgUrl)
                                    <img src="{{ $imgUrl }}" alt="" class="w-full h-full object-cover">
                                @else
                                    <svg class="w-5 h-5 text-gray-300" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-gray-800 text-sm truncate">{{ $product?->name ?? '-' }}
                                </p>
                                <p class="text-xs text-gray-400">{{ $item->qty }}x · Rp
                                    {{ number_format($item->price_at_checkout, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @endforeach
                    @if ($order->items->count() > 2)
                        <p class="text-xs text-gray-400 mt-2">+{{ $order->items->count() - 2 }} produk lainnya</p>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-50 flex items-center justify-between bg-gray-50/20">
                    <div>
                        <span class="text-xs text-gray-400">Total:</span>
                        <span class="font-black text-gray-900 ml-1">Rp
                            {{ number_format($order->grand_total, 0, ',', '.') }}</span>
                    </div>
                    <a href="{{ route('orders.show', $order) }}" wire:navigate
                        class="text-sm font-bold text-[#4E44DB] hover:underline">
                        Lihat Detail →
                    </a>
                </div>
            </div>
        @empty
            <div
                class="bg-white rounded-2xl p-10 flex flex-col justify-center items-center text-center shadow-sm border border-gray-100">
                <img src="{{ asset('assets/png/pesanan.png') }}" class="w-80 h-auto object-center mb-4" alt="">
                <p class="text-black font-bold text-lg">Kamu belum mempunyai pesanan.</p>
                <p class="text-gray-500 text-xs font-medium mb-4">Yuk belanja dulu biar dapat voucher menarik!</p>
                <a href="{{ route('buy-mobile') }}" wire:navigate
                    class="inline-flex items-center gap-2 px-8 py-3.5 text-sm font-bold text-white bg-[#4E44DB] rounded-2xl shadow-lg shadow-[#4E44DB]/30 hover:bg-[#3d35b8] hover:-translate-y-0.5 transition-all">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Mulai Belanja
                </a>
            </div>
        @endforelse

        @if ($orders->hasPages())
            <div class="mt-8">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
