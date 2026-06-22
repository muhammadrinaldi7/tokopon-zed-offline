<div x-data="{ showQcModal: false }" class="max-w-7xl mx-auto p-4 md:p-8 space-y-6">

    {{-- ========================================== --}}
    {{-- 1. BANNER UTAMA (Desain Asli) --}}
    {{-- ========================================== --}}
    <div
        class="bg-[#4E44DB] rounded-2xl p-4 md:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 text-white shadow-md relative overflow-hidden">
        {{-- Tombol Back & Judul --}}
        <div class="flex items-center gap-4 z-10">
            <a href="{{ route('zoffline.sell-phone-history') }}" wire:navigate
                class="bg-black/10 hover:bg-black/20 p-2.5 rounded-xl transition">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-xl md:text-2xl font-bold tracking-wide">Detail Jual HP Bekas</h1>
                <span class="text-xs bg-light-200 text-neutral-400 font-medium">SPL-{{ $sellPhone->id }}</span>
            </div>
        </div>

        {{-- Badge Status --}}
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
                class="inline-block px-4 py-2.5 text-sm font-bold border rounded-xl {{ $statusColors[$sellPhone->status] ?? 'bg-white text-gray-800' }}">
                {{ $statusLabels[$sellPhone->status] ?? $sellPhone->status }}
            </span>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- 2. KARTU STRUK / RECEIPT --}}
    {{-- ========================================== --}}
    <div class="bg-white border border-neutral-200 shadow-sm rounded-3xl overflow-hidden mt-2">
        {{-- Header Produk --}}
        <div class="p-6 md:p-8 flex items-center justify-between gap-4">
            <div>
                <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest">{{ $sellPhone->phone_brand }}</p>
                <h3 class="text-2xl font-black text-neutral-900">{{ $sellPhone->phone_model }}</h3>
                <p class="text-sm text-neutral-500 mt-1 font-medium">
                    {{ $sellPhone->phone_ram ?? $sellPhone->phone_ram . 'RAM' }} •
                    {{ $sellPhone->phone_storage }} Storage</p>
                @if ($sellPhone->inspections->isNotEmpty())
                    <p class="text-sm text-neutral-500 mt-1 font-medium">
                        IMEI: {{ $sellPhone->inspections->first()->imei }}
                    </p>
                    <button @click="showQcModal = true" type="button" 
                        class="mt-3 text-xs font-bold text-violet-600 bg-violet-50 hover:bg-violet-100 px-3 py-1.5 rounded-lg transition-colors inline-flex items-center gap-1.5 border border-violet-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        Lihat Hasil QC ({{ $sellPhone->inspections->first()->passed_count }}/{{ $sellPhone->inspections->first()->total_items }} Lulus)
                    </button>
                @endif
            </div>

        </div>

        {{-- Divider Garis Putus-Putus ala Struk --}}
        <div class="relative flex items-center justify-center h-4">
            <div class="absolute w-full border-t-[3px] border-dashed border-neutral-200"></div>
            <div class="absolute -left-3 w-6 h-6 bg-gray-50 rounded-full border border-neutral-200"></div>
            <div class="absolute -right-3 w-6 h-6 bg-gray-50 rounded-full border border-neutral-200"></div>
        </div>

        {{-- Harga --}}
        <div class="p-6 md:p-8">
            <p class="text-xs font-black text-neutral-400 uppercase tracking-widest mb-2">Penawaran Harga Toko</p>
            @if ($sellPhone->appraised_value)
                <h2 class="text-4xl font-black text-emerald-600">Rp
                    {{ number_format($sellPhone->appraised_value, 0, ',', '.') }}</h2>
                @if ($sellPhone->is_price_adjusted)
                    <p
                        class="text-xs font-bold text-amber-500 mt-2 bg-amber-50 inline-block px-2 py-1 rounded-md border border-amber-200 flex items-center w-fit gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Nominal disesuaikan oleh Admin
                    </p>
                @endif
            @else
                <h2 class="text-4xl font-black text-neutral-300">Menunggu...</h2>
            @endif
        </div>
    </div>


    {{-- ========================================== --}}
    {{-- 4. AREA AKSI & FLOW --}}
    {{-- ========================================== --}}
    <div class="pt-2">
        @if ($sellPhone->status === 'PENDING')
            <button wire:click="cancel" wire:confirm="Batalkan pengajuan ini?"
                class="w-full py-4 bg-white border border-rose-200 text-rose-500 rounded-2xl font-bold text-sm hover:bg-rose-50 transition shadow-sm">
                Batalkan Pengajuan Ini
            </button>
        @elseif (in_array($sellPhone->status, ['OFFERED', 'REVISED_OFFER']))
            <div class="flex gap-3">
                <button wire:click="cancel" wire:confirm="Tolak tawaran?"
                    class="w-1/3 py-4 bg-white border border-neutral-200 text-neutral-600 rounded-2xl font-bold hover:bg-neutral-50 transition shadow-sm">Tolak</button>
                <button wire:click="acceptOffer" wire:confirm="Terima tawaran?"
                    class="w-2/3 py-4 bg-emerald-500 text-white rounded-2xl font-bold text-lg hover:bg-emerald-600 transition shadow-lg shadow-emerald-500/25">Terima
                    Tawaran</button>
            </div>
        @elseif ($sellPhone->status === 'WAITING_FOR_DEVICE')
            <div class="bg-blue-50 border border-blue-200 rounded-3xl p-6 md:p-8 shadow-sm">
                <p class="font-bold text-blue-900 mb-2">Input Resi Kurir Anda</p>
                <p class="text-sm text-blue-700 mb-6">Kirim HP Anda ke <strong>TokoPun Pusat (Setiabudi)</strong>, lalu
                    masukkan nomor resinya di bawah ini.</p>
                <form wire:submit="submitReceipt" class="flex flex-col sm:flex-row gap-3">
                    <input type="text" wire:model="customerShippingReceipt"
                        class="flex-1 px-4 py-3.5 rounded-xl border border-blue-300 bg-white font-mono text-sm focus:ring-blue-500"
                        placeholder="Ketik Nomor Resi...">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3.5 rounded-xl font-bold transition shadow-md shadow-blue-600/20 shrink-0">Kirim
                        Resi</button>
                </form>
                @error('customerShippingReceipt')
                    <span class="text-xs text-rose-500 font-bold block mt-2">{{ $message }}</span>
                @enderror
            </div>
        @elseif (Auth::user()->hasRole('fl') && $sellPhone->status === 'PAYING')
            <div class="bg-white border border-neutral-200 rounded-3xl p-6 shadow-sm mb-4 text-center">
                <p class="text-teal-600 font-bold text-sm">Pencairan Dana Sedang Diproses</p>
                {{-- <button wire:click="submitComplete"
                    class="w-full py-4 bg-teal-500 text-white rounded-2xl font-bold hover:bg-teal-600 transition shadow-lg shadow-teal-500/25">
                    Tandai Transaksi Selesai
                </button> --}}
            </div>
        @endif
        @if ($sellPhone->payment_receipt_path)
            <div class="mt-4">
                <h4 class="text-sm font-bold text-gray-700 mb-2">Bukti Pembayaran (Transfer)</h4>
                <div class="rounded-xl overflow-hidden border border-gray-200">
                    {{-- Menggunakan Storage::url() untuk memanggil gambar dari folder public --}}
                    <img src="{{ Storage::url($sellPhone->payment_receipt_path) }}" alt="Bukti Pembayaran"
                        class="w-full h-auto object-cover max-w-sm">
                </div>
                <div class="mt-2 text-xs text-gray-500">
                    Dibayarkan menggunakan Rekening Toko: <span
                        class="font-bold">{{ $sellPhone->store_bank_no }}</span>
                </div>
            </div>
        @else
            <p class="text-xs text-gray-400 italic">Bukti pembayaran belum diunggah oleh Admin.</p>
        @endif

        {{-- Info Readonly Resi / Bank Customer --}}
        @if (in_array($sellPhone->status, ['INSPECTING', 'PAYING', 'COMPLETED']))
            <div class="mt-6 text-center bg-white border border-neutral-200 rounded-2xl p-4">

                <p class="text-xs text-neutral-400 font-medium">Customer: <strong
                        class="text-neutral-700">{{ $sellPhone->user->name }}</strong> • Rekening: <strong
                        class="text-neutral-700">{{ $sellPhone->user->bankAccounts->first()->bank_name }}
                        ({{ $sellPhone->user->bankAccounts->first()->account_number }})</strong></p>

            </div>
        @endif
    </div>
    {{-- ========================================== --}}
    {{-- 3. KONDISI & FOTO UNIT --}}
    {{-- ========================================== --}}
    <div class="bg-white border border-neutral-200 shadow-sm rounded-3xl p-6 md:p-8">
        <h4 class="text-sm font-black text-neutral-800 mb-4 border-b border-neutral-100 pb-3">Kondisi & Bukti Foto</h4>

        <div class="mb-5">
            @if ($sellPhone->minus_desc)
                <ul
                    class="space-y-2 text-sm font-medium text-neutral-600 list-disc list-inside ml-2 marker:text-rose-500">

                    {{-- 1. Pecah dulu berdasarkan titik (. ) untuk memisahkan bagian Catatan Tambahan --}}
                    @foreach (array_filter(explode('. ', $sellPhone->minus_desc)) as $sentence)
                        {{-- 2. Pecah lagi berdasarkan garis vertikal ( | ) untuk memisahkan kategori kondisi --}}
                        @foreach (array_filter(explode(' | ', $sentence)) as $item)
                            @php
                                $cleanItem = trim($item);
                                // Pecah berdasarkan ': ' untuk memisahkan "Nama Kategori" dan "Isi" (Maksimal 2 bagian)
                                $parts = explode(': ', $cleanItem, 2);
                            @endphp

                            <li>
                                {{-- Jika formatnya adalah "Kategori: Isi", tebalkan nama kategorinya --}}
                                @if (count($parts) === 2)
                                    <strong class="text-neutral-800">{{ $parts[0] }}:</strong> {{ $parts[1] }}
                                @else
                                    {{-- Jika tidak ada titik dua (misal kalimat biasa), tampilkan apa adanya --}}
                                    {{ $cleanItem }}
                                @endif
                            </li>
                        @endforeach
                    @endforeach

                </ul>
            @else
                <p class="text-sm font-medium text-emerald-600 bg-emerald-50 px-3 py-2 rounded-lg inline-block">
                    ✅ Mulus Normal / Tidak ada minus
                </p>
            @endif
        </div>

        @php $photos = $sellPhone->getMedia('photos'); @endphp
        @if ($photos->count() > 0)
            <div class="flex overflow-x-auto gap-4 pb-2 snap-x hide-scroll">
                @foreach ($photos as $photo)
                    @php $labelPosisi = $photo->getCustomProperty('label') ?? 'Foto Unit'; @endphp
                    <a href="{{ $photo->getUrl() }}" target="_blank"
                        class="shrink-0 w-32 flex flex-col gap-2 snap-center group">
                        <div
                            class="w-32 h-32 rounded-2xl overflow-hidden border border-neutral-200 bg-neutral-50 relative">
                            <img src="{{ $photo->getUrl() }}"
                                class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                            <div
                                class="absolute inset-0 bg-black/10 opacity-0 group-hover:opacity-100 transition-opacity">
                            </div>
                        </div>
                        <span
                            class="text-xs font-bold text-neutral-500 text-center truncate">{{ $labelPosisi }}</span>
                    </a>
                @endforeach
            </div>
            <style>
                .hide-scroll::-webkit-scrollbar {
                    display: none;
                }
            </style>
        @else
            <p class="text-sm text-neutral-400 italic">Tidak ada foto terlampir.</p>
        @endif
    </div>

