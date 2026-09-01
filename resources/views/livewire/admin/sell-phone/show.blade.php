<div class="relative min-h-screen bg-gradient-to-br p-4 sm:p-8">
    {{-- Decorative Background Elements --}}
    <div
        class="absolute top-0 left-0 w-full h-96 bg-gradient-to-br from-blue-600/5 to-purple-600/5 blur-3xl pointer-events-none -z-10">
    </div>

    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="{{ route('admin.sell-phones.index') }}" wire:navigate
                class="text-sm font-bold text-slate-400 hover:text-blue-600 mb-2 inline-flex items-center gap-1 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
            <h1
                class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-indigo-700 tracking-tight mt-1">
                Detail Pembelian #SPL-{{ $sellPhone->id }}</h1>
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

                $statusLabels = [
                    'PAYING' => 'MENUNGGU DIBAYAR',
                ];
            @endphp
            <span
                class="px-4 py-2 font-bold uppercase rounded-lg text-sm tracking-wider {{ $statusColors[$sellPhone->status] ?? 'bg-gray-100 text-gray-800' }}">
                Status: {{ $statusLabels[$sellPhone->status] ?? str_replace('_', ' ', $sellPhone->status) }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Kolom Kiri: Detail Pengajuan --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Info Perangkat --}}
            <div class="bg-white/70 backdrop-blur-xl rounded-2xl shadow-sm border border-white/50 p-6">
                <h3 class="font-bold text-lg text-gray-900 border-b border-gray-100 pb-3 mb-4">Informasi Perangkat</h3>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Merek & Model</p>
                        <p class="font-medium text-gray-900">{{ $sellPhone->phone_brand }} {{ $sellPhone->phone_model }}
                        </p>
                    </div>
                    {{-- <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Kapasitas</p>
                        <p class="font-medium text-gray-900">{{ $sellPhone->phone_ram ?? '-' }} RAM /
                            {{ $sellPhone->phone_storage ?? '-' }} Storage</p>
                    </div> --}}
                    <div class="col-span-2 mt-2 flex flex-wrap gap-6">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Nomor IMEI</p>
                            <p
                                class="font-mono text-gray-900 bg-gray-100 px-3 py-1.5 rounded-lg inline-block font-bold">
                                {{ $sellPhone->imei ?? 'Belum ada IMEI' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Cabang</p>
                            <p
                                class="font-mono text-gray-900 bg-gray-100 px-3 py-1.5 rounded-lg inline-block font-bold">
                                {{ $sellPhone->branch->name ?? 'N/A' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Ditangani Oleh
                                (Frontliner)</p>
                            <p
                                class="font-medium text-gray-900 bg-blue-50 text-blue-700 border border-blue-100 px-3 py-1.5 rounded-lg inline-flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ optional($sellPhone->handledBy)->name ?? 'Sistem / Pelanggan' }}
                            </p>
                        </div>
                    </div>
                    <div class="col-span-2 mt-2" x-data="{ showQcModal: false }">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Deskripsi Kondisi
                            (Catatan Pelanggan)</p>
                        <div class="p-2 bg-gray-50 rounded-lg text-sm text-gray-700 font-medium">
                            {{ $sellPhone->minus_desc ?: 'Tidak ada catatan.' }}
                        </div>

                        @if ($sellPhone->status !== 'PENDING' && $sellPhone->status !== 'WAITING_FOR_DEVICE')
                            <button @click="showQcModal = true"
                                class="mt-3 px-4 py-2 bg-indigo-50 text-indigo-600 border border-indigo-100 rounded-lg text-sm font-bold hover:bg-indigo-100 transition">
                                Lihat Detail Inspeksi QC
                            </button>

                            {{-- Modal QC --}}
                            <template x-teleport="body">
                                <div x-show="showQcModal"
                                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
                                    style="display: none;">
                                    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] flex flex-col"
                                        @click.away="showQcModal = false">
                                        <div
                                            class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                                            <h3 class="font-bold text-lg text-gray-900">Hasil Inspeksi QC Fisik</h3>
                                            <button @click="showQcModal = false"
                                                class="text-gray-400 hover:text-gray-600">
                                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="p-6 overflow-y-auto space-y-4">
                                            @php
                                                $qcRecords = $sellPhone->inspections()->latest()->get();
                                            @endphp
                                            @forelse($qcRecords as $record)
                                                <div
                                                    class="p-4 border rounded-lg {{ $record->verdict === 'pass' ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}">
                                                    <div class="flex justify-between items-center mb-2">
                                                        <span
                                                            class="font-bold {{ $record->verdict === 'pass' ? 'text-emerald-700' : 'text-rose-700' }}">
                                                            {{ strtoupper($record->verdict) }}
                                                        </span>
                                                        <span
                                                            class="text-xs text-gray-500">{{ $record->created_at->format('d M Y H:i') }}</span>
                                                    </div>

                                                    @if ($record->checklist_results)
                                                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                                            @foreach ($record->checklist_results as $item)
                                                                <div
                                                                    class="flex justify-between items-center p-2 rounded {{ ($item['type'] ?? '') === 'boolean' ? ($item['value'] ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-100') : 'bg-gray-50 text-gray-700 border border-gray-200' }}">
                                                                    <span
                                                                        class="font-medium truncate pr-2">{{ $item['name'] ?? '-' }}</span>
                                                                    <span class="font-bold flex-shrink-0">
                                                                        @if (($item['type'] ?? '') === 'boolean')
                                                                            @if ($item['value'])
                                                                                <svg class="w-4 h-4 text-emerald-500"
                                                                                    fill="none" viewBox="0 0 24 24"
                                                                                    stroke="currentColor">
                                                                                    <path stroke-linecap="round"
                                                                                        stroke-linejoin="round"
                                                                                        stroke-width="2"
                                                                                        d="M5 13l4 4L19 7" />
                                                                                </svg>
                                                                            @else
                                                                                <svg class="w-4 h-4 text-rose-500"
                                                                                    fill="none" viewBox="0 0 24 24"
                                                                                    stroke="currentColor">
                                                                                    <path stroke-linecap="round"
                                                                                        stroke-linejoin="round"
                                                                                        stroke-width="2"
                                                                                        d="M6 18L18 6M6 6l12 12" />
                                                                                </svg>
                                                                            @endif
                                                                        @else
                                                                            {{ $item['value'] ?? '-' }}
                                                                        @endif
                                                                    </span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    <div
                                                        class="mt-4 pt-3 border-t {{ $record->verdict === 'pass' ? 'border-emerald-200' : 'border-rose-200' }}">
                                                        <p class="text-sm font-medium text-gray-800"><span
                                                                class="text-xs text-gray-500 block mb-1">Catatan
                                                                Inspektur:</span>{{ $record->inspector_notes ?: 'Tidak ada catatan khusus.' }}
                                                        </p>
                                                        <p class="text-xs text-gray-500 mt-2">Diperiksa oleh: <span
                                                                class="font-bold text-gray-700">{{ $record->inspector->name ?? 'Sistem' }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-gray-500 text-sm text-center italic">Belum ada data
                                                    inspeksi.</p>
                                            @endforelse
                                        </div>
                                        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end">
                                            <button @click="showQcModal = false"
                                                class="px-4 py-2 bg-white border border-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-50">Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        @endif
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
            <div class="bg-white/70 backdrop-blur-xl rounded-2xl shadow-sm border border-white/50 p-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                    <h3 class="font-bold text-lg text-gray-900">Informasi Pelanggan & Pembayaran</h3>
                    @canany(['correct-sell-phone-bank', 'manage-trade-in'])
                        <button type="button" wire:click="openEditBank"
                            class="text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg border border-blue-200/60 transition flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            <span>Koreksi Rekening</span>
                        </button>
                    @endcanany
                </div>

                @php
                    $userBank = $sellPhone->user?->bankAccounts?->first();
                    $displayBank = $sellPhone->bank_name ?: $userBank?->bank_name ?? null;
                    $displayAccNo = $sellPhone->bank_account_number ?: $userBank?->account_number ?? null;
                    $displayAccName = $sellPhone->bank_account_name ?: $userBank?->account_name ?? null;
                @endphp

                @if ($isEditingBank)
                    {{-- Form Koreksi Rekening --}}
                    <div
                        class="p-4 bg-blue-50/70 rounded-xl border border-blue-200 mb-4 animate-in fade-in duration-200">
                        <h4
                            class="text-xs font-bold uppercase tracking-wider text-blue-900 mb-3 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Koreksi Data Rekening Tujuan Transfer
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">Nama Bank / e-Wallet
                                    <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="editBankName"
                                    placeholder="Contoh: BCA / BRI / Mandiri"
                                    class="w-full p-2.5 bg-white border border-gray-300 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                                @error('editBankName')
                                    <span
                                        class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">Nomor Rekening <span
                                        class="text-rose-500">*</span></label>
                                <input type="text" wire:model="editBankAccountNumber"
                                    placeholder="Nomor rekening valid"
                                    class="w-full p-2.5 bg-white border border-gray-300 rounded-lg text-xs font-mono font-bold focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                                @error('editBankAccountNumber')
                                    <span
                                        class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">Atas Nama Rekening <span
                                        class="text-rose-500">*</span></label>
                                <input type="text" wire:model="editBankAccountName"
                                    placeholder="Nama pemilik sesuai buku tabungan"
                                    class="w-full p-2.5 bg-white border border-gray-300 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none">
                                @error('editBankAccountName')
                                    <span
                                        class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-2">
                            <button type="button" wire:click="$set('isEditingBank', false)"
                                class="px-3.5 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-50 transition">
                                Batal
                            </button>
                            <button type="button" wire:click="saveBankInfo"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                                Simpan Perubahan
                            </button>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Pelanggan</p>
                        <p class="font-medium text-gray-900">{{ $sellPhone->user->name ?? 'Customer' }}</p>
                        <p class="text-sm text-gray-500">{{ $sellPhone->user->email ?? '-' }}</p>
                        <p class="text-xs text-gray-500 font-mono mt-0.5">
                            {{ $sellPhone->user->profile->phone_number ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Tujuan Transfer</p>
                        @if ($displayBank && $displayAccNo)
                            <p class="font-bold text-emerald-600 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                                {{ $displayBank }}
                            </p>
                            <p class="font-mono font-bold text-gray-900">{{ $displayAccNo }}</p>
                            <p class="text-sm text-gray-500">A.N: <span
                                    class="font-medium text-gray-800">{{ $displayAccName ?: '-' }}</span></p>
                        @else
                            <p class="text-sm text-gray-500 italic">Belum diisi pelanggan / rekening belum ada.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Section: Catatan & Kendala Transaksi --}}
            <div class="bg-white/70 backdrop-blur-xl rounded-2xl shadow-sm border border-white/50 p-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-lg text-gray-900">Kendala & Catatan Transaksi</h3>
                        @if ($sellPhone->openIssues->count() > 0)
                            <span
                                class="px-2 py-0.5 text-[10px] font-black uppercase rounded-full bg-rose-100 text-rose-700 border border-rose-200 animate-pulse">
                                {{ $sellPhone->openIssues->count() }} Perlu Diselesaikan
                            </span>
                        @endif
                    </div>

                    @canany(['manage-sell-phone-issues', 'manage-trade-in'])
                        <button type="button" wire:click="$toggle('showIssueForm')"
                            class="text-xs font-bold {{ $showIssueForm ? 'bg-gray-100 text-gray-700' : 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100' }} px-3 py-1.5 rounded-lg transition flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                @if ($showIssueForm)
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                @endif
                            </svg>
                            <span>{{ $showIssueForm ? 'Tutup Form' : 'Catat Kendala' }}</span>
                        </button>
                    @endcanany
                </div>

                {{-- Form Tambah Kendala --}}
                @if ($showIssueForm)
                    <form wire:submit="addIssue"
                        class="p-4 bg-amber-50/60 rounded-xl border border-amber-200 mb-5 animate-in fade-in duration-200 space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-amber-900">Tambah Catatan Kendala
                            Baru</h4>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-1">
                                <label class="block text-[11px] font-bold text-amber-900 mb-1">Kategori Kendala <span
                                        class="text-rose-500">*</span></label>
                                <select wire:model="issueCategory"
                                    class="w-full p-2.5 bg-white border border-amber-200 rounded-lg text-xs font-bold focus:ring-2 focus:ring-amber-500 focus:border-transparent outline-none">
                                    <option value="SALAH_NOREK">Salah No. Rekening / Bank</option>
                                    <option value="SALAH_NOMINAL">Salah Nominal / Taksiran</option>
                                    <option value="SALAH_QC">Salah Hasil QC / Grade</option>
                                    <option value="SALAH_IMEI">Salah Input IMEI / SN</option>
                                    <option value="GAGAL_TRANSFER">Gagal Transfer / Rekening Pasif</option>
                                    <option value="LAINNYA">Kendala Lainnya</option>
                                </select>
                                @error('issueCategory')
                                    <span
                                        class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-bold text-amber-900 mb-1">Keterangan / Rincian
                                    Kendala <span class="text-rose-500">*</span></label>
                                <textarea wire:model="issueComment" rows="2"
                                    placeholder="Jelaskan detail kendala atau kesalahan yang terjadi..."
                                    class="w-full p-2.5 bg-white border border-amber-200 rounded-lg text-xs font-medium focus:ring-2 focus:ring-amber-500 focus:border-transparent outline-none"></textarea>
                                @error('issueComment')
                                    <span
                                        class="text-[10px] text-rose-500 font-bold block mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-1">
                            <button type="button" wire:click="$set('showIssueForm', false)"
                                class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 rounded-lg text-xs font-bold hover:bg-gray-50 transition">
                                Batal
                            </button>
                            <button type="submit"
                                class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                                Simpan Kendala
                            </button>
                        </div>
                    </form>
                @endif

                {{-- List Kendala --}}
                @if ($sellPhone->issues->isEmpty())
                    <div class="p-6 text-center border-2 border-dashed border-gray-100 rounded-xl bg-gray-50/50">
                        <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-xs text-gray-500 font-medium">Belum ada catatan kendala pada transaksi ini.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @php
                            $categoryLabels = [
                                'SALAH_NOREK' => [
                                    'label' => 'Salah No. Rekening / Bank',
                                    'class' => 'bg-rose-50 text-rose-700 border-rose-200',
                                ],
                                'SALAH_NOMINAL' => [
                                    'label' => 'Salah Nominal / Taksiran',
                                    'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                                ],
                                'SALAH_QC' => [
                                    'label' => 'Salah QC / Grade',
                                    'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                ],
                                'SALAH_IMEI' => [
                                    'label' => 'Salah IMEI / SN',
                                    'class' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                ],
                                'GAGAL_TRANSFER' => [
                                    'label' => 'Gagal Transfer',
                                    'class' => 'bg-red-50 text-red-700 border-red-200',
                                ],
                                'LAINNYA' => [
                                    'label' => 'Lainnya',
                                    'class' => 'bg-gray-100 text-gray-700 border-gray-200',
                                ],
                            ];
                        @endphp

                        @foreach ($sellPhone->issues as $issue)
                            @php
                                $isResolved = $issue->status === 'RESOLVED';
                                $catInfo = $categoryLabels[$issue->category] ?? [
                                    'label' => $issue->category,
                                    'class' => 'bg-gray-100 text-gray-700 border-gray-200',
                                ];
                            @endphp
                            <div
                                class="p-4 rounded-xl border {{ $isResolved ? 'bg-gray-50/70 border-gray-200 opacity-90' : 'bg-amber-50/30 border-amber-100' }} transition-all">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span
                                            class="text-[10px] font-bold px-2.5 py-0.5 rounded-md border {{ $catInfo['class'] }}">
                                            {{ $catInfo['label'] }}
                                        </span>
                                        <span class="text-xs font-semibold text-gray-700">
                                            {{ $issue->user->name ?? 'User' }}
                                        </span>
                                        <span class="text-[11px] text-gray-400">
                                            • {{ $issue->created_at->diffForHumans() }}
                                            ({{ $issue->created_at->format('d/m/Y H:i') }})
                                        </span>
                                    </div>

                                    @canany(['manage-sell-phone-issues', 'manage-trade-in'])
                                        <div class="flex items-center gap-2">
                                            @if ($isResolved)
                                                <span
                                                    class="text-[10px] font-extrabold px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    SELESAI
                                                </span>
                                                <button type="button"
                                                    wire:click="toggleIssueStatus({{ $issue->id }})"
                                                    class="text-[11px] font-bold text-gray-500 hover:text-gray-800 underline transition"
                                                    title="Buka kembali kendala ini">
                                                    Buka Kembali
                                                </button>
                                            @else
                                                <button type="button"
                                                    wire:click="toggleIssueStatus({{ $issue->id }})"
                                                    class="px-2.5 py-1 text-[11px] font-bold rounded-md bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor" stroke-width="2.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    Tandai Selesai
                                                </button>
                                            @endif

                                            <button type="button" wire:click="deleteIssue({{ $issue->id }})"
                                                wire:confirm="Hapus catatan kendala ini?"
                                                class="p-1 text-gray-400 hover:text-rose-600 transition"
                                                title="Hapus kendala">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endcanany
                                </div>
                                <p
                                    class="text-xs text-gray-700 whitespace-pre-line leading-relaxed font-medium bg-white/80 p-2.5 rounded-lg border border-gray-100">
                                    {{ $issue->comment }}
                                </p>

                                {{-- Detail Penyelesaian (Jika Resolved) --}}
                                @if ($isResolved && ($issue->resolution_notes || $issue->resolvedBy))
                                    <div
                                        class="mt-2 text-[11px] text-emerald-800 bg-emerald-50/80 p-2.5 rounded-lg border border-emerald-200 flex items-start gap-2">
                                        <svg class="w-4 h-4 text-emerald-600 mt-0.5 shrink-0" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div class="flex-1">
                                            <span class="font-bold text-emerald-900">Solusi / Penyelesaian:</span>
                                            <span
                                                class="block mt-0.5 text-emerald-700 font-medium">{{ $issue->resolution_notes ?: 'Ditandai selesai.' }}</span>
                                            @if ($issue->resolvedBy)
                                                <span class="text-emerald-600 block text-[10px] mt-1 font-semibold">
                                                    Diselesaikan oleh: {{ $issue->resolvedBy->name }}
                                                    {{ $issue->resolved_at ? '(' . $issue->resolved_at->format('d/m/Y H:i') . ')' : '' }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Kolom Kanan: Aksi --}}
        <div class="space-y-6">
            {{-- Form Penaksiran Harga / Harga Akhir --}}
            <div class="bg-white/70 backdrop-blur-xl rounded-2xl shadow-sm border border-white/50 p-6">
                <h3 class="font-bold text-lg text-gray-900 border-b border-gray-100 pb-3 mb-4">Harga Akhir
                </h3>

                @if ($sellPhone->appraised_value)
                    <div class="mb-4 p-4 bg-emerald-50 border border-emerald-100 rounded-lg text-center"
                        x-data="{ editingPrice: false }">
                        <p class="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-1">Nilai Disepakati
                        </p>

                        <div x-show="!editingPrice">
                            <p class="text-2xl font-black text-emerald-700">Rp
                                {{ number_format($sellPhone->appraised_value, 0, ',', '.') }}</p>

                            @if ($sellPhone->status === 'PAYING')
                                {{-- <button type="button" @click="editingPrice = true"
                                    class="mt-2 text-xs font-bold text-emerald-600 underline hover:text-emerald-800 focus:outline-none">Ubah
                                    Harga</button> --}}
                            @else
                                <p class="text-xs text-emerald-600 mt-2">Dihitung otomatis dari Base Price & Rules.</p>
                            @endif
                        </div>

                        <div x-show="editingPrice" style="display: none;"
                            class="mt-3 text-left bg-white p-4 rounded-xl border border-emerald-200 shadow-sm">
                            <label class="block text-xs font-bold text-emerald-900 mb-2">Nominal Harga Baru
                                (Rp)</label>
                            <input type="number" wire:model="appraisedValue"
                                class="w-full p-3 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-white font-mono mb-3 outline-none transition-all">
                            <div class="flex gap-2">
                                <button type="button" wire:click="updatePrice" @click="editingPrice = false"
                                    class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-2.5 rounded-lg text-sm font-bold shadow-sm transition-colors">Simpan</button>
                                <button type="button"
                                    @click="editingPrice = false; $wire.set('appraisedValue', {{ $sellPhone->appraised_value }})"
                                    class="flex-1 bg-white border border-gray-300 text-gray-700 py-2.5 rounded-lg text-sm font-bold hover:bg-gray-50 transition-colors">Batal</button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Aksi Lainnya --}}
            @if (in_array($sellPhone->status, ['PENDING', 'OFFERED', 'PAYING', 'WAITING_FOR_DEVICE', 'INSPECTING']))
                <div class="bg-white/70 backdrop-blur-xl rounded-2xl shadow-sm border border-white/50 p-6">
                    <h3 class="font-bold text-lg text-gray-900 border-b border-gray-100 pb-3 mb-4">Aksi Transaksi</h3>

                    @if ($sellPhone->status === 'INSPECTING')
                        @if (!$qcPassed)
                            <div
                                class="p-4 bg-indigo-50 text-indigo-700 rounded-xl border border-indigo-100 mb-4 animate-in fade-in duration-300">
                                <p class="text-sm font-bold flex items-center gap-2">
                                    <svg class="w-5 h-5 animate-pulse" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Menunggu Inspeksi QC
                                </p>
                                <p class="text-xs mt-1.5 opacity-90">Silakan selesaikan form inspeksi fisik perangkat
                                    di
                                    bagian bawah halaman ini terlebih dahulu.</p>
                            </div>
                        @elseif (!$isRevising)
                            <div class="space-y-3">
                                <p class="text-sm text-gray-600 mb-4">Inspeksi QC telah selesai. Jika kondisi unit
                                    sesuai dan nilai penawaran tidak berubah, lanjutkan ke pembayaran.</p>

                                <button type="button" wire:click="markAsPaid"
                                    wire:confirm="Sesuai! Anda akan mentransfer uang ke pelanggan dan menandai lunas?"
                                    class="w-full bg-emerald-500 text-white py-2.5 rounded-lg font-bold hover:bg-emerald-600 transition flex items-center justify-center gap-2">

                                    Fisik Sesuai
                                </button>

                                <button type="button" wire:click="$set('isRevising', true)"
                                    class="w-full bg-amber-500 text-white py-2.5 rounded-lg font-bold hover:bg-amber-600 transition flex items-center justify-center gap-2">

                                    Fisik Tidak Sesuai
                                </button>

                                <button type="button" wire:click="$set('isRejecting', true)"
                                    class="w-full bg-white border-2 border-rose-100 text-rose-600 py-2.5 rounded-lg font-bold hover:bg-rose-50 transition mt-2">
                                    Tolak
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
                    @elseif($sellPhone->status === 'PAYING')
                        <div class="space-y-4">
                            <div class="p-4 bg-blue-50 border border-blue-100 rounded-xl space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-blue-900 mb-1">Rekening Bank Toko (Asal
                                        Transfer) <span class="text-rose-500">*</span></label>
                                    <p class="text-[10px] text-blue-700 leading-tight mb-2">Pilih kas/bank Accurate
                                        untuk melakukan pembayaran / pelunasan pembelian ini.</p>
                                    <div x-data="{
                                        open: false,
                                        search: '',
                                        value: @entangle('storeBankNo').live,
                                        options: [
                                            @foreach ($accurateGlAccounts as $gl)
                                                { id: '{{ $gl['account_no'] }}', label: '{{ $gl['account_no'] }} - {{ addslashes($gl['name']) }}' }, @endforeach
                                        ],
                                        get filteredOptions() {
                                            if (this.search === '') return this.options;
                                            return this.options.filter(i => i.label.toLowerCase().includes(this.search.toLowerCase()));
                                        },
                                        get selectedLabel() {
                                            const selected = this.options.find(i => i.id == this.value);
                                            return selected ? selected.label : '-- Pilih Akun GL Accurate --';
                                        }
                                    }" class="relative w-full" @click.away="open = false">
                                        <!-- Trigger -->
                                        <button type="button"
                                            @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())"
                                            class="flex items-center justify-between w-full p-2.5 text-sm font-mono text-left bg-white border rounded-lg focus:outline-none focus:ring-2 focus:ring-[#1c69d4] transition-all"
                                            :class="open ? 'border-[#1c69d4] ring-2 ring-blue-100' :
                                                'border-blue-200 hover:border-blue-300'">
                                            <span x-text="selectedLabel"
                                                :class="!value ? 'text-gray-400' : 'text-gray-900 font-semibold'"
                                                class="truncate pr-4"></span>
                                            <svg class="w-4 h-4 text-gray-400 transition-transform shrink-0"
                                                :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>

                                        <!-- Dropdown -->
                                        <div x-show="open" x-transition.opacity
                                            class="absolute z-50 w-full mt-1.5 bg-white border border-blue-100 rounded-lg shadow-xl"
                                            style="display: none;">
                                            <div class="p-2 border-b border-gray-100 bg-gray-50/50 rounded-t-lg">
                                                <div class="relative">
                                                    <svg class="absolute left-2.5 top-2.5 w-4 h-4 text-gray-400"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                    </svg>
                                                    <input type="text" x-model="search"
                                                        placeholder="Cari nama atau nomor akun..."
                                                        class="w-full pl-9 pr-3 py-2 text-sm bg-white border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-[#1c69d4] focus:border-[#1c69d4] transition-colors"
                                                        @keydown.escape="open = false" x-ref="searchInput">
                                                </div>
                                            </div>
                                            <ul class="max-h-56 overflow-y-auto py-1">
                                                <li @click="value = ''; open = false; search = ''"
                                                    class="px-4 py-2.5 text-sm text-gray-500 cursor-pointer hover:bg-gray-50 transition-colors">
                                                    -- Pilih Akun GL Accurate --
                                                </li>
                                                <template x-for="option in filteredOptions" :key="option.id">
                                                    <li @click="value = option.id; open = false; search = ''"
                                                        class="px-4 py-2.5 text-sm font-mono cursor-pointer hover:bg-blue-50 transition-colors border-l-2 border-transparent"
                                                        :class="value == option.id ?
                                                            'bg-blue-50 text-[#1c69d4] font-bold border-[#1c69d4]' :
                                                            'text-gray-700 hover:border-blue-300'">
                                                        <span x-text="option.label"></span>
                                                    </li>
                                                </template>
                                                <li x-show="filteredOptions.length === 0"
                                                    class="px-4 py-3 text-sm text-gray-400 text-center italic bg-gray-50/50">
                                                    Akun tidak ditemukan
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    @error('storeBankNo')
                                        <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div x-data="{ previewUrl: null }">
                                    <label class="block text-sm font-bold text-blue-900 mb-1">Upload Bukti Transfer
                                        <span class="text-rose-500">*</span></label>
                                    <input type="file" wire:model="paymentReceipt" accept="image/*"
                                        @change="
                                            const file = $event.target.files[0];
                                            if (file) {
                                                previewUrl = URL.createObjectURL(file);
                                            } else {
                                                previewUrl = null;
                                            }
                                        "
                                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                                    <div wire:loading wire:target="paymentReceipt" class="text-xs text-blue-600 mt-1">
                                        Mengunggah...</div>
                                    @error('paymentReceipt')
                                        <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span>
                                    @enderror

                                    <div x-show="previewUrl" x-cloak style="display: none;"
                                        class="mt-2 rounded-lg overflow-hidden border border-blue-200 aspect-video relative">
                                        <img :src="previewUrl" class="w-full h-full object-cover">
                                    </div>
                                </div>
                            </div>

                            <button type="button" wire:click="markAsPaid" wire:loading.attr="disabled"
                                @if (!$paymentReceipt || !$storeBankNo) disabled @endif
                                wire:confirm="Anda akan menandai ini Lunas dan akan dibuatkan Purchase Payment otomatis ke Accurate. Lanjutkan?"
                                class="w-full bg-emerald-500 text-white py-3 rounded-lg font-bold hover:bg-emerald-600 transition flex items-center justify-center gap-2 disabled:bg-gray-300 disabled:cursor-not-allowed">
                                <svg wire:loading wire:target="markAsPaid"
                                    class="animate-spin -ml-1 mr-2 h-5 w-5 text-white"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Tandai Lunas (Hit Accurate)
                            </button>

                            <button type="button" wire:click="$set('isRejecting', true)"
                                class="w-full bg-white border-2 border-rose-100 text-rose-600 py-2.5 rounded-lg font-bold hover:bg-rose-50 transition">
                                Batalkan Transaksi
                            </button>
                        </div>
                    @else
                        <div class="space-y-3">
                            <button type="button" wire:click="markAsPaid"
                                class="w-full bg-emerald-500 text-white py-2.5 rounded-lg font-bold hover:bg-emerald-600 transition flex items-center justify-center gap-2">
                                Proses Selanjutnya
                            </button>

                            <button type="button" wire:click="$set('isRejecting', true)"
                                class="w-full bg-white border-2 border-rose-100 text-rose-600 py-2.5 rounded-lg font-bold hover:bg-rose-50 transition">
                                Tolak / Batalkan
                            </button>
                        </div>

                    @endif
                </div>
            @endif

            {{-- Info Inventaris --}}
            @if ($sellPhone->status === 'COMPLETED')
                <div
                    class="bg-emerald-50 rounded-lg border border-emerald-100 p-6 text-center animate-in zoom-in duration-300">
                    <div
                        class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-emerald-900">Purchase Invoice & Payment Berhasil!</h3>
                    <p class="text-sm text-emerald-700 mt-1">Stok perangkat ini telah berhasil dicatat di server
                        Accurate, dan Pelunasan Pembelian sudah dikirim. Unit akan muncul di POS setelah Sinkronisasi
                        Master Data berjalan.</p>

                    @if ($sellPhone->payment_receipt_path)
                        <div class="mt-4 border-t border-emerald-200 pt-4">
                            <p class="text-xs font-bold text-emerald-800 uppercase tracking-widest mb-2">Bukti Transfer
                                Pelunasan</p>
                            <a href="{{ asset('storage/' . $sellPhone->payment_receipt_path) }}" target="_blank"
                                class="inline-block rounded-lg overflow-hidden border border-emerald-200 aspect-video w-48 mx-auto hover:opacity-80 transition cursor-zoom-in mb-3">
                                <img src="{{ asset('storage/' . $sellPhone->payment_receipt_path) }}"
                                    class="w-full h-full object-cover">
                            </a>
                        </div>
                    @else
                        <div class="mt-4 border-t border-emerald-200 pt-4">
                            <p class="text-xs font-bold text-rose-600 uppercase tracking-widest mb-2">Bukti Transfer
                                Belum Ada</p>
                        </div>
                    @endif

                    @if (!$isReuploading)
                        <button type="button" wire:click="$set('isReuploading', true)"
                            class="text-xs font-bold text-emerald-700 underline hover:text-emerald-800">
                            {{ $sellPhone->payment_receipt_path ? 'Upload Ulang Bukti Transfer' : 'Upload Bukti Transfer Sekarang' }}
                        </button>
                    @else
                        <div class="mt-4 text-left bg-white p-4 rounded-xl border border-emerald-200">
                            <label class="block text-sm font-bold text-emerald-900 mb-1">Pilih Bukti Transfer
                                Baru</label>
                            <input type="file" wire:model="paymentReceipt" accept="image/*"
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-100 file:text-emerald-700 hover:file:bg-emerald-200 mb-2">
                            <div wire:loading wire:target="paymentReceipt" class="text-xs text-emerald-600 mt-1 mb-2">
                                Mengunggah file...</div>
                            @error('paymentReceipt')
                                <span class="text-xs text-rose-500 mt-1 mb-2 block">{{ $message }}</span>
                            @enderror

                            <div class="flex gap-2 mt-2">
                                <button type="button" wire:click="$set('isReuploading', false)"
                                    class="flex-1 px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm font-bold hover:bg-gray-200 transition">Batal</button>
                                <button type="button" wire:click="reuploadReceipt"
                                    class="flex-1 px-4 py-2 bg-emerald-500 text-white rounded-lg text-sm font-bold hover:bg-emerald-600 transition">Simpan
                                    File</button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- QC Inspection Form (Full Width at Bottom) --}}
    @if ($sellPhone->status === 'INSPECTING' && !$qcPassed)
        <div class="mt-8">
            <livewire:admin.qc.inspection-form :inspectable-type="get_class($sellPhone)" :inspectable-id="$sellPhone->id"
                label="QC Inbound - Beli HP Bekas" />
        </div>
    @endif

    {{-- Rejection Modal --}}
    @if ($isRejecting)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 animate-in zoom-in duration-200">
                <h3 class="font-bold text-xl text-rose-600 mb-2">Batalkan Transaksi / Tolak</h3>
                <p class="text-sm text-gray-600 mb-4">Silakan masukkan alasan penolakan. Alasan ini akan tercatat dalam
                    sistem dan dapat dilihat oleh pelanggan.</p>

                <form wire:submit="reject">
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Alasan Penolakan</label>
                        <textarea wire:model="rejectReason" rows="3"
                            class="w-full border-gray-200 rounded-lg focus:ring-rose-500 focus:border-rose-500 bg-gray-50"
                            placeholder="Misal: Layar pecah parah, icloud terkunci, dll..."></textarea>
                        @error('rejectReason')
                            <span class="text-xs text-rose-500 mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="$set('isRejecting', false)"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-bold hover:bg-gray-200 transition">Kembali</button>
                        <button type="submit"
                            class="px-4 py-2 bg-rose-600 text-white rounded-lg font-bold hover:bg-rose-700 transition flex items-center gap-2">
                            <svg wire:loading wire:target="reject" class="animate-spin h-4 w-4"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Konfirmasi Tolak
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showQcWarningModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 animate-in zoom-in duration-200">
                <div class="flex items-center gap-3 mb-4">
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center shrink-0 {{ $latestQcVerdict === 'fail' ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600' }}">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-xl text-gray-900">Peringatan: QC Tidak Pass!</h3>
                        <p class="text-sm text-gray-500 font-medium uppercase mt-0.5">
                            Status Terakhir: <span
                                class="font-black {{ $latestQcVerdict === 'fail' ? 'text-rose-600' : 'text-amber-600' }}">{{ $latestQcVerdict }}</span>
                        </p>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
                    <p class="text-sm text-gray-700 leading-relaxed">
                        Perangkat ini <b>tidak sepenuhnya lolos inspeksi fisik</b>. <br><br>
                        Memproses perangkat ini berarti Anda <b>mengabaikan hasil QC</b> dan tetap akan membelinya.
                        Pastikan Anda telah mendapatkan persetujuan manajer cabang sebelum melanjutkan.
                    </p>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('showQcWarningModal', false)"
                        class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-50 transition">
                        Batal & Kembali
                    </button>
                    <button type="button" wire:click="$set('forceProcessQc', true); markAsPaid()"
                        class="px-5 py-2.5 {{ $latestQcVerdict === 'fail' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-amber-500 hover:bg-amber-600' }} text-white rounded-lg font-bold transition flex items-center gap-2 shadow-sm">
                        Ya, Tetap Lanjutkan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
