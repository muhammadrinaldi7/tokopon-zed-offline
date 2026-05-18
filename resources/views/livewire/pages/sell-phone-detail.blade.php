<div class="bg-white min-h-screen pb-20 pt-8">
    <div class="max-w-4xl mx-auto px-6">

        {{-- Banner --}}
        <div
            class="bg-[#4E44DB] rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 text-white shadow-md mb-8 relative overflow-hidden">
            <div class="flex items-center gap-4 z-10">
                <a href="{{ route('sell-phone-history') }}" wire:navigate
                    class="bg-black/10 hover:bg-black/20 p-2.5 rounded-lg transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="flex items-center gap-3">
                    <h1 class="text-xl md:text-2xl font-bold tracking-wide">Detail Jual HP Bekas</h1>
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
                        'PENDING' => 'Menunggu Taksiran Admin',
                        'OFFERED' => 'Penawaran Tersedia',
                        'REVISED_OFFER' => 'Tawaran Revisi',
                        'WAITING_FOR_DEVICE' => 'Menunggu Pengiriman Fisik',
                        'INSPECTING' => 'Fisik Diinspeksi Admin',
                        'PAYING' => 'Proses Pencairan Dana',
                        'COMPLETED' => 'Transaksi Lunas',
                        'CANCELLED' => 'Dibatalkan',
                    ];
                @endphp
                <span
                    class="px-4 py-2 text-sm font-bold border rounded-xl {{ $statusColors[$sellPhone->status] ?? 'bg-white text-gray-800' }}">
                    {{ $statusLabels[$sellPhone->status] ?? $sellPhone->status }}
                </span>
            </div>
        </div>

        {{-- Phone Details --}}
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 mb-8 text-center max-w-xl mx-auto">
            <h2 class="text-2xl font-extrabold text-gray-900 mb-2">{{ $sellPhone->phone_brand }}
                {{ $sellPhone->phone_model }}</h2>
            <p class="text-sm text-gray-500 mb-6">{{ $sellPhone->phone_ram ?? '-' }} RAM /
                {{ $sellPhone->phone_storage ?? '-' }} Storage</p>

            <div class="bg-neutral-50 p-5 md:p-6 rounded-2xl border border-neutral-100 shadow-sm">
                <span class="block text-xs font-black text-neutral-500 uppercase tracking-widest mb-4">Keterangan &
                    Kelengkapan</span>

                @if ($sellPhone->minus_desc)
                    <ul class="space-y-3">
                        {{-- Memecah teks berdasarkan '. ' menjadi array agar bisa di-looping --}}
                        @foreach (array_filter(explode('. ', $sellPhone->minus_desc)) as $desc)
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5">
                                    <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <span class="text-sm text-neutral-700 font-medium">{{ trim($desc) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-neutral-500 italic font-medium">Tidak ada keterangan tambahan.</p>
                @endif
            </div>

            @php $photos = $sellPhone->getMedia('photos'); @endphp
            @if ($photos->count() > 0)
                <div class="mb-6">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3 text-left">Foto Fisik Unit
                    </p>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach ($photos as $photo)
                            <a href="{{ $photo->getUrl() }}" target="_blank"
                                class="aspect-square rounded-2xl overflow-hidden border border-gray-200 block hover:opacity-80 transition shadow-sm">
                                <img src="{{ $photo->getUrl() }}" class="w-full h-full object-cover">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($sellPhone->appraised_value)
                <div class="p-6 bg-emerald-50 rounded-2xl border border-emerald-100">
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-2">Nilai Penawaran Toko
                    </p>
                    <p class="text-4xl font-black text-emerald-700">Rp
                        {{ number_format($sellPhone->appraised_value, 0, ',', '.') }}</p>
                </div>
            @endif
        </div>

        {{-- Flow Sections --}}
        <div class="max-w-2xl mx-auto space-y-6 pb-12">

            @if (in_array($sellPhone->status, ['PENDING', 'OFFERED']))
                <button wire:click="cancel" wire:confirm="Anda yakin ingin membatalkan pengajuan ini?"
                    class="w-full bg-white text-rose-500 py-3 rounded-xl border-2 border-rose-100 hover:bg-rose-50 transition font-bold text-sm">
                    Batalkan Pengajuan
                </button>
            @endif

            @if ($sellPhone->status === 'PENDING')
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
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Sedang Ditaksir Admin</h3>
                    <p class="text-gray-500 text-sm">Tim kami sedang menilai harga beli HP Anda. Notifikasi akan masuk
                        jika penawaran sudah diberikan.</p>
                </div>
            @endif

            @if (in_array($sellPhone->status, ['OFFERED', 'REVISED_OFFER']))
                <div class="bg-emerald-50 rounded-3xl p-8 text-center border border-emerald-200">
                    <h3 class="font-bold text-emerald-900 text-xl mb-2">Terima Penawaran Ini?</h3>
                    <p class="text-sm text-emerald-700 mb-6">Jika Anda setuju dengan nilai yang ditawarkan di atas, klik
                        Terima untuk melanjutkan ke proses pencairan dana.</p>
                    <button wire:click="acceptOffer" wire:confirm="Setuju dengan penawaran dan siap mengirimkan unit?"
                        class="bg-emerald-500 text-white px-8 py-3.5 rounded-xl font-bold text-lg hover:bg-emerald-600 transition w-full shadow-lg shadow-emerald-500/25">
                        Ya, Saya Setuju!
                    </button>
                </div>
            @endif

            @if (in_array($sellPhone->status, ['WAITING_FOR_DEVICE', 'INSPECTING', 'PAYING', 'COMPLETED']))
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-4">
                        <h3 class="font-bold text-gray-900">
                            {{ Auth::user()->hasRole('fl') ? 'Data Pencairan Dana Customer' : 'Status Pengiriman & Verifikasi' }}
                        </h3>
                        @if ($sellPhone->status === 'INSPECTING')
                            <span
                                class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full text-[10px] font-bold">Sedang
                                Dicek Admin Pusat</span>
                        @elseif($sellPhone->status === 'PAYING')
                            <span
                                class="bg-teal-100 text-teal-700 px-3 py-1 rounded-full text-[10px] font-bold">Pencairan
                                Dana</span>
                        @endif
                    </div>

                    @if ($sellPhone->status === 'WAITING_FOR_DEVICE')
                        <div class="bg-[#00b16a]/5 rounded-2xl p-6 border border-[#00b16a]/20 mt-2">
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
                    @else
                        <div
                            class="bg-gray-50 rounded-2xl p-5 border border-gray-200 flex items-center justify-between mt-2">
                            @if (Auth::user()->hasRole('fl'))
                                <div>
                                    <p class="text-xs text-gray-500 font-bold uppercase mb-1">DATA CUSTOMER</p>
                                    <p class="font-mono tracking-widest font-bold text-gray-800 text-lg">
                                        {{ $sellPhone->user->name }}</p>
                                    <p class="font-mono tracking-widest font-bold text-gray-800 text-lg">
                                        {{ $sellPhone->user->profile->phone_number }}</p>
                                    <p class="text-xs text-gray-500 font-bold uppercase mb-1">DATA PENCAIRAN DANA</p>
                                    <p class="font-mono tracking-widest font-bold text-gray-800 text-lg">
                                        {{ $sellPhone->user->bankAccounts->first()->bank_name }}</p>
                                    <p class="font-mono tracking-widest font-bold text-gray-800 text-lg">
                                        {{ $sellPhone->user->bankAccounts->first()->account_number }}</p>
                                    <p class="font-mono tracking-widest font-bold text-gray-800 text-lg">
                                        {{ $sellPhone->user->bankAccounts->first()->account_name }}</p>
                                </div>
                            @else
                                <div>
                                    <p class="text-xs text-gray-500 font-bold uppercase mb-1">Resi Pengiriman Anda</p>
                                    <p class="font-mono tracking-widest font-bold text-gray-800 text-lg">
                                        {{ $sellPhone->customer_shipping_receipt }}</p>
                                </div>
                            @endif
                            <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            @if ($sellPhone->status === 'PAYING')
                <div
                    class="bg-teal-50 border border-teal-200 text-center rounded-3xl p-8 relative overflow-hidden mt-8 shadow-sm">
                    <h3 class="text-xl text-teal-900 font-black mb-2">Fisik Sesuai, Pencairan Diproses!</h3>
                    <p class="text-teal-700 text-sm mb-0 mx-auto">Admin kami sedang melakukan transfer dana ke rekening
                        Anda. Harap tunggu beberapa saat.</p>
                </div>
                @if (Auth::user()->hasRole('fl'))
                    <div class="flex">
                        <div>
                            <p class="text-xs text-gray-500 font-bold uppercase mb-1">Tandai Selesai dan Kirim Produk
                                Ke Sistem</p>
                            <button wire:click="submitComplete"
                                class="bg-[#00b16a] text-white px-6 py-3 rounded-xl font-bold hover:bg-[#009e5f] transition shrink-0"
                                wire:loading.attr="disabled">Tandai Selesai</button>
                        </div>
                    </div>
                @endif
            @endif

            @if ($sellPhone->status === 'COMPLETED')
                <div
                    class="bg-[#4E44DB] text-center rounded-3xl p-8 relative overflow-hidden text-white mt-8 shadow-xl shadow-[#4E44DB]/30">
                    <svg class="absolute -right-10 -bottom-10 w-48 h-48 text-white opacity-5" fill="currentColor"
                        viewBox="0 0 24 24">
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" />
                    </svg>

                    <h3 class="text-2xl font-black mb-2">Transaksi Lunas!</h3>
                    <p class="text-white/80 text-sm mb-0 mx-auto">Dana telah berhasil ditransfer ke rekening Anda.
                        Terima kasih telah menjual HP Anda di TokoPun.</p>
                </div>
            @endif

        </div>
    </div>
</div>
