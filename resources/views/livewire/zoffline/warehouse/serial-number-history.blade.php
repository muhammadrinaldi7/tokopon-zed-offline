<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('zoffline.check-serial-number') }}" wire:navigate
                class="text-sm font-medium text-gray-500 hover:text-blue-600 flex items-center gap-1 mb-2 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Pencarian
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Riwayat Serial Number</h1>
            <p class="text-gray-500 text-sm mt-1">Perjalanan masuk dan keluar barang untuk SN/IMEI: <span
                    class="font-mono font-bold text-gray-800">{{ $sn }}</span></p>
        </div>

        @if ($productSn)
            @php
                $statusColors = [
                    'Available' => 'bg-green-100 text-green-800 border-green-200',
                    'Sold' => 'bg-blue-100 text-blue-800 border-blue-200',
                    'Reserved' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                    'Unavailable' => 'bg-red-100 text-red-800 border-red-200',
                ];
                $colorClass = $statusColors[$productSn->status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
            @endphp
            <div class="hidden md:flex flex-col items-end">
                <span class="text-xs text-gray-500 font-bold uppercase tracking-wider mb-1">Status Saat Ini</span>
                <span class="px-4 py-1.5 text-sm font-bold rounded-full border {{ $colorClass }}">
                    {{ strtoupper($productSn->status) }}
                </span>
            </div>
        @endif
    </div>

    @if (empty($history))
        <div
            class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center flex flex-col items-center justify-center">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 mb-4">
                <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Riwayat Tidak Ditemukan</h3>
            <p class="text-gray-500">Belum ada catatan aktivitas (pembelian/penjualan) untuk Serial Number ini.</p>
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 md:p-10 relative overflow-hidden">
            <!-- Timeline Container -->
            <div class="relative max-w-4xl mx-auto">
                <!-- Vertical Line -->
                <div
                    class="absolute left-[39px] md:left-1/2 top-8 bottom-8 w-0.5 bg-gradient-to-b from-blue-200 via-gray-200 to-emerald-200 md:-translate-x-1/2">
                </div>

                <div class="space-y-12 relative z-10">
                    @foreach ($history as $index => $item)
                        @php
                            $isInbound = $item['type'] === 'inbound';
                            $isEven = $index % 2 === 0;
                        @endphp

                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between w-full group">

                            <!-- Left Side (For desktop: Content if even, Empty if odd) -->
                            <div class="hidden md:block w-5/12 {{ $isEven ? 'text-right pr-8' : 'invisible' }}">
                                @if ($isEven)
                                    <div class="text-sm font-bold text-gray-400 mb-1">
                                        {{ \Carbon\Carbon::parse($item['date'])->format('d M Y - H:i') }}</div>
                                    <h3
                                        class="text-lg font-bold {{ $isInbound ? 'text-blue-700' : 'text-emerald-700' }}">
                                        {{ $item['title'] }}</h3>
                                    <p class="text-gray-600 font-medium text-sm mt-1">{{ $item['actor'] }}</p>
                                    <p class="text-gray-400 text-xs mt-1">{{ $item['notes'] }}</p>

                                    <!-- Pricing Card (Visible on Left Side for Even items) -->
                                    <div
                                        class="mt-4 inline-block text-left bg-gray-50 border border-gray-100 rounded-xl p-4 shadow-sm relative group-hover:border-{{ $isInbound ? 'blue' : 'emerald' }}-200 transition-colors w-full max-w-sm">
                                        <div class="flex justify-between items-center">
                                            <span
                                                class="text-xs font-bold text-gray-500 uppercase">{{ $isInbound ? 'Harga Beli (HPP)' : 'Harga Jual' }}</span>
                                            <span class="text-lg font-black text-gray-800">Rp
                                                {{ number_format($item['price'], 0, ',', '.') }}</span>
                                        </div>

                                        @if (!$isInbound && $item['profit'] !== null)
                                            <div
                                                class="mt-3 pt-3 border-t border-gray-200 flex justify-between items-center">
                                                <span class="text-xs font-bold text-gray-500 uppercase">Margin /
                                                    Selisih</span>
                                                <span
                                                    class="text-sm font-bold {{ $item['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                    {{ $item['profit'] >= 0 ? '+' : '' }} Rp
                                                    {{ number_format($item['profit'], 0, ',', '.') }}
                                                </span>
                                            </div>
                                        @endif

                                        @if ($item['doc_link'])
                                            <div class="mt-3 text-right">
                                                <a href="{{ $item['doc_link'] }}"
                                                    class="inline-flex items-center gap-1 text-xs font-bold text-[#1c69d4] hover:underline">
                                                    Lihat Dokumen
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                    </svg>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <!-- Center Node -->
                            <div class="absolute left-6 md:static md:w-2/12 flex justify-center mt-2 md:mt-0">
                                <div
                                    class="w-10 h-10 rounded-full border-4 border-white shadow-md flex items-center justify-center transition-transform group-hover:scale-110 {{ $isInbound ? 'bg-blue-500' : 'bg-emerald-500' }}">
                                    @if ($isInbound)
                                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                        </svg>
                                    @endif
                                </div>
                            </div>

                            <!-- Right Side (For desktop: Content if odd, Empty if even. For mobile: Always content) -->
                            <div
                                class="w-full md:w-5/12 pl-20 md:pl-8 {{ !$isEven ? 'block' : 'block md:invisible' }}">
                                <!-- Only visible on mobile if Even (because desktop handles it on left) -->
                                <div class="{{ $isEven ? 'block md:hidden' : 'block' }}">
                                    <div class="text-sm font-bold text-gray-400 mb-1">
                                        {{ \Carbon\Carbon::parse($item['date'])->format('d M Y - H:i') }}</div>
                                    <h3
                                        class="text-lg font-bold {{ $isInbound ? 'text-blue-700' : 'text-emerald-700' }}">
                                        {{ $item['title'] }}</h3>
                                    <p class="text-gray-600 font-medium text-sm mt-1">{{ $item['actor'] }}</p>
                                    <p class="text-gray-400 text-xs mt-1">{{ $item['notes'] }}</p>
                                </div>

                                <!-- Pricing Card (Visible on whichever side has content) -->
                                <div
                                    class="mt-4 bg-gray-50 border border-gray-100 rounded-xl p-4 shadow-sm relative group-hover:border-{{ $isInbound ? 'blue' : 'emerald' }}-200 transition-colors">
                                    <div class="flex justify-between items-center">
                                        <span
                                            class="text-xs font-bold text-gray-500 uppercase">{{ $isInbound ? 'Harga Beli (HPP)' : 'Harga Jual' }}</span>
                                        <span class="text-lg font-black text-gray-800">Rp
                                            {{ number_format($item['price'], 0, ',', '.') }}</span>
                                    </div>

                                    @if (!$isInbound && $item['profit'] !== null)
                                        <div
                                            class="mt-3 pt-3 border-t border-gray-200 flex justify-between items-center">
                                            <span class="text-xs font-bold text-gray-500 uppercase">Margin /
                                                Selisih</span>
                                            <span
                                                class="text-sm font-bold {{ $item['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $item['profit'] >= 0 ? '+' : '' }} Rp
                                                {{ number_format($item['profit'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @endif

                                    @if ($item['doc_link'])
                                        <a href="{{ $item['doc_link'] }}"
                                            class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-[#1c69d4] hover:underline">
                                            Lihat Dokumen
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
