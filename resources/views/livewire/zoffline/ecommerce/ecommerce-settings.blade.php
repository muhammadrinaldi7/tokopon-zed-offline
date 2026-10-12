<div class="p-4 sm:p-6 min-h-screen bg-neutral-100 space-y-6">
    {{-- Header with navigation tabs --}}
    @include('livewire.zoffline.ecommerce.partials.header', ['title' => 'Pengaturan E-Commerce & Mobile App'])

    <div class="space-y-6">
        {{-- Hero Intro Card --}}
        <div class="bg-gradient-to-r from-neutral-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="relative z-10 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-300 text-xs font-bold uppercase tracking-wider mb-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Remote Config & Multi-Unit Engine
                </div>
                <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-white">
                    Pusat Kontrol Fitur & Unit Usaha E-Commerce
                </h2>
                <p class="text-sm text-slate-300 mt-2 leading-relaxed">
                    Atur perilaku aplikasi mobile dan konfigurasi independen per Unit Usaha (gudang online, cabang Accurate, sales, kasir, dan rekening bank).
                </p>
            </div>
        </div>

        {{-- Core Feature Toggles Grid (Global System) --}}
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

        {{-- GLOBAL CS HOTLINE & HELPDESK --}}
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl shadow-xs">
                        📱
                    </div>
                    <div>
                        <h4 class="text-base font-black text-gray-900">WhatsApp CS Bantuan & Call Center</h4>
                        <p class="text-xs text-gray-500">Nomor kontak resmi yang dibuka saat pelanggan menekan tombol 'Hubungi CS' di aplikasi mobile</p>
                    </div>
                </div>

                <form wire:submit="saveCsPhone" class="flex items-center gap-3 w-full sm:w-auto">
                    <input type="text" wire:model="csPhone" placeholder="Contoh: 081298765432"
                        class="text-xs font-semibold rounded-xl border-gray-200 px-4 py-2.5 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition w-full sm:w-60">
                    <button type="submit"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition duration-150 shrink-0 shadow-sm shadow-emerald-600/25">
                        <span wire:loading.remove wire:target="saveCsPhone">Simpan CS</span>
                        <span wire:loading wire:target="saveCsPhone">Menyimpan...</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- ========================================================== --}}
        {{-- SECTION: PENGATURAN SPESIFIK PER BUSINESS UNIT (MULTI-UNIT) --}}
        {{-- ========================================================== --}}
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm space-y-6">
            
            {{-- Header Multi-Unit --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-gray-100">
                <div>
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-[11px] font-black uppercase tracking-wider mb-1.5">
                        <span>🏢</span> Unit Usaha Scoped Configuration
                    </div>
                    <h3 class="text-xl font-black text-gray-900 tracking-tight">
                        Pengaturan Khusus Unit Usaha: <span class="text-indigo-600">{{ $currentBU ? $currentBU->name : 'Pilih Unit' }}</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Pilih tab Unit Usaha di bawah ini untuk menentukan Gudang Online, Cabang Accurate, Sales, Kasir, dan Rekening Bank masing-masing unit tanpa saling mengganggu.
                    </p>
                </div>

                {{-- Unit Usaha Tab Buttons --}}
                <div class="flex items-center flex-wrap gap-2">
                    @foreach($businessUnits as $bu)
                        <button type="button" wire:click="selectBusinessUnit({{ $bu->id }})"
                            class="px-4 py-2 rounded-2xl text-xs font-black transition-all flex items-center gap-2 shadow-xs {{ $selectedBusinessUnitId == $bu->id ? 'bg-indigo-600 text-white shadow-indigo-600/20' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            <span class="w-2 h-2 rounded-full {{ $selectedBusinessUnitId == $bu->id ? 'bg-white' : 'bg-gray-400' }}"></span>
                            {{ $bu->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Grid Setting Per Unit Usaha --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                {{-- 1. Online Warehouse for this BU --}}
                <div class="bg-neutral-50 rounded-2xl p-5 border border-gray-200/70 flex flex-col justify-between hover:border-amber-200 transition">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-xl font-bold">
                                🏬
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-gray-900">Gudang Online Store</h4>
                                <p class="text-[11px] text-gray-500">Sumber stok online {{ $currentBU?->name }}</p>
                            </div>
                        </div>

                        <div class="space-y-3 mt-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Pilih Gudang Online Unit Ini</label>
                                <select wire:model="selectedWarehouseId"
                                    class="w-full text-xs font-semibold rounded-xl border-gray-200 px-3.5 py-2.5 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition bg-white">
                                    <option value="">-- Tidak Ada Gudang Online Aktif --</option>
                                    @foreach($buWarehouses as $wh)
                                        <option value="{{ $wh->id }}">
                                            {{ $wh->name }} {{ $wh->is_online_store ? '★ (Aktif Saat Ini)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="p-3 rounded-xl bg-amber-50 border border-amber-200/60 text-xs text-amber-900">
                                <span class="font-bold">Gudang Aktif Unit:</span> {{ $onlineWarehouse ? $onlineWarehouse->name : 'Belum Ditentukan' }}
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 mt-4 border-t border-gray-200/60">
                        <button type="button" wire:click="saveWarehouse"
                            class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition duration-150 flex items-center justify-center gap-2 shadow-xs">
                            <span wire:loading.remove wire:target="saveWarehouse">Simpan Gudang Online ({{ $currentBU?->name }})</span>
                            <span wire:loading wire:target="saveWarehouse">Menyimpan...</span>
                        </button>
                    </div>
                </div>

                {{-- 2. Accurate Default Branch for this BU --}}
                <div class="bg-neutral-50 rounded-2xl p-5 border border-gray-200/70 flex flex-col justify-between hover:border-teal-200 transition">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center text-xl font-bold">
                                🏢
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-gray-900">Cabang Faktur Accurate</h4>
                                <p class="text-[11px] text-gray-500">Target cabang header faktur {{ $currentBU?->name }}</p>
                            </div>
                        </div>

                        <div class="space-y-3 mt-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Nama Cabang Default</label>
                                @if($buBranches->isNotEmpty())
                                    <select wire:model="defaultBranchName"
                                        class="w-full text-xs font-semibold rounded-xl border-gray-200 px-3.5 py-2.5 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition mb-2 bg-white">
                                        @foreach($buBranches as $branch)
                                            <option value="{{ $branch->name }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                <input type="text" wire:model="defaultBranchName" placeholder="Contoh: Banjarbaru"
                                    class="w-full text-xs font-semibold rounded-xl border-gray-200 px-3.5 py-2.5 focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition bg-white">
                                <span class="text-[11px] text-gray-400 mt-1 block">Pastikan sesuai master Cabang Accurate Online unit ini.</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 mt-4 border-t border-gray-200/60">
                        <button type="button" wire:click="saveDefaultBranch"
                            class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition duration-150 flex items-center justify-center gap-2 shadow-xs">
                            <span wire:loading.remove wire:target="saveDefaultBranch">Simpan Cabang Accurate</span>
                            <span wire:loading wire:target="saveDefaultBranch">Menyimpan...</span>
                        </button>
                    </div>
                </div>

                {{-- 3. Default Sales / CS for this BU --}}
                <div class="bg-neutral-50 rounded-2xl p-5 border border-gray-200/70 flex flex-col justify-between hover:border-purple-200 transition">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl font-bold">
                                🧑‍💼
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-gray-900">Default Sales E-Commerce</h4>
                                <p class="text-[11px] text-gray-500">Pramuniaga order online {{ $currentBU?->name }}</p>
                            </div>
                        </div>

                        <div class="space-y-3 mt-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Pilih Karyawan Sales Unit Ini</label>
                                <select wire:model="defaultSalesId"
                                    class="w-full text-xs font-semibold rounded-xl border-gray-200 px-3.5 py-2.5 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition bg-white">
                                    <option value="">-- Tanpa Sales Default (Kosong) --</option>
                                    @foreach($buSales as $sales)
                                        <option value="{{ $sales->id }}">
                                            {{ $sales->name }} {{ $sales->employee_no ? "({$sales->employee_no})" : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="text-[11px] text-gray-400 mt-1 block">Otomatis tercatat atas nama sales ini di Accurate untuk pesanan {{ $currentBU?->name }}.</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 mt-4 border-t border-gray-200/60">
                        <button type="button" wire:click="saveDefaultSales"
                            class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition duration-150 flex items-center justify-center gap-2 shadow-xs">
                            <span wire:loading.remove wire:target="saveDefaultSales">Simpan Sales Default</span>
                            <span wire:loading wire:target="saveDefaultSales">Menyimpan...</span>
                        </button>
                    </div>
                </div>

                {{-- 4. Default Cashier / Handled By for this BU --}}
                <div class="bg-neutral-50 rounded-2xl p-5 border border-gray-200/70 flex flex-col justify-between hover:border-blue-200 transition">
                    <div>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-xl font-bold">
                                🖥️
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-gray-900">Kasir / Handled By Default</h4>
                                <p class="text-[11px] text-gray-500">Penanggung jawab order {{ $currentBU?->name }}</p>
                            </div>
                        </div>

                        <div class="space-y-3 mt-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Pilih Kasir / Admin Penanggung Jawab</label>
                                <select wire:model="defaultHandledById"
                                    class="w-full text-xs font-semibold rounded-xl border-gray-200 px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition bg-white">
                                    <option value="">-- Tanpa Kasir Default (Admin Approval Manual) --</option>
                                    @foreach($buCashiers as $cashier)
                                        <option value="{{ $cashier->id }}">
                                            {{ $cashier->name }} ({{ $cashier->email }})
                                        </option>
                                    @endforeach
                                </select>
                                <span class="text-[11px] text-gray-400 mt-1 block">Ditugaskan pada kolom <code class="text-[10px] bg-gray-200 px-1 py-0.5 rounded">handled_by</code> order mobile saat pesanan dibuat.</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 mt-4 border-t border-gray-200/60">
                        <button type="button" wire:click="saveDefaultHandledBy"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition duration-150 flex items-center justify-center gap-2 shadow-xs">
                            <span wire:loading.remove wire:target="saveDefaultHandledBy">Simpan Kasir Default</span>
                            <span wire:loading wire:target="saveDefaultHandledBy">Menyimpan...</span>
                        </button>
                    </div>
                </div>

                {{-- 5. Active Mobile Bank Accounts for this BU --}}
                <div class="bg-neutral-50 rounded-2xl p-5 border border-gray-200/70 flex flex-col justify-between hover:border-emerald-200 transition md:col-span-2">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold">
                                    🏦
                                </div>
                                <div>
                                    <h4 class="text-sm font-black text-gray-900">Rekening Transfer Aktif di Mobile</h4>
                                    <p class="text-[11px] text-gray-500">{{ $mobileBanks->count() }} rekening terdaftar untuk {{ $currentBU?->name }}</p>
                                </div>
                            </div>

                            <a href="{{ route('zoffline.ecommerce.payment-methods') }}" wire:navigate
                                class="text-xs font-extrabold text-indigo-600 hover:text-indigo-800 underline">
                                Kelola Master Bank &rarr;
                            </a>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto pr-1 mt-4">
                            @forelse($mobileBanks as $bank)
                                <div class="p-3 rounded-xl bg-white border border-gray-200 flex items-center justify-between text-xs shadow-2xs">
                                    <div>
                                        <div class="font-bold text-gray-800 flex items-center gap-1.5">
                                            <span>{{ $bank->name }}</span>
                                            @if($bank->businessUnit)
                                                <span class="text-[10px] px-1.5 py-0.2 bg-indigo-50 text-indigo-600 font-bold rounded">{{ $bank->businessUnit->name }}</span>
                                            @else
                                                <span class="text-[10px] px-1.5 py-0.2 bg-gray-100 text-gray-500 font-semibold rounded">Semua Unit</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-gray-500 mt-0.5">{{ $bank->account_number }} • a.n. {{ $bank->account_owner ?: 'Toko' }}</div>
                                    </div>
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0" title="Aktif di Mobile"></span>
                                </div>
                            @empty
                                <div class="col-span-2 text-center py-6 text-xs text-gray-400 bg-white rounded-xl border border-dashed border-gray-200">
                                    Belum ada rekening dengan status <em>Tampilkan di Mobile</em> untuk unit usaha ini.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-gray-200/60 flex items-center justify-between">
                        <span class="text-[11px] text-gray-400">Tambah rekening baru untuk {{ $currentBU?->name }} di menu Bank:</span>
                        <a href="{{ route('zoffline.ecommerce.payment-methods') }}" wire:navigate
                            class="text-xs font-bold text-emerald-700 hover:text-emerald-900">
                            + Buka Pengaturan Rekening
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>
