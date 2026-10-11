<div class="p-4 sm:p-6 min-h-screen bg-neutral-100 space-y-6">
    {{-- Header Modul E-Commerce --}}
    @include('livewire.zoffline.ecommerce.partials.header', ['title' => 'Manajemen Rekening Bank & Pembayaran Mobile'])

    {{-- Hero Summary Card --}}
    <div class="bg-gradient-to-r from-neutral-900 via-slate-900 to-indigo-950 rounded-3xl p-6 sm:p-7 text-white shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="relative z-10 max-w-xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-bold uppercase tracking-wider mb-2.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>{{ $totalMobileActive }} Rekening Aktif di Mobile App</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white">
                Rekening Tujuan Transfer Pelanggan
            </h2>
            <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                Kelola rekening bank yang muncul di aplikasi smartphone pembeli saat melakukan checkout belanja online. Cukup aktifkan saklar toggle untuk memunculkan atau menyembunyikan rekening.
            </p>
        </div>

        <div class="relative z-10 flex items-center gap-3 shrink-0">
            <button wire:click="openCreateModal"
                class="px-5 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-2xl text-xs font-bold shadow-lg shadow-indigo-600/30 flex items-center gap-2 transition duration-150">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"></path></svg>
                <span>Tambah Rekening Bank</span>
            </button>
        </div>
    </div>

    {{-- Content Table & Filters --}}
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        {{-- Toolbar Filter --}}
        <div class="p-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            {{-- Tabs Filter --}}
            <div class="flex items-center gap-1.5 bg-gray-100/80 p-1 rounded-xl text-xs font-bold self-start">
                <button wire:click="$set('filterTab', 'all')"
                    class="px-3 py-1.5 rounded-lg transition-all {{ $filterTab === 'all' ? 'bg-white text-indigo-600 shadow-xs' : 'text-gray-500 hover:text-gray-800' }}">
                    Semua ({{ count($methods) }})
                </button>
                <button wire:click="$set('filterTab', 'mobile_only')"
                    class="px-3 py-1.5 rounded-lg transition-all {{ $filterTab === 'mobile_only' ? 'bg-white text-emerald-600 shadow-xs' : 'text-gray-500 hover:text-gray-800' }}">
                    📱 Tampil di Mobile
                </button>
                <button wire:click="$set('filterTab', 'non_mobile')"
                    class="px-3 py-1.5 rounded-lg transition-all {{ $filterTab === 'non_mobile' ? 'bg-white text-gray-800 shadow-xs' : 'text-gray-500 hover:text-gray-800' }}">
                    Non-Mobile
                </button>
            </div>

            {{-- Search & Business Unit Filter --}}
            <div class="flex flex-col sm:flex-row items-center gap-3">
                <div class="relative w-full sm:w-64">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama bank, no rek, pemilik..."
                        class="w-full text-xs rounded-xl border-gray-200 pl-9 pr-4 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <select wire:model.live="selectedBusinessUnitId"
                    class="w-full sm:w-auto text-xs font-semibold rounded-xl border-gray-200 py-2.5 pl-3 pr-8 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                    <option value="">Semua Cabang / Store</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Table Rekening --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/70 border-b border-gray-100 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                        <th class="px-5 py-3.5">Nama Bank & Akun</th>
                        <th class="px-5 py-3.5">Nomor Rekening</th>
                        <th class="px-5 py-3.5">Atas Nama</th>
                        <th class="px-5 py-3.5">Cabang / Unit Bisnis</th>
                        <th class="px-5 py-3.5 text-center">Tampil di Mobile</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse($methods as $method)
                        <tr class="hover:bg-gray-50/70 transition-colors {{ $method->is_visible_mobile ? 'bg-indigo-50/15' : '' }}">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-700 font-black text-xs flex items-center justify-center border border-indigo-100 shadow-xs">
                                        {{ substr(strtoupper($method->bank_name ?: $method->name), 0, 4) }}
                                    </div>
                                    <div>
                                        <div class="font-extrabold text-gray-900 text-sm">{{ $method->name }}</div>
                                        <div class="text-[11px] text-gray-400 font-medium">Bank: {{ $method->bank_name ?: '-' }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4 font-mono font-bold text-gray-800 text-sm">
                                {{ $method->account_number ?: '-' }}
                            </td>

                            <td class="px-5 py-4 font-semibold text-gray-700">
                                {{ $method->account_owner ?: '-' }}
                            </td>

                            <td class="px-5 py-4">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200">
                                    {{ $method->businessUnit?->name ?: 'Semua Toko' }}
                                </span>
                            </td>

                            {{-- Toggle Tampil di Mobile --}}
                            <td class="px-5 py-4 text-center">
                                <button type="button" wire:click="toggleMobileVisible({{ $method->id }})"
                                    title="Klik untuk ubah visibilitas di aplikasi mobile"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $method->is_visible_mobile ? 'bg-emerald-500' : 'bg-gray-300' }}">
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out {{ $method->is_visible_mobile ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                </button>
                                <div class="text-[10px] font-bold mt-1 {{ $method->is_visible_mobile ? 'text-emerald-600' : 'text-gray-400' }}">
                                    {{ $method->is_visible_mobile ? 'Aktif Mobile' : 'Mati' }}
                                </div>
                            </td>

                            {{-- Status Aktif Master --}}
                            <td class="px-5 py-4 text-center">
                                <button type="button" wire:click="toggleActive({{ $method->id }})"
                                    class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $method->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-600 border border-rose-200' }}">
                                    {{ $method->is_active ? 'Aktif' : 'Non-Aktif' }}
                                </button>
                            </td>

                            {{-- Aksi --}}
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="openEditModal({{ $method->id }})"
                                        class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition duration-150"
                                        title="Edit Rekening">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>

                                    <button wire:click="delete({{ $method->id }})"
                                        wire:confirm="Yakin ingin menghapus rekening ini?"
                                        class="p-2 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition duration-150"
                                        title="Hapus Rekening">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                                <div class="text-3xl mb-2">🏦</div>
                                <div class="font-bold text-gray-600">Belum ada rekening pembayaran yang sesuai filter</div>
                                <div class="text-xs text-gray-400 mt-1">Silakan tambahkan rekening bank baru dengan tombol di atas.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Tambah / Edit Rekening --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-gray-100 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-5">
                    <div>
                        <h3 class="text-base font-black text-gray-900">
                            {{ $isEdit ? 'Edit Rekening Pembayaran' : 'Tambah Rekening Pembayaran Baru' }}
                        </h3>
                        <p class="text-xs text-gray-400">Rekening ini digunakan untuk tujuan transfer saat pembeli checkout.</p>
                    </div>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form wire:submit="save" class="space-y-4 text-xs font-semibold">
                    {{-- Nama Rekening / Label Tampilan --}}
                    <div>
                        <label class="block text-gray-700 font-bold mb-1">Nama Tampilan di Mobile <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="name" placeholder="Contoh: BCA Transfer Utama"
                            class="w-full text-xs rounded-xl border-gray-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                        @error('name') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Pilihan Bank --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-gray-700 font-bold mb-1">Nama Bank <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="bank_name" placeholder="BCA / Mandiri / BRI / QRIS"
                                class="w-full text-xs rounded-xl border-gray-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                            @error('bank_name') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-gray-700 font-bold mb-1">Unit Bisnis / Store <span class="text-rose-500">*</span></label>
                            <select wire:model="business_unit_id"
                                class="w-full text-xs rounded-xl border-gray-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                                @foreach($businessUnits as $bu)
                                    <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                                @endforeach
                            </select>
                            @error('business_unit_id') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Nomor Rekening & Atas Nama --}}
                    <div>
                        <label class="block text-gray-700 font-bold mb-1">Nomor Rekening <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="account_number" placeholder="Contoh: 883901928471920"
                            class="w-full text-xs font-mono font-bold rounded-xl border-gray-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                        @error('account_number') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-gray-700 font-bold mb-1">Atas Nama Pemilik Rekening <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="account_owner" placeholder="Contoh: PT TOKOPON GADGET INDONESIA"
                            class="w-full text-xs rounded-xl border-gray-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                        @error('account_owner') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Kode Akun Accurate --}}
                    <div>
                        <label class="block text-gray-700 font-bold mb-1">Kode Akun Kas/Bank Accurate <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="accurate_bank_no" placeholder="Contoh: 11.10.001"
                            class="w-full text-xs font-mono rounded-xl border-gray-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition">
                        <span class="text-[11px] text-gray-400 mt-1 block">Dibutuhkan untuk pemetaan sinkronisasi transaksi ke sistem Accurate.</span>
                        @error('accurate_bank_no') <span class="text-rose-500 text-[11px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Checkboxes Status --}}
                    <div class="pt-3 border-t border-gray-100 space-y-2">
                        <label class="flex items-center gap-2.5 p-3 rounded-2xl bg-emerald-50/70 border border-emerald-200/60 cursor-pointer">
                            <input type="checkbox" wire:model="is_visible_mobile" class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                            <div>
                                <div class="font-bold text-emerald-900 text-xs">Tampilkan di Aplikasi Mobile</div>
                                <div class="text-[11px] text-emerald-700">Rekening ini langsung bisa dipilih oleh pembeli saat checkout.</div>
                            </div>
                        </label>

                        <label class="flex items-center gap-2.5 p-3 rounded-2xl bg-gray-50 border border-gray-200 cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                            <div>
                                <div class="font-bold text-gray-800 text-xs">Status Rekening Aktif</div>
                                <div class="text-[11px] text-gray-500">Rekening aktif dan dapat menerima pembayaran.</div>
                            </div>
                        </label>
                    </div>

                    {{-- Tombol Aksi --}}
                    <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-2.5">
                        <button type="button" wire:click="$set('showModal', false)"
                            class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 text-xs font-bold transition">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md shadow-indigo-600/20 transition flex items-center gap-2">
                            <span wire:loading.remove wire:target="save">Simpan Rekening</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
