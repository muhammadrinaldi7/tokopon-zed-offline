<div class="min-h-screen w-full pb-12 pt-8 px-4 sm:px-6 lg:px-8 font-sans">

    <!-- Header Section -->
    <div class="max-w-7xl mx-auto mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
                Tutup Shift Kasir
            </h2>
            <p class="mt-2 text-base text-slate-500">
                Lakukan rekonsiliasi uang fisik dan tutup shift sebelum mengakhiri pekerjaan hari ini.
            </p>
        </div>
        <div>
            <a href="{{ route('zoffline.pos') }}"
                class="inline-flex items-center justify-center px-6 py-2.5 border border-slate-300 shadow-sm text-sm font-bold rounded-xl text-slate-700 bg-white hover:bg-slate-50 hover:shadow-md transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke POS
            </a>
        </div>
    </div>

    @if ($this->shiftSummary)

        <!-- Top Stats (Info Kasir) -->
        <div class="max-w-7xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 mb-8">
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Kasir</span>
                <span class="text-lg font-black text-slate-800 truncate">{{ auth()->user()->name }}</span>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Waktu Buka Shift</span>
                <div class="flex items-baseline gap-1">
                    <span
                        class="text-lg font-black text-slate-800">{{ $this->shiftSummary['shift']->opened_at->format('H:i') }}</span>
                    <span
                        class="text-sm font-semibold text-slate-500">({{ $this->shiftSummary['shift']->shift_date->format('d M') }})</span>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Struk</span>
                <div class="flex items-baseline gap-1">
                    <span
                        class="text-lg font-black text-slate-800">{{ $this->shiftSummary['total_transactions'] }}</span>
                    <span class="text-sm font-semibold text-slate-500">Transaksi</span>
                </div>
            </div>
            <div
                class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col relative overflow-hidden">
                <div class="absolute right-0 top-0 w-16 h-16 bg-indigo-50 rounded-bl-full -z-0"></div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1 relative z-10">Total
                    Penjualan</span>
                <span class="text-lg font-black text-indigo-700 relative z-10">
                    Rp {{ number_format($this->shiftSummary['total_sales'], 0, ',', '.') }}
                </span>
            </div>
        </div>

        <!-- Main Form Content (Dibuat Center dengan max-w-4xl) -->
        <div class="max-w-7xl mx-auto mt-10">
            <div class="bg-white p-6 sm:p-8 shadow-sm sm:rounded-3xl border border-slate-200">

                <div class="mb-6">
                    <h3 class="text-xl font-extrabold text-slate-900">Hitung Uang Fisik Laci</h3>
                    <p class="text-sm text-slate-500 mt-1">Masukkan rincian pecahan uang yang ada di laci kasir saat ini
                        secara akurat.</p>
                </div>

                <form wire:submit="closeShift" class="space-y-8">

                    <!-- List Rincian Uang Fisik -->
                    <div>
                        <div class="grid grid-cols-1 gap-3">
                            @foreach ($denominations as $denom => $qty)
                                @php
                                    $colorClass = match ($denom) {
                                        100000 => 'border-rose-500 hover:shadow-rose-100',
                                        50000 => 'border-blue-500 hover:shadow-blue-100',
                                        20000 => 'border-emerald-500 hover:shadow-emerald-100',
                                        10000 => 'border-purple-500 hover:shadow-purple-100',
                                        5000 => 'border-amber-500 hover:shadow-amber-100',
                                        2000 => 'border-slate-400 hover:shadow-slate-100',
                                        1000 => 'border-lime-500 hover:shadow-lime-100',
                                        default => 'border-gray-300 hover:shadow-gray-100',
                                    };
                                @endphp

                                <div
                                    class="group bg-white rounded-xl border border-slate-200 border-l-4 {{ $colorClass }} p-3 sm:p-4 shadow-sm hover:shadow-md transition-all duration-200 flex flex-row items-center justify-between focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-transparent gap-2 sm:gap-4">
                                    <div class="w-28 sm:w-36 flex-shrink-0">
                                        <span class="font-extrabold text-base sm:text-lg text-slate-800 tracking-tight">
                                            Rp {{ number_format($denom, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <div
                                        class="hidden sm:block flex-grow border-b-2 border-dashed border-slate-100 mx-2 group-hover:border-slate-200 transition-colors">
                                    </div>
                                    <div class="flex items-center justify-end flex-grow sm:flex-grow-0 gap-3 sm:gap-6">
                                        <div class="flex items-center gap-1.5 sm:gap-2">
                                            <span class="text-slate-400 font-bold text-xs sm:text-sm">x</span>
                                            <input wire:model.live.debounce.500ms="denominations.{{ $denom }}"
                                                type="number" min="0" placeholder="0"
                                                class="block w-16 sm:w-20 px-2 py-1.5 sm:py-2 text-center bg-slate-50 border-0 text-slate-900 rounded-lg ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-indigo-600 font-bold transition-all text-sm sm:text-base">
                                        </div>
                                        <div class="w-24 sm:w-32 text-right flex flex-col justify-center">
                                            <span
                                                class="text-[9px] sm:text-[10px] uppercase font-bold tracking-wider text-slate-400 mb-0.5">Subtotal</span>
                                            <div class="font-bold text-indigo-600 text-sm sm:text-base truncate">
                                                Rp {{ number_format($denom * max(0, (int) $qty), 0, ',', '.') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @error('denominations.*')
                            <div
                                class="mt-3 flex items-center gap-2 text-rose-600 bg-rose-50 p-3 rounded-lg border border-rose-100">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-sm font-semibold">Terdapat input jumlah yang tidak valid.</span>
                            </div>
                        @enderror
                    </div>

                    <!-- Rekap Akhir Form -->
                    <div class="space-y-4 pt-4 border-t border-slate-100">

                        <!-- Pindahan: Modal Awal Laci -->
                        <div
                            class="bg-indigo-50/50 rounded-2xl p-4 sm:p-5 border border-indigo-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="bg-indigo-100 p-2 rounded-lg">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-indigo-800">Modal Awal Laci</span>
                            </div>
                            <span class="text-lg font-black text-indigo-900">
                                Rp {{ number_format($this->shiftSummary['shift']->starting_cash, 0, ',', '.') }}
                            </span>
                        </div>

                        <!-- Total Uang Fisik Aktual -->
                        <div
                            class="bg-slate-900 rounded-2xl p-6 flex flex-col sm:flex-row justify-between items-center shadow-lg">
                            <span class="text-slate-400 text-sm font-bold uppercase tracking-widest mb-2 sm:mb-0">Total
                                Uang Fisik Aktual</span>
                            <span class="text-3xl sm:text-4xl font-black text-white tracking-tight">
                                Rp {{ number_format($this->totalCash, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <label for="closingNotes" class="block text-sm font-bold text-slate-700 mb-2">
                            Catatan Closing <span class="text-slate-400 font-normal">(Opsional / Wajib jika ada
                                selisih)</span>
                        </label>
                        <textarea wire:model="closingNotes" id="closingNotes" rows="2"
                            class="block w-full rounded-xl border-0 py-3 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm transition-all mb-6"
                            placeholder="Tulis alasan jika ada selisih uang (contoh: uang kembalian jatuh, dll)..."></textarea>

                        <button type="submit"
                            class="w-full flex justify-center items-center gap-2 py-4 px-4 border border-transparent rounded-xl shadow-lg shadow-rose-200 text-base font-bold text-white bg-rose-600 hover:bg-rose-700 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-600 transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Konfirmasi & Tutup Shift
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="max-w-7xl mx-auto">
            <div class="bg-amber-50 border-l-4 border-amber-400 p-5 rounded-r-xl shadow-sm">
                <div class="flex items-center">
                    <svg class="h-6 w-6 text-amber-500 mr-3" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    <p class="text-sm font-medium text-amber-800">
                        Anda tidak memiliki shift yang sedang aktif.
                        <a href="{{ route('zoffline.pos.open-shift') }}"
                            class="font-extrabold underline hover:text-amber-900 transition-colors">Buka shift baru di
                            sini</a>.
                    </p>
                </div>
            </div>
        </div>
    @endif
    <!-- Simple Status Modal -->
    <div x-data="{ show: @entangle('showStatusModal') }" x-show="show" class="relative z-[100]" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="display: none;">
        <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-sm border border-slate-200">
                    <div class="px-4 pb-4 pt-5 sm:p-6 sm:pb-4 text-center">
                        @if($statusType === 'success')
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 mb-4">
                                <svg class="h-10 w-10 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-extrabold leading-6 text-slate-900 mb-2">Sukses</h3>
                        @else
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 mb-4">
                                <svg class="h-10 w-10 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-extrabold leading-6 text-slate-900 mb-2">Perhatian</h3>
                        @endif
                        
                        <p class="text-sm text-slate-600 mb-6">{{ $statusMessage }}</p>

                        <div class="flex flex-col gap-2">
                            <a href="{{ route('zoffline.pos') }}" class="w-full justify-center inline-flex items-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white shadow-md hover:bg-indigo-700 transition-all text-center">
                                Kembali ke Dashboard POS
                            </a>
                            <button @click="show = false" type="button" class="w-full justify-center rounded-xl bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-all">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
