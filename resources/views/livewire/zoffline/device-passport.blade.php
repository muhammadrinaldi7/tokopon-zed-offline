<div class="relative min-h-screen bg-gradient-to-br p-4 sm:p-8">
    {{-- Decorative Background Elements --}}
    <div
        class="absolute top-0 left-0 w-full h-96 bg-gradient-to-br from-blue-600/5 to-purple-600/5 blur-3xl pointer-events-none -z-10">
    </div>

    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1
                    class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-indigo-700 tracking-tight">
                    Device Passport (Pusat Histori IMEI)</h1>
                <p class="text-sm text-slate-500 mt-2 font-medium">Lacak seluruh riwayat hidup (lifecycle) dari sebuah
                    perangkat di dalam ekosistem.</p>
            </div>
        </div>

        {{-- Search Card --}}
        <div class="bg-white/70 backdrop-blur-xl rounded-2xl shadow-sm border border-white/50 overflow-hidden mb-8">
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
                        <input type="text" wire:model="searchImei" placeholder="Masukkan 15 digit IMEI atau Serial Number..."
                            class="w-full pl-12 pr-4 py-4 text-lg font-bold bg-slate-50/50 border-slate-200/60 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all placeholder:font-normal placeholder:text-slate-400">
                        @error('searchImei') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    <button type="submit" wire:loading.attr="disabled"
                        class="px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-blue-500/30 transition-all flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="search">Lacak Perangkat</span>
                        <span wire:loading wire:target="search">Mencari...</span>
                    </button>
                </form>
            </div>
        </div>

        @if($searched)
            @if(count($deviceHistory) > 0)
                {{-- Device Header --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 mb-8 overflow-hidden">
                    <div class="p-6 sm:p-8 bg-gradient-to-r from-slate-50 to-white flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6">
                        <div class="flex items-center gap-5">
                            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-sm border border-blue-200">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">IMEI: {{ $searchImei }}</div>
                                <h2 class="text-2xl font-black text-slate-800">{{ $deviceInfo['model'] }}</h2>
                                <p class="text-sm text-slate-500 font-medium mt-1">{{ $deviceInfo['specs'] }}</p>
                            </div>
                        </div>
                        <div class="text-left sm:text-right">
                            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Status Terkini</div>
                            <div class="inline-flex items-center px-4 py-2 bg-slate-100 text-slate-800 font-bold rounded-xl border border-slate-200">
                                {{ $deviceInfo['latest_status'] }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Timeline --}}
                <div class="relative pl-4 sm:pl-8">
                    {{-- Vertical Line --}}
                    <div class="absolute left-8 sm:left-12 top-4 bottom-4 w-1 bg-slate-200 rounded-full"></div>

                    <div class="space-y-8">
                        @foreach($deviceHistory as $index => $event)
                            <div class="relative flex items-start group">
                                {{-- Icon --}}
                                <div class="absolute left-[-16px] sm:left-0 top-0 w-10 h-10 {{ $event['color'] }} text-white rounded-xl shadow-lg flex items-center justify-center ring-4 ring-white z-10 transition-transform group-hover:scale-110">
                                    @if($event['icon'] === 'shopping-cart')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                    @elseif($event['icon'] === 'arrow-down-tray')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16v2a2 2 0 002 2h14a2 2 0 002-2v-2m-5-4l-3 3m0 0l-3-3m3 3V4" /></svg>
                                    @elseif($event['icon'] === 'clipboard-document-check')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                    @elseif($event['icon'] === 'wrench-screwdriver')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" /></svg>
                                    @elseif($event['icon'] === 'arrows-right-left')
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                                    @endif
                                </div>

                                {{-- Content Card --}}
                                <div class="ml-12 sm:ml-16 w-full">
                                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-md transition-shadow">
                                        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 mb-3">
                                            <div class="flex items-center gap-3">
                                                <h3 class="text-lg font-bold text-slate-800">{{ $event['type'] }}</h3>
                                                @if(isset($event['status']))
                                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-600 text-[10px] font-black uppercase rounded-lg border border-slate-200 tracking-wider">
                                                        {{ $event['status'] }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-sm font-bold text-slate-400 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">
                                                {{ \Carbon\Carbon::parse($event['date'])->translatedFormat('d M Y, H:i') }}
                                            </div>
                                        </div>
                                        
                                        <p class="text-slate-600 text-sm leading-relaxed mb-4">
                                            {{ $event['description'] }}
                                        </p>

                                        @if(!empty($event['meta']))
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-slate-50 rounded-xl p-4 border border-slate-100">
                                                @foreach($event['meta'] as $key => $val)
                                                    <div>
                                                        <div class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-1">{{ $key }}</div>
                                                        <div class="text-sm font-semibold text-slate-700">{{ $val ?: '-' }}</div>
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
                <div class="bg-white/70 backdrop-blur-xl rounded-2xl shadow-sm border border-white/50 p-12 text-center">
                    <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Riwayat Tidak Ditemukan</h3>
                    <p class="text-slate-500 max-w-md mx-auto">Kami tidak dapat menemukan riwayat perangkat dengan IMEI <strong>"{{ $searchImei }}"</strong> di sistem Penjualan, Pembelian, maupun Klaim Garansi kami.</p>
                </div>
            @endif
        @endif
    </div>
</div>
