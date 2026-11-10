<div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-500 to-amber-500 flex items-center justify-center text-white shadow-md shadow-rose-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 tracking-tight">Accurate Sync Issue Tracker</h1>
                    <p class="text-xs text-gray-500">Pusat pemantauan pasif kendala & kegagalan sinkronisasi webhook Accurate ke database</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Passive Monitor Active
            </span>
            <button wire:click="$refresh" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-white text-gray-700 border border-gray-200 hover:bg-gray-50 shadow-sm transition">
                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Segarkan
            </button>
        </div>
    </div>

    <!-- Flash Message -->
    @if ($flashMessage)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ $flashMessage }}</span>
            </div>
            <button @click="show = false" class="text-emerald-500 hover:text-emerald-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Open Issues -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Kendala Terbuka</p>
                    <p class="text-2xl font-black text-gray-900 mt-1">{{ number_format($totalOpen) }}</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-gray-500">
                <span class="text-rose-600 font-semibold mr-1">{{ $totalOpen > 0 ? 'Perlu dievaluasi' : 'Semua aman' }}</span>
                <span>pada webhook Accurate</span>
            </div>
        </div>

        <!-- Critical & Errors -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Kritis / Error</p>
                    <p class="text-2xl font-black text-rose-600 mt-1">{{ number_format($criticalErrors) }}</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-gray-500">
                <span>Membutuhkan perhatian langsung</span>
            </div>
        </div>

        <!-- Top Reason -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Kendala Terbanyak</p>
                    @if ($topReason)
                        @php $meta = \App\Services\SyncIssueRecorder::getReasonMeta($topReason->reason_code); @endphp
                        <p class="text-sm font-bold text-gray-900 mt-1 truncate" title="{{ $meta['label'] }}">{{ $meta['label'] }}</p>
                        <p class="text-xs text-amber-600 font-semibold mt-0.5">{{ number_format($topReason->count) }} kasus aktif</p>
                    @else
                        <p class="text-sm font-bold text-gray-400 mt-1">Tidak ada</p>
                    @endif
                </div>
                <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Resolved / Ignored -->
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Selesai / Diabaikan</p>
                    <p class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($totalResolved) }}</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-gray-500">
                <span>{{ number_format($totalIgnored) }} kendala diabaikan</span>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm space-y-4">
        <!-- Search and Quick Status Tabs -->
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <!-- Search input -->
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari SKU, Serial Number (IMEI), Nama Gudang, Event..."
                    class="w-full pl-9 pr-4 py-2 rounded-xl text-xs bg-gray-50 border border-gray-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1c69d4]/20 focus:border-[#1c69d4] transition"
                />
            </div>

            <!-- Status Pill Tabs -->
            <div class="inline-flex rounded-xl bg-gray-100 p-1 text-xs">
                <button
                    wire:click="$set('statusFilter', 'open')"
                    class="px-3 py-1.5 rounded-lg font-medium transition {{ $statusFilter === 'open' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                    Terbuka ({{ $totalOpen }})
                </button>
                <button
                    wire:click="$set('statusFilter', 'resolved')"
                    class="px-3 py-1.5 rounded-lg font-medium transition {{ $statusFilter === 'resolved' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                    Selesai ({{ $totalResolved }})
                </button>
                <button
                    wire:click="$set('statusFilter', 'ignored')"
                    class="px-3 py-1.5 rounded-lg font-medium transition {{ $statusFilter === 'ignored' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                    Diabaikan ({{ $totalIgnored }})
                </button>
                <button
                    wire:click="$set('statusFilter', '')"
                    class="px-3 py-1.5 rounded-lg font-medium transition {{ $statusFilter === '' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-900' }}">
                    Semua
                </button>
            </div>
        </div>

        <!-- Secondary Dropdown Filters -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 pt-2 border-t border-gray-100">
            <!-- Reason Code Filter -->
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Jenis Kendala</label>
                <select wire:model.live="reasonFilter" class="w-full py-1.5 px-2.5 rounded-lg text-xs bg-gray-50 border border-gray-200 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#1c69d4]">
                    <option value="">Semua Jenis</option>
                    @foreach ($availableReasons as $reason)
                        @php $meta = \App\Services\SyncIssueRecorder::getReasonMeta($reason); @endphp
                        <option value="{{ $reason }}">{{ $meta['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Severity Filter -->
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Tingkat Urgensi</label>
                <select wire:model.live="severityFilter" class="w-full py-1.5 px-2.5 rounded-lg text-xs bg-gray-50 border border-gray-200 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#1c69d4]">
                    <option value="">Semua Urgensi</option>
                    <option value="critical">Critical</option>
                    <option value="error">Error</option>
                    <option value="warning">Warning</option>
                    <option value="info">Info</option>
                </select>
            </div>

            <!-- Business Unit Filter -->
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Unit Usaha (BU)</label>
                <select wire:model.live="businessUnitFilter" class="w-full py-1.5 px-2.5 rounded-lg text-xs bg-gray-50 border border-gray-200 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#1c69d4]">
                    <option value="">Semua Unit Usaha</option>
                    @foreach ($businessUnits as $bu)
                        <option value="{{ $bu->id }}">{{ $bu->name }} ({{ strtoupper($bu->code) }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Reset Button -->
            <div class="flex items-end">
                <button
                    wire:click="resetFilters"
                    class="w-full py-1.5 px-3 rounded-lg text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition text-center">
                    Reset Filter
                </button>
            </div>
        </div>
    </div>

    <!-- Issues Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600">
                <thead class="bg-gray-50/80 text-[11px] uppercase tracking-wider text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="py-3.5 px-4 font-semibold">Tingkat & Jenis Kendala</th>
                        <th class="py-3.5 px-4 font-semibold">Unit Usaha</th>
                        <th class="py-3.5 px-4 font-semibold">SKU / SN</th>
                        <th class="py-3.5 px-4 font-semibold">Gudang</th>
                        <th class="py-3.5 px-4 font-semibold text-center">Frekuensi</th>
                        <th class="py-3.5 px-4 font-semibold">Terakhir Dilihat</th>
                        <th class="py-3.5 px-4 font-semibold text-center">Status</th>
                        <th class="py-3.5 px-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($issues as $issue)
                        @php
                            $meta = \App\Services\SyncIssueRecorder::getReasonMeta($issue->reason_code);
                            $severityClass = match ($issue->severity) {
                                'critical' => 'bg-rose-500 text-white',
                                'error' => 'bg-rose-100 text-rose-700 border-rose-200',
                                'warning' => 'bg-amber-100 text-amber-700 border-amber-200',
                                'info' => 'bg-sky-100 text-sky-700 border-sky-200',
                                default => 'bg-gray-100 text-gray-700 border-gray-200',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50/60 transition group">
                            <!-- Severity & Reason -->
                            <td class="py-3 px-4">
                                <div class="flex items-start gap-2">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border {{ $severityClass }} mt-0.5">
                                        {{ $issue->severity }}
                                    </span>
                                    <div>
                                        <div class="font-semibold text-gray-900 flex items-center gap-1.5">
                                            <span>{{ $meta['label'] }}</span>
                                            @if ($issue->event_type)
                                                <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-gray-100 text-gray-600">{{ $issue->event_type }}</span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-gray-400 mt-0.5 line-clamp-1" title="{{ $meta['description'] }}">
                                            {{ $meta['description'] }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Business Unit -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if ($issue->businessUnit)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                        {{ $issue->businessUnit->name }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            <!-- SKU / SN -->
                            <td class="py-3 px-4">
                                <div class="space-y-0.5">
                                    @if ($issue->item_no)
                                        <div class="font-mono font-medium text-gray-800 text-[11px]">
                                            <span class="text-gray-400">SKU:</span> {{ $issue->item_no }}
                                        </div>
                                    @endif
                                    @if ($issue->serial_number)
                                        <div class="font-mono text-emerald-700 text-[11px] flex items-center gap-1">
                                            <span class="text-gray-400">SN:</span> {{ $issue->serial_number }}
                                        </div>
                                    @endif
                                    @if (!$issue->item_no && !$issue->serial_number)
                                        <span class="text-gray-400 italic">Umum / Dokumen</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Gudang -->
                            <td class="py-3 px-4">
                                @if ($issue->warehouse_name)
                                    <span class="font-medium text-gray-700 text-[11px]">
                                        {{ $issue->warehouse_name }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            <!-- Occurrences Count -->
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold {{ $issue->occurrences > 5 ? 'bg-rose-100 text-rose-800' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $issue->occurrences }}x
                                </span>
                            </td>

                            <!-- Last seen -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="text-[11px] text-gray-700 font-medium">
                                    {{ $issue->last_seen_at ? $issue->last_seen_at->diffForHumans() : '-' }}
                                </div>
                                <div class="text-[10px] text-gray-400 font-mono">
                                    {{ $issue->last_seen_at ? $issue->last_seen_at->format('d/m/Y H:i') : '' }}
                                </div>
                            </td>

                            <!-- Status -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if ($issue->status === 'open')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Open
                                    </span>
                                @elseif ($issue->status === 'resolved')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Resolved
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 border border-gray-200">
                                        Ignored
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <button
                                    wire:click="showDetail({{ $issue->id }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-[#1c69d4]/10 text-[#1c69d4] hover:bg-[#1c69d4]/20 transition">
                                    <span>Detail</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-800">Tidak ada kendala sinkronisasi yang sesuai filter</p>
                                    <p class="text-xs text-gray-400 max-w-sm">Semua webhook Accurate diproses dengan aman atau belum ada data anomali yang tertangkap.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($issues->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $issues->links() }}
            </div>
        @endif
    </div>

    <!-- Detail Modal -->
    @if ($showDetailModal && $selectedIssue)
        @php
            $modalMeta = \App\Services\SyncIssueRecorder::getReasonMeta($selectedIssue->reason_code);
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm" x-data @keydown.escape.window="$wire.closeDetail()">
            <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden border border-gray-100">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <div class="flex items-center gap-2.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                            {{ $selectedIssue->severity === 'critical' || $selectedIssue->severity === 'error' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $selectedIssue->severity }}
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 leading-none">{{ $modalMeta['label'] }}</h3>
                            <p class="text-xs text-gray-400 mt-1 font-mono">Fingerprint: {{ substr($selectedIssue->fingerprint, 0, 16) }}...</p>
                        </div>
                    </div>
                    <button wire:click="closeDetail" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto space-y-5 text-xs text-gray-700">
                    <!-- Deskripsi Singkat -->
                    <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-100 text-blue-900 leading-relaxed">
                        <p class="font-semibold text-blue-950 mb-0.5">Penjelasan Masalah:</p>
                        {{ $modalMeta['description'] }}
                    </div>

                    <!-- Metadata Grid -->
                    <div class="grid grid-cols-2 gap-3 p-3.5 rounded-xl bg-gray-50 border border-gray-100">
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Unit Usaha</span>
                            <span class="font-semibold text-gray-900">{{ $selectedIssue->businessUnit?->name ?? '-' }} ({{ $selectedIssue->businessUnit?->code ?? '-' }})</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Tahap (Stage)</span>
                            <span class="font-mono text-gray-800">{{ $selectedIssue->stage }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">SKU (Item No)</span>
                            <span class="font-mono font-bold text-gray-900">{{ $selectedIssue->item_no ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Nomor Seri (SN)</span>
                            <span class="font-mono font-bold text-emerald-700">{{ $selectedIssue->serial_number ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Gudang Accurate</span>
                            <span class="font-medium text-gray-900">{{ $selectedIssue->warehouse_name ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Event Type Webhook</span>
                            <span class="font-mono text-gray-900">{{ $selectedIssue->event_type ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Pertama Kali Muncul</span>
                            <span class="text-gray-700">{{ $selectedIssue->first_seen_at ? $selectedIssue->first_seen_at->format('d/m/Y H:i:s') : '-' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Terakhir Terjadi ({{ $selectedIssue->occurrences }}x)</span>
                            <span class="text-gray-700">{{ $selectedIssue->last_seen_at ? $selectedIssue->last_seen_at->format('d/m/Y H:i:s') : '-' }}</span>
                        </div>
                    </div>

                    <!-- Expected vs Actual (if any) -->
                    @if ($selectedIssue->expected || $selectedIssue->actual)
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 rounded-xl bg-emerald-50/60 border border-emerald-100">
                                <span class="text-emerald-800 font-bold block text-[11px] mb-1">Ekspektasi Sistem:</span>
                                <pre class="font-mono text-[11px] text-emerald-950 overflow-x-auto">{{ json_encode($selectedIssue->expected, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                            <div class="p-3 rounded-xl bg-rose-50/60 border border-rose-100">
                                <span class="text-rose-800 font-bold block text-[11px] mb-1">Kondisi Aktual:</span>
                                <pre class="font-mono text-[11px] text-rose-950 overflow-x-auto">{{ json_encode($selectedIssue->actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            </div>
                        </div>
                    @endif

                    <!-- Context Data -->
                    @if ($selectedIssue->context)
                        <div>
                            <span class="text-gray-700 font-bold block text-xs mb-1.5">Context Tambahan:</span>
                            <pre class="p-3 rounded-xl bg-gray-900 text-gray-100 font-mono text-[11px] overflow-x-auto max-h-48 leading-relaxed">{{ json_encode($selectedIssue->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @endif

                    <!-- Related Webhook Log Info -->
                    @if ($selectedIssue->webhookLog)
                        <div class="p-3.5 rounded-xl bg-gray-50 border border-gray-200 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-gray-400 block">Terkait Webhook Log</span>
                                <span class="font-mono font-bold text-gray-800">Log #{{ $selectedIssue->webhook_log_id }} ({{ $selectedIssue->webhookLog->event_type }})</span>
                                <span class="text-gray-500 text-[11px] block mt-0.5">Status Log: <strong class="uppercase">{{ $selectedIssue->webhookLog->status }}</strong></span>
                            </div>
                            <button
                                wire:click="retryWebhook({{ $selectedIssue->webhook_log_id }})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white text-gray-700 border border-gray-300 hover:bg-gray-100 shadow-sm transition">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Retry Webhook Job
                            </button>
                        </div>
                    @endif

                    <!-- Resolution Section -->
                    <div class="space-y-2 pt-2 border-t border-gray-100">
                        <label class="block text-xs font-bold text-gray-800">Catatan Penyelesaian / Tindak Lanjut:</label>
                        <textarea
                            wire:model="resolutionNote"
                            rows="2"
                            placeholder="Tuliskan catatan perbaikan atau alasan jika diabaikan..."
                            class="w-full p-2.5 rounded-xl text-xs bg-gray-50 border border-gray-200 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#1c69d4]"
                        ></textarea>
                        @if ($selectedIssue->resolved_at)
                            <p class="text-[11px] text-gray-400">
                                Diselesaikan pada: {{ $selectedIssue->resolved_at->format('d/m/Y H:i') }}
                                @if ($selectedIssue->resolver)
                                    oleh <strong>{{ $selectedIssue->resolver->name }}</strong>
                                @endif
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3.5 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <div>
                        @if ($selectedIssue->status !== 'open')
                            <button
                                wire:click="reopen({{ $selectedIssue->id }})"
                                class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 transition">
                                Buka Kembali
                            </button>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($selectedIssue->status !== 'ignored')
                            <button
                                wire:click="markIgnored({{ $selectedIssue->id }})"
                                class="px-3.5 py-1.5 rounded-lg text-xs font-medium text-gray-600 bg-gray-200/80 hover:bg-gray-200 transition">
                                Abaikan
                            </button>
                        @endif

                        @if ($selectedIssue->status !== 'resolved')
                            <button
                                wire:click="markResolved({{ $selectedIssue->id }})"
                                class="px-4 py-1.5 rounded-lg text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition">
                                Tandai Selesai
                            </button>
                        @endif

                        <button
                            wire:click="closeDetail"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium text-gray-600 hover:text-gray-800 transition">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
