<div class="relative min-h-screen bg-neutral-50 p-4 sm:p-8 font-sans">
    {{-- Decorative Background Elements --}}
    <div
        class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-emerald-100/50 to-neutral-50 pointer-events-none -z-10">
    </div>

    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-black text-emerald-900 tracking-tight flex items-center gap-2">
                    {{-- <svg class="w-8 h-8 text-amber-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg> --}}
                    Cek Kualitas Perangkat
                </h1>
                <p class="text-sm text-emerald-700 mt-2 font-medium">Lacak riwayat inspeksi & kualitas perangkat secara
                    transparan.</p>
            </div>
            <div>
                <button wire:click="goBack"
                    class="px-4 py-2 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-bold rounded-xl transition-all text-sm shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali
                </button>
            </div>
        </div>

        {{-- Search Card --}}
        <div class="bg-white rounded-2xl shadow-xl shadow-emerald-900/5 border border-emerald-100 overflow-hidden mb-8">
            <div class="p-6">
                <form wire:submit="search" class="flex flex-col sm:flex-row gap-4">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" wire:model="searchImei" placeholder="Masukkan 15 digit IMEI..."
                            class="w-full pl-12 pr-4 py-4 text-lg font-bold bg-slate-50 border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all placeholder:font-normal placeholder:text-slate-400 text-center tracking-widest uppercase">
                        @error('searchImei')
                            <span class="text-rose-500 text-sm mt-1 block text-center">{{ $message }}</span>
                        @enderror
                    </div>
                    <button type="submit" wire:loading.attr="disabled"
                        class="px-8 py-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-lg shadow-emerald-600/30 transition-all flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="search">Cari IMEI</span>
                        <span wire:loading wire:target="search">Mencari...</span>
                    </button>
                </form>
            </div>
        </div>

        @if ($searched)
            @if (count($deviceHistory) > 0)
                {{-- Device Scorecard --}}
                <div
                    class="bg-white rounded-[2rem] shadow-2xl shadow-emerald-900/10 border border-emerald-100 mb-8 overflow-hidden relative">
                    {{-- Watermark --}}
                    <div class="absolute -right-10 -bottom-10 opacity-[0.03] pointer-events-none">
                        <svg class="w-96 h-96" fill="currentColor" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd"
                                d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </div>

                    <div class="p-8 sm:p-12 relative z-10">
                        <div
                            class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 border-b border-neutral-100 pb-8 mb-8">
                            <div class="flex items-center gap-6">
                                <div
                                    class="w-20 h-20 bg-emerald-50 text-emerald-600 rounded-3xl flex items-center justify-center flex-shrink-0 border-2 border-emerald-100">
                                    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <div
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-100 text-amber-800 text-[10px] font-black uppercase tracking-widest rounded-lg border border-amber-200 mb-2">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                        VERIFIED DEVICE
                                    </div>
                                    <h2 class="text-3xl font-black text-neutral-800 mb-1">{{ $deviceInfo['model'] }}
                                    </h2>
                                    <p class="text-neutral-500 font-medium">{{ $deviceInfo['specs'] }}</p>
                                </div>
                            </div>
                            <div
                                class="w-full md:w-auto text-left md:text-right bg-neutral-50 p-4 rounded-2xl border border-neutral-100">
                                <div class="text-[11px] font-bold text-neutral-400 uppercase tracking-widest mb-1">Nomor
                                    Seri / IMEI</div>
                                <div class="text-xl font-mono font-black text-neutral-700 tracking-wider">
                                    {{ $searchImei }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="col-span-1 md:col-span-2">
                                <h3 class="text-sm font-bold text-neutral-400 uppercase tracking-widest mb-4">Kesimpulan
                                    Status</h3>
                                <div
                                    class="inline-flex items-center gap-3 px-5 py-3 {{ str_contains(strtolower($deviceInfo['latest_status']), 'void') ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }} border-2 font-bold rounded-xl text-lg">
                                    @if (str_contains(strtolower($deviceInfo['latest_status']), 'void'))
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                            </path>
                                        </svg>
                                    @else
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @endif
                                    {{ $deviceInfo['latest_status'] }}
                                </div>
                            </div>
                            <div class="col-span-1">
                                <h3 class="text-sm font-bold text-neutral-400 uppercase tracking-widest mb-4">Total
                                    Riwayat</h3>
                                <div class="text-3xl font-black text-neutral-800">
                                    {{ count($deviceHistory) }} <span
                                        class="text-sm font-bold text-neutral-400 uppercase tracking-wider ml-1">Aktivitas</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3 mb-6 px-2">
                    <svg class="w-5 h-5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-lg font-bold text-neutral-700">Timeline Riwayat Perangkat</h3>
                </div>

                {{-- Timeline --}}
                <div class="relative pl-4 sm:pl-8">
                    {{-- Vertical Line --}}
                    <div class="absolute left-8 sm:left-12 top-4 bottom-4 w-0.5 bg-neutral-200"></div>

                    <div class="space-y-8">
                        @foreach ($deviceHistory as $index => $event)
                            <div class="relative flex items-start group">
                                {{-- Icon --}}
                                <div
                                    class="absolute left-[-16px] sm:left-0 top-0 w-10 h-10 {{ str_contains($event['type'], 'Inspeksi') ? ($event['status'] === 'LULUS QC' ? 'bg-emerald-500' : 'bg-rose-500') : (str_contains($event['type'], 'Penjualan') ? 'bg-blue-500' : 'bg-neutral-800') }} text-white rounded-full shadow-lg flex items-center justify-center ring-4 ring-neutral-50 z-10 transition-transform group-hover:scale-110">
                                    @if ($event['icon'] === 'shopping-cart')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    @elseif($event['icon'] === 'arrow-down-tray')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 16v2a2 2 0 002 2h14a2 2 0 002-2v-2m-5-4l-3 3m0 0l-3-3m3 3V4" />
                                        </svg>
                                    @elseif($event['icon'] === 'clipboard-document-check')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                        </svg>
                                    @elseif($event['icon'] === 'wrench-screwdriver')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
                                        </svg>
                                    @elseif($event['icon'] === 'arrows-right-left')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    @endif
                                </div>

                                {{-- Content Card --}}
                                <div class="ml-12 sm:ml-16 w-full">
                                    <div
                                        class="bg-white rounded-2xl shadow-sm border {{ str_contains($event['type'], 'Inspeksi') ? 'border-emerald-100' : 'border-neutral-200' }} p-6 transition-all hover:shadow-md">

                                        <div
                                            class="flex flex-col sm:flex-row justify-between sm:items-center gap-3 mb-4">
                                            <div class="flex flex-wrap items-center gap-3">
                                                <h3 class="text-lg font-bold text-neutral-800">{{ $event['type'] }}
                                                </h3>
                                                @if (isset($event['status']))
                                                    <span
                                                        class="px-2.5 py-1 text-[10px] font-black uppercase rounded-lg border tracking-wider
                                                        {{ $event['status'] === 'LULUS QC'
                                                            ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
                                                            : ($event['status'] === 'TIDAK LULUS'
                                                                ? 'bg-rose-50 border-rose-200 text-rose-700'
                                                                : 'bg-neutral-100 border-neutral-200 text-neutral-700') }}">
                                                        {{ $event['status'] }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div
                                                class="text-sm font-bold text-neutral-500 bg-neutral-50 px-3 py-1.5 rounded-lg border border-neutral-100 flex items-center gap-2">
                                                <svg class="w-4 h-4 text-neutral-400" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ \Carbon\Carbon::parse($event['date'])->translatedFormat('d M Y') }}
                                            </div>
                                        </div>

                                        @if (str_contains($event['type'], 'Inspeksi') && isset($event['checklist']) && count($event['checklist']) > 0)
                                            {{-- Inspection Checklist Section --}}
                                            <div
                                                class="bg-emerald-50/50 rounded-xl border border-emerald-100/50 p-5 mb-5">
                                                <div
                                                    class="flex justify-between items-center mb-4 border-b border-emerald-100 pb-3">
                                                    <h4
                                                        class="font-bold text-emerald-800 text-sm flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Ringkasan Inspeksi
                                                    </h4>
                                                    <div
                                                        class="text-sm font-black text-emerald-700 bg-emerald-100 px-3 py-1 rounded-lg">
                                                        {{ $event['passed_points'] ?? 0 }} /
                                                        {{ $event['total_points'] ?? 0 }} LULUS
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-3 gap-x-6">
                                                    @foreach (array_slice($event['checklist'], 0, 8) as $chk)
                                                        @if (isset($chk['type']) && $chk['type'] === 'boolean')
                                                            <div class="flex items-center gap-3">
                                                                @if (isset($chk['value']) && ($chk['value'] == 1 || $chk['value'] === true || $chk['value'] === '1'))
                                                                    <div
                                                                        class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0">
                                                                        <svg class="w-3.5 h-3.5" fill="none"
                                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                stroke-width="3" d="M5 13l4 4L19 7" />
                                                                        </svg>
                                                                    </div>
                                                                    <span
                                                                        class="text-sm font-medium text-neutral-700">{{ $chk['name'] }}</span>
                                                                @else
                                                                    <div
                                                                        class="w-5 h-5 rounded-full bg-rose-500 text-white flex items-center justify-center shrink-0">
                                                                        <svg class="w-3.5 h-3.5" fill="none"
                                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round"
                                                                                stroke-width="3"
                                                                                d="M6 18L18 6M6 6l12 12" />
                                                                        </svg>
                                                                    </div>
                                                                    <span
                                                                        class="text-sm font-medium text-neutral-500 line-through">{{ $chk['name'] }}</span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                                @if (count($event['checklist']) > 8)
                                                    <div
                                                        class="mt-3 text-xs text-emerald-600 font-bold bg-emerald-100/50 inline-block px-2 py-1 rounded">
                                                        + {{ count($event['checklist']) - 8 }} poin pengecekan
                                                        lainnya...
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        <p class="text-neutral-600 text-sm leading-relaxed mb-4">
                                            {{ $event['description'] }}
                                        </p>

                                        @if (!empty($event['meta']))
                                            <div
                                                class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-neutral-50 rounded-xl p-4 border border-neutral-100">
                                                @foreach ($event['meta'] as $key => $val)
                                                    <div>
                                                        <div
                                                            class="text-[10px] text-neutral-400 font-black uppercase tracking-widest mb-1">
                                                            {{ $key }}</div>
                                                        <div class="text-sm font-bold text-neutral-700">
                                                            {{ $val ?: '-' }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div
                    class="bg-white rounded-3xl shadow-xl shadow-neutral-200/50 border border-neutral-100 p-12 text-center max-w-2xl mx-auto">
                    <div
                        class="w-24 h-24 bg-neutral-50 rounded-full flex items-center justify-center mx-auto mb-6 border-8 border-white shadow-sm">
                        <svg class="w-10 h-10 text-neutral-300" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-black text-neutral-800 mb-2">Riwayat Tidak Ditemukan</h3>
                    <p class="text-neutral-500 font-medium">Kami tidak menemukan rekam jejak untuk IMEI <br><strong
                            class="text-neutral-800 text-lg mt-2 inline-block bg-neutral-100 px-3 py-1 rounded-lg">"{{ $searchImei }}"</strong>
                    </p>
                </div>
            @endif
        @endif
    </div>
</div>
