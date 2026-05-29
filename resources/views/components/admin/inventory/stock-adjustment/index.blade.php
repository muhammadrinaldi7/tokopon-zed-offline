<div class="p-6 max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6 border-b pb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Adjustment Product Stok</h1>
            <p class="text-sm text-gray-500">Sinkronisasi massal data penyesuaian persediaan ke Accurate via CSV</p>
        </div>
        <div class="text-xs bg-gray-100 px-3 py-1.5 rounded text-gray-600 font-mono">
            Zona Waktu: Asia/Makassar (WITA)
        </div>
    </div>

    @if ($message)
        <div wire:poll.2s="checkQueueStatus"
            class="mb-6 p-4 rounded-lg flex items-start justify-between {{ $isProcessing ? 'bg-blue-50 border border-blue-200 text-blue-800' : 'bg-green-50 border border-green-200 text-green-800' }}">
            <div class="flex items-center space-x-3">
                @if ($isProcessing)
                    <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                @else
                    <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                @endif
                <div>
                    <span
                        class="font-semibold block">{{ $isProcessing ? 'Proses Sinkronisasi Berjalan' : 'Selesai' }}</span>
                    <p class="text-sm opacity-90">{{ $message }}</p>
                </div>
            </div>
            <button wire:click="clearMessage" class="text-gray-400 hover:text-gray-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Unggah File CSV Gudang</h2>

            <div
                class="relative border-2 border-dashed border-gray-300 rounded-xl p-8 flex flex-col items-center justify-center bg-gray-50 hover:bg-gray-100 transition cursor-pointer group">
                <input type="file" wire:model="csv_file"
                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />

                <div class="text-center space-y-2 z-0">
                    <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-gray-600 transition" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <div class="flex text-sm text-gray-600">
                        <span class="relative font-medium text-indigo-600 hover:text-indigo-500">Klik untuk pilih file
                            CSV</span>
                        <p class="pl-1">atau seret file ke sini</p>
                    </div>
                    <p class="text-xs text-gray-400">Maksimal ukuran berkas 10MB (.CSV)</p>
                </div>
            </div>

            <div wire:loading wire:target="csv_file" class="mt-4 text-sm text-indigo-600 flex items-center space-x-2">
                <svg class="animate-spin h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span>Mengunggah file ke sistem, mohon tunggu sebentar...</span>
            </div>
        </div>

        <div
            class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
            <div class="flex items-center space-x-1.5">
                <span class="inline-block h-2 w-2 rounded-full bg-green-500"></span>
                <span>Koneksi API Accurate Siap</span>
            </div>
            <span>Gunakan template urutan kolom standar dari sistem.</span>
        </div>
    </div>
</div>
