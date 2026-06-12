<div>
    <div class="mb-4 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Antrean QC Inbound (Vendor)</h1>
            <p class="text-sm text-gray-500">Daftar produk second dari penerimaan barang (vendor) yang memerlukan Quality Control.</p>
        </div>
        <div class="flex items-center gap-3">
            <select wire:model.live="filterQcStatus" class="border border-gray-300 rounded-lg text-sm py-2 px-3 focus:ring-[#1c69d4] focus:border-[#1c69d4] bg-white cursor-pointer appearance-none">
                <option value="pending">Belum QC (Antrean)</option>
                <option value="done">Sudah QC</option>
                <option value="all">Semua Produk</option>
            </select>
            <div class="relative">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari IMEI / SKU..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] w-full min-w-[200px]">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">SKU / Item No</th>
                        <th scope="col" class="px-6 py-3">IMEI / SN</th>
                        <th scope="col" class="px-6 py-3">Tanggal Terima</th>
                        <th scope="col" class="px-6 py-3">Status QC</th>
                        <th scope="col" class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                {{ $item->item_no }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-mono text-blue-600 font-semibold">{{ $item->serial_number }}</span>
                            </td>
                            <td class="px-6 py-4">
                                {{ $item->receipt_date ? \Carbon\Carbon::parse($item->receipt_date)->format('d M Y') : '-' }}
                            </td>
                            <td class="px-6 py-4">
                                @if($item->qc_status === 'Pending Inbound')
                                    <span class="bg-yellow-100 text-yellow-800 text-[11px] font-bold px-2.5 py-1 rounded border border-yellow-300 uppercase">
                                        Antrean
                                    </span>
                                @elseif(str_contains(strtolower($item->qc_status), 'pass'))
                                    <span class="bg-green-100 text-green-800 text-[11px] font-bold px-2.5 py-1 rounded border border-green-300 uppercase">
                                        Lolos QC
                                    </span>
                                @else
                                    <span class="bg-red-100 text-red-800 text-[11px] font-bold px-2.5 py-1 rounded border border-red-300 uppercase">
                                        {{ $item->qc_status }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($item->qc_status === 'Pending Inbound')
                                    <button type="button"
                                        wire:click="openQcModal({{ $item->id }}, '{{ $item->serial_number }}')"
                                        class="px-3 py-1.5 bg-[#1c69d4] text-white rounded-lg text-xs font-bold hover:bg-blue-700 transition inline-flex items-center gap-1 shadow-sm"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                        Lakukan QC
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400 font-semibold italic border border-gray-200 px-3 py-1.5 rounded-lg bg-gray-50">Selesai</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                                <p>Tidak ada antrean QC Inbound saat ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $items->links() }}
        </div>
    </div>

    <!-- QC Modal -->
    @if ($showQcModal && $selectedSnId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Form Quality Control Inbound</h3>
                    <button wire:click="$set('showQcModal', false)" class="text-gray-400 hover:text-rose-500 font-bold">&times;</button>
                </div>
                <div class="p-4 overflow-y-auto flex-1">
                    <div class="mb-4 p-4 bg-blue-50 border border-blue-100 rounded-lg">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            <span class="text-blue-900 font-medium">IMEI: <span class="font-mono">{{ $selectedImei }}</span></span>
                        </div>
                    </div>
                    
                    {{-- We use key() to force component re-render when selectedSnId changes --}}
                    @livewire('admin.qc.inspection-form', [
                        'inspectableType' => \App\Models\ProductSerialNumber::class,
                        'inspectableId' => $selectedSnId,
                        'label' => 'QC Inbound'
                    ], key('qc-form-'.$selectedSnId))
                </div>
                <div class="p-4 border-t border-gray-100 flex justify-end">
                    <button type="button" wire:click="$set('showQcModal', false)" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-lg transition">Batal</button>
                </div>
            </div>
        </div>
    @endif
</div>
