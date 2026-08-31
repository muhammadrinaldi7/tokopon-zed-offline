<div>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">QC Barang Retur</h1>
            <p class="text-sm text-slate-500 mt-1">Daftar barang bekas retur pelanggan yang belum melalui Quality Control (QC).</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-slate-500 bg-slate-50 uppercase font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Tgl. Retur</th>
                        <th class="px-6 py-4">Pelanggan</th>
                        <th class="px-6 py-4">Perangkat & IMEI</th>
                        <th class="px-6 py-4">Masalah (Keluhan)</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($claims as $claim)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-slate-600">
                                {{ $claim->updated_at->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-800">{{ $claim->customer->name ?? 'Anonim' }}</div>
                                <div class="text-xs text-slate-500">{{ $claim->customer->phone ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-700">
                                    {{ $claim->warranty->orderItem->product_name ?? 'Unknown Device' }}
                                </div>
                                <div class="text-xs text-slate-500 font-mono mt-0.5">
                                    SN: {{ $claim->serial_number ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2.5 py-1 bg-red-50 text-red-700 text-xs font-medium rounded border border-red-100">
                                    {{ Str::limit($claim->issue_description, 50) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('zoffline.qc-returns.inspect', $claim->id) }}" wire:navigate 
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                    Mulai QC
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex justify-center mb-3">
                                    <svg class="w-12 h-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <p class="text-lg font-medium text-slate-600">Tidak ada barang retur</p>
                                <p class="text-sm">Semua barang retur sudah selesai melalui proses QC.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
