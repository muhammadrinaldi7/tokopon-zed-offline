<div class="p-4 sm:p-6 min-h-screen bg-neutral-100">
    {{-- Header Modul E-Commerce --}}
    @include('livewire.zoffline.ecommerce.partials.header', ['title' => 'Manajemen Kupon & Voucher E-Commerce'])

    {{-- Content Area --}}
    <div class="space-y-6">
        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-200/80 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Voucher Aktif</span>
                    <h3 class="text-2xl font-black text-gray-900 mt-1">{{ number_format($stats['total_active']) }}</h3>
                    <p class="text-[11px] text-emerald-600 font-semibold mt-0.5">Dapat digunakan pelanggan mobile</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                    🎟️
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-200/80 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Klaim Checkout</span>
                    <h3 class="text-2xl font-black text-indigo-600 mt-1">{{ number_format($stats['total_redemptions']) }}</h3>
                    <p class="text-[11px] text-gray-400 mt-0.5">Transaksi berhasil menggunakan kupon</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                    🏷️
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-200/80 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Diskon Diberikan</span>
                    <h3 class="text-2xl font-black text-purple-600 mt-1">Rp {{ number_format($stats['total_savings'], 0, ',', '.') }}</h3>
                    <p class="text-[11px] text-purple-500 font-semibold mt-0.5">Nilai penghematan pelanggan</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                    💰
                </div>
            </div>
        </div>

        {{-- Filter & Action Bar --}}
        <div class="bg-white rounded-2xl p-4 shadow-xs border border-gray-200/80 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                {{-- Search --}}
                <div class="relative flex-1 md:w-64">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari kode atau judul..."
                        class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                {{-- Filter Tipe --}}
                <select wire:model.live="filterType" class="py-2 px-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Semua Tipe Diskon</option>
                    <option value="PRODUCT_DISCOUNT_FIXED">Diskon Produk (Nominal)</option>
                    <option value="PRODUCT_DISCOUNT_PERCENT">Diskon Produk (Persentase)</option>
                    <option value="SHIPPING_DISCOUNT_FIXED">Diskon Ongkir (Nominal)</option>
                    <option value="SHIPPING_DISCOUNT_PERCENT">Diskon Ongkir (Persentase)</option>
                </select>

                {{-- Filter Status --}}
                <select wire:model.live="filterStatus" class="py-2 px-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="all">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Non-Aktif</option>
                </select>
            </div>

            <button wire:click="openCreateModal"
                class="w-full md:w-auto px-4 py-2.5 bg-neutral-900 hover:bg-neutral-800 text-white rounded-xl text-xs font-bold shadow-xs flex items-center justify-center gap-2 transition-all">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Buat Voucher Baru</span>
            </button>
        </div>

        {{-- Coupons Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($coupons as $coupon)
                @php
                    $isExpired = $coupon->isExpired();
                    $isStarted = $coupon->isStarted();
                    $hasQuota = $coupon->hasQuota();
                @endphp
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-200/80 flex flex-col justify-between hover:shadow-md transition-shadow relative overflow-hidden">
                    {{-- Status Banner Tag --}}
                    @if(!$coupon->is_active)
                        <div class="absolute top-0 right-0 bg-gray-200 text-gray-700 text-[9px] font-black uppercase px-3 py-1 rounded-bl-xl">
                            Non-Aktif
                        </div>
                    @elseif($isExpired)
                        <div class="absolute top-0 right-0 bg-red-100 text-red-700 text-[9px] font-black uppercase px-3 py-1 rounded-bl-xl border-b border-l border-red-200">
                            Kedaluwarsa
                        </div>
                    @elseif(!$isStarted)
                        <div class="absolute top-0 right-0 bg-amber-100 text-amber-800 text-[9px] font-black uppercase px-3 py-1 rounded-bl-xl">
                            Akan Datang
                        </div>
                    @elseif(!$hasQuota)
                        <div class="absolute top-0 right-0 bg-orange-100 text-orange-800 text-[9px] font-black uppercase px-3 py-1 rounded-bl-xl">
                            Kuota Habis
                        </div>
                    @else
                        <div class="absolute top-0 right-0 bg-emerald-100 text-emerald-800 text-[9px] font-black uppercase px-3 py-1 rounded-bl-xl">
                            Aktif
                        </div>
                    @endif

                    <div>
                        {{-- Badge Tipe Kupon --}}
                        <div class="flex items-center gap-2 mb-2">
                            @if(str_contains($coupon->type, 'PERCENT'))
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-purple-50 text-purple-700 border border-purple-200">
                                    Diskon {{ (int)$coupon->discount_value }}%
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-blue-50 text-blue-700 border border-blue-200">
                                    Potongan Rp {{ number_format($coupon->discount_value, 0, ',', '.') }}
                                </span>
                            @endif

                            @if(str_contains($coupon->type, 'SHIPPING'))
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-amber-50 text-amber-800 border border-amber-200">
                                    Gratis Ongkir
                                </span>
                            @endif
                        </div>

                        {{-- Kode & Judul --}}
                        <div class="flex items-baseline gap-2">
                            <h4 class="text-lg font-black font-mono text-gray-900 tracking-wider bg-gray-50 px-2 py-0.5 rounded border border-dashed border-gray-300">
                                {{ $coupon->code }}
                            </h4>
                        </div>
                        <h5 class="text-sm font-bold text-gray-800 mt-1">{{ $coupon->title }}</h5>

                        @if($coupon->description)
                            <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $coupon->description }}</p>
                        @endif

                        {{-- Spesifikasi Aturan Kupon --}}
                        <div class="mt-4 pt-3 border-t border-gray-100 space-y-1.5 text-xs text-gray-600">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">Min. Belanja:</span>
                                <span class="font-bold text-gray-800">
                                    {{ $coupon->min_purchase_amount > 0 ? 'Rp ' . number_format($coupon->min_purchase_amount, 0, ',', '.') : 'Tanpa Minimum' }}
                                </span>
                            </div>

                            @if($coupon->max_discount_amount)
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-400">Maks. Potongan:</span>
                                    <span class="font-bold text-purple-700">Rp {{ number_format($coupon->max_discount_amount, 0, ',', '.') }}</span>
                                </div>
                            @endif

                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">Kuota Terpakai:</span>
                                <span class="font-bold {{ !$hasQuota ? 'text-red-600' : 'text-gray-800' }}">
                                    {{ $coupon->used_count }} / {{ $coupon->usage_limit_total ?? '∞' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">Batas per Akun:</span>
                                <span class="font-bold text-gray-800">{{ $coupon->usage_limit_per_user ? $coupon->usage_limit_per_user . 'x' : 'Tak Terbatas' }}</span>
                            </div>

                            <div class="flex items-center justify-between pt-1 text-[11px] text-gray-400">
                                <span>Berlaku:</span>
                                <span>{{ $coupon->end_date ? $coupon->end_date->format('d M Y') : 'Selamanya' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Action Footer --}}
                    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5">
                            <button wire:click="viewRedemptions({{ $coupon->id }})"
                                title="Lihat riwayat pemakaian"
                                class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-xs font-bold transition-all flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                <span>{{ $coupon->redemptions_count }} Klaim</span>
                            </button>

                            <button wire:click="toggleActive({{ $coupon->id }})"
                                class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all {{ $coupon->is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                {{ $coupon->is_active ? 'Aktif' : 'Off' }}
                            </button>
                        </div>

                        <div class="flex items-center gap-1">
                            <button wire:click="openEditModal({{ $coupon->id }})"
                                class="p-1.5 text-gray-400 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-all" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>

                            @if($coupon->used_count == 0)
                                <button wire:click="delete({{ $coupon->id }})" wire:confirm="Yakin ingin menghapus voucher ini?"
                                    class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-all" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-2xl p-12 text-center border border-dashed border-gray-300">
                    <span class="text-4xl">🎟️</span>
                    <h4 class="text-base font-extrabold text-gray-800 mt-3">Belum Ada Kupon Voucher</h4>
                    <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">Buat kupon pertama Anda untuk memberikan promosi potongan belanja atau gratis ongkir pada pengguna mobile app.</p>
                    <button wire:click="openCreateModal" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold shadow-xs hover:bg-indigo-700">
                        + Buat Voucher Pertama
                    </button>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div>
            {{ $coupons->links() }}
        </div>
    </div>

    {{-- MODAL CREATE / EDIT VOUCHER --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-gray-100 animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <div class="flex items-center gap-2.5">
                        <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">🎟️</span>
                        <div>
                            <h3 class="text-base font-black text-gray-900">{{ $couponId ? 'Edit Kupon Voucher' : 'Buat Voucher Kupon Baru' }}</h3>
                            <p class="text-xs text-gray-400">Atur parameter dan batasan penggunaan kupon diskon.</p>
                        </div>
                    </div>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form wire:submit.prevent="save" class="mt-5 space-y-4">
                    {{-- Kode & Status --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Kode Voucher <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="code" placeholder="Contoh: HEMAT50" uppercase
                                class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-mono font-bold uppercase focus:ring-2 focus:ring-indigo-500 focus:bg-white @error('code') border-red-500 @enderror">
                            @error('code') <span class="text-[11px] text-red-500 mt-0.5 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Status Voucher</label>
                            <label class="flex items-center gap-2 mt-2 cursor-pointer">
                                <input type="checkbox" wire:model="is_active" class="w-4 h-4 rounded text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                <span class="text-xs font-semibold text-gray-700">Aktifkan Voucher Ini</span>
                            </label>
                        </div>
                    </div>

                    {{-- Judul & Deskripsi --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Judul / Nama Promo <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="title" placeholder="Contoh: Diskon Gajian 50 Ribu"
                            class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:bg-white @error('title') border-red-500 @enderror">
                        @error('title') <span class="text-[11px] text-red-500 mt-0.5 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Deskripsi & Ketentuan (Opsional)</label>
                        <textarea wire:model="description" rows="2" placeholder="Jelaskan syarat dan ketentuan singkat untuk pelanggan..."
                            class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-indigo-500 focus:bg-white"></textarea>
                    </div>

                    {{-- Tipe & Nilai Diskon --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Tipe Diskon <span class="text-red-500">*</span></label>
                            <select wire:model.live="type" class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500">
                                <option value="PRODUCT_DISCOUNT_FIXED">Diskon Produk (Nominal Rp)</option>
                                <option value="PRODUCT_DISCOUNT_PERCENT">Diskon Produk (Persentase %)</option>
                                <option value="SHIPPING_DISCOUNT_FIXED">Potongan Ongkir (Nominal Rp)</option>
                                <option value="SHIPPING_DISCOUNT_PERCENT">Potongan Ongkir (Persentase %)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Nilai Diskon {{ str_contains($type, 'PERCENT') ? '(%)' : '(Rp)' }} <span class="text-red-500">*</span>
                            </label>
                            <input type="number" wire:model="discount_value" placeholder="{{ str_contains($type, 'PERCENT') ? 'Contoh: 10' : 'Contoh: 25000' }}"
                                class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-indigo-500 focus:bg-white @error('discount_value') border-red-500 @enderror">
                            @error('discount_value') <span class="text-[11px] text-red-500 mt-0.5 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Batas Maksimal (Jika Persen) & Minimum Belanja --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Maks. Potongan Rp (Cap)
                                <span class="text-gray-400 font-normal">({{ str_contains($type, 'PERCENT') ? 'Disarankan' : 'Opsional' }})</span>
                            </label>
                            <input type="number" wire:model="max_discount_amount" placeholder="Kosongkan jika tanpa batas"
                                class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:bg-white">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Min. Belanja (Rp)</label>
                            <input type="number" wire:model="min_purchase_amount" placeholder="0 jika tanpa batas"
                                class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:bg-white">
                        </div>
                    </div>

                    {{-- Kuota Global & Limit Per User --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Total Kuota Global</label>
                            <input type="number" wire:model="usage_limit_total" placeholder="Kosongkan jika tak terbatas"
                                class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:bg-white">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Batas Pemakaian Per Akun</label>
                            <input type="number" wire:model="usage_limit_per_user" placeholder="Default: 1x pemakaian"
                                class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:bg-white">
                        </div>
                    </div>

                    {{-- Periode Aktif --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Mulai</label>
                            <input type="datetime-local" wire:model="start_date"
                                class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:bg-white">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Berakhir</label>
                            <input type="datetime-local" wire:model="end_date"
                                class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:bg-white @error('end_date') border-red-500 @enderror">
                            @error('end_date') <span class="text-[11px] text-red-500 mt-0.5 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Footer Buttons --}}
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                        <button type="button" wire:click="$set('showModal', false)"
                            class="px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-50 transition-all">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all flex items-center gap-2">
                            <span>Simpan Voucher</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- MODAL RIWAYAT REDEMPTIONS / PEMAKAIAN KUPON --}}
    @if($showRedemptionsModal && $selectedCouponForRedemptions)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 animate-in fade-in zoom-in-95 duration-200">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-mono font-black text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded">
                                {{ $selectedCouponForRedemptions->code }}
                            </span>
                            <h3 class="text-base font-extrabold text-gray-900">Riwayat Penggunaan Voucher</h3>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">Daftar pelanggan dan pesanan yang berhasil menggunakan voucher ini.</p>
                    </div>
                    <button wire:click="closeRedemptionsModal" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="mt-4 max-h-96 overflow-y-auto">
                    @if($selectedCouponForRedemptions->redemptions->isEmpty())
                        <div class="text-center py-8 text-gray-400 text-xs">
                            Belum ada riwayat transaksi pemakaian untuk voucher ini.
                        </div>
                    @else
                        <table class="w-full text-xs text-left">
                            <thead class="bg-gray-50 text-gray-500 font-bold uppercase text-[10px] tracking-wider">
                                <tr>
                                    <th class="py-2.5 px-3 rounded-l-lg">Pelanggan</th>
                                    <th class="py-2.5 px-3">No. Pesanan</th>
                                    <th class="py-2.5 px-3">Potongan</th>
                                    <th class="py-2.5 px-3 rounded-r-lg">Waktu Digunakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($selectedCouponForRedemptions->redemptions as $redemption)
                                    <tr>
                                        <td class="py-3 px-3">
                                            <div class="font-bold text-gray-900">{{ $redemption->user?->name ?? 'Guest/Pelanggan' }}</div>
                                            <div class="text-[11px] text-gray-400">{{ $redemption->user?->email }}</div>
                                        </td>
                                        <td class="py-3 px-3 font-mono font-bold text-gray-800">
                                            {{ $redemption->order?->order_number ?? '-' }}
                                        </td>
                                        <td class="py-3 px-3 font-bold text-emerald-600">
                                            Rp {{ number_format($redemption->discount_applied, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-3 text-gray-400">
                                            {{ $redemption->redeemed_at ? $redemption->redeemed_at->format('d M Y H:i') : '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="mt-5 pt-3 border-t border-gray-100 flex justify-end">
                    <button wire:click="closeRedemptionsModal" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-all">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
