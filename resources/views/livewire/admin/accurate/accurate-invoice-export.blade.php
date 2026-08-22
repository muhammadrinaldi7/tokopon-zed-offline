<div class="p-6 bg-white rounded-lg shadow-md">

    @if ($messageSuccess)
        <div class="p-3 mb-4 text-green-700 bg-green-100 border border-green-300 rounded">
            {{ $messageSuccess }}
        </div>
    @endif
    @if ($messageError)
        <div class="p-3 mb-4 text-red-700 bg-red-100 border border-red-300 rounded">
            {!! $messageError !!}
        </div>
    @endif

    <div class="p-5 mb-8 bg-gray-50 border border-gray-200 rounded-lg">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="mb-2 text-lg font-semibold text-gray-800">1. Upload Data Excel (.xlsx)</h3>
                <p class="text-sm text-gray-600">
                    Unduh template, isi dengan data transaksi masa lalu di Microsoft Excel, lalu unggah ke sistem.
                </p>
            </div>

            <div class="flex gap-2">
                <button wire:click="downloadTemplate" type="button"
                    class="flex items-center px-4 py-2 text-sm font-semibold text-blue-700 bg-blue-100 border border-blue-300 rounded hover:bg-blue-200 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Unduh Template
                </button>

                <button wire:click="downloadMasterProduct" type="button"
                    class="flex items-center px-4 py-2 text-sm font-semibold text-green-700 bg-green-100 border border-green-300 rounded hover:bg-green-200 transition-colors" title="Referensi Kode Barang">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    Master Produk
                </button>

                <button wire:click="downloadMasterVendor" type="button"
                    class="flex items-center px-4 py-2 text-sm font-semibold text-purple-700 bg-purple-100 border border-purple-300 rounded hover:bg-purple-200 transition-colors" title="Referensi No Vendor">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Master Vendor
                </button>
            </div>
        </div>

        <form wire:submit="importData" class="flex items-center gap-4">
            <input type="file" wire:model="file" accept=".xlsx,.xls,.csv"
                class="block w-full text-sm text-gray-700 border border-gray-300 rounded cursor-pointer bg-white focus:outline-none file:mr-4 file:py-2 file:px-4 file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                required>
            <button type="submit"
                class="px-5 py-2 font-bold text-white bg-blue-600 rounded whitespace-nowrap hover:bg-blue-700 disabled:opacity-50 transition-colors"
                wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="importData">Proses & Simpan Draft</span>
                <span wire:loading wire:target="importData">Sedang Membaca Excel...</span>
            </button>
        </form>
        @error('file')
            <span class="block mt-2 text-sm text-red-600 font-medium">{{ $message }}</span>
        @enderror
    </div>

    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">2. Kirim Data ke Accurate</h3>
            <div class="flex items-center gap-2 mt-1">
                <p class="text-sm text-gray-600">Total data antrean draft: <span class="font-bold">{{ $draftInvoices->total() }}</span> Faktur</p>
                <button wire:click="$refresh" class="p-1 text-gray-500 hover:text-blue-600 transition-colors" title="Refresh Jumlah Antrean">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex gap-2">
            @if($draftInvoices->total() > 0)
            <button wire:click="clearDrafts" wire:confirm="Anda yakin ingin mengosongkan semua data draft? Data yang belum dikirim ke Accurate akan hilang."
                class="px-4 py-2 font-bold text-red-600 bg-red-100 border border-red-200 rounded hover:bg-red-200 focus:outline-none transition-colors shadow-sm flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Kosongkan Draft
            </button>
            @endif

            <button wire:click="pushToAccurateApi" wire:loading.attr="disabled"
                class="px-5 py-2 font-bold text-white bg-indigo-600 rounded hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 disabled:opacity-50 transition-colors shadow-sm">
                <span wire:loading.remove wire:target="pushToAccurateApi">
                    <svg class="inline w-4 h-4 mr-1 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                        </path>
                    </svg>
                    Mulai Sinkronisasi (Background)
                </span>
                <span wire:loading wire:target="pushToAccurateApi">
                    Menyiapkan Job...
                </span>
            </button>
        </div>
    </div>

    <div class="overflow-x-auto border border-gray-200 rounded-lg shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b border-gray-200">
                    <th class="p-3 text-sm font-semibold text-gray-700">No Faktur</th>
                    <th class="p-3 text-sm font-semibold text-gray-700">Tanggal</th>
                    <th class="p-3 text-sm font-semibold text-gray-700">ID Vendor Accurate</th>
                    <th class="p-3 text-sm font-semibold text-gray-700">Cabang</th>
                    <th class="p-3 text-sm font-semibold text-center text-gray-700">Jml Item</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($draftInvoices as $invoice)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="p-3 text-sm text-gray-800 font-medium">
                            {{ $invoice->invoice_no }}
                            @if($invoice->sync_error)
                                <div class="mt-1 text-xs text-red-600 bg-red-50 p-1.5 rounded border border-red-200">
                                    <span class="font-bold">Gagal:</span> {{ $invoice->sync_error }}
                                </div>
                            @endif
                        </td>
                        <td class="p-3 text-sm text-gray-600">
                            {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y') }}</td>
                        <td class="p-3 text-sm text-gray-600">{{ $invoice->vendor_id }}</td>
                        <td class="p-3 text-sm text-gray-600">{{ $invoice->branch_name ?? '-' }}</td>
                        <td class="p-3 text-sm text-center">
                            <span class="px-2.5 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full">
                                {{ $invoice->items_count }} Item
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Belum ada data draft. Silakan unggah file CSV di atas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $draftInvoices->links() }}
    </div>
</div>
