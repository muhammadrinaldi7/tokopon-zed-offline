<div>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-black text-gray-800">Master Metode Pembayaran</h2>
            <p class="text-gray-500 text-sm">Kelola daftar rekening bank toko dan integrasi kode akun Accurate.</p>
        </div>
        <button wire:click="create"
            class="bg-[#1c69d4] text-white px-5 py-2.5 rounded-lg font-bold hover:bg-blue-700 transition flex items-center gap-2 shadow-sm">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Rekening
        </button>
    </div>

    <!-- Tabs Navigasi Unit Usaha -->
    <div class="mb-4 flex space-x-2 border-b border-gray-200">
        <button wire:click="$set('activeTab', 'all')"
            class="px-4 py-2 font-bold text-sm transition-colors {{ $activeTab === 'all' ? 'text-[#1c69d4] border-b-2 border-[#1c69d4]' : 'text-gray-500 hover:text-gray-700' }}">
            Semua Unit
        </button>
        @foreach($businessUnits as $unit)
            <button wire:click="$set('activeTab', {{ $unit->id }})"
                class="px-4 py-2 font-bold text-sm transition-colors {{ $activeTab == $unit->id ? 'text-[#1c69d4] border-b-2 border-[#1c69d4]' : 'text-gray-500 hover:text-gray-700' }}">
                {{ $unit->name }}
            </button>
        @endforeach
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr
                    class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-bold">
                    <th class="p-4">Nama Metode</th>
                    <th class="p-4">Detail Rekening</th>
                    {{-- <th class="p-4">MDR (%)</th> --}}
                    <th class="p-4">Accurate Bank No</th>
                    <th class="p-4 text-center">Unit Usaha</th>
                    <th class="p-4 text-center">Status</th>
                    <th class="p-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($paymentMethods as $method)
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="p-4">
                            <p class="font-bold text-gray-900">{{ $method->name }}</p>
                        </td>
                        <td class="p-4">
                            @if ($method->bank_name || $method->account_number)
                                <p class="text-sm font-bold text-gray-800">{{ $method->bank_name }}</p>
                                <p class="text-sm text-gray-500 font-mono mt-0.5">{{ $method->account_number }}</p>
                                <p class="text-xs text-gray-400 mt-0.5 uppercase tracking-wide">a.n
                                    {{ $method->account_owner }}</p>
                            @else
                                <span class="text-sm text-gray-400 italic">Kas Tunai / Bebas</span>
                            @endif
                        </td>
                        {{-- <td class="p-4">
                            <span class="font-bold text-gray-900">{{ $method->mdr_percentage }}%</span>
                        </td> --}}
                        <td class="p-4">
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                {{ $method->accurate_bank_no }}
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <span class="px-2 py-1 text-[10px] font-bold bg-blue-50 text-blue-600 rounded">
                                {{ $method->businessUnit ? $method->businessUnit->name : 'N/A' }}
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            <button wire:click="toggleActive({{ $method->id }})"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $method->is_active ? 'bg-emerald-500' : 'bg-gray-200' }}">
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $method->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </td>
                        <td class="p-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="manageRates({{ $method->id }})"
                                    class="px-3 py-1.5 bg-blue-50 text-[#1c69d4] hover:bg-blue-100 transition rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-sm">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                    Tarif MDR ({{ $method->rates->count() }})
                                </button>
                                <button wire:click="edit({{ $method->id }})"
                                    class="p-2 text-gray-400 hover:text-[#1c69d4] hover:bg-blue-50 rounded-lg transition">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button wire:click="delete({{ $method->id }})"
                                    wire:confirm="Yakin ingin menghapus rekening ini?"
                                    class="p-2 text-gray-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-500">
                            Belum ada metode pembayaran. Silakan tambahkan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-lg text-gray-900">{{ $isEdit ? 'Edit Rekening' : 'Tambah Rekening' }}
                    </h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nama Tampilan <span
                                    class="text-rose-500">*</span></label>
                            <input type="text" wire:model="name"
                                class="w-full p-2 border-gray-200 rounded-lg focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm"
                                placeholder="Contoh: BCA Manual, Kasir Tunai, dll">
                            @error('name')
                                <span class="text-xs text-rose-500 mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Unit Usaha <span
                                    class="text-rose-500">*</span></label>
                            <select wire:model.live="business_unit_id"
                                class="w-full p-2 border-gray-200 rounded-lg focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm">
                                <option value="">-- Pilih Unit Usaha --</option>
                                @foreach($businessUnits as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                            @error('business_unit_id')
                                <span class="text-xs text-rose-500 mt-1">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nama Bank (Opsional)</label>
                            <input type="text" wire:model="bank_name"
                                class="w-full p-2 border-gray-200 rounded-lg focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm"
                                placeholder="Contoh: BCA">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Atas Nama (Opsional)</label>
                            <input type="text" wire:model="account_owner"
                                class="w-full p-2 border-gray-200 rounded-lg focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm"
                                placeholder="Contoh: PT TokoPun">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nomor Rekening (Opsional)</label>
                        <input type="text" wire:model="account_number"
                            class="w-full p-2 border-gray-200 rounded-lg focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm font-mono"
                            placeholder="1234567890">
                    </div>

                    {{-- <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Beban MDR (%) <span
                                class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" wire:model="mdr_percentage"
                            class="w-full p-2 border-gray-200 rounded-lg focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm"
                            placeholder="Contoh: 0.7 atau 1.5">
                        @error('mdr_percentage')
                            <span class="text-xs text-rose-500 mt-1">{{ $message }}</span>
                        @enderror
                    </div> --}}

                    <div class="p-4 bg-amber-50 rounded-lg border border-amber-100">
                        <label class="block text-sm font-bold text-amber-900 mb-1">Accurate Bank No <span
                                class="text-rose-500">*</span></label>
                        <p class="text-xs text-amber-700 mb-3">Pilih Akun Bank (CASH_BANK) dari buku besar Accurate.
                        </p>
                        <select wire:model="accurate_bank_no"
                            class="w-full p-2 border-amber-200 rounded-lg focus:ring-amber-500 focus:border-amber-500 text-sm font-mono bg-white">
                            <option value="">-- Pilih Akun GL Accurate --</option>
                            @php
                                $selectedUnit = collect($businessUnits)->firstWhere('id', $business_unit_id);
                                $unitCode = $selectedUnit ? $selectedUnit->code : null;
                                $filteredGlAccounts = collect($accurateGlAccounts)->where('database_source', $unitCode);
                            @endphp
                            @foreach ($filteredGlAccounts as $gl)
                                <option value="{{ $gl->account_no }}">{{ $gl->account_no }} - {{ $gl->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('accurate_bank_no')
                            <span class="text-xs text-rose-500 mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-center gap-2 mt-2">
                        <input type="checkbox" wire:model="is_active" id="isActive"
                            class="rounded  text-[#1c69d4] focus:ring-[#1c69d4] border-gray-300">
                        <label for="isActive" class="text-sm text-gray-700">Aktifkan metode ini</label>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                    <button wire:click="$set('showModal', false)"
                        class="px-5 py-2 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">Batal</button>
                    <button wire:click="save"
                        class="px-5 py-2 text-sm font-bold text-white bg-[#1c69d4] rounded-lg hover:bg-blue-700 transition">Simpan
                        Rekening</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: Kelola Tarif MDR List
    ═══════════════════════════════════════════════════════════ --}}
    @if ($showRatesModal && $selectedPaymentMethodForRates)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[85vh]">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="font-bold text-lg text-gray-900">Kelola Tarif MDR -
                            {{ $selectedPaymentMethodForRates->name }}</h3>
                        <p class="text-xs text-gray-400">Atur tarif MDR khusus kartu debit/kredit/cicilan untuk EDC
                            ini.</p>
                    </div>
                    <button wire:click="$set('showRatesModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 space-y-4">
                    <div class="flex justify-between items-center">
                        <p class="text-sm font-bold text-gray-800">Daftar Tarif Aktif</p>
                        <button wire:click="createRate"
                            class="px-3 py-1.5 bg-[#1c69d4] hover:bg-blue-700 text-white rounded-lg text-xs font-bold flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Tambah Tarif MDR
                        </button>
                    </div>

                    <div class="border rounded-xl overflow-hidden bg-white">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr
                                    class="bg-gray-50 border-b text-xs uppercase tracking-wider text-gray-500 font-bold">
                                    <th class="p-3">Nama Opsi / Tenor</th>
                                    <th class="p-3 text-center">Beban MDR</th>
                                    <th class="p-3 text-center">Accurate Account No</th>
                                    <th class="p-3 text-center">Status</th>
                                    <th class="p-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y text-sm">
                                @forelse($rates as $rate)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="p-3 font-bold text-gray-800">{{ $rate->name }}</td>
                                        <td class="p-3 text-center font-mono font-bold text-blue-600">
                                            {{ $rate->mdr_percentage }}%</td>
                                        <td class="p-3 text-center font-mono text-xs text-gray-500">
                                            {{ $rate->accurate_account_no ?? '-' }}</td>
                                        <td class="p-3 text-center">
                                            <button wire:click="toggleRateActive({{ $rate->id }})"
                                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors {{ $rate->is_active ? 'bg-emerald-500' : 'bg-gray-200' }}">
                                                <span
                                                    class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform {{ $rate->is_active ? 'translate-x-5' : 'translate-x-1' }}"></span>
                                            </button>
                                        </td>
                                        <td class="p-3 text-right">
                                            <div class="flex items-center justify-end gap-1.5">
                                                <button wire:click="editRate({{ $rate->id }})"
                                                    class="p-1.5 text-gray-400 hover:text-[#1c69d4] hover:bg-blue-50 rounded-md transition">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <button wire:click="deleteRate({{ $rate->id }})"
                                                    wire:confirm="Hapus tarif ini?"
                                                    class="p-1.5 text-gray-400 hover:text-rose-500 hover:bg-rose-50 rounded-md transition">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="p-6 text-center text-gray-400 italic">Belum ada
                                            tarif MDR terkonfigurasi.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end shrink-0">
                    <button wire:click="$set('showRatesModal', false)"
                        class="px-5 py-2 text-sm font-bold text-gray-600 bg-white border rounded-lg hover:bg-gray-50 transition">Tutup</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════
         MODAL: Tambah/Edit Tarif Form
    ═══════════════════════════════════════════════════════════ --}}
    @if ($showRateFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-900">{{ $isEditRate ? 'Edit Tarif MDR' : 'Tambah Tarif MDR' }}</h3>
                    <button wire:click="$set('showRateFormModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nama Tarif / Opsi / Tenor <span
                                class="text-rose-500">*</span></label>
                        <input type="text" wire:model="rateName"
                            class="w-full p-2 border border-gray-200 rounded-lg focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm"
                            placeholder="Contoh: Debit BCA (Sesama), Cicilan 3 Bulan, dsb.">
                        @error('rateName')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Beban MDR (%) <span
                                class="text-rose-500">*</span></label>
                        <input type="number" step="0.01" min="0" max="100"
                            wire:model="rateMdrPercentage"
                            class="w-full p-2 border border-gray-200 rounded-lg focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm font-mono"
                            placeholder="0.00">
                        @error('rateMdrPercentage')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="p-4 bg-amber-50 rounded-lg border border-amber-100">
                        <label class="block text-sm font-bold text-amber-900 mb-1">Accurate Account No
                            (Opsional)</label>
                        <p class="text-xs text-amber-700 mb-2">Kode akun perkiraan Buku Besar untuk pencatatan detail
                            potongan/beban MDR bank.</p>
                        <input type="text" wire:model.live="rateAccurateAccountNo"
                            class="w-full p-2 border border-gray-200 rounded-lg focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm font-mono"
                            placeholder="50.02.003">
                        @error('rateAccurateAccountNo')
                            <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-center gap-2 mt-2">
                        <input type="checkbox" wire:model="rateIsActive" id="rateIsActive"
                            class="rounded text-[#1c69d4] focus:ring-[#1c69d4] border-gray-300">
                        <label for="rateIsActive" class="text-sm text-gray-700">Aktifkan tarif ini</label>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                    <button wire:click="$set('showRateFormModal', false)"
                        class="px-5 py-2 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">Batal</button>
                    <button wire:click="saveRate"
                        class="px-5 py-2 text-sm font-bold text-white bg-[#1c69d4] rounded-lg hover:bg-blue-700 transition">Simpan
                        Tarif</button>
                </div>
            </div>
        </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════
         TABEL: Sinkronisasi Akun GL Accurate
    ═══════════════════════════════════════════════════════════ --}}
