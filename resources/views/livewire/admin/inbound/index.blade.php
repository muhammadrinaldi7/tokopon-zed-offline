<div>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800">Inbound (Purchase Orders)</h2>
        <button wire:click="syncPos" class="px-4 py-2 bg-blue-600 text-white rounded shadow hover:bg-blue-700">
            <i class="fas fa-sync-alt mr-2"></i> Sinkronisasi PO
        </button>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="mb-4">
            <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari PO Number atau Vendor..." class="w-full max-w-md p-2 border rounded border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-left text-sm text-gray-600 border-b border-gray-200">
                        <th class="p-3">Tanggal PO</th>
                        <th class="p-3">No. PO</th>
                        <th class="p-3">Vendor</th>
                        <th class="p-3">Total Item</th>
                        <th class="p-3">Progress QC</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-700">
                    @forelse($pos as $po)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition">
                            <td class="p-3">{{ \Carbon\Carbon::parse($po->po_date)->format('d M Y') }}</td>
                            <td class="p-3 font-semibold">{{ $po->po_number }}</td>
                            <td class="p-3">{{ $po->vendor->vendor_name ?? '-' }}</td>
                            <td class="p-3">{{ $po->items->sum('quantity_ordered') }} Unit</td>
                            <td class="p-3">
                                @php
                                    $ordered = $po->items->sum('quantity_ordered');
                                    $received = $po->items->sum('quantity_received');
                                    $percent = $ordered > 0 ? min(100, round(($received / $ordered) * 100)) : 0;
                                @endphp
                                <div class="w-full bg-gray-200 rounded-full h-2.5 mb-1">
                                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $percent }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500">{{ $received }} / {{ $ordered }} ({{ $percent }}%)</span>
                            </td>
                            <td class="p-3">
                                @if($po->status === 'COMPLETED')
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">Selesai</span>
                                @elseif($po->status === 'PARTIAL')
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-semibold">Parsial</span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-semibold">Menunggu</span>
                                @endif
                            </td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.inbound.scan', $po->id) }}" class="inline-block px-3 py-1 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 transition">
                                    Mulai QC <i class="fas fa-barcode ml-1"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-4 text-center text-gray-500">Belum ada data Purchase Order. Silakan klik Sinkronisasi PO.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $pos->links() }}
        </div>
    </div>
</div>