{{-- MODAL QC RESULTS --}}
@if ($sellPhone->inspections->isNotEmpty())
<div x-show="showQcModal" style="display: none;"
    class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
    
    {{-- Backdrop --}}
    <div x-transition.opacity.duration.300ms class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" @click="showQcModal = false"></div>

    {{-- Modal Content --}}
    <div class="relative bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-8 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-8 scale-95" @click.stop>

        {{-- Header --}}
        <div class="flex items-center justify-between p-6 border-b border-neutral-100 bg-neutral-50/50">
            <div>
                <h3 class="text-lg font-black text-neutral-800">Detail Inspeksi QC</h3>
                <p class="text-xs text-neutral-500 font-medium mt-1">Status: <span class="uppercase font-bold {{ $sellPhone->inspections->first()->verdict === 'fail' ? 'text-rose-500' : 'text-emerald-500' }}">{{ $sellPhone->inspections->first()->verdict }}</span></p>
            </div>
            <button @click="showQcModal = false" type="button"
                class="p-2 text-neutral-400 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        {{-- Body (Scrollable) --}}
        <div class="p-6 overflow-y-auto">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach ($sellPhone->inspections->first()->checklist_results ?? [] as $item)
                    @php
                        $isOk = ($item['type'] ?? 'boolean') === 'boolean' ? !empty($item['value']) : true;
                    @endphp
                    <div class="flex items-center justify-between p-3 rounded-2xl border {{ $isOk ? 'border-emerald-100 bg-emerald-50/30' : 'border-rose-100 bg-rose-50/50' }}">
                        <span class="text-sm font-bold {{ $isOk ? 'text-neutral-700' : 'text-rose-700' }}">{{ $item['name'] ?? '-' }}</span>
                        @if (($item['type'] ?? 'boolean') === 'boolean')
                            @if ($isOk)
                                <span class="text-emerald-500 bg-white px-2 py-1 rounded-lg text-xs font-black shadow-sm border border-emerald-100">Lulus</span>
                            @else
                                <span class="text-rose-600 bg-white px-2 py-1 rounded-lg text-xs font-black shadow-sm border border-rose-100">Gagal</span>
                            @endif
                        @else
                            <span class="text-neutral-600 font-bold text-sm bg-white px-2 py-1 rounded-lg shadow-sm border border-neutral-100">{{ $item['value'] ?? '-' }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
            
            @if($sellPhone->inspections->first()->inspector_notes)
            <div class="mt-6 bg-neutral-50 rounded-2xl p-4 border border-neutral-100">
                <p class="text-xs font-black text-neutral-400 uppercase tracking-widest mb-2">Catatan Inspektor</p>
                <p class="text-sm font-medium text-neutral-700">{{ $sellPhone->inspections->first()->inspector_notes }}</p>
            </div>
            @endif
        </div>
        
        {{-- Footer --}}
        <div class="p-5 border-t border-neutral-100 bg-neutral-50/50 text-right">
            <button @click="showQcModal = false" type="button" class="px-6 py-2.5 bg-neutral-800 text-white font-bold rounded-xl shadow-sm hover:bg-neutral-900 transition-colors">Tutup</button>
        </div>
    </div>
</div>
@endif

</div>
