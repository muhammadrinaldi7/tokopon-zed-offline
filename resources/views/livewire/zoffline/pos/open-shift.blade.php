<div class="min-h-screen w-full pb-12 pt-8 px-4 sm:px-6 lg:px-8 font-sans">

    <div class="max-w-7xl mx-auto mb-8">
        <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
            Mulai Shift Baru
        </h2>
        <p class="mt-2 text-base text-slate-500">
            Halo, <span class="font-bold text-slate-800">{{ auth()->user()->name }}</span>. Silakan masukkan modal awal
            laci kasir sebelum melakukan transaksi hari ini.
        </p>
    </div>

    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10">

        <div class="lg:col-span-8">
            @if ($hasActiveShift)
                <div
                    class="bg-white border border-indigo-100 rounded-3xl p-10 text-center shadow-sm flex flex-col justify-center items-center min-h-[400px] relative overflow-hidden">
                    <div class="absolute inset-0 bg-indigo-50/50"></div>
                    <div class="relative z-10">
                        <div
                            class="bg-indigo-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
                            <svg class="h-10 w-10 text-indigo-600" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-2xl font-extrabold text-indigo-950 mb-3">Shift Anda Sedang Aktif</h3>
                        <p class="text-base text-indigo-700/80 mb-8 max-w-md mx-auto">
                            Anda sudah membuka shift hari ini. Silakan kembali ke halaman POS untuk melanjutkan
                            transaksi penjualan.
                        </p>
                        <a href="{{ route('zoffline.pos') }}"
                            class="inline-flex justify-center items-center rounded-xl px-8 py-3.5 bg-indigo-600 text-base font-bold text-white shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:shadow-indigo-300 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200">
                            Kembali ke Kasir (POS)
                        </a>
                    </div>
                </div>
            @else
                <div class="bg-white p-6 sm:p-8 shadow-sm sm:rounded-3xl border border-slate-200">
                    <form wire:submit="openShift" class="space-y-8">

                        <div>
                            <div class="flex items-center justify-between mb-5">
                                <label class="block text-lg font-bold text-slate-800">
                                    Rincian Pecahan Uang
                                </label>
                            </div>
                            <div class="grid grid-cols-1 gap-3">
                                @foreach ($denominations as $denom => $qty)
                                    @php
                                        // Mengubah jadi border kiri (border-l) karena formatnya sekarang list horizontal memanjang
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

                                        <!-- Kiri: Nominal Pecahan -->
                                        <div class="w-28 sm:w-36 flex-shrink-0">
                                            <span
                                                class="font-extrabold text-base sm:text-lg text-slate-800 tracking-tight">
                                                Rp {{ number_format($denom, 0, ',', '.') }}
                                            </span>
                                        </div>

                                        <!-- Tengah: Garis Penghubung (Hanya tampil di layar agak besar biar mirip buku kas) -->
                                        <div
                                            class="hidden sm:block flex-grow border-b-2 border-dashed border-slate-100 mx-2 group-hover:border-slate-200 transition-colors">
                                        </div>

                                        <!-- Kanan: Grup Input & Subtotal -->
                                        <div
                                            class="flex items-center justify-end flex-grow sm:flex-grow-0 gap-3 sm:gap-6">

                                            <!-- Kotak Input Quantity -->
                                            <div class="flex items-center gap-1.5 sm:gap-2">
                                                <span class="text-slate-400 font-bold text-xs sm:text-sm">x</span>
                                                <input
                                                    wire:model.live.debounce.500ms="denominations.{{ $denom }}"
                                                    type="number" min="0" placeholder="0"
                                                    class="block w-16 sm:w-20 px-2 py-1.5 sm:py-2 text-center bg-slate-50 border-0 text-slate-900 rounded-lg ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-inset focus:ring-indigo-600 font-bold transition-all text-sm sm:text-base">
                                            </div>

                                            <!-- Teks Subtotal -->
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
                                    class="mt-3 flex items-center gap-2 text-red-600 bg-red-50 p-3 rounded-lg border border-red-100">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-sm font-semibold">Terdapat input jumlah yang tidak valid.</span>
                                </div>
                            @enderror
                        </div>

                        <div
                            class="bg-slate-900 rounded-2xl p-6 sm:p-8 flex flex-col sm:flex-row justify-between items-center shadow-lg relative overflow-hidden">
                            <svg class="absolute right-0 top-0 opacity-5 w-32 h-32 -mt-4 -mr-4" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
                            </svg>

                            <div class="relative z-10 flex flex-col mb-2 sm:mb-0 text-center sm:text-left">
                                <span class="text-slate-400 text-sm font-bold uppercase tracking-widest mb-1">Total
                                    Modal Awal</span>
                                <span class="text-3xl sm:text-4xl font-black text-white tracking-tight">
                                    Rp {{ number_format($this->totalCash, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <label for="openingNotes" class="block text-sm font-bold text-slate-700 mb-2">
                                Catatan Tambahan <span class="text-slate-400 font-normal">(Opsional)</span>
                            </label>
                            <textarea wire:model="openingNotes" id="openingNotes" rows="2"
                                class="block w-full rounded-xl border-0 py-3 px-4 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm transition-all"
                                placeholder="Contoh: Laci kekurangan uang koin Rp 500..."></textarea>
                        </div>

                        <div class="pt-4">
                            <button type="submit"
                                class="w-full flex justify-center items-center gap-2 py-4 px-4 border border-transparent rounded-xl shadow-lg shadow-indigo-200 text-base font-bold text-white bg-indigo-600 hover:bg-indigo-700 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600 transition-all duration-200">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Buka Shift & Masuk POS
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        <div class="lg:col-span-4">
            <div class="bg-white shadow-sm rounded-3xl border border-slate-200 p-6 sm:p-8 sticky top-8">
                <h3 class="text-xl font-extrabold text-slate-900 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Riwayat Shift Anda
                </h3>

                @if ($this->recentShifts->count() > 0)
                    <div class="space-y-4">
                        @foreach ($this->recentShifts as $shift)
                            <div
                                class="group border border-slate-100 bg-slate-50/50 rounded-2xl p-4 hover:bg-white hover:border-indigo-100 hover:shadow-md transition-all duration-200">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-slate-400 group-hover:text-indigo-500 transition-colors"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ $shift->shift_date->format('d M Y') }}
                                    </p>

                                    @if ($shift->status == 'open')
                                        <span
                                            class="px-2.5 py-1 text-[10px] uppercase font-bold tracking-wider rounded-md bg-emerald-100 text-emerald-700">Open</span>
                                    @else
                                        <span
                                            class="px-2.5 py-1 text-[10px] uppercase font-bold tracking-wider rounded-md bg-slate-200 text-slate-600">Closed</span>
                                    @endif
                                </div>

                                <div class="flex flex-col space-y-2 mt-1 border-t border-slate-100/80 pt-3">
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-slate-500 font-medium">Modal Awal</span>
                                        <span class="font-bold text-slate-900">Rp
                                            {{ number_format($shift->starting_cash, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-slate-500 font-medium">Status Rekon</span>
                                        <span>
                                            @if ($shift->reconciliation_status == 'balanced')
                                                <span
                                                    class="text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded text-xs">BALANCED</span>
                                            @elseif($shift->reconciliation_status == 'over')
                                                <span
                                                    class="text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded text-xs">BALANCED</span>
                                            @elseif($shift->reconciliation_status == 'short')
                                                <span
                                                    class="text-rose-600 font-bold bg-rose-50 px-2 py-0.5 rounded text-xs">SHORT</span>
                                            @else
                                                <span class="text-slate-400 font-medium">-</span>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div
                        class="bg-slate-50 border border-dashed border-slate-300 rounded-2xl p-8 text-center flex flex-col items-center justify-center">
                        <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <p class="text-sm text-slate-500 font-medium">Anda belum memiliki riwayat shift.</p>
                    </div>
                @endif
            </div>
        </div>

    </div>
    <!-- Simple Status Modal -->
    <div x-data="{ show: @entangle('showStatusModal') }" x-show="show" class="relative z-[100]" aria-labelledby="modal-title"
        role="dialog" aria-modal="true" style="display: none;">
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
                    class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-sm border border-slate-200">
                    <div class="px-4 pb-4 pt-5 sm:p-6 sm:pb-4 text-center">
                        @if ($statusType === 'success')
                            <div
                                class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 mb-4">
                                <svg class="h-10 w-10 text-emerald-600" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-extrabold leading-6 text-slate-900 mb-2">{{ $statusTitle }}</h3>
                        @else
                            <div
                                class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 mb-4">
                                <svg class="h-10 w-10 text-amber-600" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-extrabold leading-6 text-slate-900 mb-2">{{ $statusTitle }}</h3>
                        @endif

                        <p class="text-sm text-slate-600 mb-6">{{ $statusMessage }}</p>

                        <div class="flex flex-col gap-2">
                            <a href="{{ route('zoffline.pos') }}"
                                class="w-full justify-center inline-flex items-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white shadow-md hover:bg-indigo-700 transition-all text-center">
                                Lanjut ke Transaksi Penjualan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
