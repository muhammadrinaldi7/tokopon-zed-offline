<div class="relative min-h-screen bg-gradient-to-br p-4 sm:p-8" wire:poll.30s>
    {{-- Decorative Background Elements --}}
    <div
        class="absolute top-0 left-0 w-full h-96 bg-gradient-to-br from-blue-600/5 to-purple-600/5 blur-3xl pointer-events-none -z-10">
    </div>

    <div class="flex items-center justify-between mb-8">
        <div>
            <h1
                class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-indigo-700 tracking-tight">
                Finance Dashboard: SellPhone</h1>
            <p class="text-sm text-slate-500 mt-2 font-medium">Monitoring dan proses pencairan dana pembelian HP bekas ke pelanggan.</p>
        </div>
    </div>

    {{-- Summary Cards (KPI) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Card 1: Perlu Dicairkan --}}
        <div wire:click="setTab('PAYING')" class="cursor-pointer bg-white rounded-2xl p-5 shadow-sm border {{ $summary['paying_count'] > 0 ? 'border-rose-300 ring-4 ring-rose-50/50' : 'border-slate-100 hover:border-blue-200' }} transition-all relative overflow-hidden group">
            @if($summary['paying_count'] > 0)
                <div class="absolute inset-0 bg-rose-50/30 animate-pulse"></div>
            @endif
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-[11px] uppercase tracking-widest font-bold {{ $summary['paying_count'] > 0 ? 'text-rose-600' : 'text-slate-500' }}">Perlu Dicairkan</p>
                    <h3 class="text-3xl font-black {{ $summary['paying_count'] > 0 ? 'text-rose-700' : 'text-slate-800' }} mt-1">{{ $summary['paying_count'] }}</h3>
                    <p class="text-xs font-bold {{ $summary['paying_count'] > 0 ? 'text-rose-500' : 'text-slate-400' }} mt-1">Total: Rp {{ number_format($summary['paying_total'], 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 rounded-full {{ $summary['paying_count'] > 0 ? 'bg-rose-100 text-rose-600' : 'bg-slate-50 text-slate-400' }} flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
        </div>

        {{-- Card 2: Menunggu Approval --}}
        <div wire:click="setTab('PENDING_APPROVAL')" class="cursor-pointer bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:border-amber-200 transition-all group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] uppercase tracking-widest font-bold text-slate-500">Menunggu Approval</p>
                    <h3 class="text-3xl font-black text-slate-800 mt-1">{{ $summary['pending_approval_count'] }}</h3>
                    <p class="text-xs font-bold text-slate-400 mt-1">Menunggu persetujuan SPV</p>
                </div>
                <div class="w-12 h-12 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
        </div>

        {{-- Card 3: Sedang Inspeksi --}}
        <div wire:click="setTab('INSPECTING')" class="cursor-pointer bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:border-indigo-200 transition-all group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] uppercase tracking-widest font-bold text-slate-500">Sedang Inspeksi</p>
                    <h3 class="text-3xl font-black text-slate-800 mt-1">{{ $summary['inspecting_count'] }}</h3>
                    <p class="text-xs font-bold text-slate-400 mt-1">Pengecekan fisik perangkat</p>
                </div>
                <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
            </div>
        </div>

        {{-- Card 4: Selesai Bulan Ini --}}
        <div wire:click="setTab('COMPLETED')" class="cursor-pointer bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:border-emerald-200 transition-all group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] uppercase tracking-widest font-bold text-slate-500">Selesai (Bulan Ini)</p>
                    <h3 class="text-3xl font-black text-slate-800 mt-1">{{ $summary['completed_count'] }}</h3>
                    <p class="text-xs font-bold text-slate-400 mt-1">Total: Rp {{ number_format($summary['completed_total'], 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white/70 backdrop-blur-xl rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-white/50 overflow-hidden transition-all duration-300">
        
        {{-- Quick Filter Tabs & Search --}}
        <div class="p-5 border-b border-slate-100/50 bg-white/40">
            {{-- Tabs --}}
            <div class="flex flex-wrap gap-2 mb-4">
                <button wire:click="setTab('')" class="px-4 py-2 text-xs font-bold rounded-full border transition-all {{ $status === '' ? 'bg-slate-800 text-white border-slate-800 shadow-sm' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                    Semua
                </button>
                <button wire:click="setTab('PAYING')" class="relative px-4 py-2 text-xs font-bold rounded-full border transition-all {{ $status === 'PAYING' ? 'bg-rose-500 text-white border-rose-500 shadow-sm' : 'bg-white text-rose-600 border-rose-200 hover:bg-rose-50' }}">
                    <span class="flex items-center gap-1.5">
                        @if($summary['paying_count'] > 0)
                            <span class="w-2 h-2 rounded-full bg-current animate-pulse"></span>
                        @endif
                        Perlu Dicairkan ({{ $summary['paying_count'] }})
                    </span>
                </button>
                <button wire:click="setTab('PENDING_APPROVAL')" class="px-4 py-2 text-xs font-bold rounded-full border transition-all {{ $status === 'PENDING_APPROVAL' ? 'bg-amber-500 text-white border-amber-500 shadow-sm' : 'bg-white text-amber-600 border-amber-200 hover:bg-amber-50' }}">
                    Approval ({{ $summary['pending_approval_count'] }})
                </button>
                <button wire:click="setTab('INSPECTING')" class="px-4 py-2 text-xs font-bold rounded-full border transition-all {{ $status === 'INSPECTING' ? 'bg-indigo-500 text-white border-indigo-500 shadow-sm' : 'bg-white text-indigo-600 border-indigo-200 hover:bg-indigo-50' }}">
                    Inspeksi ({{ $summary['inspecting_count'] }})
                </button>
                <button wire:click="setTab('COMPLETED')" class="px-4 py-2 text-xs font-bold rounded-full border transition-all {{ $status === 'COMPLETED' ? 'bg-emerald-500 text-white border-emerald-500 shadow-sm' : 'bg-white text-emerald-600 border-emerald-200 hover:bg-emerald-50' }}">
                    Selesai
                </button>
            </div>

            {{-- Filters Bar --}}
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1 group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors duration-300 group-focus-within:text-blue-600">
                        <svg class="w-5 h-5 text-slate-400 group-focus-within:text-blue-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari pelanggan, tipe HP, atau #ID..." class="w-full pl-11 pr-4 py-2.5 text-sm bg-white/60 border-slate-200/60 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-300 placeholder:text-slate-400 font-medium">
                </div>
                <select wire:model.live="date_filter" class="py-2.5 px-4 text-sm bg-white/60 border-slate-200/60 rounded-xl focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-300 w-full sm:w-48 font-bold text-slate-700 cursor-pointer">
                    <option value="all">Semua Waktu</option>
                    <option value="today">Hari Ini</option>
                    <option value="last_7_days">7 Hari Terakhir</option>
                    <option value="this_month">Bulan Ini</option>
                </select>
                @if($status === 'INSPECTING')
                <select wire:model.live="status_inspeksi" class="py-2.5 px-4 text-sm bg-white/60 border-slate-200/60 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-300 w-full sm:w-48 font-bold text-slate-700 cursor-pointer">
                    <option value="">Status Inspeksi</option>
                    <option value="pass">Lulus</option>
                    <option value="conditional">Kondisional</option>
                    <option value="fail">Gagal</option>
                </select>
                @endif
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-[11px] text-slate-500 bg-slate-50/80 uppercase font-black tracking-wider">
                    <tr>
                        <th class="px-6 py-4 border-b border-slate-100">Waktu & ID</th>
                        <th class="px-6 py-4 border-b border-slate-100">Cabang & FL</th>
                        <th class="px-6 py-4 border-b border-slate-100">Pelanggan</th>
                        <th class="px-6 py-4 border-b border-slate-100">HP Ditawarkan</th>
                        <th class="px-6 py-4 border-b border-slate-100 text-right">Nominal & Status</th>
                        <th class="px-6 py-4 border-b border-slate-100 text-center">Aging</th>
                        <th class="px-6 py-4 border-b border-slate-100 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/60">
                    @forelse ($sellPhones as $item)
                        @php
                            $isPaying = $item->status === 'PAYING';
                            $isCompleted = $item->status === 'COMPLETED' || $item->status === 'CANCELLED';
                            
                            // Hitung Aging
                            $hoursDiff = $item->updated_at->diffInHours(now());
                            $agingText = $item->updated_at->diffForHumans();
                            
                            $agingColor = 'text-slate-500 bg-slate-100';
                            if (!$isCompleted) {
                                if ($hoursDiff >= 48) {
                                    $agingColor = 'text-rose-700 bg-rose-100 border border-rose-200';
                                } elseif ($hoursDiff >= 24) {
                                    $agingColor = 'text-amber-700 bg-amber-100 border border-amber-200';
                                } else {
                                    $agingColor = 'text-emerald-700 bg-emerald-100 border border-emerald-200';
                                }
                            }
                            
                            // Badge Baru (< 24 jam)
                            $isNew = $item->created_at->diffInHours(now()) < 24;
                        @endphp
                        
                        <tr class="transition-colors duration-200 group {{ $isPaying ? 'bg-amber-50/40' : '' }} {{ $isCompleted ? 'opacity-70' : '' }}">
                            <td class="px-6 py-5 whitespace-nowrap {{ $isPaying ? 'border-l-4 border-l-rose-500' : 'border-l-4 border-l-transparent' }}">
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-slate-900 bg-slate-100/80 px-2.5 py-1 rounded-md text-xs tracking-wide group-hover:bg-blue-100 group-hover:text-blue-700 transition-colors">#SPL-{{ $item->id }}</span>
                                    @if($isNew && !$isCompleted)
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-rose-100 text-rose-600 border border-rose-200 animate-pulse">Baru</span>
                                    @endif
                                </div>
                                <div class="text-xs text-slate-500 mt-2 font-medium flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ $item->created_at->format('d M Y, H:i') }}
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="font-bold text-slate-800 flex items-center gap-1.5 text-xs">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                    {{ optional($item->businessUnit)->name ?? 'Global' }}
                                </div>
                                <div class="text-[11px] text-slate-500 mt-1 font-medium flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    FL: {{ optional($item->handledBy)->name ?? '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="font-bold text-slate-800">{{ optional($item->user)->name }}</div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="font-bold text-blue-700 bg-blue-50/50 inline-block px-2.5 py-1 rounded-lg border border-blue-100/50 text-xs">
                                    {{ $item->phone_brand }} {{ $item->phone_model }}
                                </div>
                                <div class="text-[10px] text-slate-500 mt-1.5 font-medium bg-slate-50 inline-block px-2 py-0.5 rounded border border-slate-100">
                                    {{ $item->phone_ram ?: '-' }} RAM / {{ $item->phone_storage ?: '-' }} ROM
                                </div>
                            </td>
                            <td class="px-6 py-5 text-right whitespace-nowrap">
                                @if ($item->appraised_value)
                                    <div class="text-base font-black {{ $isPaying ? 'text-rose-600' : 'text-emerald-600' }}">
                                        Rp {{ number_format($item->appraised_value, 0, ',', '.') }}
                                    </div>
                                @else
                                    <div class="text-xs text-slate-400 font-medium italic">Belum ditaksir</div>
                                @endif
                                
                                @php
                                    $statusColors = [
                                        'PENDING' => 'bg-amber-100 text-amber-800 border-amber-200',
                                        'OFFERED' => 'bg-sky-100 text-sky-800 border-sky-200',
                                        'WAITING_FOR_DEVICE' => 'bg-purple-100 text-purple-800 border-purple-200',
                                        'INSPECTING' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                        'PENDING_APPROVAL' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                        'PAYING' => 'bg-rose-100 text-rose-700 border-rose-300', // Khusus Paying jadi Rose/Merah
                                        'COMPLETED' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                        'CANCELLED' => 'bg-slate-100 text-slate-600 border-slate-200',
                                    ];
                                @endphp
                                <div class="mt-2 flex flex-col items-end gap-1">
                                    <span class="px-2 py-1 text-[9px] font-black uppercase rounded border tracking-wider {{ $statusColors[$item->status] ?? 'bg-slate-100 text-slate-800 border-slate-200' }} shadow-sm">
                                        {{ str_replace('_', ' ', $item->status) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-5 text-center">
                                <span class="px-2.5 py-1 text-[10px] font-bold rounded-full {{ $agingColor }} inline-block">
                                    {{ $agingText }}
                                </span>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <a href="{{ route('admin.sell-phones.show', $item) }}" wire:navigate
                                    class="inline-flex items-center gap-1.5 px-4 py-2 {{ $isPaying ? 'bg-rose-500 hover:bg-rose-600 text-white shadow-md' : 'bg-white text-blue-600 border border-blue-100 hover:bg-blue-50 shadow-sm' }} font-bold text-xs rounded-xl transition-all duration-200 focus:ring-2 focus:ring-blue-500/20 group-hover:-translate-y-0.5">
                                    {{ $isPaying ? 'Bayar Sekarang' : 'Detail' }}
                                    <svg class="w-4 h-4 {{ $isPaying ? 'animate-bounce-x' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-10 h-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                    </div>
                                    <p class="text-base font-bold text-slate-700">Tidak ada pengajuan ditemukan</p>
                                    <p class="text-sm mt-1 text-slate-500 font-medium">Belum ada pelanggan yang mengajukan penjualan HP di cabang ini dengan filter tersebut.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($sellPhones->hasPages())
            <div class="p-5 border-t border-slate-100/50 bg-slate-50/30">
                {{ $sellPhones->links() }}
            </div>
        @endif
    </div>
    
    <style>
        @keyframes bounce-x {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(3px); }
        }
        .animate-bounce-x {
            animation: bounce-x 1s infinite;
        }
    </style>
</div>
