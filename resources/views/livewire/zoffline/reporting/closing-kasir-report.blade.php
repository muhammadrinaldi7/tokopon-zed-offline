<div class="p-6 bg-[#f7f7f7] min-h-screen">
    <div class="flex flex-col items-start mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Laporan Closing Kasir</h1>
            <p class="text-sm text-gray-500 mt-1">Rekapitulasi aktivitas buka & tutup shift kasir</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3 w-full">
            <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center justify-between">
                <span class="text-xs text-gray-500 mr-2 font-medium">Separator:</span>
                <select wire:model="csvSeparator" class="text-sm border-none bg-transparent focus:ring-0 text-gray-700 p-0 font-medium cursor-pointer w-full text-right truncate">
                    <option value=";">Semicolon (;)</option>
                    <option value=",">Comma (,)</option>
                </select>
            </div>

            <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center">
                <select wire:model.live="businessUnitFilter" class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent p-0 cursor-pointer w-full truncate">
                    <option value="">Semua BU</option>
                    @foreach ($this->availableBusinessUnits as $bu)
                        <option value="{{ $bu }}">{{ $bu }}</option>
                    @endforeach
                </select>
            </div>

            <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center">
                <select wire:model.live="branchFilter" class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent p-0 cursor-pointer w-full truncate">
                    <option value="">Semua Cabang</option>
                    @foreach ($this->availableBranches as $branch)
                        <option value="{{ $branch }}">{{ $branch }}</option>
                    @endforeach
                </select>
            </div>

            <div class="bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm col-span-1 flex items-center">
                <select wire:model.live="dateRange" class="border-none text-sm font-bold text-blue-600 focus:ring-0 bg-transparent p-0 cursor-pointer w-full truncate">
                    <option value="today">Hari Ini</option>
                    <option value="yesterday">Kemarin</option>
                    <option value="this_week">Minggu Ini</option>
                    <option value="this_month">Bulan Ini</option>
                    <option value="last_month">Bulan Lalu</option>
                    <option value="this_year">Tahun Ini</option>
                    <option value="custom">Kustom</option>
                </select>
            </div>

            @if ($dateRange === 'custom')
                <div class="md:col-span-2 lg:col-span-2 flex items-center justify-between gap-2 bg-white px-3 py-2 rounded-xl border border-gray-200 shadow-sm">
                    <input type="date" wire:model.live="startDate" class="border-none bg-transparent p-0 text-sm focus:ring-0 text-gray-700 w-full text-center">
                    <span class="text-gray-400 text-sm font-bold">-</span>
                    <input type="date" wire:model.live="endDate" class="border-none bg-transparent p-0 text-sm focus:ring-0 text-gray-700 w-full text-center">
                </div>
            @endif

            <button wire:click="exportCsv" wire:loading.attr="disabled" class="flex items-center justify-center gap-2 bg-indigo-500 hover:bg-indigo-600 disabled:opacity-75 disabled:cursor-wait text-white text-sm font-bold py-2 px-4 rounded-xl shadow-sm transition-colors w-full h-full min-h-10.5">
                <svg wire:loading.remove wire:target="exportCsv" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <svg wire:loading wire:target="exportCsv" class="animate-spin w-4 h-4 text-white shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="exportCsv">Export CSV</span>
                <span wire:loading wire:target="exportCsv">Memproses...</span>
            </button>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-blue-50 rounded-full opacity-50"></div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Shift</p>
            <h3 class="text-xl font-black text-[#1c69d4]">{{ number_format($summary['total_shifts']) }} <span class="text-sm font-medium text-gray-400">Sesi</span></h3>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Modal Awal</p>
            <h3 class="text-xl font-bold text-gray-700">Rp {{ number_format($summary['modal_awal'], 0, ',', '.') }}</h3>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Penerimaan Tunai</p>
            <h3 class="text-xl font-bold text-gray-700">Rp {{ number_format($summary['expected_cash'], 0, ',', '.') }}</h3>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-emerald-50 rounded-full opacity-50"></div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Setoran Fisik</p>
            <h3 class="text-xl font-black text-emerald-600">Rp {{ number_format($summary['actual_cash'], 0, ',', '.') }}</h3>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Selisih Keseluruhan</p>
            <h3 class="text-xl font-bold {{ $summary['cash_difference'] < 0 ? 'text-red-500' : ($summary['cash_difference'] > 0 ? 'text-emerald-500' : 'text-gray-700') }}">
                Rp {{ number_format($summary['cash_difference'], 0, ',', '.') }}
            </h3>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col md:flex-row justify-between items-center gap-3">
            <h3 class="font-bold text-gray-700 text-sm">Detail Sesi Kasir</h3>
            <div class="relative w-full md:w-64">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama kasir..." class="w-full text-sm pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all shadow-sm">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white text-xs text-gray-400 uppercase tracking-wider border-b border-gray-100">
                        <th class="py-4 px-6 font-semibold">Tgl / Waktu Buka</th>
                        <th class="py-4 px-6 font-semibold">Tutup</th>
                        <th class="py-4 px-6 font-semibold">Kasir & Cabang</th>
                        <th class="py-4 px-6 text-right font-semibold">Modal Awal</th>
                        <th class="py-4 px-6 text-right font-semibold">Expected (Sistem)</th>
                        <th class="py-4 px-6 text-right font-semibold">Actual (Fisik)</th>
                        <th class="py-4 px-6 text-right font-semibold">Selisih</th>
                        <th class="py-4 px-6 text-center font-semibold">Status</th>
                        <th class="py-4 px-6 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-50">
                    @php
                        $canReopen = auth()->user() && (
                            auth()->user()->can('reopening-shiff-kasir') || 
                            auth()->user()->hasRole('reopening-shiff-kasir') || 
                            auth()->user()->hasRole('superadmin')
                        );
                    @endphp
                    @forelse ($shifts as $shift)
                        <tr class="hover:bg-gray-50/80 transition-colors group">
                            <td class="py-4 px-6">
                                <div class="font-semibold text-gray-700">{{ $shift->shift_date ? $shift->shift_date->format('d/m/Y') : '-' }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $shift->opened_at ? $shift->opened_at->format('H:i') : '-' }}</div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-medium text-gray-700">{{ $shift->closed_at ? $shift->closed_at->format('H:i') : '-' }}</div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-semibold text-gray-800">{{ $shift->user->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $shift->branch->name ?? '-' }}</div>
                            </td>
                            <td class="py-4 px-6 text-right font-medium text-gray-600">
                                Rp {{ number_format($shift->starting_cash, 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-6 text-right font-medium text-gray-600">
                                Rp {{ number_format($shift->expected_cash, 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-6 text-right font-semibold text-emerald-600">
                                Rp {{ number_format($shift->actual_cash, 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-6 text-right font-bold">
                                @if($shift->cash_difference < 0)
                                    <span class="text-red-500">Rp {{ number_format($shift->cash_difference, 0, ',', '.') }}</span>
                                @elseif($shift->cash_difference > 0)
                                    <span class="text-emerald-500">+Rp {{ number_format($shift->cash_difference, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-gray-400">Rp 0</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if(strtolower($shift->status) === 'open')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-600 border border-amber-200">
                                        OPEN
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">
                                        CLOSED
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if(strtolower($shift->status) === 'closed')
                                    @if($canReopen)
                                        <button wire:click="confirmReopenShift({{ $shift->id }})"
                                            type="button"
                                            title="Buka Kembali Shift Kasir Ini"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 shadow-2xs hover:shadow-xs transition-all duration-150 cursor-pointer">
                                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                            </svg>
                                            Buka Kembali
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-300 font-medium" title="Perlu role reopening-shiff-kasir">-</span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Aktif
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 px-6 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                </div>
                                <h3 class="text-base font-bold text-gray-800 mb-1">Data Tidak Ditemukan</h3>
                                <p class="text-gray-500 text-sm">Tidak ada data closing kasir pada rentang waktu atau kriteria yang dipilih.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($shifts->hasPages())
            <div class="p-4 border-t border-gray-100 bg-white">
                {{ $shifts->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Reopen Shift Kasir -->
    <div x-data="{ show: @entangle('showReopenModal') }" x-show="show" class="relative z-50" aria-labelledby="modal-reopen-title" role="dialog" aria-modal="true" style="display: none;">
        <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="show" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">
                    
                    <form wire:submit="executeReopenShift">
                        <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-4 text-white flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="bg-white/20 p-2 rounded-xl backdrop-blur-xs">
                                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-white" id="modal-reopen-title">Buka Kembali Shift Kasir</h3>
                                    <p class="text-xs text-amber-100">Otorisasi Admin (Role: reopening-shiff-kasir)</p>
                                </div>
                            </div>
                            <button type="button" wire:click="cancelReopen" class="text-white/80 hover:text-white transition-colors cursor-pointer">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 space-y-4">
                            @if ($reopenShiftData)
                                <div class="bg-amber-50/60 rounded-xl p-4 border border-amber-200/80 text-sm space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-500 font-medium">Kasir:</span>
                                        <span class="font-bold text-slate-800">{{ $reopenShiftData['cashier_name'] }} ({{ $reopenShiftData['branch_name'] }})</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-500 font-medium">Tanggal Shift:</span>
                                        <span class="font-bold text-slate-800">{{ $reopenShiftData['shift_date'] }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-500 font-medium">Waktu Shift:</span>
                                        <span class="font-bold text-slate-800">{{ $reopenShiftData['opened_at'] }} s/d {{ $reopenShiftData['closed_at'] }}</span>
                                    </div>
                                    <div class="flex justify-between items-center pt-2 border-t border-amber-200/50">
                                        <span class="text-slate-500 font-medium">Setoran Fisik Sebelumnya:</span>
                                        <span class="font-black text-amber-700">Rp {{ number_format($reopenShiftData['actual_cash'] ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @endif

                            <div class="bg-blue-50 border-l-4 border-blue-400 p-3 rounded-r-lg text-xs text-blue-800">
                                <p class="font-bold mb-1">Dampak Pembukaan Kembali Shift:</p>
                                <ul class="list-disc list-inside space-y-0.5 text-blue-700">
                                    <li>Status shift kasir akan dikembalikan menjadi <strong>OPEN</strong>.</li>
                                    <li>Kasir dapat langsung kembali melakukan transaksi di POS.</li>
                                    <li>Data rincian uang closing sebelumnya akan direset sehingga kasir dapat menghitung ulang saat shift benar-benar selesai.</li>
                                </ul>
                            </div>

                            <div>
                                <label for="reopenReason" class="block text-sm font-bold text-slate-700 mb-1.5">
                                    Alasan Pembukaan Kembali <span class="text-rose-500">*</span>
                                </label>
                                <textarea wire:model="reopenReason" id="reopenReason" rows="3"
                                    placeholder="Contoh: Kasir tidak sengaja menekan tombol closing shift saat jam kerja masih berlangsung..."
                                    class="block w-full rounded-xl border border-slate-300 p-3 text-slate-900 text-sm focus:border-amber-500 focus:ring-amber-500 placeholder:text-slate-400"></textarea>
                                @error('reopenReason')
                                    <p class="mt-1 text-xs text-rose-500 font-semibold">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="bg-slate-50 px-6 py-4 rounded-b-2xl flex items-center justify-end gap-3 border-t border-slate-100">
                            <button type="button" wire:click="cancelReopen"
                                class="px-4 py-2.5 rounded-xl border border-slate-300 text-sm font-bold text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-sm font-bold text-white shadow-md shadow-amber-200 transition-all cursor-pointer">
                                <span wire:loading.remove wire:target="executeReopenShift">Konfirmasi Buka Kembali</span>
                                <span wire:loading.inline-flex wire:target="executeReopenShift" class="items-center gap-1.5">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Memproses...
                                </span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