<div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
        <div>
            <h3 class="font-bold text-gray-900">Daftar Akun GL Accurate (CASH_BANK)</h3>
            <p class="text-xs text-gray-500">Tabel ini berisi cache daftar akun dari Accurate untuk mempermudah
                pemilihan saat membuat metode pembayaran.</p>
        </div>
        <button wire:click="syncGlAccounts"
            class="bg-emerald-500 text-white px-4 py-2 rounded-lg font-bold hover:bg-emerald-600 transition flex items-center gap-2 shadow-sm text-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Sync GL Accounts
        </button>
    </div>
    <div class="p-0">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-bold">
                    <th class="p-4">No Akun Accurate</th>
                    <th class="p-4">Nama Akun</th>
                    <th class="p-4 text-center">Tipe Akun</th>
                    <th class="p-4 text-center">Sumber DB</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm">
                @forelse($accurateGlAccounts as $gl)
                    <tr class="hover:bg-gray-50/50 transition">
                        <td class="p-4 font-mono font-bold text-blue-600">{{ $gl->account_no }}</td>
                        <td class="p-4 font-bold text-gray-800">{{ $gl->name }}</td>
                        <td class="p-4 text-center">
                            <span
                                class="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs font-bold">{{ $gl->account_type }}</span>
                        </td>
                        <td class="p-4 text-center text-gray-500">{{ $gl->database_source }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-8 text-center text-gray-500">
                            Belum ada data Akun GL yang ditarik dari Accurate. Silakan klik tombol Sync di atas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

</div>
