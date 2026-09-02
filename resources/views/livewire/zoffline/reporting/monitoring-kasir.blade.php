<div>
    {{-- Close your eyes. Count to one. That is how long forever feels. --}}
    <div class="min-h-screen p-6">
        <div class="max-w-7xl mx-auto">
            
            {{-- Header --}}
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Monitoring Kasir</h1>
                    <p class="text-neutral-500 text-sm mt-1">Rekapitulasi transaksi kasir (Hari Ini) - Cabang: {{ $branchName }}</p>
                </div>

            </div>

            {{-- Table Card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50 border-b border-gray-100">
                                <th class="py-4 px-6 text-sm font-semibold text-gray-600">Nama Kasir</th>
                                <th class="py-4 px-6 text-sm font-semibold text-gray-600 text-center">Jumlah Struk</th>
                                <th class="py-4 px-6 text-sm font-semibold text-gray-600 text-right">Nominal Tunai</th>
                                <th class="py-4 px-6 text-sm font-semibold text-gray-600 text-right">Nominal Settle</th>
                                <th class="py-4 px-6 text-sm font-semibold text-gray-600 text-right">Status (Selisih)</th>
                                <th class="py-4 px-6 text-sm font-semibold text-gray-600 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($monitoringData as $data)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-blue-100/50 flex items-center justify-center text-blue-600 font-bold">
                                                {{ strtoupper(substr($data['nama'], 0, 1)) }}
                                            </div>
                                            <span class="font-medium text-gray-800">{{ $data['nama'] }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-center text-gray-700 font-medium">
                                        {{ number_format($data['jumlah_struk'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-4 px-6 text-right text-gray-800 font-semibold">
                                        Rp {{ number_format($data['nominal_tunai'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-4 px-6 text-right text-neutral-400">
                                        {{ $data['nominal_settle'] !== null ? 'Rp ' . number_format($data['nominal_settle'], 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="py-4 px-6 text-right text-neutral-400">
                                        {{ $data['selisih'] !== null ? 'Rp ' . number_format($data['selisih'], 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <button wire:click="openModalDetail({{ $data['kasir_id'] }})" class="px-4 py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 font-semibold text-sm rounded-xl transition-colors">
                                            Lihat Detail
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 px-6 text-center text-neutral-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                            </svg>
                                            <p>Belum ada transaksi hari ini.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- Modal Detail Kasir --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl w-full max-w-4xl max-h-[90vh] flex flex-col shadow-2xl overflow-hidden">
            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <h3 class="text-xl font-bold text-gray-800">Detail Transaksi Tunai</h3>
                <button wire:click="closeModalDetail" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            {{-- Body --}}
            <div class="p-6 overflow-y-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="py-3 px-4 text-sm font-semibold text-gray-600">No Transaksi</th>
                            <th class="py-3 px-4 text-sm font-semibold text-gray-600 text-right">Nominal Tunai</th>
                            <th class="py-3 px-4 text-sm font-semibold text-gray-600 text-center">Monitoring By</th>
                            <th class="py-3 px-4 text-sm font-semibold text-gray-600 text-right">Aksi / Settle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($kasirOrders as $order)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-3 px-4 font-medium text-gray-800">{{ $order['order_number'] }}</td>
                                <td class="py-3 px-4 text-right font-semibold">Rp {{ number_format($order['nominal_tunai'], 0, ',', '.') }}</td>
                                <td class="py-3 px-4 text-center text-gray-600">
                                    @if($order['settlement'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ $order['settlement']['monitoring_by_name'] ?? 'Admin' }}
                                        </span>
                                    @else
                                        <span class="text-neutral-400 italic">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    @if($order['settlement'])
                                        <div class="text-sm text-gray-600 font-medium">
                                            Settle: Rp {{ number_format($order['settlement']['nominal_settle'], 0, ',', '.') }}
                                        </div>
                                        @if($order['settlement']['selisih'] != 0)
                                            <div class="text-xs text-red-500">
                                                Selisih: Rp {{ number_format($order['settlement']['selisih'], 0, ',', '.') }}
                                            </div>
                                        @endif
                                    @else
                                        <div class="flex items-center justify-end gap-2" x-data="{ 
                                            val: @entangle('formSettle.' . $order['order_id']),
                                            formatValue(v) {
                                                if (!v) return '';
                                                let num = parseInt(v.toString().replace(/\D/g, '')) || 0;
                                                return num.toLocaleString('id-ID');
                                            }
                                        }" x-init="val = formatValue(val)">
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 sm:text-sm">Rp</span>
                                                </div>
                                                <input type="text" 
                                                    x-model="val"
                                                    @input="val = formatValue($event.target.value)"
                                                    class="w-32 pl-8 pr-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-right"
                                                >
                                            </div>
                                            <button wire:click="terimaSettlement({{ $order['order_id'] }})"
                                                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                                                Terima
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-gray-500">Tidak ada transaksi tunai</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
