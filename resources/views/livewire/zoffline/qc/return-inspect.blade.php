<div>
    <div class="mb-6">
        <a href="{{ route('zoffline.qc-returns') }}" wire:navigate class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-slate-800 transition-colors mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali ke Antrean
        </a>
        <h1 class="text-2xl font-bold text-slate-800">Inspeksi QC Barang Retur</h1>
        <p class="text-sm text-slate-500 mt-1">Lakukan Quality Control ulang terhadap barang cacat hasil klaim garansi.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        {{-- Kolom Kiri: Informasi Barang Retur (Klaim) --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-slate-50 border-b border-slate-200 px-5 py-4">
                    <h3 class="font-bold text-slate-800">Detail Klaim Pelanggan</h3>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <div class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-1">Perangkat (Barang Rusak)</div>
                        <div class="font-semibold text-slate-800">{{ $claim->warranty->orderItem->product_name ?? 'Unknown' }}</div>
                        <div class="text-sm text-slate-500 font-mono mt-0.5">IMEI: {{ $claim->serial_number ?? '-' }}</div>
                    </div>

                    <hr class="border-slate-100">

                    <div>
                        <div class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-1">Keluhan Pelanggan</div>
                        <div class="p-3 bg-red-50 text-red-800 rounded-lg text-sm border border-red-100 italic">
                            "{{ $claim->issue_description }}"
                        </div>
                    </div>

                    <hr class="border-slate-100">

                    <div>
                        <div class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-1">Status Resolusi Klaim</div>
                        <div class="text-sm text-slate-700">
                            Diganti dengan unit baru (IMEI: <span class="font-mono font-bold">{{ $claim->replacement_imei ?? '-' }}</span>)
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 text-sm text-blue-800">
                <h4 class="font-bold mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Catatan Inspektur
                </h4>
                <p class="leading-relaxed">
                    Pastikan memeriksa secara mendetail bagian yang dikeluhkan oleh pelanggan di atas. Hasil inspeksi ini akan menentukan kelayakan barang ini untuk masuk kembali ke gudang sebagai Grade B/C.
                </p>
            </div>
        </div>

        {{-- Kolom Kanan: Livewire Form QC --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <livewire:admin.qc.inspection-form 
                    :inspectableType="App\Models\WarrantyClaim::class"
                    :inspectableId="$claim->id"
                    :secondProductVariantId="null"
                    :imei="$claim->serial_number"
                    label="QC Barang Retur"
                    :hideHeader="true"
                    :wire:key="'qc-form-'.$claim->id"
                />
            </div>
        </div>

    </div>
</div>
