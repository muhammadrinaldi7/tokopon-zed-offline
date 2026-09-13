<div class="p-6 bg-[#f7f7f7] min-h-screen">
    {{-- Header & Breadcrumbs --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('zoffline.reporting') }}" wire:navigate class="hover:text-blue-600 transition-colors flex items-center gap-1 font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Pusat Laporan
                </a>
                <span>/</span>
                <span class="text-gray-800 font-semibold">Laporan Pesan (WA & Email)</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Laporan Pesan & Notifikasi</h1>
            <p class="text-sm text-gray-500 mt-0.5">Tracking status <code class="text-xs bg-gray-200 px-1 py-0.5 rounded text-gray-700 font-mono">is_sent</code>, riwayat pengiriman WhatsApp dan Email, serta rincian apa yang dikirim ke pelanggan.</p>
        </div>

        <div class="flex items-center gap-3">
            @if ($canSync)
                {{-- Tombol Sinkron Data Lama --}}
                <button wire:click="syncHistoricalLogs" wire:loading.attr="disabled"
                    onclick="return confirm('Apakah Anda yakin ingin menyinkronkan seluruh transaksi lama yang sudah berstatus WA/Email terkirim ke dalam tabel laporan ini?') || event.stopImmediatePropagation()"
                    class="flex items-center gap-2 bg-white hover:bg-gray-50 border border-gray-200 text-gray-700 disabled:opacity-75 disabled:cursor-wait text-sm font-semibold py-2.5 px-4 rounded-xl shadow-xs transition-all hover:shadow-sm"
                    title="Sinkronkan data transaksi lama yang bernilai is_wa_sent / is_email_sent">
                    <svg wire:loading.remove wire:target="syncHistoricalLogs" class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <svg wire:loading wire:target="syncHistoricalLogs" class="animate-spin w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="syncHistoricalLogs">Sinkron Data Lama</span>
                    <span wire:loading wire:target="syncHistoricalLogs">Menyinkronkan...</span>
                </button>
            @endif

            {{-- Tombol Export Excel --}}
            <button wire:click="exportExcel" wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-75 disabled:cursor-wait text-white text-sm font-semibold py-2.5 px-4 rounded-xl shadow-sm transition-all hover:shadow-md">
                <svg wire:loading.remove wire:target="exportExcel" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <svg wire:loading wire:target="exportExcel" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span wire:loading.remove wire:target="exportExcel">Export Excel</span>
                <span wire:loading wire:target="exportExcel">Mengunduh...</span>
            </button>
        </div>
    </div>

    {{-- KPI Badges / Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Total Pesan --}}
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total Pengiriman</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($stats['total']) }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Semua log tercatat</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
            </div>
        </div>

        {{-- WhatsApp Sukses --}}
        <div class="bg-white p-5 rounded-2xl border border-emerald-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">WhatsApp Terkirim</p>
                <p class="text-2xl font-bold text-emerald-700 mt-1">{{ number_format($stats['wa_success']) }}</p>
                <p class="text-xs text-emerald-500 mt-0.5"><span class="font-bold">is_sent = true</span></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                </svg>
            </div>
        </div>

        {{-- Email Sukses --}}
        <div class="bg-white p-5 rounded-2xl border border-sky-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-sky-600">Email Terkirim</p>
                <p class="text-2xl font-bold text-sky-700 mt-1">{{ number_format($stats['email_success']) }}</p>
                <p class="text-xs text-sky-500 mt-0.5"><span class="font-bold">is_sent = true</span></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
        </div>

        {{-- Pengiriman Gagal --}}
        <div class="bg-white p-5 rounded-2xl border border-rose-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-rose-600">Pengiriman Gagal</p>
                <p class="text-2xl font-bold text-rose-700 mt-1">{{ number_format($stats['failed']) }}</p>
                <p class="text-xs text-rose-500 mt-0.5"><span class="font-bold">is_sent = false</span></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 mb-6">
        <div class="flex flex-wrap items-center gap-3">
            {{-- Rentang Tanggal --}}
            <div class="flex items-center bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 shadow-inner">
                <span class="text-xs text-gray-500 mr-2 font-medium">Periode:</span>
                <select wire:model.live="dateRange" class="text-xs sm:text-sm font-semibold border-none bg-transparent focus:ring-0 text-gray-800 p-0 cursor-pointer">
                    <option value="today">Hari Ini</option>
                    <option value="yesterday">Kemarin</option>
                    <option value="this_week">Minggu Ini</option>
                    <option value="this_month">Bulan Ini</option>
                    <option value="this_year">Tahun Ini</option>
                    <option value="custom">Kustom Tanggal</option>
                </select>
            </div>

            @if ($dateRange === 'custom')
                <div class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5">
                    <input type="date" wire:model.live="startDate" class="border-none bg-transparent p-0 text-xs sm:text-sm text-gray-700 focus:ring-0">
                    <span class="text-gray-400 text-xs">s/d</span>
                    <input type="date" wire:model.live="endDate" class="border-none bg-transparent p-0 text-xs sm:text-sm text-gray-700 focus:ring-0">
                </div>
            @endif

            {{-- Channel Filter --}}
            <div class="flex items-center bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 shadow-inner">
                <span class="text-xs text-gray-500 mr-2 font-medium">Saluran:</span>
                <select wire:model.live="channelFilter" class="text-xs sm:text-sm font-semibold border-none bg-transparent focus:ring-0 text-gray-800 p-0 cursor-pointer">
                    <option value="">Semua Saluran</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="email">Email</option>
                </select>
            </div>

            {{-- Status is_sent Filter --}}
            <div class="flex items-center bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 shadow-inner">
                <span class="text-xs text-gray-500 mr-2 font-medium">Status:</span>
                <select wire:model.live="statusFilter" class="text-xs sm:text-sm font-semibold border-none bg-transparent focus:ring-0 text-gray-800 p-0 cursor-pointer">
                    <option value="">Semua Status</option>
                    <option value="1">Terkirim (is_sent = 1)</option>
                    <option value="0">Gagal (is_sent = 0)</option>
                </select>
            </div>

            {{-- Admin Multi-BU / Branch Filter --}}
            @if ($isAdmin)
                <div class="flex items-center bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 shadow-inner">
                    <span class="text-xs text-gray-500 mr-2 font-medium">Unit:</span>
                    <select wire:model.live="businessUnitFilter" class="text-xs sm:text-sm font-semibold border-none bg-transparent focus:ring-0 text-gray-800 p-0 cursor-pointer">
                        <option value="">Semua Unit</option>
                        @foreach ($businessUnits as $bu)
                            <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center bg-gray-50 border border-gray-200 rounded-xl px-3 py-1.5 shadow-inner">
                    <span class="text-xs text-gray-500 mr-2 font-medium">Cabang:</span>
                    <select wire:model.live="branchFilter" class="text-xs sm:text-sm font-semibold border-none bg-transparent focus:ring-0 text-gray-800 p-0 cursor-pointer">
                        <option value="">Semua Cabang</option>
                        @foreach ($branches as $br)
                            <option value="{{ $br->id }}">{{ $br->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Search Bar --}}
            <div class="flex-1 min-w-[220px] relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari No. Ref, Customer, HP, Email, Konten..."
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl py-2 pl-9 pr-3 text-xs sm:text-sm text-gray-800 focus:bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-all placeholder-gray-400">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            {{-- Reset Filter Button --}}
            <button wire:click="resetFilters" class="text-xs font-semibold text-gray-500 hover:text-gray-800 py-2 px-3 hover:bg-gray-100 rounded-xl transition-colors">
                Reset
            </button>
        </div>
    </div>

    {{-- Main Data Table --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-gray-50 text-gray-500 font-semibold border-b border-gray-200 uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="py-3.5 px-4">Waktu Kirim</th>
                        <th class="py-3.5 px-3 text-center">Saluran</th>
                        <th class="py-3.5 px-4">Penerima</th>
                        <th class="py-3.5 px-4">Referensi</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4">Apa Yang Dikirim</th>
                        <th class="py-3.5 px-3 text-center">Lampiran</th>
                        <th class="py-3.5 px-4">Pengirim</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-blue-50/30 transition-colors">
                            {{-- Waktu Kirim --}}
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-medium text-gray-800">{{ $log->sent_at ? $log->sent_at->format('d M Y') : '-' }}</div>
                                <div class="text-[11px] text-gray-400">{{ $log->sent_at ? $log->sent_at->format('H:i:s') : '-' }}</div>
                            </td>

                            {{-- Channel Badge --}}
                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                @if ($log->channel === 'whatsapp')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                        </svg>
                                        WhatsApp
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200/60">
                                        <svg class="w-3.5 h-3.5 fill-none stroke-current" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        Email
                                    </span>
                                @endif
                            </td>

                            {{-- Penerima --}}
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-gray-800">{{ $log->recipient_name ?: 'Customer' }}</div>
                                <div class="text-xs font-mono text-gray-500">{{ $log->recipient }}</div>
                            </td>

                            {{-- Referensi --}}
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-mono font-semibold text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700 border border-gray-200">
                                    {{ $log->reference_number ?: '-' }}
                                </span>
                                <div class="text-[11px] text-gray-400 capitalize mt-0.5">{{ str_replace('_', ' ', $log->message_type ?: 'Pesan') }}</div>
                            </td>

                            {{-- Status is_sent --}}
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if ($log->is_sent)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Terkirim
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800" title="{{ $log->error_message }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Gagal
                                    </span>
                                @endif
                            </td>

                            {{-- Ringkasan Apa Yang Dikirim --}}
                            <td class="py-3.5 px-4 max-w-xs">
                                <p class="text-xs text-gray-600 line-clamp-2" title="{{ $log->content }}">
                                    {{ Str::limit($log->content ?: $log->subject ?: 'Tidak ada teks isi', 80) }}
                                </p>
                            </td>

                            {{-- Lampiran --}}
                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                @if ($log->attachment_name || $log->attachment_url || $log->source_id || $log->reference_number)
                                    <a href="{{ route('reporting.message-logs.attachment', $log->id) }}" target="_blank"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 hover:text-rose-900 text-xs font-semibold border border-rose-200/70 transition-all hover:scale-105 shadow-2xs"
                                        title="Buka / Unduh Lampiran (PDF): {{ $log->attachment_name ?: 'Struk Transaksi' }}">
                                        <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        <span>PDF</span>
                                    </a>
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>

                            {{-- Pengirim --}}
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="text-xs font-semibold text-gray-800">{{ $log->sentBy?->name ?: 'Sistem' }}</div>
                                <div class="text-[11px] text-gray-400">{{ $log->branch?->name ?: $log->businessUnit?->name ?: '-' }}</div>
                            </td>

                            {{-- Aksi --}}
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <button wire:click="viewDetails({{ $log->id }})"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-gray-100 hover:bg-blue-600 hover:text-white text-gray-700 text-xs font-semibold transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="text-base font-semibold text-gray-600">Tidak ada riwayat pengiriman pesan</p>
                                <p class="text-xs text-gray-400 mt-1">Coba sesuaikan filter pencarian atau rentang tanggal.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($logs->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    {{-- Detail Modal ("Ada Apa Yang Dikirim") --}}
    @if ($showDetailModal && $selectedLog)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full overflow-hidden border border-gray-100 animate-in fade-in zoom-in-95 duration-200">
                {{-- Modal Header --}}
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50 to-white">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Rincian Pengiriman</span>
                            @if ($selectedLog->is_sent)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">Terkirim (is_sent = 1)</span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">Gagal (is_sent = 0)</span>
                            @endif
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mt-1">
                            {{ $selectedLog->channel === 'whatsapp' ? 'Pesan WhatsApp (Qontak)' : 'Pesan Email' }}
                            @if ($selectedLog->reference_number)
                                <span class="text-blue-600">#{{ $selectedLog->reference_number }}</span>
                            @endif
                        </h3>
                    </div>
                    <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-600 p-2 rounded-full hover:bg-gray-100 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-6 max-h-[75vh] overflow-y-auto space-y-6">
                    {{-- Metadata Cards --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 bg-gray-50 p-4 rounded-2xl border border-gray-200/60 text-xs">
                        <div>
                            <span class="text-gray-400 block font-medium">Penerima</span>
                            <span class="font-bold text-gray-800 block text-sm">{{ $selectedLog->recipient_name ?: 'Customer' }}</span>
                            <span class="font-mono text-gray-500">{{ $selectedLog->recipient }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block font-medium">Waktu Pengiriman</span>
                            <span class="font-semibold text-gray-800 block">{{ $selectedLog->sent_at ? $selectedLog->sent_at->format('d/m/Y H:i:s') : '-' }}</span>
                            <span class="text-[11px] text-gray-400">{{ $selectedLog->sent_at ? $selectedLog->sent_at->diffForHumans() : '' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block font-medium">Pengirim (User)</span>
                            <span class="font-semibold text-gray-800 block">{{ $selectedLog->sentBy?->name ?: 'Sistem' }}</span>
                            <span class="text-gray-500">{{ $selectedLog->branch?->name ?: '-' }}</span>
                        </div>
                        @if ($selectedLog->subject)
                            <div class="col-span-2 sm:col-span-3 pt-2 border-t border-gray-200">
                                <span class="text-gray-400 block font-medium">Subjek / Template</span>
                                <span class="font-semibold text-gray-800">{{ $selectedLog->subject }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Error Notice if Failed --}}
                    @if (!$selectedLog->is_sent && $selectedLog->error_message)
                        <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 text-xs">
                            <div class="flex items-center gap-2 text-rose-700 font-bold mb-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Penyebab Kegagalan Pengiriman:
                            </div>
                            <p class="text-rose-600 font-mono whitespace-pre-wrap">{{ $selectedLog->error_message }}</p>
                        </div>
                    @endif

                    {{-- Konten Yang Dikirim --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Rincian Apa Yang Dikirim (Konten Pesan)
                            </h4>
                            <span class="text-xs text-gray-400">Teks lengkap terkirim</span>
                        </div>
                        <div class="bg-gray-900 text-gray-100 rounded-2xl p-4 text-xs font-mono whitespace-pre-wrap leading-relaxed border border-gray-800 max-h-60 overflow-y-auto">
                            {{ $selectedLog->content ?: 'Tidak ada rangkuman konten tersimpan.' }}
                        </div>
                    </div>

                    {{-- Lampiran Dokumen / PDF Attachment --}}
                    @if ($selectedLog->attachment_url || $selectedLog->attachment_name || $selectedLog->source_id || $selectedLog->reference_number)
                        <div class="bg-gradient-to-r from-blue-50/70 via-sky-50/50 to-indigo-50/70 border border-blue-200/90 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm hover:border-blue-300 transition-all">
                            <a href="{{ route('reporting.message-logs.attachment', $selectedLog->id) }}" target="_blank" class="flex items-center gap-3.5 group flex-1">
                                <div class="w-11 h-11 rounded-xl bg-rose-100/80 text-rose-600 flex items-center justify-center group-hover:scale-105 group-hover:bg-rose-600 group-hover:text-white transition-all shadow-xs">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-xs font-bold text-gray-800 group-hover:text-blue-600 transition-colors">Lampiran Dokumen Resmi</p>
                                        <span class="text-[10px] uppercase font-bold tracking-wider px-1.5 py-0.5 rounded bg-rose-100 text-rose-700">PDF</span>
                                    </div>
                                    <p class="text-xs text-gray-600 font-mono group-hover:underline underline-offset-2">{{ $selectedLog->attachment_name ?: ('Struk_' . ($selectedLog->reference_number ?: $selectedLog->id) . '.pdf') }}</p>
                                </div>
                            </a>
                            <a href="{{ route('reporting.message-logs.attachment', $selectedLog->id) }}" target="_blank"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold shadow-sm transition-all hover:shadow-md hover:-translate-y-0.5 whitespace-nowrap">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                <span>Buka / Unduh Berkas</span>
                            </a>
                        </div>
                    @endif

                    {{-- Technical Debug Accordion (Payload & Response) --}}
                    @if ($selectedLog->payload || $selectedLog->response_payload)
                        <div x-data="{ open: false }" class="border border-gray-200 rounded-2xl overflow-hidden">
                            <button @click="open = !open" type="button" class="w-full px-4 py-2.5 bg-gray-50 text-left text-xs font-semibold text-gray-600 flex items-center justify-between hover:bg-gray-100 transition-colors">
                                <span>Informasi Teknis & Payload API (Debug)</span>
                                <svg class="w-4 h-4 transform transition-transform" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" class="p-4 bg-gray-900 text-gray-300 font-mono text-[11px] overflow-x-auto space-y-3">
                                @if ($selectedLog->payload)
                                    <div>
                                        <p class="text-yellow-400 font-bold mb-1">// Outgoing Request Payload</p>
                                        <pre class="whitespace-pre-wrap">{{ json_encode($selectedLog->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                @endif
                                @if ($selectedLog->response_payload)
                                    <div>
                                        <p class="text-emerald-400 font-bold mb-1">// API Response Data</p>
                                        <pre class="whitespace-pre-wrap">{{ json_encode($selectedLog->response_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-end">
                    <button wire:click="closeDetailModal" class="px-5 py-2 rounded-xl bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs font-bold transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
