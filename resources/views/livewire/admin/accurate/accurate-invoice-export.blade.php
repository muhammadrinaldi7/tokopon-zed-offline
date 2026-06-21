<div class="p-6 bg-white rounded-lg shadow-md">

    @if (session()->has('success'))
        <div class="p-3 mb-4 text-green-700 bg-green-100 border border-green-300 rounded">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-3 mb-4 text-red-700 bg-red-100 border border-red-300 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="p-5 mb-8 bg-gray-50 border border-gray-200 rounded-lg">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="mb-2 text-lg font-semibold text-gray-800">1. Upload Data CSV (Erzap)</h3>
                <p class="text-sm text-gray-600">
                    Unduh template, isi dengan data transaksi masa lalu, lalu unggah ke sistem.
                </p>
            </div>

            <button wire:click="downloadTemplate" type="button"
                class="flex items-center px-4 py-2 text-sm font-semibold text-blue-700 bg-blue-100 border border-blue-300 rounded hover:bg-blue-200 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                Unduh Template CSV
            </button>
        </div>

        <form wire:submit="importData" class="flex items-center gap-4">
            <input type="file" wire:model="file" accept=".csv"
                class="block w-full text-sm text-gray-700 border border-gray-300 rounded cursor-pointer bg-white focus:outline-none file:mr-4 file:py-2 file:px-4 file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                required>
            <button type="submit"
                class="px-5 py-2 font-bold text-white bg-blue-600 rounded whitespace-nowrap hover:bg-blue-700 disabled:opacity-50 transition-colors"
                wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="importData">Proses & Simpan Draft</span>
                <span wire:loading wire:target="importData">Sedang Membaca CSV...</span>
            </button>
        </form>
        @error('file')
            <span class="block mt-2 text-sm text-red-600 font-medium">{{ $message }}</span>
        @enderror
    </div>

    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">2. Kirim Data ke Accurate</h3>
            <p class="text-sm text-gray-600">Total data siap dikirim: <span
                    class="font-bold">{{ $draftInvoices->total() }}</span> Faktur</p>
        </div>

        <button wire:click="pushToAccurateApi" wire:loading.attr="disabled"
            class="px-5 py-2 font-bold text-white bg-indigo-600 rounded hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 disabled:opacity-50 transition-colors shadow-sm">
            <span wire:loading.remove wire:target="pushToAccurateApi">
                <svg class="inline w-4 h-4 mr-1 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                    </path>
                </svg>
                Push ke API Accurate
            </span>
            <span wire:loading wire:target="pushToAccurateApi">
                Menyinkronkan Data...
            </span>
        </button>
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
                        <td class="p-3 text-sm text-gray-800 font-medium">{{ $invoice->invoice_no }}</td>
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
