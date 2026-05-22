<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.trade-ins.index') }}" wire:navigate class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">Detail Pengajuan #TRD-{{ $tradeIn->id }}</h1>
            </div>
            <p class="text-sm text-gray-500 mt-1 ml-9">Pelanggan: <span class="font-bold text-gray-700">{{ $tradeIn->user->name }}</span> ({{ $tradeIn->created_at->format('d M Y, H:i') }})</p>
        </div>
        <div>
            @php
                $statusColors = [
                    'PENDING' => 'bg-amber-100 text-amber-800',
                    'OFFERED' => 'bg-blue-100 text-blue-800',
                    'WAITING_FOR_DEVICE' => 'bg-purple-100 text-purple-800',
                    'INSPECTING' => 'bg-indigo-100 text-indigo-800',
                    'WAITING_PAYMENT' => 'bg-orange-100 text-orange-800',
                    'PAYING' => 'bg-teal-100 text-teal-800',
                    'COMPLETED' => 'bg-emerald-100 text-emerald-800',
                    'CANCELLED' => 'bg-rose-100 text-rose-800',
                ];
            @endphp
            <span class="px-4 py-2 font-bold uppercase rounded-lg tracking-wider border border-white/20 shadow-sm {{ $statusColors[$tradeIn->status] ?? 'bg-gray-100 text-gray-800' }}">
                Status: {{ str_replace('_', ' ', $tradeIn->status) }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- LEFT PANEL: Customer Old Phone Details --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-900 text-lg mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                    Informasi HP Lama (Milik User)
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Merek & Tipe</p>
                        <p class="font-bold text-gray-900 text-lg">{{ $tradeIn->old_phone_brand }} {{ $tradeIn->old_phone_model }}</p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                            <p class="text-[10px] text-gray-400 font-bold uppercase">RAM</p>
                            <p class="font-semibold text-gray-800">{{ $tradeIn->old_phone_ram ?? '-' }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                            <p class="text-[10px] text-gray-400 font-bold uppercase">Storage</p>
                            <p class="font-semibold text-gray-800">{{ $tradeIn->old_phone_storage ?? '-' }}</p>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-1">Deskripsi Minus/Kondisi (Oleh Klien)</p>
                        <div class="bg-rose-50 border border-rose-100 rounded-lg p-4 text-sm text-rose-800">
                            {!! nl2br(e($tradeIn->old_phone_minus_desc)) !!}
                        </div>
                    </div>

                    @if($tradeIn->buybackDevice)
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Master Harga Dasar</p>
                        <div class="p-3 bg-blue-50 border border-blue-100 rounded-lg text-sm text-blue-900 font-medium">
                            Base Price: <span class="font-bold text-lg">Rp {{ number_format($tradeIn->buybackDevice->base_price, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Photos --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-gray-900 text-lg mb-4">Foto Fisik Unit Asli</h3>
                @php $photos = $tradeIn->getMedia('customer_unit_photos'); @endphp
                @if($photos->count() > 0)
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($photos as $photo)
                            <a href="{{ $photo->getUrl() }}" target="_blank" class="aspect-square rounded-lg overflow-hidden border border-gray-200 block hover:opacity-80 transition cursor-zoom-in">
                                <img src="{{ $photo->getUrl() }}" class="w-full h-full object-cover">
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 italic">Pengguna tidak melampirkan foto fisik.</p>
                @endif
            </div>

            {{-- Tracking Resi --}}
            @if($tradeIn->customer_shipping_receipt)
                <div class="bg-[#1c69d4]/5 rounded-lg border border-[#1c69d4]/20 p-6">
                    <h3 class="font-bold text-[#1c69d4] mb-1">Resi Pengiriman Pelanggan</h3>
                    <p class="text-sm text-gray-600 mb-2">Pelanggan telah mengirim unit lama ke toko kita via ekspedisi.</p>
                    <div class="bg-white px-4 py-3 rounded-lg border border-gray-200 font-mono text-gray-800 font-bold tracking-widest text-center shadow-sm">
                        {{ $tradeIn->customer_shipping_receipt }}
                    </div>
                </div>
            @endif
        </div>

        {{-- RIGHT PANEL: Appraisal & Actions --}}
        <div class="lg:col-span-2 space-y-6">
            
            @if(in_array($tradeIn->status, ['WAITING_FOR_DEVICE', 'INSPECTING']))
                {{-- Form Pilih Variant --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 {{ $tradeIn->status === 'INSPECTING' ? 'mt-6 border-amber-200 bg-amber-50/30' : '' }}">
                    <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-4">
                        <h3 class="font-bold text-gray-900 text-xl">
                            Harga Disepakati
                        </h3>
                        <div class="text-right">
                            <span class="text-xs font-bold text-emerald-600 uppercase tracking-widest block mb-1">Trade In Value</span>
                            @if(in_array($tradeIn->status, ['WAITING_FOR_DEVICE', 'INSPECTING']))
                                <div class="flex items-center justify-end gap-1">
                                    <span class="text-lg font-bold text-emerald-700">Rp</span>
                                    <input type="number" wire:model.live="appraisedValue" class="text-2xl font-black text-emerald-700 w-40 border-0 border-b-2 border-emerald-200 focus:ring-0 focus:border-emerald-500 px-1 bg-transparent text-right p-0 m-0 leading-none h-8">
                                    <button type="button" wire:click="updateAppraisedValue" class="ml-2 bg-emerald-100 text-emerald-700 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider hover:bg-emerald-200 transition shadow-sm">Ubah Harga</button>
                                </div>
                                @error('appraisedValue') <span class="text-xs text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                            @else
                                <span class="text-2xl font-black text-emerald-700">Rp {{ number_format($tradeIn->appraised_value, 0, ',', '.') }}</span>
                            @endif
                        </div>
                    </div>

                    <p class="text-sm text-gray-500 mb-6">
                        Silakan pilih 1 (satu) unit spesifik dari gudang yang akan ditukarkan untuk konsumen berdasarkan target produk mereka.
                    </p>

                    <form>
                        <div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h4 class="font-bold text-gray-900">Pilih Unit Kandidat</h4>
                                <p class="text-sm text-gray-500">Kandidat unit produk incaran: 
                                    <span class="font-bold {{ $tradeIn->target_product_type === \App\Models\Product::class ? 'text-emerald-600' : 'text-[#1c69d4]' }}">
                                        [{{ $tradeIn->target_product_type === \App\Models\Product::class ? 'BARU' : 'SECOND' }}] {{ $tradeIn->targetProduct->name }}
                                    </span>
                                </p>
                            </div>
                            <div class="relative max-w-xs">
                                <input type="text" wire:model.live.debounce.300ms="searchVariant" placeholder="Cari warna/storage..." class="w-full text-sm rounded-lg border-gray-200 py-2 focus:ring-[#1c69d4] focus:border-[#1c69d4]">
                            </div>
                        </div>
                        @error('selectedVariants') <span class="text-sm text-rose-500 font-bold block mb-3 bg-rose-50 p-2 rounded-lg">{{ $message }}</span> @enderror

                        <div class="space-y-3 mb-8 max-h-80 overflow-y-auto pr-2">
                            @forelse($availableVariants as $variant)
                                @php
                                    $isSelected = in_array($variant->id, $selectedVariants);
                                @endphp
                                <label class="block cursor-pointer">
                                    <div class="flex items-center gap-4 p-4 rounded-lg border-2 transition-all {{ $isSelected ? 'border-[#1c69d4] bg-[#1c69d4]/5' : 'border-gray-100 hover:border-gray-300' }}">
                                        <div class="w-5 h-5 rounded-full border {{ $isSelected ? 'bg-[#1c69d4] border-[#1c69d4]' : 'border-gray-300' }} flex items-center justify-center shrink-0">
                                            @if($isSelected)
                                                <div class="w-2.5 h-2.5 bg-white rounded-full"></div>
                                            @endif
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <h5 class="font-bold text-gray-900">{{ $variant->color }} - {{ $variant->storage }}</h5>
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-gray-100 text-gray-600">
                                                    {{ $tradeIn->target_product_type === \App\Models\Product::class ? 'Baru' : ($variant->condition_desc ?? 'Bekas') }}
                                                </span>
                                            </div>
                                            <p class="text-sm font-black text-[#1c69d4] mt-1">Harga: Rp {{ number_format($variant->price, 0, ',', '.') }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[10px] text-gray-400 font-bold uppercase mb-1">Estimasi Tambahan dari Klien</p>
                                            @php
                                                $topup = max(0, $variant->price - (float) ($appraisedValue ?: 0));
                                            @endphp
                                            <span class="font-bold {{ $topup > 0 ? 'text-amber-600' : 'text-emerald-500' }}">Rp {{ number_format($topup, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                    <input type="radio" name="variant_selection" class="hidden" wire:click="toggleVariant({{ $variant->id }})" value="{{ $variant->id }}">
                                </label>
                            @empty
                                <div class="text-center py-6 bg-gray-50 rounded-lg border border-gray-200 border-dashed">
                                    <p class="text-sm text-gray-500">Tidak ada unit (IMEI) yang tersedia dengan stok > 0 untuk produk ini.</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="flex gap-4">
                            <button type="button" wire:click="cancelTradeIn" wire:confirm="Yakin ingin membatalkan aplikasi trade-in secara sepihak?" class="px-6 py-3.5 rounded-lg font-bold bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition">
                                Tolak & Batalkan
                            </button>
                            <button type="button" wire:click="markAsPhysicallyVerified" wire:confirm="Penawaran ini akan dikunci dan dikirim ke halaman Pelanggan untuk dikonfirmasi. Lanjutkan?" class="flex-1 px-6 py-3 rounded-lg font-bold bg-[#1c69d4] text-white hover:bg-indigo-700 shadow-sm shadow-[#1c69d4]/30 transition">
                                Ajukan Penawaran Harga Akhir
                            </button>
                        </div>
                    </form>
                </div>
            @endif


            
            @if($tradeIn->status === 'OFFERED')
                <div class="bg-amber-50 rounded-lg shadow-sm border border-amber-200 p-6 mt-6">
                    <h3 class="font-bold text-amber-800 mb-2 text-xl flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Menunggu Persetujuan Pelanggan
                    </h3>
                    <p class="text-amber-700 mb-4">Anda telah mengajukan penawaran harga akhir. Silakan arahkan FrontLiner atau Pelanggan untuk mengecek halaman "Detail Transaksi" di akun mereka dan menekan tombol persetujuan.</p>
                    <div class="bg-white p-4 rounded-lg border border-amber-100 flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500 font-bold mb-1">Top-Up yang Harus Dibayar:</p>
                            <p class="text-2xl font-black text-amber-600">Rp {{ number_format($tradeIn->topup_amount, 0, ',', '.') }}</p>
                        </div>
                        <button type="button" wire:click="cancelTradeIn" wire:confirm="Batalkan penawaran ini?" class="px-4 py-2 rounded-lg font-bold bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 transition">
                            Batal Sepihak
                        </button>
                    </div>
                </div>
            @endif
            
            @if(in_array($tradeIn->status, ['WAITING_PAYMENT', 'PAYING', 'COMPLETED', 'CANCELLED']))
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 opacity-70">
                    <h3 class="font-bold text-gray-900 mb-2">Penawaran yang Terkunci</h3>
                    <p class="text-sm text-gray-500 mb-4">Konsumen sudah menyetujui unit atau transaksi sudah berjalan melebihi tahap penawaran.</p>
                    <div class="text-2xl font-black text-emerald-600 mb-4">
                        Taksiran Disetujui: Rp {{ number_format($tradeIn->appraised_value, 0, ',', '.') }}
                    </div>
                    @if($tradeIn->product_variant_id)
                        @php $var = \App\Models\SecondProductVariant::find($tradeIn->product_variant_id); @endphp
                        @if($var)
                        <div class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                            <p class="text-xs font-bold text-gray-500 uppercase">Unit Diberikan (Target):</p>
                            <p class="font-bold text-gray-800">{{ $tradeIn->targetProduct->name }} ({{ $var->color }} - {{ $var->storage }})</p>
                        </div>
                        @endif
                    @endif
                </div>
            @endif

            @if($tradeIn->status === 'WAITING_PAYMENT')
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-6 relative">
                    <h3 class="font-bold text-orange-900 text-xl mb-2">Menunggu Pembayaran Manual</h3>
                    <p class="text-sm text-orange-700 mb-5">Pelanggan perlu mentransfer sisa tagihan sebesar <strong>Rp {{ number_format($tradeIn->topup_amount, 0, ',', '.') }}</strong>. Admin Finance memverifikasi transfer sebelum konfirmasi.</p>
                    <button wire:click="promptConfirmPayment" class="bg-orange-500 text-white px-6 py-3 rounded-lg font-bold hover:bg-orange-600 transition shadow-sm shadow-orange-500/25">
                        <svg class="w-5 h-5 inline-block mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                        Konfirmasi Pembayaran Diterima
                    </button>

                    {{-- Modal Konfirmasi & Input SN --}}
                    @if($showConfirmModal)
                        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden border border-gray-100">
                                <div class="p-6 bg-gray-50 border-b border-gray-100">
                                    <h3 class="font-black text-xl text-gray-900">Konfirmasi Integrasi Accurate</h3>
                                    <p class="text-sm text-gray-500 mt-1">Lengkapi data Serial Number (SN) / IMEI untuk Purchase & Sales Invoice.</p>
                                </div>
                                <div class="p-6 space-y-5">
                                    <div>
                                        <label class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-1 block">
                                            SN HP Lama Pelanggan
                                            <span class="text-rose-500">*</span>
                                        </label>
                                        <div class="text-[10px] text-gray-400 mb-2 leading-tight">Digunakan untuk Purchase Invoice (Toko menerima HP lama). Anda dapat membiarkan nilai auto-generated ini atau mengubahnya.</div>
                                        <input type="text" wire:model="oldPhoneSN" class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:border-[#1c69d4] focus:ring-0 transition-all font-bold text-gray-700 bg-gray-50" placeholder="Contoh: IMEI123456789">
                                        @error('oldPhoneSN') <span class="text-xs text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-1 block">
                                            SN HP Incaran (Baru/Second)
                                            <span class="text-rose-500">*</span>
                                        </label>
                                        <div class="text-[10px] text-gray-400 mb-2 leading-tight">Digunakan untuk Sales Invoice (Toko menyerahkan HP incaran).</div>
                                        <input type="text" wire:model="targetSN" class="w-full border border-gray-200 rounded-lg p-3 text-sm focus:border-[#1c69d4] focus:ring-0 transition-all font-bold text-[#1c69d4]" placeholder="Ketik SN / IMEI perangkat di sini">
                                        @error('targetSN') <span class="text-xs text-rose-500 font-bold block mt-1">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="p-6 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                                    <button wire:click="$set('showConfirmModal', false)" type="button" class="px-5 py-2.5 rounded-lg text-gray-600 font-bold bg-white border border-gray-200 hover:bg-gray-100 transition-all text-sm">Batal</button>
                                    <button wire:click="confirmPayment" wire:target="confirmPayment" wire:loading.attr="disabled" type="button" class="px-5 py-2.5 rounded-lg text-white font-bold bg-[#1c69d4] hover:bg-blue-700 transition-all text-sm shadow-md shadow-blue-500/20 flex items-center gap-2">
                                        <span wire:loading.remove wire:target="confirmPayment">Submit & Sinkronisasi Accurate</span>
                                        <span wire:loading wire:target="confirmPayment">Memproses...</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Tombol Konversi ke Produk Second --}}
            @if($tradeIn->status === 'COMPLETED')
                @php
                    $alreadyConverted = \App\Models\SecondProductVariant::where('trade_in_id', $tradeIn->id)->exists();
                @endphp

                @if($alreadyConverted)
                    <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-6">
                        <div class="flex items-center gap-3 mb-2">
                            <svg class="w-6 h-6 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <h3 class="font-bold text-emerald-800 text-lg">Sudah Dikonversi ke Katalog Second</h3>
                        </div>
                        <p class="text-sm text-emerald-700">Unit HP lama dari trade-in ini sudah masuk ke inventaris produk bekas dan siap dijual di toko online.</p>
                    </div>
                @else
                    <div class="bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200 rounded-lg p-6">
                        <h3 class="font-bold text-amber-900 text-xl mb-2">Konversi HP Lama ke Produk Second</h3>
                        <p class="text-sm text-amber-700 mb-5">Trade-in selesai. HP lama pelanggan kini jadi milik toko. Masukkan ke katalog produk bekas agar bisa dijual kembali secara online.</p>
                        <button wire:click="$set('convertModal', true)" class="bg-amber-500 text-white px-6 py-3 rounded-lg font-bold hover:bg-amber-600 transition shadow-sm shadow-amber-500/25">
                            <svg class="w-5 h-5 inline-block mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            Jual Sebagai Produk Second
                        </button>
                    </div>
                @endif
            @endif

        </div>
    </div>

    {{-- Modal Konversi --}}
    @if($convertModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" wire:click.self="$set('convertModal', false)">
            <div class="bg-white rounded-3xl shadow-sm w-full max-w-lg mx-4 overflow-hidden">
                <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-5 text-white">
                    <h2 class="text-xl font-bold">Konversi ke Produk Second</h2>
                    <p class="text-amber-100 text-sm mt-1">{{ $tradeIn->old_phone_brand }} {{ $tradeIn->old_phone_model }} — {{ $tradeIn->old_phone_storage ?? '' }}</p>
                </div>
                <form wire:submit="convertToProduct" class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Harga Jual (Dipasang oleh Manajer) <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-gray-500 font-bold">Rp</span>
                            </div>
                            <input type="number" wire:model="sellPrice" class="pl-12 w-full text-lg font-bold rounded-lg border-gray-200 py-3 focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500" placeholder="0" required>
                        </div>
                        @error('sellPrice') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Kondisi Fisik</label>
                        <select wire:model="secondCondition" class="w-full rounded-lg border-gray-200 py-3 focus:ring-amber-500 focus:border-amber-500">
                            <option value="Bekas - Mulus">Bekas - Mulus</option>
                            <option value="Bekas - Normal">Bekas - Normal</option>
                            <option value="Bekas - Minus">Bekas - Minus</option>
                            <option value="Bekas">Bekas</option>
                        </select>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 text-sm text-gray-600">
                        <p><strong>Info:</strong> Ini akan membuat entri <strong>Produk</strong> baru bernama <em>{{ $tradeIn->old_phone_brand }} {{ $tradeIn->old_phone_model }}</em> dengan flag <code>is_second = true</code>, dan 1 varian fisik berstok 1 unit.</p>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="$set('convertModal', false)" class="flex-1 py-3 rounded-lg font-bold bg-gray-100 text-gray-600 hover:bg-gray-200 transition">Batal</button>
                        <button type="submit" class="flex-1 py-3 rounded-lg font-bold bg-amber-500 text-white hover:bg-amber-600 transition shadow-sm shadow-amber-500/25">
                            <span wire:loading.remove wire:target="convertToProduct">Simpan & Masukkan Katalog</span>
                            <span wire:loading wire:target="convertToProduct">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
