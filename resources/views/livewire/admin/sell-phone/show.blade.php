<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="{{ route('admin.sell-phones.index') }}" wire:navigate
                class="text-sm font-bold text-gray-400 hover:text-[#1c69d4] mb-2 inline-flex items-center gap-1 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Detail Penjualan HP #SPL-{{ $sellPhone->id }}</h1>
        </div>
        <div>
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
            @endphp
            <span
                class="px-4 py-2 font-bold uppercase rounded-lg text-sm tracking-wider {{ $statusColors[$sellPhone->status] ?? 'bg-gray-100 text-gray-800' }}">
                Status: {{ str_replace('_', ' ', $sellPhone->status) }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Kolom Kiri: Detail Pengajuan --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Info Perangkat --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-lg text-gray-900 border-b border-gray-100 pb-3 mb-4">Informasi Perangkat</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Merek & Model</p>
                        <p class="font-medium text-gray-900">{{ $sellPhone->phone_brand }} {{ $sellPhone->phone_model }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Kapasitas</p>
                        <p class="font-medium text-gray-900">{{ $sellPhone->phone_ram ?? '-' }} RAM /
                            {{ $sellPhone->phone_storage ?? '-' }} Storage</p>
                    </div>
                    <div class="col-span-2 mt-2">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Deskripsi Kondisi
                            (Catatan Pelanggan)</p>
                        <div class="p-3 bg-gray-50 rounded-lg text-sm text-gray-700 whitespace-pre-wrap font-medium">
                            {{ $sellPhone->minus_desc ?: 'Tidak ada catatan.' }}
                        </div>
                    </div>

                    @if ($sellPhone->buybackDevice)
                        <div class="col-span-2 mt-2">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Master Harga Dasar
                            </p>
                            <div
                                class="p-3 bg-blue-50 border border-blue-100 rounded-lg text-sm text-blue-900 font-medium">
                                Base Price: <span class="font-bold text-lg">Rp
                                    {{ number_format($sellPhone->buybackDevice->base_price, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endif

                    @php $photos = $sellPhone->getMedia('photos'); @endphp
                    @if ($photos->count() > 0)
                        <div class="col-span-2 mt-2 border-t border-gray-100 pt-4">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Foto Fisik Unit
                            </p>
                            <div class="grid grid-cols-3 md:grid-cols-4 gap-3">
                                @foreach ($photos as $photo)
                                    <a href="{{ $photo->getUrl() }}" target="_blank"
                                        class="aspect-square rounded-lg overflow-hidden border border-gray-200 block hover:opacity-80 transition cursor-zoom-in shadow-sm">
                                        <img src="{{ $photo->getUrl() }}" class="w-full h-full object-cover">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Info Pelanggan & Rekening --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-lg text-gray-900 border-b border-gray-100 pb-3 mb-4">Informasi Pelanggan &
                    Pembayaran</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Pelanggan</p>
                        <p class="font-medium text-gray-900">{{ $sellPhone->user->name }}</p>
                        <p class="text-sm text-gray-500">{{ $sellPhone->user->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Tujuan Transfer</p>
                        @if ($sellPhone->user->bankAccounts->first())
                            <p class="font-bold text-emerald-600">
                                {{ $sellPhone->user->bankAccounts->first()->bank_name }}</p>
                            <p class="font-medium text-gray-900">
                                {{ $sellPhone->user->bankAccounts->first()->account_number }}
                            </p>
                            <p class="text-sm text-gray-500">A.N:
                                {{ $sellPhone->user->bankAccounts->first()->account_name }}
                            </p>
                        @else
                            <p class="text-sm text-gray-500 italic">Belum diisi pelanggan.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Aksi --}}
        <div class="space-y-6">
            {{-- Form Penaksiran Harga / Harga Akhir --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                <h3 class="font-bold text-lg text-gray-900 border-b border-gray-100 pb-3 mb-4">Harga Akhir / Penawaran
                </h3>

                @if ($sellPhone->appraised_value)
                    <div class="mb-4 p-4 bg-emerald-50 border border-emerald-100 rounded-lg text-center">
                        <p class="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-1">Nilai Disepakati /
                            Penawaran</p>
                        <p class="text-2xl font-black text-emerald-700">Rp
                            {{ number_format($sellPhone->appraised_value, 0, ',', '.') }}</p>
                        <p class="text-xs text-emerald-600 mt-2">Dihitung otomatis dari Base Price & Rules.</p>
                    </div>
                @endif
            </div>

            {{-- Aksi Lainnya --}}
            @if (in_array($sellPhone->status, ['PENDING', 'OFFERED', 'PAYING', 'WAITING_FOR_DEVICE', 'INSPECTING']))
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-lg text-gray-900 border-b border-gray-100 pb-3 mb-4">Aksi Transaksi</h3>

                    @if ($sellPhone->status === 'INSPECTING')
                        @if (!$isRevising)
                            <div class="space-y-3">
                                <p class="text-sm text-gray-600 mb-4">Fisik HP telah tiba. Silakan cocokan kondisi fisik
                                    asli dengan deskripsi awal. Apakah kondisinya sesuai?</p>

                                <button type="button" wire:click="markAsPaid"
                                    wire:confirm="Sesuai! Anda akan mentransfer uang ke pelanggan dan menandai lunas?"
                                    class="w-full bg-emerald-500 text-white py-2.5 rounded-lg font-bold hover:bg-emerald-600 transition flex items-center justify-center gap-2">

                                    Fisik Sesuai (Lanjutkan Pembayaran)
                                </button>

                                <button type="button" wire:click="$set('isRevising', true)"
                                    class="w-full bg-amber-500 text-white py-2.5 rounded-lg font-bold hover:bg-amber-600 transition flex items-center justify-center gap-2">

                                    Fisik Tidak Sesuai (Revisi Harga)
                                </button>

                                <button type="button" wire:click="reject"
                                    wire:confirm="Yakin menolak transaksi ini mentah-mentah dan mengembalikan unit ke pelanggan?"
                                    class="w-full bg-white border-2 border-rose-100 text-rose-600 py-2.5 rounded-lg font-bold hover:bg-rose-50 transition mt-2">
                                    Tolak Mentah-mentah
                                </button>
                            </div>
                        @else
                            <form wire:submit="submitRevision"
                                class="space-y-4 bg-amber-50 p-4 rounded-lg border border-amber-100">
                                <h4 class="font-bold text-amber-900">Revisi Nilai Penawaran</h4>
                                <p class="text-xs text-amber-700">Karena kita tidak menambahkan form alasan, pelanggan
                                    akan otomatis diberitahu bahwa fisik tidak sesuai ekspektasi.</p>
                                <div>
                                    <label class="block text-sm font-bold text-amber-900 mb-1">Harga Penawaran Baru
                                        (Rp)</label>
                                    <input type="number" wire:model="revisedAppraisedValue"
                                        class="w-full rounded-lg border-amber-200 focus:ring-amber-500 focus:border-amber-500 bg-white">
                                    @error('revisedAppraisedValue')
                                        <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" wire:click="$set('isRevising', false)"
                                        class="flex-1 bg-white border border-gray-200 text-gray-600 py-2.5 rounded-lg font-bold hover:bg-gray-50 transition">
                                        Batal
                                    </button>
                                    <button type="submit"
                                        class="flex-1 bg-amber-500 text-white py-2.5 rounded-lg font-bold hover:bg-amber-600 transition shadow-sm shadow-amber-500/20">
                                        Kirim Revisi
                                    </button>
                                </div>
                            </form>
                        @endif
                    @elseif($sellPhone->status === 'REVISED_OFFER')
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                            <p class="font-bold text-amber-900 text-sm">Menunggu Respon Pelanggan</p>
                            <p class="text-xs text-amber-700 mt-1">Anda baru saja mengajukan revisi harga sebesar
                                <strong>Rp {{ number_format($sellPhone->appraised_value, 0, ',', '.') }}</strong>.
                                Menunggu klien untuk menyetujui atau menolak.
                            </p>
                        </div>
                    @else
                        <div class="space-y-3">
                            <button type="button" wire:click="markAsPaid"
                                wire:confirm="Sesuai skenario, pastikan Anda sudah mengecek fisik HP pelanggan langsung. Yakin menandai transaksi ini Lunas?"
                                class="w-full bg-emerald-500 text-white py-2.5 rounded-lg font-bold hover:bg-emerald-600 transition flex items-center justify-center gap-2">

                                Tandai Selesai / Lunas
                            </button>

                            <button type="button" wire:click="reject" wire:confirm="Yakin ingin menolak penawaran ini?"
                                class="w-full bg-white border-2 border-rose-100 text-rose-600 py-2.5 rounded-lg font-bold hover:bg-rose-50 transition">
                                Tolak / Batalkan
                            </button>
                        </div>

                    @endif
                </div>
            @endif

            {{-- Konversi Inventaris --}}
            @if ($sellPhone->status === 'COMPLETED' && !\App\Models\ProductVariant::where('sell_phone_id', $sellPhone->id)->exists())
                <div class="bg-purple-50 rounded-lg border border-purple-100 p-6 animate-in zoom-in duration-300">
                    <h3 class="font-bold text-lg text-purple-900 mb-2">Masuk Ke Inventaris</h3>
                    <p class="text-sm text-purple-700 mb-4">Transaksi sudah lunas. Daftarkan HP ini ke etalase toko
                        sebagai barang seken (Second Hand) agar bisa langsung dibeli orang lain.</p>

                    <button type="button" wire:click="$set('convertModal', true)"
                        class="w-full bg-purple-600 text-white py-2.5 rounded-lg font-bold hover:bg-purple-700 transition">
                        Jual Sebagai Barang Second
                    </button>
                </div>

                {{-- Modal Konversi --}}
                @if ($convertModal)
                    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
                        wire:click.self="$set('convertModal', false)">
                        <div class="bg-white rounded-3xl shadow-sm w-full max-w-md mx-4 overflow-hidden">
                            <div class="bg-gradient-to-r from-purple-500 to-#1c69d4 px-6 py-5 text-white">
                                <h2 class="text-xl font-bold">Daftarkan Produk Second</h2>
                                <p class="text-purple-100 text-sm mt-1">{{ $sellPhone->phone_brand }}
                                    {{ $sellPhone->phone_model }}</p>
                            </div>
                            <form wire:submit="convertToProduct" class="p-6 space-y-5">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Harga Jual Baru
                                            (Rp)</label>
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <span class="text-gray-500 font-bold">Rp</span>
                                            </div>
                                            <input type="number" wire:model="sellPrice"
                                                class="pl-12 w-full text-lg font-bold rounded-lg border-gray-200 py-3 focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500"
                                                placeholder="0" required>
                                        </div>
                                        @error('sellPrice')
                                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">Kondisi</label>
                                        <select wire:model="secondCondition"
                                            class="w-full rounded-lg border-gray-200 py-3 focus:ring-purple-500 focus:border-purple-500">
                                            <option value="Like New">Like New</option>
                                            <option value="Bekas (Mulus)">Bekas (Mulus)</option>
                                            <option value="Bekas (Ada Minus)">Bekas (Ada Minus)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end gap-3">
                                    <button type="button" wire:click="$set('convertModal', false)"
                                        class="flex-1 px-4 py-3 font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition">Batal</button>
                                    <button type="submit"
                                        class="flex-1 px-4 py-3 font-bold text-white bg-purple-600 hover:bg-purple-700 rounded-lg transition shadow-sm shadow-purple-500/25">Simpan
                                        ke Katalog</button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            @elseif(\App\Models\ProductVariant::where('sell_phone_id', $sellPhone->id)->exists())
                <div class="bg-emerald-50 rounded-lg border border-emerald-100 p-6 text-center">
                    <div
                        class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-emerald-900">Telah Masuk Katalog</h3>
                    <p class="text-sm text-emerald-700 mt-1">HP ini sudah didaftarkan sebagai varian produk second dan
                        siap dijual.</p>
                </div>
            @endif
        </div>
    </div>
</div>
