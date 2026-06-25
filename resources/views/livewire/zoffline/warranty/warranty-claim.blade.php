<div class="min-h-screen bg-gray-50 p-4 md:p-8">
    <div class="max-w-3xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <a href="{{ route('zoffline') }}" wire:navigate class="text-sm font-bold text-gray-500 hover:text-blue-600 inline-flex items-center gap-1 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali
            </a>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight">Klaim Garansi</h1>
        </div>

        @if ($isSubmitted)
            <div class="bg-emerald-50 border-2 border-dashed border-emerald-200 rounded-2xl p-8 text-center mt-8">
                <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-gray-900">Klaim Berhasil Diajukan</h3>
                <p class="text-gray-500 mt-2 max-w-sm mx-auto">
                    Klaim garansi telah masuk ke sistem dan menunggu persetujuan Manager.
                </p>
                <div class="mt-8 flex justify-center gap-4">
                    <button wire:click="resetForm" class="px-6 py-3 bg-white border border-emerald-200 text-emerald-700 font-bold rounded-xl hover:bg-emerald-100 transition-colors shadow-sm">
                        Ajukan Klaim Lainnya
                    </button>
                </div>
            </div>
        @else
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
                <div class="mb-8 border-b border-gray-100 pb-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-2">Cari Perangkat</h2>
                    <p class="text-sm text-gray-500 mb-4">Masukkan Serial Number / IMEI perangkat yang ingin diklaim.</p>
                    
                    <form wire:submit="searchWarranties" class="flex gap-3">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <input type="text" wire:model="searchQuery" class="w-full bg-gray-50 border-gray-200 rounded-xl pl-11 pr-4 py-3 text-sm font-mono focus:ring-blue-500 focus:border-blue-500" placeholder="Scan SN / IMEI...">
                        </div>
                        <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-colors shadow-sm whitespace-nowrap">
                            Cari Garansi
                        </button>
                    </form>
                    @error('searchQuery') <span class="text-xs text-rose-500 mt-2 block font-medium">{{ $message }}</span> @enderror
                </div>

                @if(count($foundWarranties) > 0)
                    <div class="mb-8">
                        <h3 class="text-sm font-black text-gray-400 uppercase tracking-wider mb-4">Pilih Garansi yang Ingin Diklaim</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($foundWarranties as $warranty)
                                @php
                                    $isExpired = $warranty->expires_at < \Carbon\Carbon::now();
                                    $isMaxClaimed = $warranty->claims_used >= $warranty->policy->max_claims;
                                    $isDisabled = $isExpired || $isMaxClaimed || $warranty->status !== 'active';
                                @endphp
                                <label class="relative cursor-pointer group">
                                    <input type="radio" wire:model="selectedWarrantyId" value="{{ $warranty->id }}" wire:click="selectWarranty({{ $warranty->id }})" class="peer hidden" {{ $isDisabled ? 'disabled' : '' }}>
                                    <div class="h-full border-2 rounded-2xl p-5 transition-all
                                        {{ $isDisabled ? 'border-gray-200 bg-gray-50 opacity-60 cursor-not-allowed' : 'border-gray-200 bg-white hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50/30' }}">
                                        
                                        <div class="flex justify-between items-start mb-3">
                                            <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $warranty->policy->type === 'insurance' ? 'bg-purple-100 text-purple-600' : 'bg-blue-100 text-blue-600' }}">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                            </div>
                                            <div class="text-right">
                                                @if($isExpired)
                                                    <span class="inline-block px-2 py-1 bg-rose-100 text-rose-700 text-[10px] font-bold rounded-md">Kedaluwarsa</span>
                                                @elseif($isMaxClaimed)
                                                    <span class="inline-block px-2 py-1 bg-amber-100 text-amber-700 text-[10px] font-bold rounded-md">Limit Klaim</span>
                                                @else
                                                    <span class="inline-block px-2 py-1 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-md">Aktif</span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <h4 class="font-bold text-gray-900 mb-1 line-clamp-1">{{ $warranty->policy->name }}</h4>
                                        <div class="space-y-1.5 mt-3 text-xs text-gray-500">
                                            <div class="flex justify-between">
                                                <span>Berakhir:</span>
                                                <span class="font-bold text-gray-700">{{ $warranty->expires_at->format('d M Y') }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span>Klaim Tersedia:</span>
                                                <span class="font-bold text-gray-700">{{ $warranty->policy->max_claims - $warranty->claims_used }}x</span>
                                            </div>
                                        </div>

                                        <!-- Custom Radio Button Indicator -->
                                        <div class="absolute top-5 right-5 w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-blue-500 flex items-center justify-center transition-colors {{ $isDisabled ? 'hidden' : '' }}">
                                            <div class="w-2.5 h-2.5 rounded-full bg-blue-500 opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedWarrantyId') <span class="text-xs text-rose-500 mt-2 block font-medium">{{ $message }}</span> @enderror
                    </div>

                    @if($selectedWarrantyId)
                        <div class="border-t border-gray-100 pt-8 animate-fade-in-up">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-3">
                                <h3 class="text-lg font-bold text-gray-900">Informasi Pelanggan & Kerusakan</h3>
                                <button type="button" wire:click="openQcHistory" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-sm font-bold rounded-xl transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                                    Cek Riwayat QC
                                </button>
                            </div>
                            <form wire:submit="submitClaim">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div class="form-control">
                                        <label class="block text-sm font-bold text-gray-700 mb-2">Nama Pelanggan</label>
                                        <input type="text" wire:model="customer_name" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-blue-500 focus:border-blue-500" required>
                                        @error('customer_name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="form-control">
                                        <label class="block text-sm font-bold text-gray-700 mb-2">Nomor Telepon</label>
                                        <input type="text" wire:model="customer_phone" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                                <div class="form-control mb-8">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi Masalah / Kerusakan</label>
                                    <textarea wire:model="issue_description" rows="4" class="w-full bg-gray-50 border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Jelaskan secara detail keluhan pelanggan..." required></textarea>
                                    @error('issue_description') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                                    
                                    @php
                                        $selectedWarranty = $foundWarranties->firstWhere('id', $selectedWarrantyId);
                                    @endphp
                                    @if($selectedWarranty)
                                        <div class="mt-3 bg-blue-50 rounded-lg p-3">
                                            <p class="text-xs font-bold text-blue-800 mb-2">Pastikan masalah termasuk dalam cakupan garansi ini:</p>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($selectedWarranty->policy->coverage as $cov)
                                                    @if($cov['covered'])
                                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-white text-emerald-700 rounded-md text-[10px] font-bold border border-emerald-100 shadow-sm">
                                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                            {{ $cov['name'] }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                
                                <div class="flex justify-end">
                                    <button type="submit" class="px-8 py-3 bg-[#1c69d4] hover:bg-[#3f36b8] text-white font-bold rounded-xl transition-all shadow-sm flex items-center justify-center gap-2 w-full md:w-auto">
                                        <svg wire:loading wire:target="submitClaim" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        Ajukan Klaim Sekarang
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                @endif
            </div>
        @endif
    </div>

    <!-- Modal QC History -->
    @if($showQcModal && $qcInspection)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" wire:click="$set('showQcModal', false)"></div>

            <!-- Modal Content -->
            <div class="relative bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-fade-in-up">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-gray-50">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 rounded-lg text-indigo-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-xl text-gray-900">Riwayat QC Unboxing</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Dilakukan pada: {{ $qcInspection->created_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>
                    <button wire:click="$set('showQcModal', false)" class="text-gray-400 hover:text-gray-600 bg-white hover:bg-gray-100 rounded-full p-2 transition-colors shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 space-y-6">
                    <!-- Inspector Info -->
                    <div class="flex justify-between items-center bg-indigo-50/50 p-4 rounded-xl border border-indigo-100/50">
                        <div>
                            <p class="text-xs font-bold text-indigo-400 uppercase tracking-wider mb-1">Diinspeksi Oleh</p>
                            <p class="font-bold text-indigo-900">{{ $qcInspection->inspector->name ?? 'Staff' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold text-indigo-400 uppercase tracking-wider mb-1">Status Final QC</p>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-100 text-emerald-700 rounded-md text-xs font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Lulus / Passed
                            </span>
                        </div>
                    </div>

                    <!-- Photos -->
                    @php
                        $mediaItems = $qcInspection->getMedia('qc_photos');
                    @endphp
                    @if($mediaItems->count() > 0)
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 mb-3">Foto Kondisi Awal (Unboxing)</h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                                @foreach($mediaItems as $media)
                                    <a href="{{ $media->getUrl() }}" target="_blank" class="aspect-square rounded-xl overflow-hidden border border-gray-100 shadow-sm bg-gray-50 group relative block">
                                        <img src="{{ $media->getUrl() }}" alt="QC Photo" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110">
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors flex items-center justify-center">
                                            <svg class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity drop-shadow-md" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" /></svg>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Checklist -->
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 mb-3">Hasil Checklist QC Fungsional</h4>
                        @php
                            // Decode JSON from DB
                            $results = is_array($qcInspection->checklist_results) ? $qcInspection->checklist_results : json_decode($qcInspection->checklist_results, true);
                            $grouped = collect($results)->groupBy('category');
                        @endphp
                        
                        <div class="space-y-4">
                            @foreach($grouped as $category => $items)
                                <div class="bg-gray-50 rounded-xl border border-gray-100 overflow-hidden">
                                    <div class="bg-gray-100/50 px-4 py-2 border-b border-gray-100">
                                        <h5 class="text-xs font-bold text-gray-600 uppercase">{{ $category }}</h5>
                                    </div>
                                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        @foreach($items as $item)
                                            <div class="flex items-center justify-between bg-white p-2.5 rounded-lg border border-gray-100 shadow-sm">
                                                <span class="text-sm font-medium text-gray-700">{{ $item['name'] }}</span>
                                                @if($item['type'] === 'boolean')
                                                    @if($item['value'])
                                                        <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                                    @else
                                                        <svg class="w-5 h-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    @endif
                                                @else
                                                    <span class="text-sm font-bold text-gray-900">{{ $item['value'] }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Notes -->
                    @if($qcInspection->inspector_notes)
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 mb-2">Catatan Tambahan</h4>
                            <div class="bg-amber-50 border border-amber-100 rounded-xl p-4">
                                <p class="text-sm text-amber-800">{{ $qcInspection->inspector_notes }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
