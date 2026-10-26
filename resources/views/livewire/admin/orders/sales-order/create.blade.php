<div class="max-w-7xl mx-auto p-4 md:p-6 min-h-screen">
    <!-- Top Bar -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <a href="{{ route('admin.sales-orders.index') }}" wire:navigate
                class="text-xs font-bold text-gray-500 hover:text-[#1c69d4] flex items-center gap-1.5 mb-2 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Daftar Sales Order
            </a>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight">Buat Sales Order Baru</h1>
            <p class="text-gray-500 text-xs mt-0.5">Input pesanan pelanggan, alokasi IMEI fisik / Indent, dan pencatatan Uang Muka (DP).</p>
        </div>

        <!-- Operating Context Badges -->
        <div class="flex flex-wrap items-center gap-2 bg-gray-50 border border-gray-200/80 rounded-2xl p-2.5 px-3.5 shadow-sm text-xs">
            <div class="flex items-center gap-1.5 text-gray-700 font-semibold">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>{{ Auth::user()->branch->name ?? 'Cabang Utama' }}</span>
            </div>
            <span class="text-gray-300">|</span>
            <div class="flex items-center gap-1.5 text-gray-600">
                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <span>{{ Auth::user()->warehouse->name ?? 'Gudang Toko' }}</span>
            </div>
        </div>
    </div>

    <!-- Stepper Navigation -->
    <div class="mb-8 max-w-3xl mx-auto">
        <div class="flex items-center justify-between relative">
            <!-- Background Line -->
            <div class="absolute left-6 right-6 top-5 h-1 bg-gray-200 rounded-full z-0"></div>
            <!-- Active Progress Line -->
            <div class="absolute left-6 top-5 h-1 bg-[#1c69d4] rounded-full z-0 transition-all duration-300"
                style="width: {{ $wizardStep == 1 ? '0%' : ($wizardStep == 2 ? '50%' : 'calc(100% - 3rem)') }}"></div>

            <!-- Step 1 -->
            <div class="relative z-10 flex flex-col items-center">
                <button type="button" wire:click="$set('wizardStep', 1)"
                    class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all {{ $wizardStep >= 1 ? 'bg-[#1c69d4] text-white shadow-lg shadow-blue-500/30' : 'bg-gray-200 text-gray-500' }}">
                    @if ($wizardStep > 1)
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    @else
                        1
                    @endif
                </button>
                <div class="mt-2 text-center">
                    <span class="text-xs font-black block {{ $wizardStep >= 1 ? 'text-[#1c69d4]' : 'text-gray-400' }}">Informasi Pesanan</span>
                    <span class="text-[10px] text-gray-400 font-medium">Pelanggan & Sales</span>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="relative z-10 flex flex-col items-center">
                <button type="button" @if($user_id) wire:click="$set('wizardStep', 2)" @endif
                    class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all {{ $wizardStep >= 2 ? 'bg-[#1c69d4] text-white shadow-lg shadow-blue-500/30' : ($wizardStep == 1 ? 'bg-white border-2 border-gray-300 text-gray-400' : 'bg-gray-200 text-gray-400') }}">
                    @if ($wizardStep > 2)
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    @else
                        2
                    @endif
                </button>
                <div class="mt-2 text-center">
                    <span class="text-xs font-black block {{ $wizardStep >= 2 ? 'text-[#1c69d4]' : 'text-gray-400' }}">Keranjang & IMEI</span>
                    <span class="text-[10px] text-gray-400 font-medium">Produk & Kunci Unit</span>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="relative z-10 flex flex-col items-center">
                <button type="button" @if($user_id && count($items) > 0 && !empty($items[0]['variant_id'])) wire:click="$set('wizardStep', 3)" @endif
                    class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all {{ $wizardStep >= 3 ? 'bg-[#1c69d4] text-white shadow-lg shadow-blue-500/30' : 'bg-white border-2 border-gray-300 text-gray-400' }}">
                    3
                </button>
                <div class="mt-2 text-center">
                    <span class="text-xs font-black block {{ $wizardStep >= 3 ? 'text-[#1c69d4]' : 'text-gray-400' }}">Review & DP</span>
                    <span class="text-[10px] text-gray-400 font-medium">Ringkasan & Bayar DP</span>
                </div>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        {{-- ==================== STEP 1: INFORMASI PESANAN ==================== --}}
        @if ($wizardStep == 1)
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-8 w-full max-w-4xl mx-auto animate-fade-in-up">
                <div class="flex items-center gap-3.5 mb-6 pb-4 border-b border-gray-100">
                    <div class="p-3 bg-blue-50 text-[#1c69d4] rounded-2xl">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-black text-gray-900 text-lg">Data Pelanggan & Transaksi</h3>
                        <p class="text-xs text-gray-500">Pilih pelanggan pemesan dan pramuniaga yang melayani pesanan ini</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Pelanggan -->
                    <div class="relative md:col-span-2">
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Pelanggan <span class="text-red-500">*</span>
                            </label>
                            @if (!$user_id)
                                <div class="flex items-center gap-2">
                                    <div wire:loading wire:target="searchCustomer" class="text-[#1c69d4]">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    <button type="button" wire:click.prevent="$set('showNewCustomerModal', true)"
                                        class="text-[#1c69d4] hover:text-blue-700 font-bold text-xs bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-xl transition-all flex items-center gap-1 cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Pelanggan Baru
                                    </button>
                                </div>
                            @endif
                        </div>

                        @if ($user_id)
                            <!-- Selected Customer Card -->
                            <div class="w-full bg-blue-50/70 border border-blue-200 rounded-2xl p-4 flex justify-between items-center shadow-sm">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-11 h-11 rounded-xl bg-[#1c69d4] text-white flex items-center justify-center font-black text-sm uppercase shadow-sm">
                                        {{ substr($selectedCustomerName, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="font-black text-gray-900 text-sm flex items-center gap-2">
                                            {{ $selectedCustomerName }}
                                            <span class="text-[10px] bg-blue-100 text-blue-700 font-bold px-2 py-0.5 rounded-full">Pelanggan Terpilih</span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3 mt-1 text-xs text-gray-600 font-medium">
                                            @if ($selectedCustomerPhone)
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                    </svg>
                                                    {{ $selectedCustomerPhone }}
                                                </span>
                                            @endif
                                            @if ($selectedCustomerEmail)
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                    {{ $selectedCustomerEmail }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <button type="button" wire:click="clearCustomer"
                                    class="px-3 py-1.5 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-colors flex items-center gap-1"
                                    title="Ganti Pelanggan">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Ganti
                                </button>
                            </div>
                            <input type="hidden" wire:model="user_id" required>
                        @else
                            <div class="relative">
                                <input type="text" wire:model.live.debounce.300ms="searchCustomer"
                                    class="w-full px-4 py-3 pl-10 rounded-2xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors font-medium placeholder-gray-400"
                                    placeholder="Ketik nama, nomor HP WhatsApp, atau email pelanggan..." autocomplete="off">
                                <div class="absolute left-3.5 top-3.5 text-gray-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>

                            @if (!empty($customerSearchResults))
                                <div class="absolute z-30 mt-2 w-full bg-white rounded-2xl shadow-2xl border border-gray-100 max-h-64 overflow-y-auto divide-y divide-gray-50">
                                    @foreach ($customerSearchResults as $res)
                                        <div wire:click="selectCustomer({{ $res['id'] }}, '{{ addslashes($res['name']) }}', '{{ addslashes($res['email'] ?? '') }}', '{{ addslashes($res['phone'] ?? '') }}')"
                                            class="px-5 py-3 hover:bg-blue-50/80 cursor-pointer transition-colors flex justify-between items-center group">
                                            <div>
                                                <div class="font-bold text-gray-900 text-sm group-hover:text-[#1c69d4]">{{ $res['name'] }}</div>
                                                <div class="text-xs text-gray-500 mt-0.5">{{ $res['email'] }}</div>
                                            </div>
                                            @if (isset($res['phone']) && $res['phone'] !== '-')
                                                <div class="text-xs font-semibold text-gray-700 bg-gray-100 group-hover:bg-blue-100 group-hover:text-blue-700 px-2.5 py-1 rounded-lg">
                                                    {{ $res['phone'] }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif

                        @error('user_id')
                            <span class="text-xs text-red-500 mt-1.5 block font-medium">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Tanggal SO -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                            Tanggal Sales Order <span class="text-red-500">*</span>
                        </label>
                        <input type="date" wire:model="order_date"
                            class="w-full px-4 py-3 rounded-2xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors font-medium"
                            required>
                        @error('order_date')
                            <span class="text-xs text-red-500 mt-1.5 block font-medium">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Pramuniaga (Sales) -->
                    <div class="relative">
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                Pramuniaga / Sales
                            </label>
                            @if (!$sales_id)
                                <div wire:loading wire:target="searchSales" class="text-[#1c69d4]">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        @if ($sales_id)
                            <!-- Selected Sales Card -->
                            <div class="w-full bg-emerald-50/70 border border-emerald-200 rounded-2xl p-4 flex justify-between items-center shadow-sm">
                                <div>
                                    <div class="font-black text-emerald-950 text-sm">{{ $selectedSalesName }}</div>
                                    <div class="text-xs font-semibold text-emerald-700 mt-0.5">
                                        {{ $selectedSalesNo ?? 'Tanpa NIK' }} &bull; {{ $selectedSalesBranch ?? 'Semua Cabang' }}
                                    </div>
                                </div>
                                <button type="button" wire:click="clearSales"
                                    class="px-3 py-1.5 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-colors flex items-center gap-1"
                                    title="Ganti Pramuniaga">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Ganti
                                </button>
                            </div>
                            <input type="hidden" wire:model="sales_id">
                        @else
                            <div class="relative">
                                <input type="text" wire:model.live.debounce.300ms="searchSales"
                                    class="w-full px-4 py-3 pl-10 rounded-2xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors font-medium placeholder-gray-400"
                                    placeholder="Ketik nama pramuniaga / sales..." autocomplete="off">
                                <div class="absolute left-3.5 top-3.5 text-gray-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                            </div>

                            @if (!empty($salesSearchResults))
                                <div class="absolute z-30 mt-2 w-full bg-white rounded-2xl shadow-2xl border border-gray-100 max-h-64 overflow-y-auto divide-y divide-gray-50">
                                    @foreach ($salesSearchResults as $res)
                                        <div wire:click="selectSales({{ $res['id'] }}, '{{ addslashes($res['name']) }}', '{{ addslashes($res['branch_name'] ?? '') }}', '{{ addslashes($res['employee_no'] ?? '') }}')"
                                            class="px-5 py-3 hover:bg-emerald-50/80 cursor-pointer transition-colors group flex justify-between items-center">
                                            <div>
                                                <div class="font-bold text-gray-900 group-hover:text-emerald-700 text-sm">{{ $res['name'] }}</div>
                                                <div class="text-xs text-gray-500 mt-0.5">
                                                    {{ $res['employee_no'] ?? 'N/A' }} &bull; {{ $res['branch_name'] }}
                                                </div>
                                            </div>
                                            <span class="text-xs font-bold text-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity">Pilih &rarr;</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        @elseif ($wizardStep == 2)
            {{-- ==================== STEP 2: KERANJANG & KUNCI IMEI ==================== --}}
            <div class="space-y-4 w-full animate-fade-in-up">
                <!-- Action Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-3 border-b border-gray-200/80 gap-3">
                    <div>
                        <h3 class="font-black text-gray-900 text-lg tracking-tight">Daftar Produk Pesanan</h3>
                        <p class="text-xs text-gray-500">Pilih produk, tentukan kuantitas, harga, dan alokasikan IMEI fisik atau biarkan kosong jika Indent.</p>
                    </div>
                    <button type="button" wire:click="addItem"
                        class="px-4 py-2.5 bg-blue-50 text-[#1c69d4] hover:bg-[#1c69d4] hover:text-white font-bold rounded-2xl text-xs transition-all flex items-center gap-2 border border-blue-200 shadow-sm self-start sm:self-auto cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Baris Produk
                    </button>
                </div>

                @error('items')
                    <div class="p-3 bg-red-50 border border-red-200 rounded-2xl text-xs text-red-600 font-semibold flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $message }}</span>
                    </div>
                @enderror

                <!-- Items Loop -->
                @foreach ($items as $index => $item)
                    <div class="bg-white rounded-3xl p-5 md:p-6 border border-gray-200 shadow-sm relative group transition-all hover:border-blue-300"
                        wire:key="item-{{ $index }}">
                        
                        <!-- Top Row in Card: Number, Name, Status Badge & Delete -->
                        <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100 gap-2">
                            <div class="flex items-center gap-2.5">
                                <span class="w-7 h-7 rounded-lg bg-gray-100 text-gray-700 font-black text-xs flex items-center justify-center">
                                    #{{ $index + 1 }}
                                </span>
                                @if (!empty($item['product_name']))
                                    <span class="font-bold text-gray-900 text-sm md:text-base">{{ $item['product_name'] }}</span>
                                    @if (!empty($item['item_no']))
                                        <span class="text-[10px] bg-gray-100 text-gray-500 font-semibold px-2 py-0.5 rounded-md">{{ $item['item_no'] }}</span>
                                    @endif
                                @else
                                    <span class="text-xs font-semibold text-gray-400 italic">Pilih produk pada kolom di bawah...</span>
                                @endif
                            </div>

                            <div class="flex items-center gap-3">
                                <!-- Dynamic Status Badge -->
                                @php
                                    $snCount = count($item['serial_numbers'] ?? []);
                                    $itemQty = (int)($item['qty'] ?: 1);
                                @endphp

                                @if ($snCount == $itemQty && $itemQty > 0)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                        🔒 Keep IMEI Lengkap ({{ $snCount }}/{{ $itemQty }})
                                    </span>
                                @elseif ($snCount > 0 && $snCount < $itemQty)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black bg-amber-100 text-amber-800 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                        ⚠️ Keep IMEI Sebagian ({{ $snCount }}/{{ $itemQty }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                        📦 Indent (0/{{ $itemQty }})
                                    </span>
                                @endif

                                @if (count($items) > 1)
                                    <button type="button" wire:click="removeItem({{ $index }})"
                                        class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors cursor-pointer"
                                        title="Hapus Baris Ini">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Product Inputs Grid -->
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                            <!-- Product Search -->
                            <div class="lg:col-span-4 relative">
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                                    Cari & Pilih Produk <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input type="text"
                                        wire:model.live.debounce.300ms="items.{{ $index }}.searchProduct"
                                        class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors placeholder-gray-400 font-medium"
                                        placeholder="Ketik nama produk / SKU..." autocomplete="off">
                                    <div wire:loading wire:target="items.{{ $index }}.searchProduct"
                                        class="absolute right-3 top-3 text-[#1c69d4]">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>

                                @if (!empty($item['searchResults']))
                                    <div class="absolute z-30 mt-2 left-0 right-0 bg-white rounded-2xl shadow-2xl border border-gray-100 max-h-64 overflow-y-auto divide-y divide-gray-50">
                                        @foreach ($item['searchResults'] as $res)
                                            <div wire:click="selectProduct({{ $index }}, {{ $res['id'] }}, '{{ addslashes($res['name']) }}', {{ $res['price'] }})"
                                                class="px-4 py-3 hover:bg-blue-50 cursor-pointer transition-colors group">
                                                <div class="font-bold text-gray-900 text-sm group-hover:text-[#1c69d4] leading-tight">
                                                    {{ $res['name'] }}
                                                </div>
                                                <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                                    <span class="text-xs text-[#1c69d4] font-black">
                                                        Rp {{ number_format($res['price'], 0, ',', '.') }}
                                                    </span>
                                                    <span class="text-gray-300">&bull;</span>
                                                    @if ($res['stock'] > 0)
                                                        <span class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md flex items-center gap-1">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                            Stok Toko: {{ $res['stock'] }} unit
                                                        </span>
                                                    @else
                                                        <span class="text-[10px] font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-md">
                                                            Stok Toko: 0 (Indent)
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <input type="hidden" wire:model="items.{{ $index }}.variant_id" required>
                                @error("items.$index.variant_id")
                                    <span class="text-xs text-red-500 mt-1 block font-medium">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Kuantitas -->
                            <div class="lg:col-span-2">
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5 lg:text-center">Qty</label>
                                <input type="number" min="1"
                                    wire:model.live.debounce.500ms="items.{{ $index }}.qty"
                                    class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm text-center bg-gray-50 focus:bg-white transition-colors font-bold">
                                @error("items.$index.qty")
                                    <span class="text-xs text-red-500 mt-1 block font-medium">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Harga Satuan -->
                            <div class="lg:col-span-2">
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5 lg:text-right">Harga Satuan</label>
                                <input type="text" x-data="{
                                    rawAmount: @entangle('items.' . $index . '.unit_price'),
                                    formattedAmount: '',
                                    init() {
                                        this.formattedAmount = this.formatNumber(this.rawAmount);
                                        this.$watch('rawAmount', value => {
                                            if (document.activeElement !== this.$refs.amountInput) {
                                                this.formattedAmount = this.formatNumber(value);
                                            }
                                        });
                                    },
                                    formatNumber(val) {
                                        if (!val) return '';
                                        let num = parseInt(String(val).replace(/\D/g, ''), 10);
                                        return isNaN(num) ? '' : num.toLocaleString('id-ID');
                                    },
                                    updateAmount(e) {
                                        let val = e.target.value;
                                        let num = parseInt(val.replace(/\D/g, ''), 10);
                                        if (isNaN(num)) num = 0;
                                        this.formattedAmount = this.formatNumber(num);
                                
                                        clearTimeout(this.timeout);
                                        this.timeout = setTimeout(() => {
                                            this.rawAmount = num;
                                            $wire.$commit();
                                        }, 400);
                                    }
                                }" x-ref="amountInput"
                                    x-model="formattedAmount" @input="updateAmount($event)"
                                    class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm lg:text-right bg-gray-50 focus:bg-white transition-colors font-bold">
                            </div>

                            <!-- Diskon -->
                            <div class="lg:col-span-2">
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5 lg:text-right">Diskon Satuan</label>
                                <input type="text" x-data="{
                                    rawAmount: @entangle('items.' . $index . '.discount'),
                                    formattedAmount: '',
                                    init() {
                                        this.formattedAmount = this.formatNumber(this.rawAmount);
                                        this.$watch('rawAmount', value => {
                                            if (document.activeElement !== this.$refs.discountInput) {
                                                this.formattedAmount = this.formatNumber(value);
                                            }
                                        });
                                    },
                                    formatNumber(val) {
                                        if (!val) return '';
                                        let num = parseInt(String(val).replace(/\D/g, ''), 10);
                                        return isNaN(num) ? '' : num.toLocaleString('id-ID');
                                    },
                                    updateAmount(e) {
                                        let val = e.target.value;
                                        let num = parseInt(val.replace(/\D/g, ''), 10);
                                        if (isNaN(num)) num = 0;
                                        this.formattedAmount = this.formatNumber(num);
                                
                                        clearTimeout(this.timeout);
                                        this.timeout = setTimeout(() => {
                                            this.rawAmount = num;
                                            $wire.$commit();
                                        }, 400);
                                    }
                                }" x-ref="discountInput"
                                    x-model="formattedAmount" @input="updateAmount($event)"
                                    class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm text-red-500 focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm lg:text-right bg-red-50/50 focus:bg-white transition-colors font-bold placeholder-red-300"
                                    placeholder="0">
                            </div>

                            <!-- Total -->
                            <div class="lg:col-span-2 flex flex-col justify-end h-full">
                                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5 lg:text-right hidden lg:block">Subtotal</label>
                                <div class="font-black text-[#1c69d4] lg:text-right text-lg border-t border-gray-100 lg:border-0 pt-2 lg:pt-0">
                                    <span class="text-xs font-normal text-gray-400 lg:hidden mr-2 uppercase">Subtotal:</span>Rp {{ number_format($item['total'], 0, ',', '.') }}
                                </div>
                            </div>
                        </div>

                        <!-- SECTION ALOKASI KUNCI IMEI / INDENT -->
                        <div class="mt-5 pt-4 border-t border-gray-100 bg-gray-50/70 -mx-5 -mb-5 md:-mx-6 md:-mb-6 p-4 md:p-5 rounded-b-3xl">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-2.5 gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-black text-gray-800 uppercase tracking-wider">Alokasi Kunci IMEI Fisik</span>
                                    <span class="text-[11px] font-bold text-gray-500">(Maks: {{ (int) ($item['qty'] ?: 1) }} Unit)</span>
                                </div>
                                <span class="text-[11px] text-gray-500">
                                    Terkunci: <strong class="text-gray-900">{{ count($item['serial_numbers'] ?? []) }}</strong> dari <strong class="text-gray-900">{{ (int) ($item['qty'] ?: 1) }}</strong> unit
                                </span>
                            </div>

                            <!-- Guidance Helper Box -->
                            <div class="mb-3 text-[11px] text-gray-600 bg-white border border-gray-200/70 rounded-xl p-2.5 flex items-start gap-2 shadow-xs">
                                <span class="text-[#1c69d4] font-bold text-sm leading-none mt-0.5">💡</span>
                                <div>
                                    <span class="font-bold text-gray-800">Panduan:</span>
                                    Masukkan IMEI jika unit fisik <strong>Ready di Toko</strong> dan ingin di-keep agar tidak terjual ke kasir lain. Jika pesanan berupa <strong>Indent (Pre-order)</strong> dan unit fisik belum ada, <strong>kolom IMEI boleh dikosongkan</strong>.
                                </div>
                            </div>

                            <!-- Locked SN Badges List -->
                            @if (!empty($item['serial_numbers']))
                                <div class="flex flex-wrap gap-2 mb-3">
                                    @foreach ($item['serial_numbers'] as $snIdx => $sn)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold shadow-xs">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                            {{ $sn }}
                                            <button type="button"
                                                wire:click="removeSn({{ $index }}, {{ $snIdx }})"
                                                class="text-emerald-500 hover:text-red-600 hover:bg-emerald-100 p-0.5 rounded-md transition-colors cursor-pointer"
                                                title="Hapus Kunci IMEI">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            <!-- Input Row for Scan IMEI -->
                            @if (count($item['serial_numbers'] ?? []) < (int) ($item['qty'] ?: 1))
                                <div class="flex items-center gap-2">
                                    <div class="relative flex-1">
                                        <input type="text" wire:model.defer="items.{{ $index }}.scanned_sn"
                                            wire:keydown.enter.prevent="addSn({{ $index }})"
                                            class="w-full px-4 py-2 rounded-xl border border-gray-300 text-xs focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-xs bg-white font-mono placeholder-gray-400"
                                            placeholder="Scan barcode IMEI atau ketik nomor seri lalu Enter..." autocomplete="off">

                                        <!-- Loading indicator -->
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <div wire:loading wire:target="addSn({{ $index }})" class="text-[#1c69d4]">
                                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" wire:click.prevent="addSn({{ $index }})"
                                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition-colors shadow-xs flex items-center gap-1.5 cursor-pointer shrink-0">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                        </svg>
                                        + Kunci IMEI
                                    </button>
                                </div>
                            @else
                                <div class="text-xs text-emerald-700 font-bold flex items-center gap-1.5 py-1">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Semua kuantitas produk ini ({{ $item['qty'] }} unit) sudah dialokasikan kunci IMEI fisiknya.
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif ($wizardStep == 3)
            {{-- ==================== STEP 3: REVIEW & UANG MUKA (DP) ==================== --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 w-full animate-fade-in-up">
                <!-- Left Column (7 cols): Order Review & DP Payment -->
                <div class="lg:col-span-7 space-y-6">
                    <!-- Order Items & Customer Review Card -->
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100">
                            <div>
                                <h3 class="font-black text-gray-900 text-base">Ringkasan Pesanan</h3>
                                <p class="text-xs text-gray-500">Periksa detail pelanggan dan alokasi produk sebelum menyimpan</p>
                            </div>
                            <button type="button" wire:click="$set('wizardStep', 1)"
                                class="text-xs font-bold text-[#1c69d4] hover:underline">
                                Edit Info &rarr;
                            </button>
                        </div>

                        <!-- Customer Info Row -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-3 bg-gray-50 rounded-2xl mb-4 text-xs">
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Pelanggan:</span>
                                <span class="font-black text-gray-900">{{ $selectedCustomerName }}</span>
                                <span class="text-gray-500 block text-[11px]">{{ $selectedCustomerPhone ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Pramuniaga (Sales):</span>
                                <span class="font-bold text-gray-900">{{ $selectedSalesName ?? 'Tidak ditentukan' }}</span>
                                <span class="text-gray-500 block text-[11px]">{{ $selectedSalesNo ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Tanggal SO:</span>
                                <span class="font-bold text-gray-900">{{ \Carbon\Carbon::parse($order_date)->translatedFormat('d M Y') }}</span>
                                <span class="text-gray-500 block text-[11px]">{{ Auth::user()->branch->name ?? 'Cabang Utama' }}</span>
                            </div>
                        </div>

                        <!-- Products List Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-gray-100 text-gray-400 font-bold uppercase text-[10px]">
                                        <th class="py-2.5">Produk & Alokasi IMEI</th>
                                        <th class="py-2.5 text-center">Qty</th>
                                        <th class="py-2.5 text-right">Harga</th>
                                        <th class="py-2.5 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach ($items as $item)
                                        <tr>
                                            <td class="py-3">
                                                <div class="font-black text-gray-900">{{ $item['product_name'] ?: 'Produk Belum Dipilih' }}</div>
                                                <!-- IMEI / Indent Badges -->
                                                <div class="flex flex-wrap gap-1 mt-1">
                                                    @if (!empty($item['serial_numbers']))
                                                        @foreach ($item['serial_numbers'] as $sn)
                                                            <span class="inline-flex items-center gap-1 text-[10px] bg-emerald-50 text-emerald-700 border border-emerald-200 px-1.5 py-0.5 rounded font-mono font-bold">
                                                                🔒 {{ $sn }}
                                                            </span>
                                                        @endforeach
                                                    @else
                                                        <span class="text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded font-semibold">
                                                            📦 Indent (Belum Alokasi IMEI)
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="py-3 text-center font-bold text-gray-800">{{ $item['qty'] }}</td>
                                            <td class="py-3 text-right font-medium text-gray-600">Rp {{ number_format($item['unit_price'], 0, ',', '.') }}</td>
                                            <td class="py-3 text-right font-black text-gray-900">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- CARD PENERIMAAN UANG MUKA (DP) LANGSUNG -->
                    <div class="bg-white rounded-3xl shadow-sm border {{ $has_dp ? 'border-blue-300 ring-2 ring-blue-500/20' : 'border-gray-100' }} p-6 transition-all">
                        <!-- Toggle Switch Header -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 {{ $has_dp ? 'bg-[#1c69d4] text-white shadow-sm shadow-blue-500/25' : 'bg-gray-100 text-gray-500' }} rounded-2xl transition-all">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-black text-gray-900 text-base">Terima Pembayaran Uang Muka (DP) Sekarang</h4>
                                    <p class="text-xs text-gray-500">Centang jika pelanggan langsung membayar DP saat pembuatan Sales Order ini</p>
                                </div>
                            </div>

                            <!-- Native Styled Toggle -->
                            <label class="relative inline-flex items-center cursor-pointer select-none">
                                <input type="checkbox" wire:model.live="has_dp" class="sr-only peer">
                                <div class="w-12 h-6.5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[3px] after:left-[3px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1c69d4]"></div>
                            </label>
                        </div>

                        <!-- Form DP Input (Tampil saat has_dp dicentang) -->
                        @if ($has_dp)
                            <div class="mt-6 pt-6 border-t border-gray-100 space-y-6 animate-fade-in">
                                <!-- Top Row: Tanggal Pembayaran & No. Kontrak / Referensi -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">
                                            Tanggal Pembayaran <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" wire:model="dp_date"
                                            class="w-full rounded-2xl p-3 border-gray-200 text-xs font-bold focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-xs bg-white"
                                            required>
                                        @error('dp_date')
                                            <span class="text-xs text-red-500 mt-1 block font-medium">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">
                                            No. Kontrak / Referensi
                                        </label>
                                        <input type="text" wire:model="dp_contract_number"
                                            class="w-full rounded-2xl p-3 border-gray-200 text-xs font-medium focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-xs bg-white placeholder-gray-400"
                                            placeholder="Cth: No Bukti Transfer / PO Leasing (Opsional)">
                                    </div>
                                </div>

                                <!-- Customer Deposit Banner (Jika pelanggan memiliki saldo deposit) -->
                                @if ($available_customer_deposit_total > 0)
                                    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                                                <h4 class="font-bold text-blue-900 text-sm">Deposit Tersedia</h4>
                                            </div>
                                            <p class="text-xs text-blue-700 mt-0.5">
                                                Pelanggan memiliki saldo deposit <strong>Rp {{ number_format($available_customer_deposit_total, 0, ',', '.') }}</strong>.
                                            </p>
                                            @if ($use_customer_deposit)
                                                <div class="mt-2.5 flex items-center gap-2">
                                                    <span class="font-bold text-xs text-gray-700">Akan digunakan:</span>
                                                    <div class="relative max-w-xs" x-data="{
                                                        amt: @entangle('custom_deposit_amount'),
                                                        fmt: '',
                                                        init() {
                                                            this.fmt = this.amt ? parseInt(this.amt).toLocaleString('id-ID') : '';
                                                            this.$watch('amt', v => {
                                                                if (document.activeElement !== this.$refs.depInput) {
                                                                    this.fmt = v ? parseInt(v).toLocaleString('id-ID') : '';
                                                                }
                                                            });
                                                        },
                                                        onInput(e) {
                                                            let val = parseInt(e.target.value.replace(/\D/g, ''), 10) || 0;
                                                            this.fmt = val ? val.toLocaleString('id-ID') : '';
                                                            this.amt = val;
                                                        }
                                                    }">
                                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 font-bold text-xs">Rp</span>
                                                        <input type="text" x-ref="depInput" x-model="fmt" @input="onInput($event)"
                                                            class="block w-40 pl-9 pr-3 py-1.5 text-xs font-black border-blue-300 rounded-xl focus:ring-[#1c69d4] focus:border-[#1c69d4] bg-white shadow-xs"
                                                            placeholder="0">
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="shrink-0">
                                            <label class="relative inline-flex items-center cursor-pointer select-none">
                                                <input type="checkbox" wire:model.live="use_customer_deposit" class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1c69d4]"></div>
                                                <span class="ml-2.5 text-xs font-bold text-gray-700">Gunakan Deposit</span>
                                            </label>
                                        </div>
                                    </div>
                                @endif

                                <!-- Pilihan Mode Pembayaran (TUNAI vs NON-TUNAI) -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2.5">
                                        Pilih Mode Pembayaran <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <!-- TUNAI BUTTON -->
                                        <button type="button" wire:click="setDpMode('tunai')"
                                            class="w-full bg-white rounded-2xl relative flex flex-col justify-between overflow-hidden p-5 group cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-2 {{ $dp_mode === 'tunai' ? 'border-[#1c69d4] shadow-md ring-4 ring-[#1c69d4]/10 bg-blue-50/10' : 'border-gray-200 hover:border-gray-300' }}">
                                            <div class="bg-[#FFC4C4] text-neutral-800 rounded-full w-14 h-14 flex items-center justify-center transition-all duration-300 shrink-0">
                                                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                            </div>
                                            <div class="text-left mt-4">
                                                <h4 class="text-base font-black {{ $dp_mode === 'tunai' ? 'text-[#1c69d4]' : 'text-neutral-800' }}">TUNAI</h4>
                                                <p class="text-neutral-500 text-xs mt-1">Pembayaran langsung di tempat (Kas Toko)</p>
                                            </div>
                                            @if ($dp_mode === 'tunai')
                                                <div class="absolute top-4 right-4 w-6 h-6 bg-[#1c69d4] text-white rounded-full flex items-center justify-center shadow-xs">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </button>

                                        <!-- NON-TUNAI BUTTON -->
                                        <button type="button" wire:click="setDpMode('non-tunai')"
                                            class="w-full bg-white rounded-2xl relative flex flex-col justify-between overflow-hidden p-5 group cursor-pointer shadow-xs hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 border-2 {{ $dp_mode === 'non-tunai' ? 'border-[#1c69d4] shadow-md ring-4 ring-[#1c69d4]/10 bg-blue-50/10' : 'border-gray-200 hover:border-gray-300' }}">
                                            <div class="bg-[#DFE7FF] text-neutral-800 rounded-full w-14 h-14 flex items-center justify-center transition-all duration-300 shrink-0">
                                                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                                </svg>
                                            </div>
                                            <div class="text-left mt-4">
                                                <h4 class="text-base font-black {{ $dp_mode === 'non-tunai' ? 'text-[#1c69d4]' : 'text-neutral-800' }}">NON-TUNAI</h4>
                                                <p class="text-neutral-500 text-xs mt-1">Transfer bank, QRIS, EDC, atau leasing digital</p>
                                            </div>
                                            @if ($dp_mode === 'non-tunai')
                                                <div class="absolute top-4 right-4 w-6 h-6 bg-[#1c69d4] text-white rounded-full flex items-center justify-center shadow-xs">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </button>
                                    </div>
                                </div>

                                <!-- Detail Metode Pembayaran -->
                                @if ($dp_mode === 'tunai')
                                    <div class="space-y-3 animate-fade-in">
                                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider">
                                            Akun Kas Toko <span class="text-red-500">*</span>
                                        </label>
                                        @if ($cashPaymentMethods->count() > 1)
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                @foreach ($cashPaymentMethods as $cpm)
                                                    <button type="button" wire:click="selectDpPaymentMethod({{ $cpm->id }})"
                                                        class="p-4 bg-white rounded-2xl border-2 text-left transition-all flex items-center justify-between cursor-pointer {{ $dp_payment_method_id == $cpm->id ? 'border-[#1c69d4] bg-blue-50/20 shadow-sm ring-2 ring-blue-500/20' : 'border-gray-200 hover:border-gray-300' }}">
                                                        <div class="flex items-center gap-3">
                                                            <div class="w-10 h-10 rounded-xl bg-pink-100 text-pink-700 flex items-center justify-center font-bold">
                                                                💵
                                                            </div>
                                                            <div>
                                                                <span class="block text-xs font-bold text-gray-900">{{ $cpm->name }}</span>
                                                                <span class="text-[11px] text-gray-500">{{ $cpm->account_number ?? 'Kasir Fisik' }}</span>
                                                            </div>
                                                        </div>
                                                        @if ($dp_payment_method_id == $cpm->id)
                                                            <div class="w-5 h-5 bg-[#1c69d4] text-white rounded-full flex items-center justify-center text-xs">✓</div>
                                                        @endif
                                                    </button>
                                                @endforeach
                                            </div>
                                        @elseif ($cashPaymentMethods->count() == 1)
                                            @php $singleCash = $cashPaymentMethods->first(); @endphp
                                            <div class="p-4 bg-emerald-50/80 border border-emerald-200 rounded-2xl flex items-center justify-between">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-bold shadow-xs">
                                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                                    </div>
                                                    <div>
                                                        <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider block">Akun Kas Toko Terpilih</span>
                                                        <span class="text-sm font-black text-gray-900">{{ $singleCash->name }}</span>
                                                    </div>
                                                </div>
                                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[11px] font-bold rounded-lg flex items-center gap-1">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    Siap Digunakan
                                                </span>
                                            </div>
                                        @else
                                            <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-800 flex items-center gap-2">
                                                <span class="font-bold">⚠️</span>
                                                <span>Tidak ada akun kas toko spesifik untuk unit ini. Pembayaran tunai akan dicatat langsung sebagai penerimaan kas toko.</span>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <!-- NON-TUNAI: TERKUALIFIKASI PER BANK TERLEBIH DAHULU (SEPERTI POS) -->
                                    <div class="space-y-4 animate-fade-in">
                                        @if (empty($dp_bank_group))
                                            {{-- LANGKAH 1 NON-TUNAI: PILIH GRUP BANK / PROVIDER --}}
                                            <div>
                                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                                                    Pilih Bank / Institusi Non-Tunai <span class="text-red-500">*</span>
                                                </label>
                                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3.5">
                                                    @forelse ($nonCashBankGroups as $group)
                                                        @php
                                                            $imgName = strtolower(str_replace(' ', '', $group)) . '.png';
                                                            $imgPath = public_path('assets/png/paymentmethod/' . $imgName);
                                                            $hasImg = file_exists($imgPath);
                                                        @endphp
                                                        <button type="button" wire:click="selectDpBankGroup('{{ addslashes($group) }}')"
                                                            class="relative p-4.5 bg-white shadow-xs border-2 border-gray-100 rounded-2xl hover:border-[#1c69d4] hover:shadow-md hover:-translate-y-0.5 transition-all text-left flex flex-col justify-between gap-4 group min-h-[125px] cursor-pointer">
                                                            
                                                            {{-- Kanan Atas: Logo Bank / Provider --}}
                                                            <div class="flex justify-end w-full h-8">
                                                                @if ($hasImg)
                                                                    <img src="{{ asset('assets/png/paymentmethod/' . $imgName) }}"
                                                                        alt="{{ $group }}"
                                                                        class="max-h-full max-w-[100px] object-contain filter group-hover:scale-105 transition-all">
                                                                @else
                                                                    <div class="w-8 h-8 bg-gray-50 border border-gray-100 rounded-xl flex items-center justify-center text-gray-400 group-hover:text-[#1c69d4] group-hover:bg-blue-50 group-hover:border-blue-200 transition-all font-black text-xs">
                                                                        {{ strtoupper(substr($group, 0, 3)) }}
                                                                    </div>
                                                                @endif
                                                            </div>

                                                            {{-- Kiri Bawah: Nama & Ikon --}}
                                                            <div>
                                                                <div class="bg-neutral-100 group-hover:bg-blue-50 text-neutral-700 group-hover:text-[#1c69d4] rounded-full w-8 h-8 flex items-center justify-center shrink-0 transition-colors">
                                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                                                    </svg>
                                                                </div>
                                                                <h4 class="font-black text-gray-900 text-xs mt-2 group-hover:text-[#1c69d4] transition-colors uppercase tracking-wide">{{ $group }}</h4>
                                                                <p class="text-[10px] text-gray-400 font-medium leading-tight mt-0.5">
                                                                    Klik untuk pilih metode
                                                                </p>
                                                            </div>
                                                        </button>
                                                    @empty
                                                        <div class="col-span-full p-6 text-center bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                                                            <p class="text-xs text-gray-500 font-bold">Belum ada bank / metode Non-Tunai yang aktif.</p>
                                                        </div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @else
                                            {{-- LANGKAH 2 NON-TUNAI: METODE SPESIFIK DALAM BANK TERPILIH (QRIS, TRANSFER, EDC, DLL) --}}
                                            <div class="space-y-4">
                                                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                                                    <div class="flex items-center gap-3">
                                                        @php
                                                            $grpImgName = strtolower(str_replace(' ', '', $dp_bank_group)) . '.png';
                                                            $grpImgPath = public_path('assets/png/paymentmethod/' . $grpImgName);
                                                        @endphp
                                                        @if (file_exists($grpImgPath))
                                                            <div class="w-10 h-10 bg-white border border-gray-200 rounded-xl p-1.5 flex items-center justify-center shadow-2xs">
                                                                <img src="{{ asset('assets/png/paymentmethod/' . $grpImgName) }}" alt="{{ $dp_bank_group }}" class="max-h-full max-w-full object-contain">
                                                            </div>
                                                        @endif
                                                        <div>
                                                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Grup Bank Terpilih:</span>
                                                            <h4 class="text-sm font-black text-gray-900">{{ strtoupper($dp_bank_group) }}</h4>
                                                        </div>
                                                    </div>

                                                    <button type="button" wire:click="resetDpBankGroup"
                                                        class="px-3.5 py-2 bg-white border border-gray-200 hover:border-gray-300 hover:bg-gray-50 text-gray-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-2xs cursor-pointer">
                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                                        </svg>
                                                        Ganti Bank / Provider
                                                    </button>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">
                                                        Pilih Jenis Pembayaran / Rekening <span class="text-red-500">*</span>
                                                    </label>

                                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                                        @foreach ($methodsInSelectedGroup as $method)
                                                            @php
                                                                $imgName = strtolower(str_replace(' ', '', $method->name)) . '.png';
                                                                $bankImgName = strtolower(str_replace(' ', '', $method->bank_name ?? '')) . '.png';
                                                                $imgPath = public_path('assets/png/paymentmethod/' . $imgName);
                                                                $bankImgPath = public_path('assets/png/paymentmethod/' . $bankImgName);
                                                                $hasImg = file_exists($imgPath);
                                                                $hasBankImg = file_exists($bankImgPath);
                                                                $resolvedImg = $hasImg ? asset('assets/png/paymentmethod/' . $imgName) : ($hasBankImg ? asset('assets/png/paymentmethod/' . $bankImgName) : null);
                                                                $isSelected = ($dp_payment_method_id == $method->id);
                                                            @endphp
                                                            <button type="button" wire:click="selectDpPaymentMethod({{ $method->id }})"
                                                                class="relative p-4 bg-white rounded-2xl border-2 transition-all flex flex-col justify-between items-start text-left gap-2.5 cursor-pointer group min-h-[95px] {{ $isSelected ? 'border-[#1c69d4] bg-blue-50/20 shadow-md ring-2 ring-blue-500/20' : 'border-gray-100 hover:border-gray-300 shadow-xs' }}">
                                                                @if ($isSelected)
                                                                    <div class="absolute top-2.5 right-2.5 w-5 h-5 bg-[#1c69d4] text-white rounded-full flex items-center justify-center shadow-xs">
                                                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                                        </svg>
                                                                    </div>
                                                                @endif

                                                                <div class="flex items-center gap-2.5 w-full pr-6">
                                                                    <div class="w-8 h-8 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center shrink-0 p-1">
                                                                        @if ($resolvedImg)
                                                                            <img src="{{ $resolvedImg }}" alt="{{ $method->name }}" class="max-h-full max-w-full object-contain">
                                                                        @else
                                                                            <span class="font-black text-[10px] text-gray-500">{{ strtoupper(substr($method->name, 0, 2)) }}</span>
                                                                        @endif
                                                                    </div>
                                                                    <span class="text-xs font-black text-gray-900 leading-tight group-hover:text-[#1c69d4] transition-colors line-clamp-1">
                                                                        {{ $method->name }}
                                                                    </span>
                                                                </div>

                                                                @if ($method->account_number)
                                                                    <div class="pt-2 border-t border-gray-100 w-full flex items-center justify-between text-[11px]">
                                                                        <span class="text-gray-500 font-mono font-bold">{{ $method->account_number }}</span>
                                                                        @if ($method->account_owner)
                                                                            <span class="text-gray-400 text-[10px] truncate max-w-[120px]">{{ $method->account_owner }}</span>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                @error('dp_payment_method_id')
                                                    <span class="text-xs text-red-500 block font-medium">{{ $message }}</span>
                                                @enderror

                                                <!-- Rates / MDR (Jika metode terpilih punya tarif) -->
                                                @php
                                                    $selectedMethodObj = $methodsInSelectedGroup->firstWhere('id', $dp_payment_method_id);
                                                @endphp
                                                @if ($selectedMethodObj && $selectedMethodObj->rates->where('is_active', true)->count() > 0)
                                                    <div class="p-4 bg-gray-50 border border-gray-200/80 rounded-2xl space-y-2.5">
                                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                            Pilih Tarif / MDR Kartu:
                                                        </label>
                                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                                            @foreach ($selectedMethodObj->rates->where('is_active', true) as $rate)
                                                                <label class="relative flex items-center justify-between p-3 rounded-xl border-2 cursor-pointer transition-all {{ $dp_payment_method_rate_id == $rate->id ? 'border-[#1c69d4] bg-white shadow-xs text-[#1c69d4]' : 'border-gray-200 bg-white/60 hover:bg-white text-gray-700' }}">
                                                                    <input type="radio" wire:model.live="dp_payment_method_rate_id" value="{{ $rate->id }}" class="sr-only">
                                                                    <div>
                                                                        <span class="block text-xs font-bold">{{ $rate->name }}</span>
                                                                        <span class="block text-[10px] text-emerald-600 font-bold">MDR: {{ (float)$rate->mdr_percentage }}%</span>
                                                                    </div>
                                                                    @if ($dp_payment_method_rate_id == $rate->id)
                                                                        <svg class="w-4 h-4 text-[#1c69d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                                        </svg>
                                                                    @endif
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Nominal DP Input & Quick Preset Chips -->
                                <div>
                                    <div class="flex justify-between items-center mb-1.5">
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                            Nominal Uang Muka (DP) <span class="text-red-500">*</span>
                                        </label>
                                        <span class="text-xs text-gray-500 font-medium">Maksimal: Rp {{ number_format($grand_total, 0, ',', '.') }}</span>
                                    </div>

                                    <div class="relative" x-data="{
                                        rawAmount: @entangle('dp_amount'),
                                        formattedAmount: '',
                                        init() {
                                            this.formattedAmount = this.formatNumber(this.rawAmount);
                                            this.$watch('rawAmount', value => {
                                                if (document.activeElement !== this.$refs.dpInput) {
                                                    this.formattedAmount = this.formatNumber(value);
                                                }
                                            });
                                        },
                                        formatNumber(val) {
                                            if (!val) return '';
                                            let num = parseInt(String(val).replace(/\D/g, ''), 10);
                                            return isNaN(num) ? '' : num.toLocaleString('id-ID');
                                        },
                                        updateAmount(e) {
                                            let val = e.target.value;
                                            let num = parseInt(val.replace(/\D/g, ''), 10);
                                            if (isNaN(num)) num = 0;
                                            this.formattedAmount = this.formatNumber(num);
                                            clearTimeout(this.timeout);
                                            this.timeout = setTimeout(() => {
                                                this.rawAmount = num;
                                                $wire.$commit();
                                            }, 350);
                                        }
                                    }">
                                        <div class="absolute left-4 top-3.5 text-gray-400 font-black text-base">Rp</div>
                                        <input type="text" x-ref="dpInput" x-model="formattedAmount" @input="updateAmount($event)"
                                            class="w-full pl-12 pr-4 py-3 rounded-2xl border-gray-300 text-xl font-black focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-xs bg-blue-50/20 text-gray-900"
                                            placeholder="0">
                                    </div>

                                    @error('dp_amount')
                                        <span class="text-xs text-red-500 mt-1.5 block font-medium">{{ $message }}</span>
                                    @enderror

                                    <!-- Quick Preset Chips -->
                                    <div class="flex flex-wrap gap-2 mt-2.5">
                                        <button type="button" wire:click="setDpNominal(500000)"
                                            class="px-3 py-1.5 text-xs font-bold rounded-xl border border-gray-200 bg-white hover:bg-blue-50 hover:border-blue-300 hover:text-[#1c69d4] transition-all cursor-pointer shadow-2xs">
                                            Rp 500.000
                                        </button>
                                        <button type="button" wire:click="setDpNominal(1000000)"
                                            class="px-3 py-1.5 text-xs font-bold rounded-xl border border-gray-200 bg-white hover:bg-blue-50 hover:border-blue-300 hover:text-[#1c69d4] transition-all cursor-pointer shadow-2xs">
                                            Rp 1.000.000
                                        </button>
                                        <button type="button" wire:click="setDpPercentage(20)"
                                            class="px-3 py-1.5 text-xs font-bold rounded-xl border border-gray-200 bg-white hover:bg-blue-50 hover:border-blue-300 hover:text-[#1c69d4] transition-all cursor-pointer shadow-2xs">
                                            20%
                                        </button>
                                        <button type="button" wire:click="setDpPercentage(50)"
                                            class="px-3 py-1.5 text-xs font-bold rounded-xl border border-gray-200 bg-white hover:bg-blue-50 hover:border-blue-300 hover:text-[#1c69d4] transition-all cursor-pointer shadow-2xs">
                                            50%
                                        </button>
                                        <button type="button" wire:click="setDpPercentage(100)"
                                            class="px-3 py-1.5 text-xs font-black rounded-xl border border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-all cursor-pointer shadow-2xs">
                                            Lunas (100%)
                                        </button>
                                    </div>
                                </div>

                                <!-- Catatan Pembayaran DP (Opsional) -->
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                                        Catatan Pembayaran DP (Opsional)
                                    </label>
                                    <input type="text" wire:model="dp_notes"
                                        class="w-full px-4 py-2.5 rounded-2xl border-gray-200 text-xs font-medium focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-xs bg-white placeholder-gray-400"
                                        placeholder="Keterangan singkat pembayaran DP...">
                                </div>

                                <!-- Sisa Pelunasan Badge Box -->
                                @php
                                    $dpVal = (float)$dp_amount;
                                    $depVal = (float)($use_customer_deposit ? $custom_deposit_amount : 0);
                                    $totalDpNow = $dpVal + $depVal;
                                    $remaining = max(0, $grand_total - $totalDpNow);
                                @endphp
                                <div class="p-4 bg-blue-50/80 border border-blue-200 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs shadow-2xs">
                                    <div>
                                        <span class="text-gray-500 font-medium block">Sisa Tagihan (Pelunasan di POS nanti):</span>
                                        <span class="text-lg font-black {{ $remaining == 0 ? 'text-emerald-600' : 'text-[#1c69d4]' }}">
                                            Rp {{ number_format($remaining, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <div>
                                        @if ($remaining == 0 && $totalDpNow > 0)
                                            <span class="px-3 py-1.5 bg-emerald-100 text-emerald-800 text-xs font-black rounded-xl inline-flex items-center gap-1 shadow-2xs">
                                                ✓ LUNAS LANGSUNG
                                            </span>
                                        @else
                                            <span class="px-3 py-1.5 bg-blue-100 text-blue-800 text-xs font-bold rounded-xl inline-flex items-center gap-1 shadow-2xs">
                                                DP DITERIMA: Rp {{ number_format($totalDpNow, 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="mt-4 p-3.5 bg-gray-50 border border-gray-200/80 rounded-2xl text-xs text-gray-500 flex items-start gap-2.5">
                                <span class="text-gray-400 mt-0.5 text-sm">ℹ️</span>
                                <div>
                                    SO akan disimpan dengan status <strong>Pending (Belum DP)</strong>. Pelanggan dapat membayar uang muka (DP) atau langsung melunasi pesanan kapan saja nanti di kasir POS atau halaman detail SO.
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Catatan Pesanan -->
                    <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                            Catatan Khusus Sales Order (Opsional)
                        </label>
                        <textarea wire:model="notes" rows="3"
                            class="w-full px-4 py-3 rounded-2xl border-gray-200 text-xs focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors resize-none font-medium placeholder-gray-400"
                            placeholder="Tuliskan catatan khusus untuk pesanan ini (misal: warna cadangan, janji ambil tanggal sekian, dll)..."></textarea>
                    </div>
                </div>

                <!-- Right Column (5 cols): Sticky Financial Summary Card with White Blurred Gradient -->
                <div class="lg:col-span-5">
                    <div class="sticky top-6 bg-white/85 backdrop-blur-xl bg-gradient-to-br from-white/95 via-white/85 to-slate-50/90 rounded-3xl shadow-xl shadow-slate-200/50 border border-white/80 ring-1 ring-slate-200/60 p-6 md:p-7 text-gray-900 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-5">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-blue-50 text-[#1c69d4] flex items-center justify-center font-bold">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <h3 class="font-black text-gray-800 uppercase tracking-wider text-xs">
                                        Ringkasan Pembayaran
                                    </h3>
                                </div>
                                @php
                                    $totalPaidReview = (float)$dp_amount + (float)($use_customer_deposit ? $custom_deposit_amount : 0);
                                @endphp
                                @if ($has_dp && $totalPaidReview >= $grand_total && $grand_total > 0)
                                    <span class="text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-full flex items-center gap-1 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        LUNAS
                                    </span>
                                @elseif ($has_dp && $totalPaidReview > 0)
                                    <span class="text-[10px] font-black bg-blue-50 text-[#1c69d4] border border-blue-200 px-2.5 py-1 rounded-full flex items-center gap-1 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                                        DOWN PAYMENT
                                    </span>
                                @else
                                    <span class="text-[10px] font-black bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-full flex items-center gap-1 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        PENDING (BELUM DP)
                                    </span>
                                @endif
                            </div>

                            <div class="space-y-3.5 text-xs text-gray-600">
                                <div class="flex justify-between items-center">
                                    <span class="font-medium text-gray-500">Sub Total Produk</span>
                                    <span class="font-bold text-gray-800 text-sm">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between items-center text-red-500">
                                    <span class="font-medium">Diskon Total</span>
                                    <span class="font-bold text-sm">- Rp {{ number_format($discount_amount, 0, ',', '.') }}</span>
                                </div>

                                <div class="pt-3 border-t border-gray-100 flex justify-between items-center bg-gray-50/70 -mx-6 px-6 py-3 rounded-2xl">
                                    <span class="font-bold text-gray-700 uppercase tracking-wider text-[11px]">Grand Total</span>
                                    <span class="font-black text-emerald-600 text-2xl tracking-tight">
                                        Rp {{ number_format($grand_total, 0, ',', '.') }}
                                    </span>
                                </div>

                                @if ($has_dp && $totalPaidReview > 0)
                                    <div class="pt-3 border-t border-gray-100 space-y-2.5">
                                        @if ($use_customer_deposit && (float)$custom_deposit_amount > 0)
                                            <div class="flex justify-between items-center text-purple-700 bg-purple-50/60 p-2.5 rounded-xl border border-purple-100">
                                                <span class="font-medium flex items-center gap-1.5">
                                                    <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    Deposit Terpakai:
                                                </span>
                                                <span class="font-black text-sm">Rp {{ number_format((float)$custom_deposit_amount, 0, ',', '.') }}</span>
                                            </div>
                                        @endif

                                        @if ((float)$dp_amount > 0)
                                            <div class="flex justify-between items-center text-[#1c69d4] bg-blue-50/60 p-2.5 rounded-xl border border-blue-100">
                                                <span class="font-medium flex items-center gap-1.5">
                                                    <svg class="w-4 h-4 text-[#1c69d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                                    DP ({{ $dp_mode === 'tunai' ? 'Tunai' : 'Non-Tunai' }}):
                                                </span>
                                                <span class="font-black text-sm">Rp {{ number_format((float)$dp_amount, 0, ',', '.') }}</span>
                                            </div>
                                        @endif

                                        @php
                                            $remBal = max(0, $grand_total - $totalPaidReview);
                                        @endphp
                                        <div class="flex justify-between items-center text-amber-700 font-bold bg-amber-50/60 p-2.5 rounded-xl border border-amber-100">
                                            <span class="flex items-center gap-1.5">
                                                <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                Sisa Pelunasan di POS:
                                            </span>
                                            <span class="text-sm font-black text-amber-800">Rp {{ number_format($remBal, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Submit Button inside Card -->
                        <div class="mt-8 pt-5 border-t border-gray-100">
                            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                                class="w-full py-3.5 {{ $has_dp && $totalPaidReview > 0 ? 'bg-[#1c69d4] hover:bg-blue-600 shadow-blue-500/25' : 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/25' }} text-white font-black text-sm rounded-2xl shadow-lg transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                <div wire:loading.remove wire:target="save">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div wire:loading wire:target="save">
                                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <span wire:loading.remove wire:target="save">
                                    @if ($has_dp && $totalPaidReview > 0)
                                        Simpan SO & Terima DP (Rp {{ number_format($totalPaidReview, 0, ',', '.') }})
                                    @else
                                        Buat Sales Order (Pending)
                                    @endif
                                </span>
                                <span wire:loading wire:target="save">Memproses Sales Order...</span>
                            </button>
                            <p class="text-[10px] text-gray-400 text-center mt-2.5">
                                Data otomatis tersimpan di ZedPOS dan disinkronkan ke Accurate Online.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ==================== BOTTOM STEPPER NAVIGATION ==================== --}}
        <div class="flex justify-between items-center pt-6 mt-4 w-full max-w-4xl mx-auto border-t border-gray-100">
            @if ($wizardStep > 1)
                <button type="button" wire:click="prevStep"
                    class="px-6 py-3 bg-white border border-gray-200 text-gray-700 font-bold text-xs rounded-2xl hover:bg-gray-50 hover:text-gray-900 transition-colors flex items-center gap-2 cursor-pointer shadow-xs">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali
                </button>
            @else
                <div></div>
            @endif

            @if ($wizardStep < 3)
                <button type="button" wire:click="nextStep"
                    class="px-8 py-3 bg-[#1c69d4] hover:bg-blue-600 text-white font-black text-xs rounded-2xl transition-all shadow-lg hover:shadow-blue-500/30 flex items-center gap-2 cursor-pointer">
                    Lanjutkan ke Langkah {{ $wizardStep + 1 }}
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            @endif
        </div>
    </form>

    <!-- Modal Pelanggan Baru -->
    @if ($showNewCustomerModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 animate-fade-in">
            <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h3 class="text-base font-black text-gray-900">Tambah Pelanggan Baru</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Pelanggan akan tersimpan di ZedPOS & disinkron ke Accurate</p>
                    </div>
                    <button wire:click="$set('showNewCustomerModal', false)"
                        class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="new_customer_name"
                            class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-xs focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-xs font-medium"
                            placeholder="Cth: Budi Santoso">
                        @error('new_customer_name')
                            <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Nomor WhatsApp / HP <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="new_customer_phone"
                            class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-xs focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-xs font-medium"
                            placeholder="Cth: 08123456789">
                        @error('new_customer_phone')
                            <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                            Email (Opsional)
                        </label>
                        <input type="email" wire:model="new_customer_email"
                            class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-xs focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-xs font-medium"
                            placeholder="Cth: budi@gmail.com (kosongkan jika tidak ada)">
                        <p class="text-[10px] text-gray-400 mt-1">*Jika kosong, email otomatis digenerate dari nomor HP.</p>
                    </div>
                </div>

                <div class="p-5 border-t border-gray-100 bg-gray-50 flex justify-end gap-2">
                    <button type="button" wire:click="$set('showNewCustomerModal', false)"
                        class="px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition-all">
                        Batal
                    </button>
                    <button type="button" wire:click="createNewCustomer" wire:loading.attr="disabled"
                        class="px-6 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-bold text-xs transition-all flex items-center gap-2 cursor-pointer">
                        <span wire:loading.remove wire:target="createNewCustomer">Simpan & Pilih</span>
                        <span wire:loading wire:target="createNewCustomer">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
