<div class="max-w-8xl mx-auto p-4 md:p-6 min-h-screen">
    <div class="mb-6">
        <a href="{{ route('admin.sales-orders.index') }}" wire:navigate
            class="text-sm font-medium text-gray-500 hover:text-[#1c69d4] flex items-center gap-1 mb-2 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Daftar SO
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Buat Sales Order Baru</h1>
        <p class="text-gray-500 text-sm mt-1">Input pesanan pelanggan untuk pembuatan SO di Accurate</p>
    </div>

    <!-- Stepper UI -->
    <div class="mb-10 mt-4">
        <div class="flex items-center justify-between relative max-w-3xl mx-auto">
            <!-- Background Line -->
            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-gray-200 rounded-full z-0"></div>
            <!-- Progress Line -->
            <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-[#1c69d4] rounded-full z-0 transition-all duration-300" style="width: {{ ($wizardStep - 1) * 50 }}%"></div>
            
            <!-- Step 1 -->
            <div class="relative z-10 flex flex-col items-center gap-2">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-colors {{ $wizardStep >= 1 ? 'bg-[#1c69d4] text-white shadow-lg shadow-blue-500/30' : 'bg-gray-200 text-gray-500' }}">1</div>
                <span class="text-xs font-bold {{ $wizardStep >= 1 ? 'text-[#1c69d4]' : 'text-gray-500' }}">Informasi Dasar</span>
            </div>
            
            <!-- Step 2 -->
            <div class="relative z-10 flex flex-col items-center gap-2">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-colors {{ $wizardStep >= 2 ? 'bg-[#1c69d4] text-white shadow-lg shadow-blue-500/30' : 'bg-white border-2 border-gray-200 text-gray-400' }}">2</div>
                <span class="text-xs font-bold {{ $wizardStep >= 2 ? 'text-[#1c69d4]' : 'text-gray-400' }}">Keranjang Produk</span>
            </div>
            
            <!-- Step 3 -->
            <div class="relative z-10 flex flex-col items-center gap-2">
                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-colors {{ $wizardStep >= 3 ? 'bg-[#1c69d4] text-white shadow-lg shadow-blue-500/30' : 'bg-white border-2 border-gray-200 text-gray-400' }}">3</div>
                <span class="text-xs font-bold {{ $wizardStep >= 3 ? 'text-[#1c69d4]' : 'text-gray-400' }}">Review & Catatan</span>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        @if ($wizardStep == 1)
            {{-- Header Info --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8 w-full max-w-4xl mx-auto animate-fade-in-up">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg">Informasi Pesanan</h3>
                        <p class="text-xs text-gray-500">Pilih pelanggan dan tentukan tanggal pembuatan SO</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="relative">
                        <div class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2 flex justify-between items-center">
                            <label>Pelanggan *</label>
                            <div class="flex items-center gap-2">
                                <div wire:loading wire:target="searchCustomer" class="text-[#1c69d4]">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <button type="button" wire:click.prevent="$set('showNewCustomerModal', true)" class="text-[#1c69d4] hover:text-blue-700 normal-case font-bold text-[10px] bg-blue-50 px-2 py-1 rounded-md transition-colors z-10 cursor-pointer">
                                    + Baru
                                </button>
                            </div>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="searchCustomer" class="w-full px-4 py-3 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors font-medium placeholder-gray-400" placeholder="Ketik nama / email pelanggan..." autocomplete="off">

                        @if (!empty($customerSearchResults))
                            <div class="absolute z-20 mt-2 w-full bg-white rounded-xl shadow-2xl border border-gray-100 max-h-60 overflow-y-auto">
                                @foreach ($customerSearchResults as $res)
                                    <div wire:click="selectCustomer({{ $res['id'] }}, '{{ addslashes($res['name']) }}')" class="px-5 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors">
                                        <div class="font-bold text-gray-900 text-sm">{{ $res['name'] }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">{{ $res['email'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <input type="hidden" wire:model="user_id" required>
                        @error('user_id')
                            <span class="text-xs text-red-500 mt-2 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Tanggal SO *</label>
                        <input type="date" wire:model="order_date" class="w-full px-4 py-3 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors font-medium" required>
                        @error('order_date')
                            <span class="text-xs text-red-500 mt-2 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="relative">
                        <div class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2 flex justify-between items-center">
                            <label>Pramuniaga (Sales)</label>
                            <div class="flex items-center gap-2">
                                <div wire:loading wire:target="searchSales" class="text-[#1c69d4]">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="searchSales" class="w-full px-4 py-3 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors font-medium placeholder-gray-400" placeholder="Ketik nama sales (Opsional)..." autocomplete="off">

                        @if (!empty($salesSearchResults))
                            <div class="absolute z-20 mt-2 w-full bg-white rounded-xl shadow-2xl border border-gray-100 max-h-60 overflow-y-auto">
                                @foreach ($salesSearchResults as $res)
                                    <div wire:click="selectSales({{ $res['id'] }}, '{{ addslashes($res['name']) }}')" class="px-5 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors group">
                                        <div class="font-bold text-gray-800 group-hover:text-[#1c69d4] text-sm">{{ $res['name'] }}</div>
                                        <div class="text-xs font-medium text-gray-500 mt-1">{{ $res['employee_no'] ?? 'N/A' }} &bull; {{ $res['branch_name'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <input type="hidden" wire:model="sales_id">
                    </div>
                </div>
            </div>

        @elseif ($wizardStep == 2)
            {{-- Line Items --}}
            <div class="space-y-4 w-full animate-fade-in-up">
                <div class="flex items-center justify-between pb-2 border-b border-gray-200/60">
                    <h3 class="font-bold text-gray-800 text-lg">Daftar Produk</h3>
                    <button type="button" wire:click="addItem" class="px-4 py-2 bg-blue-50 text-blue-600 hover:bg-blue-100 font-bold rounded-xl text-sm transition-colors flex items-center gap-2 border border-blue-100 shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Baris
                    </button>
                </div>
                @error('items')
                    <span class="text-xs text-red-500 block">{{ $message }}</span>
                @enderror

                @foreach ($items as $index => $item)
                    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm relative group transition-all hover:border-blue-200" wire:key="item-{{ $index }}">
                        @if (count($items) > 1)
                            <button type="button" wire:click="removeItem({{ $index }})" class="absolute -top-3 -right-3 p-2 bg-white border border-gray-100 shadow-md text-red-500 hover:text-white hover:bg-red-500 rounded-full transition-colors opacity-0 group-hover:opacity-100 focus:opacity-100 z-10" title="Hapus Baris">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        @endif

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                            <!-- Produk Search -->
                            <div class="lg:col-span-4 relative">
                                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Pilih Produk</label>
                                <div class="relative">
                                    <input type="text" wire:model.live.debounce.300ms="items.{{ $index }}.searchProduct" class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors placeholder-gray-400 font-medium" placeholder="Ketik nama / SKU..." autocomplete="off">
                                    <div wire:loading wire:target="items.{{ $index }}.searchProduct" class="absolute right-3 top-3 text-[#1c69d4]">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>

                                @if (!empty($item['searchResults']))
                                    <div class="absolute z-30 mt-2 left-0 right-0 bg-white rounded-xl shadow-2xl border border-gray-100 max-h-60 overflow-y-auto">
                                        @foreach ($item['searchResults'] as $res)
                                            <div wire:click="selectProduct({{ $index }}, {{ $res['id'] }}, '{{ addslashes($res['name']) }}', {{ $res['price'] }})" class="px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors">
                                                <div class="font-bold text-gray-800 text-sm leading-tight">{{ $res['name'] }}</div>
                                                <div class="text-xs text-[#1c69d4] font-bold mt-1">Rp {{ number_format($res['price'], 0, ',', '.') }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <input type="hidden" wire:model="items.{{ $index }}.variant_id" required>
                                @error("items.$index.variant_id") <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Kuantitas -->
                            <div class="lg:col-span-2">
                                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 lg:text-center">Qty</label>
                                <input type="number" min="1" wire:model.live.debounce.500ms="items.{{ $index }}.qty" class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm text-center bg-gray-50 focus:bg-white transition-colors font-bold">
                                @error("items.$index.qty") <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            <!-- Harga -->
                            <div class="lg:col-span-2">
                                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 lg:text-right">Harga</label>
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
                                        }, 500);
                                    }
                                }" x-ref="amountInput" x-model="formattedAmount" @input="updateAmount($event)" class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm lg:text-right bg-gray-50 focus:bg-white transition-colors font-bold">
                            </div>

                            <!-- Diskon -->
                            <div class="lg:col-span-2">
                                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 lg:text-right">Diskon</label>
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
                                        }, 500);
                                    }
                                }" x-ref="discountInput" x-model="formattedAmount" @input="updateAmount($event)" class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm text-red-500 focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm lg:text-right bg-red-50 focus:bg-white transition-colors font-bold placeholder-red-300" placeholder="0">
                            </div>

                            <!-- Total -->
                            <div class="lg:col-span-2 flex flex-col justify-end h-full">
                                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5 lg:text-right hidden lg:block">Total</label>
                                <div class="font-black text-[#1c69d4] lg:text-right mt-1 lg:mt-0 text-lg border-t border-gray-100 lg:border-0 pt-3 lg:pt-0">
                                    <span class="text-xs font-normal text-gray-400 lg:hidden mr-2 uppercase">Total:</span>Rp {{ number_format($item['total'], 0, ',', '.') }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Kunci IMEI -->
                            <div>
                                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Kunci IMEI / SN (Opsional)</label>
                                <input type="text" wire:model.defer="items.{{ $index }}.serial_number" class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors" placeholder="Ketik IMEI dipisah koma jika > 1">
                                <p class="text-[10px] text-gray-500 mt-1">*Akan dikunci otomatis di SO Accurate.</p>
                            </div>

                            <!-- Multi Sales per Item -->
                            <div class="relative">
                                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Tenaga Penjual (Bisa Multi-Sales)</label>
                                <div class="flex flex-wrap gap-2 mb-2">
                                    @foreach($item['sales_names'] ?? [] as $sIndex => $sName)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-xs font-bold shadow-sm">
                                            {{ $sName }}
                                            <button type="button" wire:click="removeItemSales({{ $index }}, {{ $sIndex }})" class="text-emerald-500 hover:text-emerald-700 hover:bg-emerald-100 p-0.5 rounded-md transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </span>
                                    @endforeach
                                </div>
                                <div class="relative">
                                    <input type="text" wire:model.live.debounce.300ms="items.{{ $index }}.searchSales" class="w-full px-4 py-2.5 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors placeholder-gray-400" placeholder="Ketik nama karyawan untuk menambah sales...">
                                    <div wire:loading wire:target="items.{{ $index }}.searchSales" class="absolute right-3 top-3 text-[#1c69d4]">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                                
                                @if (!empty($item['salesSearchResults']))
                                    <div class="absolute z-30 mt-2 left-0 right-0 bg-white rounded-xl shadow-2xl border border-gray-100 max-h-60 overflow-y-auto">
                                        @foreach ($item['salesSearchResults'] as $res)
                                            <div wire:click="selectItemSales({{ $index }}, {{ $res['id'] }}, '{{ addslashes($res['name']) }}')" class="px-4 py-3 hover:bg-emerald-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors">
                                                <div class="font-bold text-gray-800 text-sm">{{ $res['name'] }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

        @elseif ($wizardStep == 3)
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 w-full animate-fade-in-up">
                {{-- Notes --}}
                <div class="lg:col-span-7">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8 flex flex-col h-full">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2.5 bg-amber-50 text-amber-600 rounded-xl">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 text-lg">Catatan Pesanan</h3>
                                <p class="text-xs text-gray-500">Informasi tambahan untuk Sales Order</p>
                            </div>
                        </div>
                        <textarea wire:model="notes" rows="6" class="w-full flex-1 px-4 py-3 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm bg-gray-50 focus:bg-white transition-colors resize-none font-medium placeholder-gray-400" placeholder="Tuliskan catatan khusus untuk pesanan ini..."></textarea>
                    </div>
                </div>

                {{-- Totals --}}
                <div class="lg:col-span-5">
                    <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl shadow-xl border border-gray-700 p-6 md:p-8 text-white relative overflow-hidden h-full flex flex-col justify-center">
                        <div class="absolute top-0 right-0 p-8 opacity-5">
                            <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
                            </svg>
                        </div>

                        <h3 class="font-bold text-gray-300 mb-6 border-b border-gray-600/50 pb-4 uppercase tracking-wider text-[11px]">Ringkasan Pembayaran</h3>
                        <div class="space-y-4 relative z-10 flex-1">
                            <div class="flex justify-between items-center text-gray-400">
                                <span class="text-sm">Sub Total</span>
                                <span class="font-bold text-white text-lg">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center text-gray-400">
                                <span class="text-sm">Total Diskon</span>
                                <span class="font-bold text-red-400 text-lg">- Rp {{ number_format($discount_amount, 0, ',', '.') }}</span>
                            </div>

                            <div class="pt-6 mt-4 border-t border-gray-600/50">
                                <div class="text-gray-400 text-xs uppercase tracking-wider mb-1">Total Akhir (Grand Total)</div>
                                <div class="font-black text-4xl text-emerald-400 tracking-tight">Rp {{ number_format($grand_total, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Navigasi Bawah --}}
        <div class="flex justify-between items-center pt-6 mt-6 w-full max-w-4xl mx-auto">
            @if ($wizardStep > 1)
                <button type="button" wire:click="prevStep" class="px-6 py-3 bg-white border border-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-50 hover:text-gray-900 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Kembali
                </button>
            @else
                <div></div>
            @endif

            @if ($wizardStep < 3)
                <button type="button" wire:click="nextStep" class="px-8 py-3 bg-[#1c69d4] text-white font-bold rounded-xl hover:bg-blue-600 transition-colors shadow-lg hover:shadow-blue-500/30 flex items-center gap-2">
                    Selanjutnya
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            @else
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="px-8 py-3 bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-emerald-500/30 flex items-center justify-center gap-3">
                    <div wire:loading.remove wire:target="save">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                    </div>
                    <div wire:loading wire:target="save">
                        <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <span wire:loading.remove wire:target="save">Buat Sales Order</span>
                    <span wire:loading wire:target="save">Memproses...</span>
                </button>
            @endif
        </div>
    </form>

    <!-- Modal Pelanggan Baru -->
    @if ($showNewCustomerModal)
        <div class="fixed inset-0 z-100 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col max-h-full">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h3 class="text-lg font-black text-gray-900">Pelanggan Baru</h3>
                        <p class="text-xs text-gray-500 mt-1">Tambah data pelanggan secara instan.</p>
                    </div>
                    <button wire:click="$set('showNewCustomerModal', false)"
                        class="p-2 bg-white border border-gray-200 text-gray-400 hover:text-red-500 hover:border-red-100 hover:bg-red-50 rounded-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4 overflow-y-auto">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Nama
                            Lengkap
                            *</label>
                        <input type="text" wire:model="new_customer_name"
                            class="w-full px-4 py-3 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm font-medium"
                            placeholder="Cth: John Doe">
                        @error('new_customer_name')
                            <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">No.
                            WhatsApp
                            *</label>
                        <input type="text" wire:model="new_customer_phone"
                            class="w-full px-4 py-3 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm font-medium"
                            placeholder="Cth: 08123456789">
                        @error('new_customer_phone')
                            <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Email
                            (Opsional)</label>
                        <input type="email" wire:model="new_customer_email"
                            class="w-full px-4 py-3 rounded-xl border-gray-200 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm font-medium"
                            placeholder="Kosongkan jika tidak ada">
                        <p class="text-[10px] text-gray-400 mt-1">*Jika kosong, sistem akan mengenerate email otomatis
                            dari
                            nomor HP.</p>
                    </div>
                </div>

                <div class="p-5 border-t border-gray-100 bg-gray-50 flex justify-end">
                    <button type="button" wire:click="createNewCustomer" wire:loading.attr="disabled"
                        class="px-6 py-2.5 bg-gray-900 text-white rounded-xl font-bold text-sm hover:bg-gray-800 transition-all flex items-center gap-2">
                        <span wire:loading.remove wire:target="createNewCustomer">Simpan & Pilih</span>
                        <span wire:loading wire:target="createNewCustomer">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
