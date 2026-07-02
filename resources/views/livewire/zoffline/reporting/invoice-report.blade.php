<div class="p-6 bg-[#f7f7f7] min-h-screen">
    <div class="flex flex-col  justify-between items-start mb-6 gap-4">
        <div class="flex flex-col md:flex-row gap-2 justify-between w-full">
            <div class="">
                <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Laporan Pembayaran</h1>
                <p class="text-sm text-gray-500 mt-1">Rekapitulasi Pembayaran</p>
            </div>
            <div class="flex items-center bg-white border border-gray-200 rounded-xl px-3 py-2 shadow-sm">
                <span class="text-xs text-gray-500 mr-2 font-medium">Separator:</span>
                <select wire:model="csvSeparator"
                    class="text-sm border-none bg-transparent focus:ring-0 text-gray-700 p-0 font-medium cursor-pointer">
                    <option value=";">Semicolon (;)</option>
                    <option value=",">Comma (,)</option>
                </select>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
            {{-- <button wire:click="exportCsv" wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-green-500 hover:bg-green-600 disabled:opacity-75 disabled:cursor-wait text-white text-sm font-bold py-2 px-4 rounded-xl shadow-sm transition-colors">
                <svg wire:loading.remove wire:target="exportCsv" class="w-4 h-4" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <svg wire:loading wire:target="exportCsv" class="animate-spin w-4 h-4 text-white"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span wire:loading.remove wire:target="exportCsv">Export CSV (Kolom)</span>
                <span wire:loading wire:target="exportCsv">Memproses...</span>
            </button> --}}

            {{-- <button wire:click="exportCsvOpsi2" wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-blue-500 hover:bg-blue-600 disabled:opacity-75 disabled:cursor-wait text-white text-sm font-bold py-2 px-4 rounded-xl shadow-sm transition-colors">
                <svg wire:loading.remove wire:target="exportCsvOpsi2" class="w-4 h-4" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <svg wire:loading wire:target="exportCsvOpsi2" class="animate-spin w-4 h-4 text-white"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span wire:loading.remove wire:target="exportCsvOpsi2">Export CSV (Multi-Row)</span>
                <span wire:loading wire:target="exportCsvOpsi2">Memproses...</span>
            </button> --}}

            <div class="flex items-center gap-3 bg-white p-2 rounded-xl border border-gray-200 shadow-sm">
                <select wire:model.live="branchFilter"
                    class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent py-1.5 pl-3 pr-8 rounded-lg cursor-pointer hover:bg-gray-50">
                    <option value="">Semua Cabang</option>
                    @foreach ($availableBranches as $branch)
                        <option value="{{ $branch }}">{{ $branch }}</option>
                    @endforeach
                </select>

                <div class="h-6 w-px bg-gray-200"></div>

                <select wire:model.live="dateRange"
                    class="border-none text-sm font-medium focus:ring-0 text-gray-700 bg-transparent py-1.5 pl-3 pr-8 rounded-lg cursor-pointer hover:bg-gray-50">
                    <option value="today">Hari Ini</option>
                    <option value="yesterday">Kemarin</option>
                    <option value="this_week">Minggu Ini</option>
                    <option value="this_month">Bulan Ini</option>
                    <option value="this_year">Tahun Ini</option>
                    <option value="custom">Kustom</option>
                </select>

                @if ($dateRange === 'custom')
                    <div class="flex items-center gap-2 px-2 border-l border-gray-100">
                        <input type="date" wire:model.live="startDate"
                            class="border-gray-200 rounded-lg text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] py-1.5">
                        <span class="text-gray-400 text-sm">-</span>
                        <input type="date" wire:model.live="endDate"
                            class="border-gray-200 rounded-lg text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] py-1.5">
                    </div>
                @endif
            </div>
            <button wire:click="exportCsvOrderPayments" wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-indigo-500 hover:bg-indigo-600 disabled:opacity-75 disabled:cursor-wait text-white text-sm font-bold py-2 px-4 rounded-xl shadow-sm transition-colors">
                <svg wire:loading.remove wire:target="exportCsvOrderPayments" class="w-4 h-4" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <svg wire:loading wire:target="exportCsvOrderPayments" class="animate-spin w-4 h-4 text-white"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span wire:loading.remove wire:target="exportCsvOrderPayments">Export CSV</span>
                <span wire:loading wire:target="exportCsvOrderPayments">Memproses...</span>
            </button>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Transaksi</p>
            <h3 class="text-xl font-black text-gray-800">{{ number_format($summary['count']) }} <span
                    class="text-sm font-medium text-gray-400">Nota</span></h3>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)]">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Gross</p>
            <h3 class="text-xl font-bold text-gray-700">Rp {{ number_format($summary['gross'], 0, ',', '.') }}</h3>
        </div>
        <div
            class="bg-white rounded-2xl p-5 border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-16 h-16 bg-blue-50 rounded-full opacity-50"></div>
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Total Net Sales</p>
            <h3 class="text-xl font-black text-[#1c69d4]">Rp {{ number_format($summary['net'], 0, ',', '.') }}</h3>
        </div>
    </div>

    {{-- Data Table --}}
    <div
        class="bg-white rounded-2xl border border-gray-100 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <h3 class="font-bold text-gray-700 text-sm">Daftar Transaksi</h3>
            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Cari No Order / Pelanggan / Sales..."
                    class="w-64 sm:w-80 pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:border-[#1c69d4] focus:ring-[#1c69d4] bg-white">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b border-gray-100 text-[11px] uppercase tracking-wider text-gray-500">
                        <th class="px-5 py-4 font-bold">Tanggal & Nota</th>
                        <th class="px-5 py-4 font-bold">Pelanggan & Sales</th>
                        <th class="px-5 py-4 font-bold">Cabang</th>
                        <th class="px-5 py-4 font-bold">Pembayaran</th>
                        <th class="px-5 py-4 font-bold text-right">Gross</th>
                        <th class="px-5 py-4 font-bold text-right">Potongan</th>
                        <th class="px-5 py-4 font-bold text-right">Net Sales</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-5 py-3">
                                <p class="text-xs font-semibold text-gray-800">
                                    {{ $order->created_at->format('d M Y H:i') }}</p>
                                <p class="text-[11px] text-gray-500 mt-0.5 font-mono">{{ $order->order_number }}</p>
                                @if ($order->accurate_invoice_no)
                                    <span
                                        class="inline-block mt-1 px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded text-[10px] font-bold">Accurate:
                                        {{ $order->accurate_invoice_no }}</span>
                                @endif
                                <div class="mt-2 text-[10px] text-gray-500 leading-tight">
                                    @foreach ($order->items as $item)
                                        @php
                                            $variant = $item->variant;
                                            $name =
                                                $variant->name ??
                                                ($variant->product ? $variant->product->name : 'Unknown Product');
                                        @endphp
                                        <div class="truncate w-40 sm:w-48" title="{{ $name }}">
                                            {{ $name }} ({{ $item->qty }}x)</div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <p class="text-xs font-bold text-gray-800">
                                    {{ $order->user ? $order->user->name : 'Walk-in' }}</p>
                                <p class="text-[11px] text-gray-500 mt-0.5 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                        </path>
                                    </svg>
                                    {{ $order->salesBy ? $order->salesBy->name : '-' }}
                                </p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-md text-[11px] font-medium">
                                    {{ $order->branch->name ?? 'Unknown' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-xs font-medium text-gray-700">
                                @forelse ($order->payments as $payment)
                                    {{ $payment->paymentMethod->bank_name }}
                                    {{ $payment->paymentMethodRate->name ?? '-' }}<br>
                                    ({{ number_format($payment->amount, 0, ',', '.') }})
                                    <br>
                                @empty
                                    -
                                @endforelse
                            </td>
                            <td class="px-5 py-3 text-right">
                                <p class="text-xs font-bold text-gray-800">Rp
                                    {{ number_format($order->total_amount, 0, ',', '.') }}</p>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if ($order->discount_amount > 0)
                                    <p class="text-[11px] text-red-500 font-medium">Diskon: Rp
                                        {{ number_format($order->discount_amount, 0, ',', '.') }}</p>
                                @endif
                                @if ($order->mdr_amount > 0)
                                    <p class="text-[11px] text-orange-500 font-medium">MDR: Rp
                                        {{ number_format($order->mdr_amount, 0, ',', '.') }}</p>
                                @endif
                                @if ($order->discount_amount == 0 && $order->mdr_amount == 0)
                                    <p class="text-[11px] text-gray-400">-</p>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                @php $net = $order->grand_total - $order->mdr_amount; @endphp
                                <p class="text-sm font-black text-[#1c69d4]">Rp {{ number_format($net, 0, ',', '.') }}
                                </p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-gray-400 text-sm">
                                Tidak ada transaksi yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-100 bg-white">
            {{ $orders->links() }}
        </div>
    </div>
</div>
