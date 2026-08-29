<div class="relative min-h-screen bg-neutral-50 p-4 sm:p-8 font-sans">
    <div class="max-w-6xl mx-auto">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('zoffline') }}" wire:navigate class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-neutral-200 flex items-center justify-center text-neutral-600 hover:bg-neutral-50 hover:text-emerald-600 transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-neutral-800 tracking-tight flex items-center gap-2">
                        Device Passport
                    </h1>
                    <p class="text-sm text-neutral-500 mt-1 font-medium">Riwayat Inspeksi Fisik & Perbandingan Kualitas Unit</p>
                </div>
            </div>
            
            <button wire:click="openQcModal" class="w-full sm:w-auto px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                Inspeksi Baru
            </button>
        </div>

        @if ($this->inspections->isEmpty())
            <div class="bg-white rounded-3xl shadow-xl shadow-neutral-200/50 border border-neutral-100 p-12 text-center max-w-2xl mx-auto mt-12">
                <div class="w-24 h-24 bg-neutral-50 rounded-full flex items-center justify-center mx-auto mb-6 border-8 border-white shadow-sm">
                    <svg class="w-10 h-10 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-2xl font-black text-neutral-800 mb-2">Belum ada Riwayat QC</h3>
                <p class="text-neutral-500 font-medium">Tidak ditemukan data inspeksi untuk IMEI <br><strong class="text-neutral-800 text-lg mt-2 inline-block bg-neutral-100 px-3 py-1 rounded-lg">"{{ $imei }}"</strong></p>
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
            <div class="bg-gradient-to-br from-indigo-900 to-indigo-700 rounded-3xl shadow-2xl p-6 sm:p-8 text-white mb-8 relative overflow-hidden">
                <div class="absolute -right-20 -top-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-indigo-500/30 rounded-full blur-2xl"></div>
                
                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                    <div class="flex items-center gap-5">
                        <div class="bg-white/10 p-4 rounded-2xl backdrop-blur-md border border-white/20">
                            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-indigo-200 text-[10px] font-black uppercase tracking-widest mb-1">Nomor Seri / IMEI</div>
                            <h2 class="text-2xl sm:text-3xl font-black font-mono tracking-wider text-white">{{ $imei }}</h2>
                            <p class="text-indigo-100 text-sm mt-1 font-medium bg-white/10 inline-block px-3 py-1 rounded-lg">{{ $productName }}</p>
                        </div>
                    </div>
                    <div class="text-left sm:text-right">
                        <div class="text-indigo-200 text-[10px] font-black uppercase tracking-widest mb-1">Total Riwayat</div>
                        <div class="text-3xl font-black text-white">
                            {{ $this->inspections->count() }} <span class="text-sm font-bold text-indigo-300 uppercase tracking-wider ml-1">Inspeksi</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Comparison Section --}}
            <div class="bg-white rounded-3xl shadow-xl shadow-neutral-200/50 border border-neutral-100 overflow-hidden mb-8">
                
                {{-- Comparison Headers / Selectors --}}
                <div class="p-6 border-b border-neutral-100 bg-neutral-50/50">
                    <div class="flex flex-col md:flex-row items-center gap-6">
                        
                        {{-- Left QC Selector --}}
                        <div class="flex-1 w-full bg-white p-4 rounded-2xl border border-neutral-200 shadow-sm relative overflow-hidden group hover:border-indigo-300 transition-colors">
                            <div class="absolute top-0 left-0 w-1 h-full bg-rose-400"></div>
                            <label class="block text-xs font-black text-rose-500 uppercase tracking-wider mb-2">QC Referensi (Kiri)</label>
                            <select wire:model.live="selectedQc1Id" class="w-full bg-neutral-50 border-0 rounded-xl p-3 text-sm font-bold text-neutral-700 focus:ring-2 focus:ring-rose-500">
                                <option value="">-- Pilih Inspeksi --</option>
                                @foreach ($this->inspections as $idx => $qc)
                                    <option value="{{ $qc->id }}">QC #{{ $this->inspections->count() - $idx }} ({{ $qc->inspected_at->format('d M y') }}) - {{ $qc->label ?? 'No Label' }}</option>
                                @endforeach
                            </select>
                            
                            @if($this->qc1)
                                <div class="mt-3 pt-3 border-t border-neutral-100 flex justify-between items-center">
                                    <span class="text-xs text-neutral-500 font-medium">Inspektor: <strong class="text-neutral-700">{{ $this->qc1->inspector->name ?? 'Unknown' }}</strong></span>
                                    <span class="px-2 py-1 {{ $this->qc1->verdict === 'pass' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} rounded-md text-[10px] font-bold uppercase">{{ $this->qc1->verdict === 'pass' ? 'Lulus' : 'Tidak Lulus' }}</span>
                                </div>
                            @endif
                        </div>

                        {{-- VS Badge --}}
                        <div class="flex flex-col items-center justify-center shrink-0">
                            <div class="w-12 h-12 rounded-full bg-neutral-900 text-white flex items-center justify-center font-black text-lg shadow-lg border-4 border-white z-10">
                                VS
                            </div>
                        </div>

                        {{-- Right QC Selector --}}
                        <div class="flex-1 w-full bg-white p-4 rounded-2xl border border-neutral-200 shadow-sm relative overflow-hidden group hover:border-indigo-300 transition-colors">
                            <div class="absolute top-0 right-0 w-1 h-full bg-emerald-400"></div>
                            <label class="block text-xs font-black text-emerald-500 uppercase tracking-wider mb-2 text-right">QC Terbaru (Kanan)</label>
                            <select wire:model.live="selectedQc2Id" class="w-full bg-neutral-50 border-0 rounded-xl p-3 text-sm font-bold text-neutral-700 focus:ring-2 focus:ring-emerald-500">
                                <option value="">-- Pilih Inspeksi --</option>
                                @foreach ($this->inspections as $idx => $qc)
                                    <option value="{{ $qc->id }}">QC #{{ $this->inspections->count() - $idx }} ({{ $qc->inspected_at->format('d M y') }}) - {{ $qc->label ?? 'No Label' }}</option>
                                @endforeach
                            </select>

                            @if($this->qc2)
                                <div class="mt-3 pt-3 border-t border-neutral-100 flex justify-between items-center">
                                    <span class="px-2 py-1 {{ $this->qc2->verdict === 'pass' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} rounded-md text-[10px] font-bold uppercase">{{ $this->qc2->verdict === 'pass' ? 'Lulus' : 'Tidak Lulus' }}</span>
                                    <span class="text-xs text-neutral-500 font-medium">Inspektor: <strong class="text-neutral-700">{{ $this->qc2->inspector->name ?? 'Unknown' }}</strong></span>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>

                {{-- Comparison Table --}}
                @if ($this->qc1 || $this->qc2)
                    @php
                        // Collect unique checklist names
                        $checklistNames = collect();
                        if ($this->qc1 && (is_array($this->qc1->checklist_results) || is_string($this->qc1->checklist_results))) {
                            $items = is_string($this->qc1->checklist_results) ? json_decode($this->qc1->checklist_results, true) : $this->qc1->checklist_results;
                            if(is_array($items)) {
                                foreach ($items as $item) $checklistNames->push($item['name'] ?? 'Unknown');
                            }
                        }
                        if ($this->qc2 && (is_array($this->qc2->checklist_results) || is_string($this->qc2->checklist_results))) {
                            $items = is_string($this->qc2->checklist_results) ? json_decode($this->qc2->checklist_results, true) : $this->qc2->checklist_results;
                            if(is_array($items)) {
                                foreach ($items as $item) $checklistNames->push($item['name'] ?? 'Unknown');
                            }
                        }
                        $checklistNames = $checklistNames->unique()->values();

                        $findItem = function ($qc, $name) {
                            if (!$qc) return null;
                            $items = is_string($qc->checklist_results) ? json_decode($qc->checklist_results, true) : $qc->checklist_results;
                            if (!is_array($items)) return null;
                            return collect($items)->firstWhere('name', $name);
                        };

                        $renderItem = function ($item) {
                            if (!$item) return '<span class="text-neutral-300 font-bold">-</span>';
                            if (isset($item['type']) && $item['type'] === 'boolean') {
                                $val = (isset($item['value']) && ($item['value'] == 1 || $item['value'] === true || $item['value'] === '1'));
                                return $val 
                                    ? '<div class="w-6 h-6 bg-emerald-100 rounded-full flex items-center justify-center mx-auto"><svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>'
                                    : '<div class="w-6 h-6 bg-rose-100 rounded-full flex items-center justify-center mx-auto"><svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></div>';
                            }
                            return '<span class="text-sm font-bold text-neutral-800 bg-neutral-100 px-3 py-1 rounded-lg">' . htmlspecialchars($item['value'] ?? '-') . '</span>';
                        };
                    @endphp

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left border-collapse">
                            <thead>
                                <tr>
                                    <th class="px-6 py-4 bg-white font-black text-neutral-400 uppercase tracking-widest text-xs border-b border-neutral-100 w-1/3">Item Pengecekan</th>
                                    <th class="px-6 py-4 bg-rose-50/30 text-center font-black text-rose-500 uppercase tracking-widest text-xs border-b border-rose-100/50 border-l border-neutral-100 w-1/3 shadow-inner">
                                        QC Kiri
                                    </th>
                                    <th class="px-6 py-4 bg-emerald-50/30 text-center font-black text-emerald-500 uppercase tracking-widest text-xs border-b border-emerald-100/50 border-l border-neutral-100 w-1/3 shadow-inner">
                                        QC Kanan
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-100">
                                @foreach ($checklistNames as $name)
                                    @php
                                        $item1 = $findItem($this->qc1, $name);
                                        $item2 = $findItem($this->qc2, $name);

                                        $isChanged = false;
                                        if ($item1 && $item2) {
                                            $val1 = $item1['value'] ?? null;
                                            $val2 = $item2['value'] ?? null;
                                            if ($val1 != $val2) {
                                                $isChanged = true;
                                            }
                                        } elseif (($item1 && !$item2) || (!$item1 && $item2)) {
                                            $isChanged = true;
                                        }
                                    @endphp
                                    <tr class="hover:bg-neutral-50 transition-colors {{ $isChanged ? 'bg-amber-50/20' : '' }}">
                                        <td class="px-6 py-4 font-bold text-neutral-700 flex items-center gap-2">
                                            {{ $name }}
                                            @if ($isChanged)
                                                <span class="inline-flex items-center justify-center w-2 h-2 bg-amber-400 rounded-full animate-pulse" title="Terdapat Perubahan"></span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-center border-l border-neutral-100 {{ $isChanged ? 'bg-amber-50/20' : '' }}">
                                            {!! $renderItem($item1) !!}
                                        </td>
                                        <td class="px-6 py-4 text-center border-l border-neutral-100 {{ $isChanged ? 'bg-amber-50/20' : '' }}">
                                            {!! $renderItem($item2) !!}
                                        </td>
                                    </tr>
                                @endforeach

                                {{-- Notes --}}
                                <tr class="bg-neutral-50/50 border-t-2 border-neutral-100">
                                    <td class="px-6 py-5 font-black text-neutral-500 uppercase tracking-widest text-xs align-top">Catatan Inspektor</td>
                                    <td class="px-6 py-5 text-sm font-medium text-neutral-600 border-l border-neutral-100 align-top bg-white leading-relaxed">
                                        {{ $this->qc1->notes ?? $this->qc1->inspector_notes ?? '-' }}
                                    </td>
                                    <td class="px-6 py-5 text-sm font-medium text-neutral-600 border-l border-neutral-100 align-top bg-white leading-relaxed">
                                        {{ $this->qc2->notes ?? $this->qc2->inspector_notes ?? '-' }}
                                    </td>
                                </tr>

                                {{-- Photos --}}
                                <tr class="border-t-2 border-neutral-100">
                                    <td class="px-6 py-5 font-black text-neutral-500 uppercase tracking-widest text-xs align-top">Foto Fisik</td>
                                    <td class="px-6 py-5 border-l border-neutral-100 align-top">
                                        @if ($this->qc1 && ($this->qc1->hasMedia('qc_photos') || $this->qc1->hasMedia('photos')))
                                            <div class="grid grid-cols-2 lg:grid-cols-3 gap-2">
                                                @php
                                                    $mediaList1 = $this->qc1->hasMedia('qc_photos') ? $this->qc1->getMedia('qc_photos') : $this->qc1->getMedia('photos');
                                                @endphp
                                                @foreach ($mediaList1 as $media)
                                                    <a href="{{ $media->getUrl() }}" target="_blank" class="block aspect-square rounded-xl overflow-hidden border border-neutral-200 hover:border-rose-400 hover:shadow-md transition relative group">
                                                        <img src="{{ $media->getUrl() }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="p-4 bg-neutral-50 border border-neutral-200 border-dashed rounded-xl text-center text-xs text-neutral-400 font-bold uppercase tracking-wider">Tidak ada foto</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-5 border-l border-neutral-100 align-top">
                                        @if ($this->qc2 && ($this->qc2->hasMedia('qc_photos') || $this->qc2->hasMedia('photos')))
                                            <div class="grid grid-cols-2 lg:grid-cols-3 gap-2">
                                                @php
                                                    $mediaList2 = $this->qc2->hasMedia('qc_photos') ? $this->qc2->getMedia('qc_photos') : $this->qc2->getMedia('photos');
                                                @endphp
                                                @foreach ($mediaList2 as $media)
                                                    <a href="{{ $media->getUrl() }}" target="_blank" class="block aspect-square rounded-xl overflow-hidden border border-neutral-200 hover:border-emerald-400 hover:shadow-md transition relative group">
                                                        <img src="{{ $media->getUrl() }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors"></div>
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="p-4 bg-neutral-50 border border-neutral-200 border-dashed rounded-xl text-center text-xs text-neutral-400 font-bold uppercase tracking-wider">Tidak ada foto</div>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-16 text-center">
                        <div class="w-16 h-16 bg-neutral-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-neutral-100">
                            <svg class="w-8 h-8 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16l2.879-2.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h4 class="text-lg font-black text-neutral-800 mb-1">Mulai Perbandingan</h4>
                        <p class="text-neutral-500 font-medium text-sm">Pilih minimal 1 riwayat QC dari *dropdown* di atas untuk melihat detail</p>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- New QC Modal -->
    @if ($showQcModal && $targetSnId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl shadow-xl w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden">
                <div class="p-5 border-b border-neutral-100 bg-neutral-50 flex justify-between items-center">
                    <h3 class="font-bold text-neutral-800 text-lg">Inspeksi QC Baru</h3>
                    <button wire:click="$set('showQcModal', false)" class="text-neutral-400 hover:text-rose-500 font-bold w-8 h-8 rounded-full hover:bg-rose-50 flex items-center justify-center transition-colors">&times;</button>
                </div>
                <div class="p-5 overflow-y-auto flex-1 bg-neutral-50/50">
                    <div class="mb-5 bg-white p-4 rounded-2xl shadow-sm border border-neutral-100">
                        <label class="block text-xs font-black text-neutral-500 uppercase tracking-wider mb-2">Pilih Label QC</label>
                        <select wire:model="newQcLabel" class="w-full p-3 rounded-xl border-neutral-200 text-sm font-bold text-neutral-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm bg-neutral-50">
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
