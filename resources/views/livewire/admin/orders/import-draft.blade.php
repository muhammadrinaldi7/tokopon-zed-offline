<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Import Migrasi Transaksi</h1>
            <p class="text-sm text-gray-500 mt-1">Impor data penjualan dari CSV dan simpan otomatis sebagai Draft ZPOS.</p>
        </div>
        <a href="{{ route('admin.orders.management') }}" wire:navigate class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
            Kembali
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Panduan Import</h2>
        
        <div class="prose prose-sm text-gray-600 mb-6 max-w-none">
            <p>Sistem akan membaca file CSV Anda dan mengelompokkan baris berdasarkan <strong>Nama Customer</strong> dan <strong>Tanggal</strong>. Semua baris yang memiliki Nama Customer dan Tanggal yang sama akan disatukan ke dalam satu pesanan (Draft Order).</p>
            
            <p class="font-bold text-gray-800 mt-4 mb-2">Penjelasan Kolom (Wajib ikuti urutan ini pada CSV Anda):</p>
            <ul class="list-disc pl-5 space-y-1">
                <li><strong>Tanggal</strong> (Kolom 1): Tanggal transaksi. Contoh: <code>2026-05-31</code> atau <code>31/05/2026</code>. Pastikan format seragam.</li>
                <li><strong>Nama Customer</strong> (Kolom 2): Nama lengkap pelanggan.</li>
                <li><strong>No HP</strong> (Kolom 3): Nomor telepon/WA pelanggan. Berguna untuk pengecekan data ganda.</li>
                <li><strong>SKU</strong> (Kolom 4): Kode SKU barang di ZPOS. Harus sama persis dengan yang terdaftar di master data katalog produk.</li>
                <li><strong>SN</strong> (Kolom 5): Serial Number atau IMEI dari perangkat. Pisahkan dengan koma jika Qty lebih dari 1 (contoh: <code>SN1, SN2</code>).</li>
                <li><strong>Qty</strong> (Kolom 6): Jumlah kuantitas barang dibeli. Contoh: <code>1</code>.</li>
                <li><strong>Harga Satuan</strong> (Kolom 7): Harga asli per 1 pcs. Contoh: <code>10000000</code>.</li>
                <li><strong>Diskon Item</strong> (Kolom 8): Potongan harga tunai untuk item ini. Contoh: <code>500000</code>.</li>
                <li><strong>Catatan</strong> (Kolom 9): Keterangan/Catatan tambahan untuk pesanan ini.</li>
                <li><strong>Tenaga Penjual</strong> (Kolom 10): (Opsional) Nama pegawai atau No Pegawai (Sesuai dengan nama di menu Kelola Pegawai) agar transaksi ini tercatat penjualannya atas nama pegawai tersebut di ZPOS.</li>
            </ul>

            <div class="mt-4 p-4 bg-blue-50 text-blue-800 rounded-lg border border-blue-100 text-sm">
                <strong>Catatan Penting:</strong> Proses import mungkin membutuhkan waktu yang agak lama karena sistem akan menyinkronkan data langsung ke Accurate API secara *realtime*. Mohon tunggu sampai *loading* selesai.
            </div>
        </div>

        <div class="flex items-center gap-4 border-t border-gray-100 pt-6">
            <button wire:click="downloadTemplate" class="px-4 py-2 border border-[#1c69d4] text-[#1c69d4] rounded-lg hover:bg-[#1c69d4] hover:text-white transition-colors text-sm font-medium flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Unduh Template CSV
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Unggah File Transaksi</h2>
        
        <form wire:submit="processImport" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">File CSV Migrasi</label>
                <input type="file" wire:model="file" accept=".csv" class="block w-full text-sm text-gray-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-md file:border-0
                    file:text-sm file:font-semibold
                    file:bg-blue-50 file:text-blue-700
                    hover:file:bg-blue-100 border border-gray-200 rounded-md">
                @error('file') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            <button type="submit" 
                wire:loading.attr="disabled"
                class="px-4 py-2 bg-[#1c69d4] text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-bold flex items-center justify-center gap-2 w-full sm:w-auto">
                <span wire:loading.remove wire:target="processImport">Mulai Proses Import</span>
                <span wire:loading wire:target="processImport">Sedang Memproses (Mohon Tunggu)...</span>
            </button>
        </form>
    </div>

    @if($summary['total'] > 0)
        <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-800">Hasil Import</h3>
                <div class="flex gap-4 mt-2 text-sm">
                    <span class="text-gray-600">Total Dibaca: <strong class="text-gray-900">{{ $summary['total'] }} Order</strong></span>
                    <span class="text-green-600">Berhasil: <strong>{{ $summary['success'] }}</strong></span>
                    <span class="text-red-600">Gagal: <strong>{{ $summary['failed'] }}</strong></span>
                </div>
            </div>
            
            @if(count($results) > 0)
            <div class="max-h-96 overflow-y-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3">Nama Customer</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Pesan / Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($results as $res)
                        <tr class="{{ $res['status'] === 'Gagal' ? 'bg-red-50' : ($res['status'] === 'Peringatan' ? 'bg-yellow-50' : '') }}">
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $res['customer'] }}</td>
                            <td class="px-6 py-3">
                                @if($res['status'] === 'Berhasil')
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Sukses</span>
                                @elseif($res['status'] === 'Peringatan')
                                    <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded">Peringatan</span>
                                @else
                                    <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">Gagal</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-gray-600">{{ $res['message'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    @endif
</div>
