<div class="bg-gray-50 min-h-screen relative max-w-md mx-auto shadow-2xl overflow-hidden font-sans">
    {{-- Header --}}
    <div class="px-5 py-6 bg-white shadow-sm border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('zoffline.home') }}" wire:navigate class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-500 hover:bg-gray-100 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-800 leading-tight">Device Passport</h1>
                <p class="text-xs text-gray-500">Riwayat Inspeksi Unit</p>
            </div>
        </div>
        <button wire:click="openQcModal" class="w-10 h-10 bg-violet-100 text-violet-600 rounded-full flex items-center justify-center hover:bg-violet-200 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
        </button>
    </div>

    <div class="p-5 pb-24">
        @if ($this->inspections->isEmpty())
            <div class="bg-white rounded-3xl shadow-sm border border-gray-200 p-8 text-center mt-4">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-800">Belum ada Riwayat QC</h3>
                <p class="text-gray-500 text-xs mt-2">Tidak ditemukan data inspeksi untuk IMEI:<br><span class="font-bold text-gray-700">{{ $imei }}</span></p>
            </div>
        @else
            @php
                $firstInspection = $this->inspections->first();
                $variant = $firstInspection->variant;
                $productName = $variant
                    ? ($variant->secondProduct->name ?? 'Unknown Product') . ' - ' . $variant->storage . ' ' . $variant->color
                    : 'Unknown Product';
            @endphp

            {{-- Device Info Card --}}
            <div class="bg-gradient-to-br from-violet-600 to-indigo-700 rounded-3xl shadow-lg p-6 text-white mb-6 relative overflow-hidden">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                <div class="absolute -left-10 -bottom-10 w-32 h-32 bg-violet-400/20 rounded-full blur-xl"></div>
                
                <div class="relative z-10 flex items-center gap-4">
                    <div class="bg-white/20 p-3 rounded-2xl backdrop-blur-sm">
                        <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-black font-mono tracking-wider">{{ $imei }}</h2>
                        <p class="text-violet-100 text-xs mt-0.5 font-medium">{{ $productName }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-4 relative before:absolute before:inset-0 before:ml-5 before:-translate-x-px before:h-full before:w-0.5 before:bg-gradient-to-b before:from-violet-300 before:via-gray-200 before:to-transparent pt-2">
                @foreach ($this->inspections as $index => $qc)
                    <div class="relative pl-12 pr-1">
                        <div class="absolute left-3.5 top-3 w-3 h-3 rounded-full border-2 border-white bg-violet-500 shadow-sm z-10"></div>
                        
                        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-800">
                                        QC #{{ $this->inspections->count() - $index }}
                                        @if ($qc->label)
                                            <span class="text-violet-600 bg-violet-50 px-2 py-0.5 rounded text-[10px] ml-1 font-bold">{{ $qc->label }}</span>
                                        @endif
                                    </h4>
                                    <p class="text-[10px] text-gray-400 mt-1 font-medium">{{ $qc->inspected_at->format('d M Y, H:i') }} • Oleh: {{ $qc->inspector->name ?? 'Unknown' }}</p>
                                </div>
                                @if ($qc->verdict === 'pass')
                                    <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-[10px] font-bold uppercase tracking-wider">Pass</span>
                                @elseif($qc->verdict === 'fail')
                                    <span class="px-2 py-1 bg-rose-100 text-rose-700 rounded-lg text-[10px] font-bold uppercase tracking-wider">Fail</span>
                                @else
                                    <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-lg text-[10px] font-bold uppercase tracking-wider">Cond.</span>
                                @endif
                            </div>
                            <button wire:click="viewQcDetail({{ $qc->id }})" class="w-full mt-1 py-2.5 bg-gray-50 text-gray-700 font-bold text-xs rounded-xl hover:bg-gray-100 hover:text-violet-600 transition border border-gray-200">
                                Lihat Detail QC
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Detail Modal --}}
    @if ($showDetailModal && $this->selectedQc)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden transform transition-all" @click.away="$wire.closeQcDetail()">
                <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 text-sm">Detail QC #{{ $this->selectedQc->id }}</h3>
                    <button wire:click="closeQcDetail" class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 hover:bg-rose-100 hover:text-rose-600 transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-5 overflow-y-auto flex-1 space-y-6">
                    {{-- Catatan --}}
                    <div>
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Catatan Inspektor</h4>
                        <div class="p-4 bg-gray-50 rounded-2xl text-sm text-gray-700 border border-gray-100 whitespace-pre-line font-medium leading-relaxed">
                            {{ $this->selectedQc->inspector_notes ?? 'Tidak ada catatan khusus.' }}
                        </div>
                    </div>

                    {{-- Checklist --}}
                    <div>
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Hasil Pengecekan</h4>
                        <div class="space-y-1 bg-white border border-gray-100 rounded-2xl overflow-hidden p-1 shadow-sm">
                            @if(is_array($this->selectedQc->checklist_results))
                                @foreach($this->selectedQc->checklist_results as $item)
                                    <div class="flex justify-between items-center p-2.5 border-b border-gray-50 last:border-0 hover:bg-gray-50 rounded-xl transition">
                                        <span class="text-xs font-medium text-gray-600">{{ $item['name'] }}</span>
                                        @if($item['type'] === 'boolean')
                                            @if($item['value'])
                                                <div class="w-6 h-6 bg-emerald-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                </div>
                                            @else
                                                <div class="w-6 h-6 bg-rose-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-xs font-bold text-gray-800 bg-gray-100 px-2 py-1 rounded-lg">{{ $item['value'] }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- Foto --}}
                    <div>
                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Foto Fisik & Kelengkapan</h4>
                        @if ($this->selectedQc->hasMedia('qc_photos'))
                            <div class="grid grid-cols-2 gap-3">
                                @foreach ($this->selectedQc->getMedia('qc_photos') as $media)
                                    <a href="{{ $media->getUrl() }}" target="_blank" class="block aspect-square rounded-2xl overflow-hidden border border-gray-200 hover:border-violet-500 hover:shadow-md transition relative group">
                                        <img src="{{ $media->getUrl() }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="p-6 bg-gray-50 border border-gray-100 border-dashed rounded-2xl text-center text-xs text-gray-400 font-medium">
                                Tidak ada foto terlampir
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- New QC Modal -->
    @if ($showQcModal && $targetSnId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl shadow-xl w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Inspeksi QC Baru</h3>
                    <button wire:click="$set('showQcModal', false)" class="text-gray-400 hover:text-rose-500 font-bold w-8 h-8 rounded-full hover:bg-rose-50 flex items-center justify-center">&times;</button>
                </div>
                <div class="p-4 overflow-y-auto flex-1 bg-gray-50/50">
                    <div class="mb-5 bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Pilih Label QC</label>
                        <select wire:model="newQcLabel" class="w-full p-2.5 rounded-xl border-gray-200 text-sm font-semibold text-gray-700 focus:ring-violet-500 focus:border-violet-500 shadow-sm bg-gray-50">
                            <option value="QC Etalase">QC Etalase (Pengecekan Berkala)</option>
                            <option value="QC Retur">QC Retur (Barang Kembali)</option>
                            <option value="QC Service">QC Service (Masuk Servis)</option>
                            <option value="QC After Service">QC After Service (Selesai Servis)</option>
                        </select>
                    </div>

                    @livewire(
                        'admin.qc.inspection-form',
                        [
                            'inspectableType' => \App\Models\ProductSerialNumber::class,
                            'inspectableId' => $targetSnId,
                            'label' => $newQcLabel,
                        ],
                        key('qc-form-' . $targetSnId)
                    )
                </div>
            </div>
        </div>
    @endif
</div>
