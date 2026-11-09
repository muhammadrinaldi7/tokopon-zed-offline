<div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-blue-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 tracking-tight">Log & Antrean Sinkronisasi Accurate</h1>
                    <p class="text-xs text-gray-500">Pusat pemantauan otomatisasi retry penjualan POS, riwayat pemulihan, dan penanganan kendala data</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button wire:click="retryAllPending" wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition disabled:opacity-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Sinkronkan Semua Pending
            </button>
            <button wire:click="$refresh" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 shadow-sm transition">
                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Segarkan
            </button>
        </div>
    </div>

    <!-- Panel Jadwal Otomatis (Cron Dynamic Settings) -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-5 text-white shadow-lg border border-slate-800">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full {{ $retryFrequency !== 'disabled' ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400' }}"></span>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-blue-200">Pengaturan Jadwal Auto-Retry (Cron Dinamis)</h3>
                </div>
                <p class="text-xs text-slate-300">
                    Sistem otomatis memindai transaksi tertunda/lelet dan mencoba mengirim ulang ke Accurate secara berkala.
                </p>
                <div class="text-[11px] text-slate-400 pt-1">
                    Terakhir dijalankan: 
                    <span class="font-semibold text-emerald-300">
                        {{ $lastRunAt ? \Carbon\Carbon::parse($lastRunAt)->translatedFormat('d M Y, H:i:s') . ' WIB (' . \Carbon\Carbon::parse($lastRunAt)->diffForHumans() . ')' : 'Belum pernah dijalankan' }}
                    </span>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 bg-white/5 p-2 rounded-xl border border-white/10">
                <select wire:model="retryFrequency"
                    class="bg-neutral-900 text-white text-xs rounded-lg px-3 py-2 border border-white/20 focus:border-blue-400 focus:ring-1 focus:ring-blue-400 outline-none">
                    <option value="every_minute">⚡ Setiap 1 Menit (Testing / Cepat)</option>
                    <option value="every_five_minutes">⚡ Setiap 5 Menit (Responsif)</option>
                    <option value="every_ten_minutes">🕒 Setiap 10 Menit (Disarankan)</option>
                    <option value="every_fifteen_minutes">🕒 Setiap 15 Menit</option>
                    <option value="every_thirty_minutes">🕒 Setiap 30 Menit</option>
                    <option value="hourly">🕐 Setiap 1 Jam</option>
                    <option value="disabled">⛔ Nonaktifkan Otomatis (Manual Saja)</option>
                </select>
                <button wire:click="saveScheduleSettings"
                    class="px-3.5 py-2 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-lg transition shadow-sm whitespace-nowrap">
                    Simpan Jadwal
                </button>
                <button wire:click="runCronNow" wire:loading.attr="disabled"
                    class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg transition shadow-sm whitespace-nowrap disabled:opacity-50"
                    title="Jalankan antrean auto-retry sekarang tanpa menunggu jadwal">
                    ⚡ Jalankan Sekarang
                </button>
            </div>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Pending Antrean -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Antrean Pending</p>
                    <p class="text-2xl font-black text-amber-600 mt-1">{{ number_format($totalPending) }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Menunggu giliran cron auto-retry</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 2. Auto-Recovered (Kemarin & Hari Ini) -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Auto-Recovered</p>
                    <div class="flex items-baseline gap-2 mt-1">
                        <p class="text-2xl font-black text-emerald-600">{{ number_format($autoRecoveredToday) }}</p>
                        <span class="text-xs text-gray-500 font-medium">(Kemarin: {{ number_format($autoRecoveredYesterday) }})</span>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-0.5">Sukses dipulihkan otomatis oleh cron</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 3. Butuh Tindakan Manual (Failed Data) -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Butuh Tindakan</p>
                    <p class="text-2xl font-black text-rose-600 mt-1">{{ number_format($failedNeedAction) }}</p>
                    <p class="text-[11px] text-rose-500 font-medium mt-0.5">Stok habis / SKU / data invalid</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- 4. Total Synced Hari Ini -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tersinkron Hari Ini</p>
                    <p class="text-2xl font-black text-indigo-600 mt-1">{{ number_format($totalSyncedToday) }}</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Total faktur aktif hari ini</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm space-y-4">
        <!-- Date Preset Tabs -->
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-gray-100">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-semibold text-gray-500 mr-1">Periode:</span>
                <button wire:click="setDatePreset('all')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $datePreset === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    Semua
                </button>
                <button wire:click="setDatePreset('today')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $datePreset === 'today' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    Hari Ini
                </button>
                <button wire:click="setDatePreset('yesterday')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $datePreset === 'yesterday' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    Kemarin
                </button>
                <button wire:click="setDatePreset('this_week')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $datePreset === 'this_week' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    Minggu Ini
                </button>
                <button wire:click="setDatePreset('this_month')"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $datePreset === 'this_month' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    Bulan Ini
                </button>
            </div>

            @if($startDate || $endDate || $search || $statusFilter || $triggerFilter || $branchFilter || $businessUnitFilter)
                <button wire:click="resetFilters" class="text-xs font-semibold text-rose-600 hover:text-rose-700 underline">
                    Reset Filter
                </button>
            @endif
        </div>

        <!-- Filter Controls -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
            <!-- Search -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Pencarian</label>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari No Order, No Faktur, atau Pesan Error..."
                        class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Status Hasil</label>
                <select wire:model.live="statusFilter" class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 focus:border-blue-500 outline-none bg-white">
                    <option value="">Semua Status</option>
                    <option value="SUCCESS">🟢 Sukses (Tersinkron)</option>
                    <option value="FAILED">🔴 Gagal (Error)</option>
                    <option value="PENDING">🟡 Pending</option>
                </select>
            </div>

            <!-- Trigger Type Filter -->
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Metode Pemicu</label>
                <select wire:model.live="triggerFilter" class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 focus:border-blue-500 outline-none bg-white">
                    <option value="">Semua Pemicu</option>
                    <option value="AUTO_RETRY_SCHEDULE">🤖 Auto-Retry (Cron)</option>
                    <option value="MANUAL_ADMIN">👤 Manual Admin</option>
                    <option value="REALTIME_POS">⚡ Realtime POS Kasir</option>
                </select>
            </div>

            <!-- Branch Filter -->
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Cabang Toko</label>
                <select wire:model.live="branchFilter" class="w-full px-3 py-2 text-xs rounded-xl border border-gray-200 focus:border-blue-500 outline-none bg-white">
                    <option value="">Semua Cabang</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Table of Logs -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-100 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Waktu & Percobaan</th>
                        <th class="py-3 px-4">Nomor Pesanan</th>
                        <th class="py-3 px-4">Cabang & Kasir</th>
                        <th class="py-3 px-4">Pemicu</th>
                        <th class="py-3 px-4">Status & Hasil</th>
                        <th class="py-3 px-4">Penyebab / Pesan Error</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs text-gray-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50/70 transition">
                            <!-- Waktu & Percobaan -->
                            <td class="py-3 px-4">
                                <div class="font-semibold text-gray-900">{{ $log->created_at ? $log->created_at->format('d/m/Y H:i') : '-' }}</div>
                                <div class="text-[10px] text-gray-400">Percobaan ke-{{ $log->attempt_number }}</div>
                            </td>

                            <!-- Nomor Pesanan -->
                            <td class="py-3 px-4">
                                @if($log->order)
                                    <div class="font-bold text-gray-900">#{{ $log->order->order_number }}</div>
                                    <div class="text-[11px] text-gray-500">Rp {{ number_format($log->order->grand_total, 0, ',', '.') }}</div>
                                @else
                                    <span class="text-gray-400">Order #{{ $log->order_id }} (Terhapus)</span>
                                @endif
                            </td>

                            <!-- Cabang & Kasir -->
                            <td class="py-3 px-4">
                                <div class="font-medium text-gray-800">{{ $log->branch->name ?? ($log->order?->branch->name ?? '-') }}</div>
                                <div class="text-[10px] text-gray-400">
                                    {{ $log->order?->handledBy->name ?? 'Kasir Umum' }}
                                </div>
                            </td>

                            <!-- Pemicu -->
                            <td class="py-3 px-4">
                                @if($log->trigger_type === 'AUTO_RETRY_SCHEDULE')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        🤖 Auto Cron
                                    </span>
                                @elseif($log->trigger_type === 'MANUAL_ADMIN')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200" title="{{ $log->executedBy ? 'Oleh: ' . $log->executedBy->name : 'Admin' }}">
                                        👤 Manual Admin
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        ⚡ POS Kasir
                                    </span>
                                @endif
                            </td>

                            <!-- Status & Hasil -->
                            <td class="py-3 px-4">
                                @if($log->status === 'SUCCESS')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        ✓ Sukses
                                    </span>
                                    @if($log->accurate_invoice_no)
                                        <div class="text-[11px] font-mono font-semibold text-blue-600 mt-1">
                                            {{ $log->accurate_invoice_no }}
                                        </div>
                                    @endif
                                @elseif($log->status === 'FAILED')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        ✕ Gagal
                                    </span>
                                    @if($log->error_category)
                                        <div class="text-[10px] font-bold text-rose-600 mt-0.5">
                                            {{ $log->category_label }}
                                        </div>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        ⏳ Pending
                                    </span>
                                @endif
                            </td>

                            <!-- Penyebab / Pesan Error -->
                            <td class="py-3 px-4 max-w-xs">
                                @if($log->status === 'FAILED' && $log->error_message)
                                    <div class="text-[11px] text-rose-700 line-clamp-2 font-medium" title="{{ $log->error_message }}">
                                        {{ $log->error_message }}
                                    </div>
                                @elseif($log->status === 'SUCCESS')
                                    <span class="text-[11px] text-gray-400 italic">Faktur & Nota berhasil dibuat</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-3 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button wire:click="openDetailModal({{ $log->id }})"
                                        class="p-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition" title="Lihat Rincian Log">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </button>

                                    @if($log->order && (empty($log->order->accurate_invoice_no) || $log->status === 'FAILED'))
                                        <button wire:click="retrySingleOrder({{ $log->order_id }})" wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 shadow-sm transition disabled:opacity-50"
                                            title="Coba sinkronkan ulang sekarang">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            Ulangi
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-10 h-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <p class="text-xs font-medium">Belum ada riwayat log sinkronisasi yang sesuai dengan filter.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Detail Modal -->
    @if($showDetailModal && $selectedLog)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 overflow-y-auto">
            <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4 border border-gray-100 animate-in fade-in zoom-in duration-150">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                            #
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">Rincian Log Sinkronisasi Accurate</h3>
                            <p class="text-xs text-gray-500">Order #{{ $selectedLog->order?->order_number ?? $selectedLog->order_id }}</p>
                        </div>
                    </div>
                    <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
                </div>

                <div class="space-y-3 text-xs">
                    <!-- Status Header Info -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-gray-50 p-3 rounded-xl border border-gray-100">
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Status</span>
                            <span class="font-bold {{ $selectedLog->status === 'SUCCESS' ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $selectedLog->status }}
                            </span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Pemicu</span>
                            <span class="font-semibold text-gray-800">{{ $selectedLog->trigger_type }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Waktu</span>
                            <span class="font-semibold text-gray-800">{{ $selectedLog->created_at->format('d/m/Y H:i:s') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Eksekutor</span>
                            <span class="font-semibold text-gray-800">{{ $selectedLog->executedBy->name ?? 'Sistem Otomatis' }}</span>
                        </div>
                    </div>

                    <!-- Error Message Box (jika ada) -->
                    @if($selectedLog->error_message)
                        <div class="bg-rose-50 border border-rose-200 p-3 rounded-xl space-y-1">
                            <div class="flex items-center gap-1.5 text-rose-800 font-bold text-xs">
                                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Kategori: {{ $selectedLog->category_label }}</span>
                            </div>
                            <p class="text-rose-700 font-mono text-[11px] whitespace-pre-wrap leading-relaxed">
                                {{ $selectedLog->error_message }}
                            </p>
                        </div>
                    @endif

                    <!-- Dokumen yang Dihasilkan -->
                    @if($selectedLog->accurate_invoice_no || $selectedLog->accurate_receipt_no)
                        <div class="bg-blue-50/60 border border-blue-200 p-3 rounded-xl space-y-1">
                            <span class="text-blue-900 font-bold text-xs block">Dokumen Berhasil Dibuat:</span>
                            <div class="flex items-center gap-4 text-xs font-mono font-bold text-blue-700">
                                <span>Faktur: {{ $selectedLog->accurate_invoice_no ?: '-' }}</span>
                                <span>Penerimaan: {{ $selectedLog->accurate_receipt_no ?: '-' }}</span>
                            </div>
                        </div>
                    @endif

                    <!-- Payload Snapshot Debugging -->
                    @if($selectedLog->payload_snapshot)
                        <div class="space-y-1">
                            <span class="text-gray-600 font-bold text-[11px] block">Snapshot Payload yang Dikirim ke Accurate:</span>
                            <pre class="bg-neutral-900 text-emerald-400 p-3 rounded-xl text-[10px] font-mono overflow-x-auto max-h-48 border border-neutral-800">{{ json_encode($selectedLog->payload_snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <div>
                        @if($selectedLog->order && (empty($selectedLog->order->accurate_invoice_no) || $selectedLog->status === 'FAILED'))
                            <button wire:click="retrySingleOrder({{ $selectedLog->order_id }})" wire:click.then="closeDetailModal"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Coba Sinkronkan Sekarang
                            </button>
                        @endif
                    </div>
                    <button wire:click="closeDetailModal"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

