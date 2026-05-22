<div class="bg-white min-h-screen pb-20 pt-8">
    <div class="max-w-5xl mx-auto px-6">

        {{-- Green Banner --}}
        <div
            class="bg-[#00b16a] rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 text-white shadow-md mb-8 relative overflow-hidden">
            <div class="flex items-center gap-4 z-10">
                <a href="{{ route('trade-in-history') }}" wire:navigate
                    class="bg-black/10 hover:bg-black/20 p-2.5 rounded-lg transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="flex items-center gap-3">
                    <svg class="w-7 h-7 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    <h1 class="text-xl md:text-2xl font-bold tracking-wide">Trade-In Mobile Phones</h1>
                </div>
            </div>

            <div class="z-10">
                @php
                    $statusColors = [
                        'PENDING' => 'bg-amber-100 text-amber-800 border-amber-200',
                        'OFFERED' => 'bg-blue-100 text-blue-800 border-blue-200',
                        'WAITING_FOR_DEVICE' => 'bg-purple-100 text-purple-800 border-purple-200',
                        'INSPECTING' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                        'PAYING' => 'bg-teal-100 text-teal-800 border-teal-200',
                        'COMPLETED' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                        'CANCELLED' => 'bg-rose-100 text-rose-800 border-rose-200',
                    ];
                    $statusLabels = [
                        'PENDING' => 'Sedang Ditaksir BM',
                        'OFFERED' => 'Penawaran Admin Siap',
                        'WAITING_FOR_DEVICE' => 'Menunggu Resi Anda',
                        'INSPECTING' => 'Paket Diinspeksi',
                        'PAYING' => 'Menunggu Pembayaran',
                        'COMPLETED' => 'Transaksi Rampung',
                        'CANCELLED' => 'Dibatalkan',
                    ];
                @endphp
                <span
                    class="px-4 py-2 text-sm font-bold border rounded-xl {{ $statusColors[$tradeIn->status] ?? 'bg-white text-gray-800' }}">
                    {{ $statusLabels[$tradeIn->status] ?? $tradeIn->status }}
                </span>
            </div>
        </div>

        {{-- Product Cards Section --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative mb-12">
            <!-- Sync Icon in the middle -->
            <div
                class="hidden md:flex absolute left-1/2 top-[40%] -translate-x-1/2 -translate-y-1/2 z-10 w-16 h-16 bg-white rounded-full items-center justify-center shadow-sm text-gray-300">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>

            <!-- New Phone Card -->
            <div
                class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-8 flex flex-col justify-between text-center min-h-[400px]">
                <div>
                    <h2 class="text-3xl font-extrabold mb-8"><span class="text-rose-500">N</span><span
                            class="text-blue-500">e</span><span class="text-blue-400">w</span></h2>
                    <div class="h-64 flex items-center justify-center mb-6">
                        <img src="{{ $tradeIn->targetProduct->getFirstMediaUrl('cover', 'thumb') ?: $tradeIn->targetProduct->getFirstMediaUrl('gallery', 'thumb') }}"
                            class="max-h-full object-contain">
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">{{ $tradeIn->targetProduct->name }}</h3>
                    <p class="text-sm text-gray-400 mt-1">Brand New (TokoPun)</p>
                </div>
            </div>

            <!-- Old Phone Card -->
            <div
                class="bg-white rounded-[2rem] border border-gray-100 shadow-sm p-8 flex flex-col justify-between text-center min-h-[400px]">
                <div>
                    <h2 class="text-3xl font-extrabold text-gray-300 mb-8">Old</h2>
                    <div class="h-64 flex items-center justify-center mb-6">
                        @if (count($tradeIn->getMedia('photos')) > 0 || count($tradeIn->getMedia('default')) > 0)
                            <img src="{{ $tradeIn->getFirstMediaUrl('photos') ?: $tradeIn->getFirstMediaUrl('default') }}"
                                class="max-h-full object-contain rounded-2xl shadow-sm border border-gray-100">
                        @else
                            <div
                                class="w-32 h-48 bg-gray-50 rounded-2xl flex items-center justify-center border-2 border-dashed border-gray-200">
                                <span
                                    class="text-gray-400 text-xs font-bold uppercase text-center px-2">{{ $tradeIn->old_phone_brand }}<br>{{ $tradeIn->old_phone_model }}</span>
                            </div>
                        @endif
                    </div>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-gray-900">{{ $tradeIn->old_phone_brand }}
                        {{ $tradeIn->old_phone_model }}</h3>
                    <p class="text-sm text-gray-400 mt-1">Used ({{ $tradeIn->old_phone_storage ?? '-' }})</p>
                </div>
            </div>
        </div>

        {{-- Estimation Price --}}
        <div class="text-center mb-12 pb-12 border-b border-gray-100 px-4">
            <h2 class="text-3xl font-bold text-gray-400 mb-2">Estimation Price</h2>

            @if ($tradeIn->status === 'PENDING')
                <div class="mt-8">
                    <p class="text-gray-900 font-extrabold text-4xl">Sedang Dihitung...</p>
                    <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mt-3">Menunggu Taksiran BM</p>
                </div>
            @elseif($tradeIn->status === 'OFFERED')
                <div class="mt-8">
                    {{-- <p class="text-gray-900 font-black text-3xl">Pilih Varian Dibawah</p> --}}
                    <p class="text-sm font-bold text-gray-400 uppercase tracking-widest mt-3 mb-2">Untuk Melihat
                        Kalkulasi Harga</p>
                    <div
                        class="inline-block bg-emerald-50 text-emerald-700 px-4 py-2 rounded-xl text-sm font-bold border border-emerald-100">
                        HP Lama Dihargai: Rp {{ number_format($tradeIn->appraised_value, 0, ',', '.') }}
                    </div>
                </div>
            @else
                {{-- Harga diset --}}
                @php
                    $selectedOption = $tradeIn->unitOptions->where('is_selected', true)->first();
                    $newPrice = $selectedOption
                        ? $selectedOption->variant->price
                        : $tradeIn->targetProduct->starting_price;
                    $topup = max(0, $newPrice - (float) $tradeIn->appraised_value);
                @endphp
                <div class="mt-4 flex flex-col items-center">
                    <div class="relative inline-block text-4xl font-black text-gray-300 px-4 py-2 opacity-60">
                        Rp {{ number_format($newPrice, 0, ',', '.') }}
                        <!-- Strikethrough custom -->
                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="w-[110%] h-1.5 bg-gray-400 rotate-[-6deg] rounded-full"></div>
                            <div class="w-[110%] h-1.5 bg-gray-400 rotate-[4deg] rounded-full absolute"></div>
                        </div>
                    </div>
                    <div
                        class="text-[4rem] sm:text-[6rem] leading-none font-black text-gray-900 tracking-tighter my-2 sm:my-4">
                        Rp {{ number_format($topup, 0, ',', '.') }}
                    </div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Best Price For You</p>
                </div>
            @endif
        </div>

        {{-- Bottom Section (Existing Logics) --}}
        <div class="max-w-2xl mx-auto space-y-6 pb-12">

            @if (in_array($tradeIn->status, ['PENDING', 'OFFERED']))
                <button wire:click="cancel" wire:confirm="Anda yakin ingin membatalkan aplikasi trade-in ini?"
                    class="w-full bg-white text-rose-500 py-3 rounded-xl border-2 border-rose-100 hover:bg-rose-50 transition font-bold text-sm">
                    Batalkan Pengajuan
                </button>
            @endif

            @if ($tradeIn->status === 'PENDING')
                <div
                    class="bg-[#4E44DB]/5 border border-[#4E44DB]/20 rounded-3xl p-8 text-center flex flex-col justify-center">
                    <div
                        class="w-16 h-16 bg-white rounded-full mx-auto flex items-center justify-center shadow-sm mb-4">
                        <svg class="w-8 h-8 text-[#4E44DB]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Menganalisa Kondisi</h3>
                    <p class="text-gray-500 text-sm">Tim Branch Manager kami sedang melakukan taksiran harga akhir
                        berdasarkan foto & deskripsi minus HP lama Anda.</p>
                </div>
            @endif

            @if ($tradeIn->status === 'OFFERED')
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-emerald-200 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-emerald-500"></div>
                    <h3 class="font-bold text-gray-900 text-lg mb-2">Penawaran Telah Tersedia!</h3>
                    <p class="text-sm text-gray-500 mb-6">Pilih salah satu unit/varian yang kami siapkan. Jika setuju
                        dengan Top-Up harganya, cukup klik pilih dan kirimkan HP lama Anda.</p>

                    <div class="space-y-3">
                        @foreach ($tradeIn->unitOptions as $option)
                            @php
                                $itemTopup = max(0, $option->variant->price - (float) ($tradeIn->appraised_value ?: 0));
                            @endphp
                            <div
                                class="bg-gray-50 rounded-2xl p-4 border border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div>
                                    <p class="font-bold text-gray-800">{{ $option->variant->color }} -
                                        {{ $option->variant->storage }}</p>
                                    <p class="text-xs text-gray-500 mt-1">Kondisi Fisik:
                                        {{ $option->variant->condition }}</p>
                                    <div class="text-sm font-black text-[#00b16a] mt-2">+ Top-Up Rp
                                        {{ number_format($itemTopup, 0, ',', '.') }}</div>
                                </div>
                                <button wire:click="selectVariant({{ $option->product_variant_id }})"
                                    wire:confirm="Setuju memilih unit ini dan siap mengirimkan HP lama?"
                                    class="bg-[#00b16a] text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-[#009e5f] transition shrink-0 shadow-md shadow-[#00b16a]/20">
                                    Deal With Us
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (in_array($tradeIn->status, [
                    'WAITING_FOR_DEVICE',
                    'INSPECTING',
                    'OFFERED',
                    'PAYING',
                    'COMPLETED',
                    'WAITING_PAYMENT',
                ]))
                @php
                    $variant = $tradeIn->productVariant;
                    $topupAmount = 0;
                    if ($variant) {
                        $topupAmount = max(0, $variant->price - (float) $tradeIn->appraised_value);
                    }
                @endphp

                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
                        <h3 class="font-bold text-gray-900">Varian Target Terpilih</h3>
                        @if ($tradeIn->status === 'INSPECTING')
                            <span
                                class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-[10px] font-bold">Sedang
                                Dicek BM Pusat</span>
                        @elseif ($tradeIn->status === 'OFFERED')
                            <span
                                class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-[10px] font-bold">Menunggu
                                Persetujuan Anda</span>
                        @endif
                    </div>

                    <p class="font-bold text-gray-800 mb-1">{{ $variant?->color }} -
                        {{ $variant?->storage }}</p>
                    <p class="text-sm text-gray-500 mb-4">Sisa Tagihan (Top-Up Terkunci): <span
                            class="font-bold text-[#4E44DB]">Rp {{ number_format($topupAmount, 0, ',', '.') }}</span>
                    </p>

                    @if ($tradeIn->status === 'OFFERED')
                        <div class="bg-amber-50 rounded-2xl p-6 border border-amber-200 mt-6">
                            <h4 class="font-bold text-amber-800 mb-2 text-lg">Konfirmasi Sisa Tagihan</h4>
                            <p class="text-sm text-amber-700 mb-6">Pihak toko telah selesai memverifikasi HP Lama Anda.
                                Sisa tagihan (Top-Up) yang harus dibayar adalah sebesar <strong>Rp
                                    {{ number_format($topupAmount, 0, ',', '.') }}</strong>. Silakan konfirmasi jika
                                Anda setuju dengan nominal ini.</p>

                            <div class="flex flex-col sm:flex-row gap-3">
                                <button type="button" wire:click="rejectOffer"
                                    wire:confirm="Anda yakin ingin menolak dan membatalkan transaksi Tukar Tambah ini secara sepihak?"
                                    class="px-6 py-3 rounded-xl font-bold bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition text-center">
                                    Tolak Penawaran
                                </button>
                                <button type="button" wire:click="acceptOffer"
                                    wire:confirm="Dengan menyetujui, Anda siap untuk membayar sisa tagihan tersebut. Lanjutkan?"
                                    class="flex-1 px-6 py-3 rounded-xl font-bold bg-[#4E44DB] text-white hover:bg-indigo-700 shadow-md shadow-[#4E44DB]/30 transition text-center">
                                    Ya, Setuju Lanjut Pembayaran
                                </button>
                            </div>
                        </div>
                    @endif

                    @if ($tradeIn->status === 'WAITING_FOR_DEVICE')
                        <div class="bg-[#00b16a]/5 rounded-2xl p-6 border border-[#00b16a]/20 mt-6">
                            <h4 class="font-bold text-[#00b16a] mb-2 text-lg">Langkah Selanjutnya</h4>
                            <div class="text-sm text-[#00b16a]/80 mb-6 space-y-2">
                                <p><strong>1.</strong> Packing HP Lama Anda dengan asuransi kurir.</p>
                                <p><strong>2.</strong> Kirim paket ke alamat <strong>TokoPun - Setiabudi</strong>.</p>
                                <p><strong>3.</strong> Input Nomor Resi di bawah agar kami bisa melacak kedatangannya.
                                </p>
                            </div>

                            <form wire:submit="submitReceipt" class="flex flex-col sm:flex-row gap-2 relative">
                                <input type="text" wire:model="customerShippingReceipt"
                                    class="w-full text-sm font-mono tracking-widest rounded-xl border-[#00b16a]/30 bg-white py-3 focus:ring-2 focus:ring-[#00b16a] focus:border-[#00b16a] placeholder:tracking-normal placeholder:font-sans"
                                    placeholder="Ketik nomor resi pengiriman">
                                <button type="submit"
                                    class="bg-[#00b16a] text-white px-6 py-3 rounded-xl font-bold hover:bg-[#009e5f] transition shrink-0"
                                    wire:loading.attr="disabled">Kirim Resi</button>
                            </form>
                            @error('customerShippingReceipt')
                                <span class="text-xs text-rose-500 mt-2 block font-medium">{{ $message }}</span>
                            @enderror
                        </div>
                    @elseif ($tradeIn->customer_shipping_receipt)
                        <div
                            class="bg-gray-50 rounded-2xl p-5 border border-gray-200 mt-4 flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-500 font-bold uppercase mb-1">Resi Pengiriman Anda (Dikirim
                                    Klien)</p>
                                <p class="font-mono tracking-widest font-bold text-gray-800 text-lg">
                                    {{ $tradeIn->customer_shipping_receipt }}</p>
                            </div>
                            <div class="w-12 h-12 bg-[#00b16a]/10 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-[#00b16a]" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            @if (in_array($tradeIn->status, ['WAITING_PAYMENT', 'PAYING', 'COMPLETED']))
                <div
                    class="bg-[#4E44DB] text-center rounded-3xl p-8 relative overflow-hidden text-white mt-8 shadow-xl shadow-[#4E44DB]/30">
                    <svg class="absolute -right-10 -bottom-10 w-48 h-48 text-white opacity-5" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                    </svg>

                    <h3 class="text-2xl font-black mb-2">Penukaran Disetujui!</h3>
                    <p class="text-white/80 text-sm mb-6 max-w-md mx-auto">Kami telah menyelesaikan inspeksi fisik dan
                        unit Anda diterima. Silakan selesaikan pembayaran untuk pengiriman HP baru Anda.</p>

                    <a href="{{ route('orders.show', $tradeIn->order_id) }}"
                        class="inline-block bg-white text-[#4E44DB] px-8 py-3.5 rounded-full font-bold shadow-lg hover:shadow-xl transition-all float-up">
                        Bayar Sisa & Lacak
                    </a>
                </div>
            @endif

        </div>
    </div>
</div>
