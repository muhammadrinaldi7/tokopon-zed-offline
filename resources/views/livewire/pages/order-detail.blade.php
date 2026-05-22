<div class="bg-gray-50 min-h-screen pb-20">
    <div class="max-w-3xl mx-auto px-6 pt-8">
        {{-- Header --}}
        <div class="mb-6">
            <a href="{{ route('orders.index') }}" wire:navigate
                class="text-sm font-medium text-gray-400 hover:text-[#4E44DB] transition">← Kembali ke Pesanan</a>
            <div class="flex items-center justify-between mt-2">
                <h1 class="text-2xl font-extrabold text-gray-900">{{ $order->order_number }}</h1>
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
                    class="text-sm font-bold px-4 py-1.5 rounded-xl border {{ $statusColors[$order->order_status] ?? '' }}">
                    {{ $statusLabels[$order->order_status] ?? $order->order_status }}
                </span>
            </div>
            <p class="text-sm text-gray-400 mt-1">Dipesan pada {{ $order->created_at->format('d M Y, H:i') }}</p>
        </div>

        {{-- Order Timeline --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            @php
                $steps = ['PENDING', 'PROCESSING', 'SHIPPED', 'COMPLETED'];
                $stepLabels = ['Pesanan Dibuat', 'Diproses', 'Dikirim', 'Selesai'];
                $currentIndex = array_search($order->order_status, $steps);
                if ($currentIndex === false) $currentIndex = -1;
            @endphp
            <div class="flex items-center justify-between">
                @foreach ($steps as $i => $step)
                    <div class="flex flex-col items-center flex-1">
                        <div @class([
                            'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all',
                            'bg-[#4E44DB] text-white' => $i <= $currentIndex,
                            'bg-gray-100 text-gray-400' => $i > $currentIndex,
                        ])>
                            @if ($i < $currentIndex)
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            @else
                                {{ $i + 1 }}
                            @endif
                        </div>
                        <span class="text-[10px] font-semibold mt-1.5 text-center {{ $i <= $currentIndex ? 'text-[#4E44DB]' : 'text-gray-400' }}">
                            {{ $stepLabels[$i] }}
                        </span>
                    </div>
                    @if (!$loop->last)
                        <div class="flex-1 h-0.5 mx-1 {{ $i < $currentIndex ? 'bg-[#4E44DB]' : 'bg-gray-100' }} rounded-full -mt-5"></div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Items --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-bold text-gray-800">Produk Dipesan</h2>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach ($order->items as $item)
                    @php
                        $variant = $item->variant;
                        $product = $variant instanceof \App\Models\SecondProductVariant
                            ? $variant->secondProduct
                            : $variant?->product;
                        $imgUrl = $product ? ($product->getFirstMediaUrl('cover', 'thumb') ?: $product->getFirstMediaUrl('gallery', 'thumb')) : '';
                    @endphp
                    <div class="flex gap-4 px-6 py-4">
                        <div class="w-16 h-16 rounded-xl bg-gray-50 overflow-hidden border border-gray-100 shrink-0 flex items-center justify-center">
                            @if ($imgUrl)
                                <img src="{{ $imgUrl }}" alt="" class="w-full h-full object-cover">
                            @else
                                <svg class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-800 text-sm">{{ $product?->name ?? '-' }}</h3>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $variant?->ram ? $variant->ram . ' / ' : '' }}{{ $variant?->storage ?? '' }}
                                {{ $variant?->color ? '- ' . $variant->color : '' }}
                                · {{ $variant?->condition }}
                            </p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs text-gray-500">{{ $item->qty }}x @ Rp {{ number_format($item->price_at_checkout, 0, ',', '.') }}</span>
                                <span class="font-bold text-gray-800">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                            </div>
                            @if($order->order_status === 'COMPLETED')
                                <div class="mt-3 text-right">
                                    @if($item->review)
                                        <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-100 flex items-center gap-1 w-fit ml-auto">
                                            <svg class="w-4 h-4 text-emerald-500 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg> 
                                            {{ $item->review->rating }}/5 (Diulas)
                                        </span>
                                    @else
                                        <button wire:click="openReviewModal({{ $item->id }})" class="inline-flex text-xs font-bold text-[#4E44DB] border border-[#4E44DB] bg-white px-4 py-1.5 rounded-lg hover:bg-[#4E44DB] hover:text-white transition-colors">
                                            Beri Ulasan
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Totals --}}
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Subtotal</span>
                    <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Ongkos Kirim</span>
                    <span>Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</span>
                </div>
                @if ($order->discount_amount > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Diskon</span>
                        <span class="text-emerald-600">-Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between pt-2 border-t border-gray-200">
                    <span class="font-bold text-gray-900">Grand Total</span>
                    <span class="font-black text-[#4E44DB] text-xl">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Shipping Address --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <h2 class="font-bold text-gray-800 mb-3">Alamat Pengiriman</h2>
            <p class="font-semibold text-gray-800">{{ $order->shipping_address_snapshot['recipient_name'] ?? '' }}</p>
            <p class="text-sm text-gray-500">{{ $order->shipping_address_snapshot['phone_number'] ?? '' }}</p>
            <p class="text-sm text-gray-600 mt-1">{{ $order->shipping_address_snapshot['full_address'] ?? '' }}</p>
            @if (!empty($order->shipping_address_snapshot['postal_code']))
                <p class="text-sm text-gray-400 mt-1">Kode Pos: {{ $order->shipping_address_snapshot['postal_code'] }}</p>
            @endif
        </div>

        {{-- Shipping Info --}}
        @if ($order->shipping)
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
                <h2 class="font-bold text-gray-800 mb-3">Info Pengiriman</h2>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-400">Kurir</p>
                        <p class="font-semibold text-gray-800">{{ strtoupper($order->shipping->courier_company ?? '-') }} ({{ $order->shipping->courier_type ?? '-' }})</p>
                    </div>
                    <div>
                        <p class="text-gray-400">No. Resi</p>
                        <p class="font-semibold text-gray-800 font-mono">{{ $order->shipping->tracking_number ?? 'Belum tersedia' }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row gap-3 mt-8">
            @if ($order->order_status === 'PENDING')
                @php
                    $pendingPayment = $order->payments->where('status', 'PENDING')->last();
                @endphp
                @if ($pendingPayment && $pendingPayment->xendit_invoice_url)
                    <a href="{{ $pendingPayment->xendit_invoice_url }}" target="_blank"
                        class="flex-1 bg-[#0097FF] text-white py-3.5 rounded-xl font-bold hover:bg-[#007ecc] transition shadow-lg shadow-[#0097FF]/25 text-center flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        Lanjutkan Pembayaran (Via Xendit)
                    </a>
                @else
                    <p class="text-sm text-amber-600 bg-amber-50 p-4 rounded-xl w-full text-center border border-amber-100">Menunggu *update* Link Pembayaran dari sistem.</p>
                @endif
            @endif

            @if ($order->order_status === 'SHIPPED')
                <button wire:click="confirmReceived"
                    class="flex-1 bg-emerald-500 text-white py-3.5 rounded-xl font-bold hover:bg-emerald-600 transition shadow-lg shadow-emerald-500/25 text-center"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="confirmReceived">Konfirmasi Diterima</span>
                    <span wire:loading wire:target="confirmReceived">Memproses...</span>
                </button>
            @endif
        </div>
    </div>

    {{-- Review Modal --}}
    @if($showReviewModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl w-full max-w-md overflow-hidden shadow-2xl relative" @click.away="$wire.closeReviewModal()">
                {{-- Close button --}}
                <button wire:click="closeReviewModal" class="absolute top-4 right-4 bg-gray-50 text-gray-400 hover:text-gray-600 p-2 rounded-full transition hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                
                <div class="p-6 md:p-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Nilai Produk</h3>
                    <p class="text-sm text-gray-500 mb-6">Bagaimana kepuasan Anda terhadap produk ini?</p>

                    <form wire:submit="submitReview">
                        <div class="flex items-center justify-center gap-2 mb-6">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" wire:click="$set('reviewRating', {{ $i }})" class="focus:outline-none transition-transform hover:scale-110">
                                    <svg class="w-10 h-10 {{ $i <= $reviewRating ? 'text-amber-400 fill-current' : 'text-gray-200 fill-current' }}" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                </button>
                            @endfor
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Ulasan (Opsional)</label>
                            <textarea wire:model="reviewComment" rows="4" class="w-full text-sm rounded-xl border-gray-200 px-4 py-3 focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition resize-none" placeholder="Tulis pengalaman Anda menggunakan produk ini..."></textarea>
                            @error('reviewComment') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex gap-3">
                            <button type="button" wire:click="closeReviewModal" class="flex-1 bg-gray-100 text-gray-700 py-3.5 rounded-xl font-bold hover:bg-gray-200 transition">Batal</button>
                            <button type="submit" class="flex-1 bg-[#4E44DB] text-white py-3.5 rounded-xl font-bold hover:bg-[#3f36b8] transition shadow-lg shadow-[#4E44DB]/25 flex items-center justify-center gap-2" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submitReview">Kirim Ulasan</span>
                                <span wire:loading wire:target="submitReview">Loading...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
