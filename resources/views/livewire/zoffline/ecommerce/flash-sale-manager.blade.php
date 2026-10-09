<div class="p-4 sm:p-6 min-h-screen bg-neutral-100">
    {{-- Header Modul E-Commerce --}}
    @include('livewire.zoffline.ecommerce.partials.header', ['title' => 'Manajemen Event Flash Sale'])

    {{-- Content Area --}}
    <div class="space-y-6">
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-extrabold text-gray-900">Event Flash Sale & Countdown</h3>
                <p class="text-xs text-gray-500 mt-0.5">Sesi flash sale akan otomatis muncul dengan timer berjalan di aplikasi mobile.</p>
            </div>
            <button wire:click="openEventModal"
                class="px-4 py-2.5 bg-neutral-800 hover:bg-neutral-900 text-white rounded-xl text-xs font-bold shadow-xs flex items-center gap-2 transition-all">
                <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path></svg>
                <span>Buat Sesi Flash Sale Baru</span>
            </button>
        </div>

        {{-- Flash Sale Sessions Cards --}}
        <div class="space-y-4">
            @forelse($flashSales as $fs)
                @php
                    $isOngoing = $fs->isRunning();
                    $now = now();
                    $isUpcoming = $now->lt($fs->start_time);
                    $isExpired = $now->gt($fs->end_time);
                @endphp
                <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
                    {{-- Header Sesi --}}
                    <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/50">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-2xl flex items-center justify-center text-lg {{ $isOngoing ? 'bg-amber-100 text-amber-700 animate-pulse' : 'bg-gray-100 text-gray-500' }}">
                                ⚡
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-extrabold text-gray-900 text-sm">{{ $fs->title }}</h4>
                                    @if($isOngoing)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">Sedang Berlangsung</span>
                                    @elseif($isUpcoming)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-blue-100 text-blue-800 border border-blue-200">Terjadwal</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-gray-100 text-gray-500">Berakhir</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5 font-mono">
                                    Periode: {{ $fs->start_time->format('d M H:i') }} s/d {{ $fs->end_time->format('d M H:i') }} WIB
                                    • Toko: <strong class="text-indigo-600">{{ $fs->businessUnit?->name ?? 'Semua Toko' }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button wire:click="openAddItemModal({{ $fs->id }})"
                                class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl text-xs font-bold transition-all border border-indigo-200 flex items-center gap-1">
                                <span>+ Tambah Produk</span>
                            </button>
                            <button wire:click="toggleEventActive({{ $fs->id }})"
                                class="px-2.5 py-1.5 text-xs font-bold rounded-xl {{ $fs->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-400' }}">
                                {{ $fs->is_active ? 'Aktif' : 'Non-Aktif' }}
                            </button>
                            <button wire:click="deleteEvent({{ $fs->id }})" onclick="return confirm('Hapus sesi flash sale ini?') || event.stopImmediatePropagation()"
                                class="px-2.5 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-xl">
                                Hapus
                            </button>
                        </div>
                    </div>

                    {{-- Products in Session --}}
                    <div class="p-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @forelse($fs->items as $item)
                                @php
                                    $prod = $item->productAccurate;
                                    $disc = $item->discount_percent;
                                @endphp
                                <div class="p-3 bg-gray-50 rounded-2xl border border-gray-200 flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5 mb-0.5">
                                            @if($disc > 0)
                                                <span class="px-1.5 py-0.2 rounded text-[10px] font-black bg-rose-600 text-white">-{{ $disc }}%</span>
                                            @endif
                                            <span class="text-[10px] text-gray-400 font-bold uppercase truncate">{{ $prod?->brandName }}</span>
                                        </div>
                                        <h5 class="text-xs font-bold text-gray-900 truncate">{{ $prod?->name ?? 'Item #' . $item->id }}</h5>
                                        <div class="flex items-baseline gap-2 mt-1">
                                            <span class="text-xs font-black text-rose-600">Rp {{ number_format($item->flash_sale_price, 0, ',', '.') }}</span>
                                            @if($item->original_price > $item->flash_sale_price)
                                                <span class="text-[10px] line-through text-gray-400">Rp {{ number_format($item->original_price, 0, ',', '.') }}</span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-gray-500 mt-1">
                                            Kuota: <strong>{{ $item->sold_stock }} / {{ $item->quota_stock }}</strong> terjual
                                        </div>
                                    </div>
                                    <button wire:click="removeItem({{ $item->id }})" class="text-gray-300 hover:text-rose-500 p-1" title="Hapus dari Flash Sale">
                                        ✕
                                    </button>
                                </div>
                            @empty
                                <div class="col-span-full py-4 text-center text-xs text-gray-400">
                                    Belum ada produk di sesi ini. Klik "+ Tambah Produk" untuk memilih produk.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-3xl p-12 text-center text-gray-400 border border-gray-200">
                    Belum ada event flash sale yang dibuat. Klik tombol di atas untuk membuat sesi pertama Anda.
                </div>
            @endforelse
        </div>
    </div>

    {{-- MODAL SESI EVENT --}}
    @if($showEventModal)
        <div class="fixed inset-0 bg-neutral-900/60 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl shadow-xl w-full max-w-md p-6 space-y-4" @click.away="$wire.set('showEventModal', false)">
                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                    <h3 class="text-base font-extrabold text-gray-900">Buat Sesi Flash Sale</h3>
                    <button wire:click="$set('showEventModal', false)" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <form wire:submit.prevent="saveEvent" class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nama / Tema Flash Sale</label>
                        <input type="text" wire:model="title" placeholder="Contoh: Flash Sale Siang Super Hemat"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Waktu Mulai</label>
                            <input type="datetime-local" wire:model="start_time"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Waktu Berakhir</label>
                            <input type="datetime-local" wire:model="end_time"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Toko / Unit Bisnis</label>
                        <select wire:model="business_unit_id" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold">
                            <option value="">Semua Toko</option>
                            @foreach($businessUnits as $bu)
                                <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                        <button type="button" wire:click="$set('showEventModal', false)" class="px-4 py-2 text-xs font-bold text-gray-500 hover:bg-gray-100 rounded-xl">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-neutral-800 hover:bg-neutral-900 text-white rounded-xl text-xs font-bold shadow-xs">Simpan Sesi</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- MODAL TAMBAH PRODUK KE FLASH SALE --}}
    @if($showItemModal)
        <div class="fixed inset-0 bg-neutral-900/60 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl shadow-xl w-full max-w-lg p-6 space-y-4" @click.away="$wire.set('showItemModal', false)">
                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                    <h3 class="text-base font-extrabold text-gray-900">Tambah Produk ke Flash Sale</h3>
                    <button wire:click="$set('showItemModal', false)" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <form wire:submit.prevent="saveItem" class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Cari Produk (Ketik Nama / SKU)</label>
                        <input type="text" wire:model.live.debounce.300ms="productSearch" placeholder="Contoh: iPhone 13, Charger Anker..."
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white outline-none">

                        @if(!empty($productSearchResults))
                            <div class="mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto divide-y divide-gray-100">
                                @foreach($productSearchResults as $res)
                                    <div wire:click="selectProduct({{ $res->id }})" class="p-2.5 hover:bg-indigo-50 cursor-pointer flex items-center justify-between text-xs">
                                        <div>
                                            <div class="font-bold text-gray-900">{{ $res->name }}</div>
                                            <div class="text-[10px] text-gray-400 font-mono">{{ $res->item_no }}</div>
                                        </div>
                                        <div class="font-bold text-gray-700">Rp {{ number_format($res->base_price, 0, ',', '.') }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Harga Asli Normal (Rp)</label>
                            <input type="number" wire:model="originalPrice" readonly
                                class="w-full px-3 py-2 bg-gray-100 border border-gray-200 rounded-xl text-xs font-bold text-gray-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Harga Spesial Flash Sale (Rp)</label>
                            <input type="number" wire:model="flashSalePrice" placeholder="Harga diskon"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-rose-600 focus:bg-white outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Kuota Stok Khusus Promo (Unit)</label>
                        <input type="number" wire:model="quotaStock" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold">
                        <span class="text-[10px] text-gray-400">Jumlah maksimal unit yang boleh dibeli dengan harga flash sale ini.</span>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
                        <button type="button" wire:click="$set('showItemModal', false)" class="px-4 py-2 text-xs font-bold text-gray-500 hover:bg-gray-100 rounded-xl">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-neutral-800 hover:bg-neutral-900 text-white rounded-xl text-xs font-bold shadow-xs">Masukkan ke Promo</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
