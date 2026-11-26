<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Pengaturan Katalog & API Trade-In</h1>
        <p class="text-gray-500 mt-1">Konfigurasikan visibilitas stok katalog serta aturan dinamis list produk, unit bisnis, proyek, & sumber harga pada API Tukar Tambah.</p>
    </div>

    <form wire:submit.prevent="save" class="space-y-6 max-w-4xl">
        {{-- Main Settings Box: Stock Threshold --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-3">Ambang Batas Stok Katalog</h2>

            <div class="space-y-4">
                <div class="bg-[#eff2ff] p-4 rounded-lg border border-[#1c69d4]/20">
                    <p class="text-sm text-[#1c69d4] font-medium leading-relaxed">
                        Pengaturan ini memastikan bahwa hanya produk yang sudah tersinkron dengan Accurate (<code>has_active_accurate = true</code>) dan memiliki stok di atas atau sama dengan nilai di bawah ini yang akan ditampilkan ke publik.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Batas Minimum Stok Tampil</label>
                    <input type="number" wire:model="minimumStockThreshold" min="0" class="w-full text-[15px] rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] transition-all" placeholder="Contoh: 5">
                    <p class="text-xs text-gray-400 mt-1.5">Produk dengan stok di bawah nilai ini tidak akan muncul di website.</p>
                    @error('minimumStockThreshold') <span class="text-rose-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Dynamic Trade-In API Settings Box --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Pengaturan Dinamis API Trade-In & Harga Unit Second</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Mengatur unit bisnis acuan harga beli, filter proyek, penyederhanaan varian warna, dan sumber harga jual unit Second.</p>
                </div>
                <a href="{{ \Illuminate\Support\Facades\Route::has('admin.buyback.index') ? route('admin.buyback.index') : url('/admin/buyback/devices') }}"
                    wire:navigate
                    class="inline-flex items-center gap-1.5 text-xs font-bold text-[#1c69d4] bg-[#eff6ff] hover:bg-blue-100 px-3.5 py-2 rounded-lg transition">
                    <span>Kelola Excel Harga Trade-In</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>

            {{-- Business Unit Acuan Harga Beli HP Lama (/old-devices) --}}
            <div class="p-4 rounded-xl border border-emerald-200/80 bg-emerald-50/40 space-y-3">
                <div>
                    <label class="block text-sm font-bold text-gray-800">
                        Business Unit Acuan Daftar & Harga Beli HP Lama (<code>/old-devices</code>)
                    </label>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Pilih unit bisnis mana saja yang dijadikan sumber master produk & acuan <code>Buy Price</code> pada endpoint HP Lama customer.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach ($businessUnits as $bu)
                        <label class="flex items-center gap-3 p-3 rounded-lg bg-white border border-gray-200 hover:border-emerald-500/50 cursor-pointer transition shadow-xs">
                            <input type="checkbox" wire:model="tradeinOldBusinessUnitIds" value="{{ $bu->id }}"
                                class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-600">
                            <div class="flex-1 min-w-0">
                                <span class="block text-sm font-bold text-gray-800 truncate">
                                    {{ $bu->name }}
                                    <span class="text-xs font-mono text-gray-400">(#{{ $bu->id }}{{ $bu->code ? ' - ' . strtoupper($bu->code) : '' }})</span>
                                </span>
                                @if (!$bu->is_active)
                                    <span class="text-[10px] font-bold text-rose-500 uppercase">Nonaktif</span>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>
                @error('tradeinOldBusinessUnitIds')
                    <span class="text-rose-500 text-xs font-semibold block">{{ $message }}</span>
                @enderror

                <div class="pt-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Filter Sumber Harga Beli HP Lama (<code>Buy Price</code>)</label>
                    <select wire:model="tradeinOldPriceSource"
                        class="w-full text-sm rounded-lg border-gray-300 px-3.5 py-2.5 shadow-sm focus:ring-4 focus:ring-emerald-600/10 focus:border-emerald-600 bg-white">
                        <option value="buy_fallback">Utamakan Buy Price (Excel), Fallback ke Harga Accurate jika belum diatur [Default]</option>
                        <option value="buy_only">Hanya tampilkan perangkat yang sudah memiliki Buy Price &gt; 0 (Sembunyikan yang belum diatur)</option>
                    </select>
                </div>
            </div>

            {{-- Filter Proyek yang Ditampilkan --}}
            <div class="p-4 rounded-xl border border-violet-200/80 bg-violet-50/40 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <label class="block text-sm font-bold text-gray-800">
                            Filter Proyek yang Ditampilkan di API Trade-In
                        </label>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Centang proyek tertentu jika hanya ingin menampilkan proyek tersebut (contoh: hanya <code>RESMI</code> &amp; <code>INTER</code>). <strong>Biarkan tidak dicentang jika ingin menampilkan semua proyek.</strong>
                        </p>
                    </div>
                </div>

                {{-- 1. Proyek HP Lama --}}
                <div class="bg-white p-3.5 rounded-xl border border-gray-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">1. Proyek HP Lama Customer (<code>/old-devices</code>)</span>
                        <span class="text-[11px] font-semibold {{ count($tradeinOldAllowedProyeks) > 0 ? 'text-violet-700 bg-violet-100' : 'text-gray-500 bg-gray-100' }} px-2 py-0.5 rounded-full">
                            {{ count($tradeinOldAllowedProyeks) > 0 ? count($tradeinOldAllowedProyeks) . ' Proyek Dipilih' : 'Semua Proyek' }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-1">
                        @foreach ($availableProyeks as $proyekItem)
                            <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 hover:border-violet-400 bg-gray-50/70 cursor-pointer text-xs font-bold text-gray-700 transition">
                                <input type="checkbox" wire:model.live="tradeinOldAllowedProyeks" value="{{ $proyekItem }}"
                                    class="rounded border-gray-300 text-violet-600 focus:ring-violet-600">
                                <span>{{ $proyekItem }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- 2. Proyek HP Tujuan Second --}}
                <div class="bg-white p-3.5 rounded-xl border border-gray-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">2. Proyek HP Tujuan Second (<code>target_type=second</code>)</span>
                        <span class="text-[11px] font-semibold {{ count($tradeinTargetSecondAllowedProyeks) > 0 ? 'text-violet-700 bg-violet-100' : 'text-gray-500 bg-gray-100' }} px-2 py-0.5 rounded-full">
                            {{ count($tradeinTargetSecondAllowedProyeks) > 0 ? count($tradeinTargetSecondAllowedProyeks) . ' Proyek Dipilih' : 'Semua Proyek' }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-1">
                        @foreach ($availableProyeks as $proyekItem)
                            <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 hover:border-violet-400 bg-gray-50/70 cursor-pointer text-xs font-bold text-gray-700 transition">
                                <input type="checkbox" wire:model.live="tradeinTargetSecondAllowedProyeks" value="{{ $proyekItem }}"
                                    class="rounded border-gray-300 text-violet-600 focus:ring-violet-600">
                                <span>{{ $proyekItem }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- 3. Proyek HP Tujuan Baru --}}
                <div class="bg-white p-3.5 rounded-xl border border-gray-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">3. Proyek HP Tujuan Baru (<code>target_type=new</code>)</span>
                        <span class="text-[11px] font-semibold {{ count($tradeinTargetNewAllowedProyeks) > 0 ? 'text-violet-700 bg-violet-100' : 'text-gray-500 bg-gray-100' }} px-2 py-0.5 rounded-full">
                            {{ count($tradeinTargetNewAllowedProyeks) > 0 ? count($tradeinTargetNewAllowedProyeks) . ' Proyek Dipilih' : 'Semua Proyek' }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-1">
                        @foreach ($availableProyeks as $proyekItem)
                            <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 hover:border-violet-400 bg-gray-50/70 cursor-pointer text-xs font-bold text-gray-700 transition">
                                <input type="checkbox" wire:model.live="tradeinTargetNewAllowedProyeks" value="{{ $proyekItem }}"
                                    class="rounded border-gray-300 text-violet-600 focus:ring-violet-600">
                                <span>{{ $proyekItem }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Filter Brand yang Ditampilkan di API Trade-In --}}
            <div class="p-4 rounded-xl border border-sky-200/80 bg-sky-50/40 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-800">
                        Filter Brand yang Ditampilkan di API Trade-In
                    </label>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Atur secara dinamis brand apa saja yang tampil pada fitur Trade-In (contoh: aktifkan <code>APPLE</code> &amp; <code>SAMSUNG</code> untuk HP Second). <strong>Kosongkan centang jika ingin menampilkan semua brand yang memiliki harga.</strong>
                    </p>
                </div>

                {{-- 1. Brand HP Tujuan Second --}}
                <div class="bg-white p-3.5 rounded-xl border border-gray-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">1. Brand HP Tujuan Second (<code>type=second</code> &amp; <code>target_type=second</code>)</span>
                        <span class="text-[11px] font-semibold {{ count($tradeinTargetSecondAllowedBrands) > 0 ? 'text-sky-700 bg-sky-100' : 'text-gray-500 bg-gray-100' }} px-2 py-0.5 rounded-full">
                            {{ count($tradeinTargetSecondAllowedBrands) > 0 ? count($tradeinTargetSecondAllowedBrands) . ' Brand Dipilih' : 'Semua Brand' }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-1">
                        @foreach ($availableBrands as $brandItem)
                            <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 hover:border-sky-400 bg-gray-50/70 cursor-pointer text-xs font-bold text-gray-700 transition">
                                <input type="checkbox" wire:model.live="tradeinTargetSecondAllowedBrands" value="{{ $brandItem['name'] }}"
                                    class="rounded border-gray-300 text-sky-600 focus:ring-sky-600">
                                <span>{{ $brandItem['name'] }} <span class="text-[10px] font-normal text-gray-400">({{ $brandItem['count'] }})</span></span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- 2. Brand HP Lama --}}
                <div class="bg-white p-3.5 rounded-xl border border-gray-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">2. Brand HP Lama Customer (<code>type=old</code> &amp; <code>/old-devices</code>)</span>
                        <span class="text-[11px] font-semibold {{ count($tradeinOldAllowedBrands) > 0 ? 'text-sky-700 bg-sky-100' : 'text-gray-500 bg-gray-100' }} px-2 py-0.5 rounded-full">
                            {{ count($tradeinOldAllowedBrands) > 0 ? count($tradeinOldAllowedBrands) . ' Brand Dipilih' : 'Semua Brand' }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-1">
                        @foreach ($availableBrands as $brandItem)
                            <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 hover:border-sky-400 bg-gray-50/70 cursor-pointer text-xs font-bold text-gray-700 transition">
                                <input type="checkbox" wire:model.live="tradeinOldAllowedBrands" value="{{ $brandItem['name'] }}"
                                    class="rounded border-gray-300 text-sky-600 focus:ring-sky-600">
                                <span>{{ $brandItem['name'] }} <span class="text-[10px] font-normal text-gray-400">({{ $brandItem['count'] }})</span></span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- 3. Brand HP Tujuan Baru --}}
                <div class="bg-white p-3.5 rounded-xl border border-gray-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wide">3. Brand HP Tujuan Baru (<code>type=new</code> &amp; <code>target_type=new</code>)</span>
                        <span class="text-[11px] font-semibold {{ count($tradeinTargetNewAllowedBrands) > 0 ? 'text-sky-700 bg-sky-100' : 'text-gray-500 bg-gray-100' }} px-2 py-0.5 rounded-full">
                            {{ count($tradeinTargetNewAllowedBrands) > 0 ? count($tradeinTargetNewAllowedBrands) . ' Brand Dipilih' : 'Semua Brand' }}
                        </span>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-1">
                        @foreach ($availableBrands as $brandItem)
                            <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-gray-200 hover:border-sky-400 bg-gray-50/70 cursor-pointer text-xs font-bold text-gray-700 transition">
                                <input type="checkbox" wire:model.live="tradeinTargetNewAllowedBrands" value="{{ $brandItem['name'] }}"
                                    class="rounded border-gray-300 text-sky-600 focus:ring-sky-600">
                                <span>{{ $brandItem['name'] }} <span class="text-[10px] font-normal text-gray-400">({{ $brandItem['count'] }})</span></span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Toggle Grouping --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex items-start gap-3 p-4 rounded-xl border border-gray-200 hover:border-[#1c69d4]/40 bg-gray-50/50 cursor-pointer transition">
                    <input type="checkbox" wire:model="tradeinGroupOldDevices"
                        class="mt-1 rounded border-gray-300 text-[#1c69d4] focus:ring-[#1c69d4]">
                    <div>
                        <span class="block text-sm font-bold text-gray-800">Ringkas HP Lama Tanpa Warna</span>
                        <span class="block text-xs text-gray-500 mt-1 leading-relaxed">
                            Menggabungkan varian warna pada API <code>/old-devices</code> menjadi <strong>Model + RAM/Storage + Proyek</strong>.
                        </span>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-4 rounded-xl border border-gray-200 hover:border-[#1c69d4]/40 bg-gray-50/50 cursor-pointer transition">
                    <input type="checkbox" wire:model="tradeinGroupTargetDevices"
                        class="mt-1 rounded border-gray-300 text-[#1c69d4] focus:ring-[#1c69d4]">
                    <div>
                        <span class="block text-sm font-bold text-gray-800">Ringkas HP Tujuan Tanpa Warna</span>
                        <span class="block text-xs text-gray-500 mt-1 leading-relaxed">
                            Menggabungkan varian warna pada API <code>/target-devices</code> (Baru & Second) menjadi <strong>Model + RAM/Storage + Proyek</strong>.
                        </span>
                    </div>
                </label>
            </div>

            {{-- Sumber Harga Jual BU Second & Up Harga Jual Trade-In Accurate --}}
            <div class="p-4 rounded-xl border border-blue-200/80 bg-blue-50/40 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-gray-800 mb-1.5">Sumber Harga Jual Unit Bisnis Second (BU 2)</label>
                    <select wire:model.live="tradeinSecondPriceSource"
                        class="w-full text-sm rounded-lg border-gray-300 px-4 py-3 shadow-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] transition-all bg-white">
                        <option value="custom_fallback">Utamakan Custom Sell Price (Excel), Fallback ke Harga Accurate [Rekomendasi]</option>
                        <option value="custom_only">Hanya Custom Sell Price (Excel) — Sembunyikan produk yang belum diatur di Excel</option>
                        <option value="accurate_only">Selalu Gunakan Harga Real Accurate (base_price)</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1.5">
                        Kolom <code>custom_sell_price</code> dikelola via Import Excel/CSV dan kebal terhadap sinkronisasi Accurate. Pilih <code>Hanya Custom Sell Price</code> jika ingin menyembunyikan produk berharga placeholder Accurate (misal <code>Rp 1</code>) yang belum diisi di Excel.
                    </p>
                    @error('tradeinSecondPriceSource') <span class="text-rose-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                </div>

                {{-- Up Harga Jual Trade-In Accurate (%) --}}
                @php
                    $simMarkup = max(0, (float) ($tradeinSecondAccurateMarkupPercent ?: 0));
                    $simRounding = max(0, (int) ($tradeinSecondAccurateRounding ?: 0));
                    $simSampleBase = 5000000;
                    $simResult = round($simSampleBase * (1 + ($simMarkup / 100)));
                    if ($simMarkup > 0 && $simRounding > 0) {
                        $simResult = ceil($simResult / $simRounding) * $simRounding;
                    }
                @endphp
                <div class="bg-white p-4 rounded-xl border border-blue-200/70 space-y-3 {{ $tradeinSecondPriceSource === 'custom_only' ? 'opacity-60' : '' }}">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <label class="block text-sm font-bold text-gray-800">
                                Up Harga Jual Trade-In Accurate (%)
                            </label>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Persentase kenaikan otomatis <strong>khusus saat harga diambil dari Accurate (<code>base_price</code>)</strong> karena harga Accurate diset harga terendah. Produk yang sudah memiliki <code>custom_sell_price</code> <strong>tidak ikut di-up</strong>.
                            </p>
                        </div>
                        @if ($tradeinSecondPriceSource === 'custom_only')
                            <span class="text-[11px] font-bold text-amber-700 bg-amber-100 px-2.5 py-1 rounded-full shrink-0">
                                Nonaktif pada mode Custom Only
                            </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Persentase Up Harga (%)</label>
                            <div class="relative">
                                <input type="number" step="0.5" min="0" max="200"
                                    wire:model.live="tradeinSecondAccurateMarkupPercent"
                                    class="w-full text-sm font-bold rounded-lg border-gray-300 px-3.5 py-2.5 pr-10 shadow-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4]"
                                    placeholder="0">
                                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-sm font-bold text-gray-400">%</span>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1">Isi <code>0</code> jika tidak ingin menaikkan harga Accurate. Contoh: <code>10</code> berarti harga Accurate × 110%.</p>
                            @error('tradeinSecondAccurateMarkupPercent') <span class="text-rose-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Pembulatan Harga Setelah Up</label>
                            <select wire:model.live="tradeinSecondAccurateRounding"
                                class="w-full text-sm rounded-lg border-gray-300 px-3.5 py-2.5 shadow-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] bg-white">
                                <option value="0">Tanpa Pembulatan Kelipatan (Persis)</option>
                                <option value="1000">Bulatkan ke atas per Rp 1.000</option>
                                <option value="10000">Bulatkan ke atas per Rp 10.000</option>
                                <option value="50000">Bulatkan ke atas per Rp 50.000</option>
                                <option value="100000">Bulatkan ke atas per Rp 100.000</option>
                            </select>
                            <p class="text-[11px] text-gray-400 mt-1">Agar hasil persentase tetap rapi (misal <code>Rp 5.500.000</code> bukan angka ganjil).</p>
                            @error('tradeinSecondAccurateRounding') <span class="text-rose-500 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Simulasi Langsung --}}
                    <div class="flex flex-wrap items-center justify-between gap-2 px-3.5 py-2.5 rounded-lg bg-slate-50 border border-slate-200 text-xs">
                        <span class="text-gray-600">
                            Simulasi Harga Accurate <strong>Rp {{ number_format($simSampleBase, 0, ',', '.') }}</strong>
                            @if ($simMarkup > 0)
                                + Up <strong class="text-[#1c69d4]">{{ rtrim(rtrim(number_format($simMarkup, 2, '.', ''), '0'), '.') }}%</strong>
                            @else
                                (Tanpa Up)
                            @endif
                        </span>
                        <span class="font-extrabold text-[#1c69d4] text-sm">
                            → Rp {{ number_format($simResult, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Strategi Harga Grup Warna --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Strategi Harga Beli Grup HP Lama</label>
                    <select wire:model="tradeinOldPriceStrategy"
                        class="w-full text-sm rounded-lg border-gray-300 px-4 py-2.5 shadow-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] bg-white">
                        <option value="min">Ambil Harga Terendah (Min) antar warna</option>
                        <option value="max">Ambil Harga Tertinggi (Max) antar warna</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">Strategi Harga Jual Grup HP Tujuan</label>
                    <select wire:model="tradeinTargetPriceStrategy"
                        class="w-full text-sm rounded-lg border-gray-300 px-4 py-2.5 shadow-sm focus:ring-4 focus:ring-[#1c69d4]/10 focus:border-[#1c69d4] bg-white">
                        <option value="min">Ambil Harga Terendah (Min — Mulai Dari) antar warna</option>
                        <option value="max">Ambil Harga Tertinggi (Max) antar warna</option>
                    </select>
                </div>
            </div>

            {{-- Filter Aksesoris Nyasar --}}
            <label class="flex items-start gap-3 p-4 rounded-xl border border-amber-200/80 bg-amber-50/40 cursor-pointer">
                <input type="checkbox" wire:model="tradeinExcludeAccessories"
                    class="mt-1 rounded border-gray-300 text-amber-600 focus:ring-amber-600">
                <div>
                    <span class="block text-sm font-bold text-gray-800">Otomatis Sembunyikan Aksesoris Nyasar di Kategori HP</span>
                    <span class="block text-xs text-gray-500 mt-0.5 leading-relaxed">
                        Menyembunyikan item dengan proyek <code>ACCESSORIES</code>, bertanda <code>is_addon</code>, atau diawali <code>CASE -</code> / <code>TEMPERED</code> / <code>CHARGER</code> agar tidak muncul di daftar perangkat HP.
                    </span>
                </div>
            </label>
        </div>

        <div class="flex justify-end">
            <button type="submit" wire:loading.attr="disabled" class="bg-[#1c69d4] text-white px-8 py-3 rounded-lg font-bold hover:bg-[#3f36b8] active:scale-95 transition-all shadow-sm shadow-[#1c69d4]/25 disabled:opacity-50 flex items-center gap-2">
                <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
                <span wire:loading wire:target="save">Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
