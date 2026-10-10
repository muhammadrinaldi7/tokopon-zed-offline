<div>
    {{-- Header with navigation tabs --}}
    @include('livewire.zoffline.ecommerce.partials.header', ['title' => 'Pengaturan E-Commerce & Mobile App'])

    <div class="space-y-6">
        {{-- Hero Intro Card --}}
        <div class="bg-gradient-to-r from-neutral-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="relative z-10 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 text-xs font-bold uppercase tracking-wider mb-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Remote Config Engine
                </div>
                <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                    Pusat Kontrol Fitur Mobile App
                </h2>
                <p class="text-sm text-slate-300 mt-2 leading-relaxed">
                    Atur perilaku aplikasi mobile secara instan tanpa perlu rebuild kode aplikasi. Toggle ini langsung disinkronkan ke endpoint API <code class="text-amber-300 bg-black/40 px-2 py-0.5 rounded text-xs font-mono">/api/v1/mobile/config</code>.
                </p>
            </div>
        </div>

        {{-- Core Feature Toggles Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- 1. Dynamic Logistics Toggle --}}
            <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-sm flex flex-col justify-between hover:border-indigo-100 transition duration-200">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl {{ $dynamicShipping ? 'bg-indigo-50 text-indigo-600' : 'bg-amber-50 text-amber-600' }} flex items-center justify-center text-2xl shadow-xs">
                            🚚
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-black tracking-wide uppercase {{ $dynamicShipping ? 'bg-indigo-100 text-indigo-700' : 'bg-emerald-100 text-emerald-700' }}">
                            {{ $dynamicShipping ? 'Mode Ekspedisi Otomatis' : 'Mode Toko / Free Ongkir' }}
                        </span>
                    </div>

                    <h3 class="text-lg font-black text-gray-900 tracking-tight">Kalkulator Kurir & Ongkir Dinamis</h3>
                    <p class="text-xs text-gray-500 mt-1.5 leading-relaxed">
                        Jika <strong>Non-Aktif</strong>, aplikasi menggunakan metode sederhana: <em>Ambil di Toko (Store Pickup)</em> atau <em>Kurir Toko (Free Ongkir)</em> tanpa perlu integrasi pihak ketiga. Jika <strong>Aktif</strong>, ongkir dihitung otomatis via API ekspedisi (Biteship).
                    </p>

                    <div class="mt-4 p-3.5 rounded-2xl bg-gray-50 border border-gray-100 text-xs text-gray-600 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-500">API Biteship:</span>
                            <span class="font-bold {{ $hasBiteshipKey ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $hasBiteshipKey ? '✓ Kredensial Terpasang' : 'Belum Dikonfigurasi' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-500">Kebutuhan Alamat:</span>
                            <span class="font-bold text-gray-700">{{ $dynamicShipping ? 'Wajib Koordinat / Kode Pos' : 'Alamat Catatan Saja' }}</span>
                        </div>
                    </div>
                </div>

                <div class="pt-6 mt-6 border-t border-gray-100 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-gray-800">Status Fitur:</div>
                        <div class="text-[11px] text-gray-400">{{ $dynamicShipping ? 'Dihitung per kilo & jarak' : 'Biaya pengiriman Rp 0 (Gratis)' }}</div>
                    </div>

                    <button type="button" wire:click="toggleDynamicShipping"
                        class="relative inline-flex h-7 w-14 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 {{ $dynamicShipping ? 'bg-indigo-600' : 'bg-gray-300' }}">
                        <span class="sr-only">Toggle Kurir Otomatis</span>
                        <span class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out {{ $dynamicShipping ? 'translate-x-7' : 'translate-x-0' }}"></span>
                    </button>
                </div>
            </div>

            {{-- 2. Instant Payment Toggle --}}
            <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-sm flex flex-col justify-between hover:border-indigo-100 transition duration-200">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl {{ $instantPayment ? 'bg-purple-50 text-purple-600' : 'bg-emerald-50 text-emerald-600' }} flex items-center justify-center text-2xl shadow-xs">
                            💳
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-black tracking-wide uppercase {{ $instantPayment ? 'bg-purple-100 text-purple-700' : 'bg-emerald-100 text-emerald-700' }}">
                            {{ $instantPayment ? 'Mode Xendit Otomatis' : 'Mode Transfer Manual' }}
                        </span>
                    </div>

                    <h3 class="text-lg font-black text-gray-900 tracking-tight">Pembayaran Instan (Xendit Gateway)</h3>
                    <p class="text-xs text-gray-500 mt-1.5 leading-relaxed">
                        Jika <strong>Non-Aktif</strong>, aplikasi menggunakan metode transfer manual ke rekening toko dan pembeli mengunggah struk bukti transfer. Jika <strong>Aktif</strong>, pembayaran menghasilkan Virtual Account & QRIS otomatis dari Xendit.
                    </p>

                    <div class="mt-4 p-3.5 rounded-2xl bg-gray-50 border border-gray-100 text-xs text-gray-600 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-500">API Xendit:</span>
                            <span class="font-bold {{ $hasXenditKey ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $hasXenditKey ? '✓ Kredensial Terpasang' : 'Belum Dikonfigurasi' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-500">Verifikasi Pembayaran:</span>
                            <span class="font-bold text-gray-700">{{ $instantPayment ? 'Webhook Otomatis' : 'Dicek Kasir / Admin' }}</span>
                        </div>
                    </div>
                </div>

                <div class="pt-6 mt-6 border-t border-gray-100 flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold text-gray-800">Status Fitur:</div>
                        <div class="text-[11px] text-gray-400">{{ $instantPayment ? 'QRIS / VA Xendit aktif' : 'Manual rekening BCA/Mandiri/dll' }}</div>
                    </div>

                    <button type="button" wire:click="toggleInstantPayment"
                        class="relative inline-flex h-7 w-14 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2 {{ $instantPayment ? 'bg-purple-600' : 'bg-gray-300' }}">
                        <span class="sr-only">Toggle Pembayaran Instan</span>
                        <span class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out {{ $instantPayment ? 'translate-x-7' : 'translate-x-0' }}"></span>
                    </button>
                </div>
            </div>

        </div>

        {{-- Secondary Configurations: CS Hotline & Linked Resources --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- CS WhatsApp Hotline Form --}}
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                        📱
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-gray-900">WhatsApp CS Bantuan</h4>
                        <p class="text-[11px] text-gray-400">Nomor kontak darurat di mobile app</p>
                    </div>
                </div>

                <form wire:submit="saveCsPhone" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1.5">Nomor WhatsApp Toko</label>
                        <input type="text" wire:model="csPhone" placeholder="Contoh: 081298765432"
                            class="w-full text-sm font-semibold rounded-xl border-gray-200 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                        @error('csPhone') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                        <p class="text-[11px] text-gray-400 mt-1.5">Format: 0812... atau 62812... Nomor ini langsung dibuka saat pembeli menekan tombol 'Hubungi CS'.</p>
                    </div>

                    <button type="submit"
                        class="w-full bg-neutral-900 hover:bg-neutral-800 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition duration-150 flex items-center justify-center gap-2 shadow-sm">
                        <span wire:loading.remove wire:target="saveCsPhone">Simpan Nomor CS</span>
                        <span wire:loading wire:target="saveCsPhone">Menyimpan...</span>
                    </button>
                </form>
            </div>

            {{-- Active Mobile Bank Accounts Overview --}}
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                            🏦
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-gray-900">Rekening Transfer Aktif</h4>
                            <p class="text-[11px] text-gray-400">{{ $mobileBanks->count() }} rekening tampil di mobile</p>
                        </div>
                    </div>

                    <a href="{{ route('admin.settings.payment-methods') }}" wire:navigate
                        class="text-xs font-bold text-indigo-600 hover:text-indigo-800 underline">
                        Kelola
                    </a>
                </div>

                <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                    @forelse($mobileBanks as $bank)
                        <div class="p-2.5 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-between text-xs">
                            <div>
                                <div class="font-bold text-gray-800">{{ $bank->name }}</div>
                                <div class="text-[11px] text-gray-400">{{ $bank->account_number }} • a.n. {{ $bank->account_owner ?: 'Toko' }}</div>
                            </div>
                            <span class="w-2 h-2 rounded-full bg-emerald-500" title="Aktif di Mobile"></span>
                        </div>
                    @empty
                        <div class="text-center py-6 text-xs text-gray-400">
                            Belum ada rekening dengan status <em>Tampilkan di Mobile</em>.
                        </div>
                    @endforelse
                </div>

                <p class="text-[11px] text-gray-400 mt-3 pt-3 border-t border-gray-100">
                    Aktifkan/nonaktifkan rekening di menu <strong>List Bank</strong> dengan menyalakan toggle <em>Tampilkan di Mobile</em>.
                </p>
            </div>

            {{-- Online Warehouse Overview --}}
            <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                            🏬
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-gray-900">Gudang Stok E-Commerce</h4>
                            <p class="text-[11px] text-gray-400">Gudang sumber stok online</p>
                        </div>
                    </div>

                    <a href="{{ route('admin.settings.warehouse') }}" wire:navigate
                        class="text-xs font-bold text-indigo-600 hover:text-indigo-800 underline">
                        Ubah
                    </a>
                </div>

                <div class="p-3.5 rounded-2xl bg-amber-50/60 border border-amber-200/60 text-xs space-y-1.5">
                    <div class="font-black text-amber-900 text-sm">
                        {{ $onlineWarehouse ? $onlineWarehouse->name : 'Belum Ditentukan' }}
                    </div>
                    <div class="text-amber-800 text-[11px]">
                        {{ $onlineWarehouse ? ($onlineWarehouse->address ?: 'Kode: ' . $onlineWarehouse->code) : 'Pilih gudang utama toko online di menu Pengaturan Gudang.' }}
                    </div>
                </div>

                <p class="text-[11px] text-gray-400 mt-4 leading-relaxed">
                    Stok produk dan nomor seri IMEI yang tampil pada aplikasi mobile dipotong langsung dari gudang ini saat pelanggan checkout.
                </p>
            </div>

        </div>
    </div>
</div>
