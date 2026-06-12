<div>
    {{-- Breadcrumb & Header --}}
    <div class="mb-6 flex justify-between items-end">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">📱 Device Passport</h1>
            <p class="text-gray-500 text-sm mt-1">Riwayat Inspeksi Fisik & QC Unit</p>
        </div>
        <div class="flex gap-3 items-center">
            <button wire:click="openQcModal"
                class="px-3 py-1.5 bg-[#1c69d4] text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Inspeksi Baru
            </button>
            <a href="{{ route('admin.dashboard') }}"
                class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Kembali ke Dashboard</a>
        </div>
    </div>

    @if ($this->inspections->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h3 class="text-lg font-bold text-gray-800">Belum ada Riwayat QC</h3>
            <p class="text-gray-500 mt-2">Tidak ditemukan data inspeksi untuk IMEI: <span
                    class="font-bold">{{ $imei }}</span></p>
        </div>
    @else
        @php
            $firstInspection = $this->inspections->first();
            $variant = $firstInspection->variant;
            $productName = $variant
                ? ($variant->secondProduct->name ?? 'Unknown Product') .
                    ' - ' .
                    $variant->storage .
                    ' ' .
                    $variant->color
                : 'Unknown Product';
        @endphp

        {{-- Device Info Card --}}
        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-2xl shadow-lg p-6 text-white mb-6">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 p-4 rounded-xl backdrop-blur-sm">
                    <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-black font-mono tracking-wider">{{ $imei }}</h2>
                    <p class="text-emerald-50 text-sm mt-1">{{ $productName }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left: Timeline --}}
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-5">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Timeline QC ({{ $this->inspections->count() }})
                    </h3>

                    <div class="relative pl-3 border-l-2 border-emerald-100 space-y-6">
                        @foreach ($this->inspections as $index => $qc)
                            <div class="relative">
                                <div
                                    class="absolute -left-[1.1rem] top-1 w-4 h-4 rounded-full border-4 border-white bg-emerald-500 shadow-sm">
                                </div>

                                <div class="pl-2">
                                    <div class="flex justify-between items-start mb-1">
                                        <h4 class="text-sm font-bold text-gray-800">
                                            QC #{{ $this->inspections->count() - $index }}
                                            @if ($qc->label)
                                                <span
                                                    class="text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded text-xs ml-1">{{ $qc->label }}</span>
                                            @endif
                                        </h4>
                                        <span
                                            class="text-[10px] text-gray-400 font-medium">{{ $qc->inspected_at->format('d M Y, H:i') }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mb-2">Oleh: <span
                                            class="font-semibold text-gray-700">{{ $qc->inspector->name ?? 'Unknown' }}</span>
                                    </p>

                                    <div class="flex items-center gap-3">
                                        <div
                                            class="px-2 py-1 bg-gray-50 border border-gray-200 rounded text-xs font-bold text-gray-600">
                                            {{ $qc->summary_label }}
                                        </div>
                                        <div>
                                            @if ($qc->verdict === 'pass')
                                                <span
                                                    class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-[10px] font-bold uppercase tracking-wider">Pass</span>
                                            @elseif($qc->verdict === 'fail')
                                                <span
                                                    class="px-2 py-1 bg-rose-100 text-rose-700 rounded text-[10px] font-bold uppercase tracking-wider">Fail</span>
                                            @else
                                                <span
                                                    class="px-2 py-1 bg-amber-100 text-amber-700 rounded text-[10px] font-bold uppercase tracking-wider">Conditional</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Right: Comparison --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    {{-- Selectors --}}
                    <div
                        class="p-4 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row gap-4 items-center justify-between">
                        <div class="flex-1 w-full">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">QC
                                1</label>
                            <select wire:model.live="selectedQc1Id"
                                class="w-full p-2 rounded-lg border-gray-300 text-sm focus:ring-emerald-500 focus:border-emerald-500 shadow-sm">
                                <option value="">-- Pilih --</option>
                                @foreach ($this->inspections as $idx => $qc)
                                    <option value="{{ $qc->id }}">QC #{{ $this->inspections->count() - $idx }}
                                        ({{ $qc->inspected_at->format('d M y') }}) - {{ $qc->label ?? 'No Label' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="hidden sm:flex flex-col items-center justify-center pt-5">
                            <div
                                class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 font-bold text-xs shadow-inner">
                                VS
                            </div>
                        </div>

                        <div class="flex-1 w-full">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5">QC
                                2</label>
                            <select wire:model.live="selectedQc2Id"
                                class="w-full p-2 rounded-lg border-gray-300 text-sm focus:ring-emerald-500 focus:border-emerald-500 shadow-sm">
                                <option value="">-- Pilih --</option>
                                @foreach ($this->inspections as $idx => $qc)
                                    <option value="{{ $qc->id }}">QC #{{ $this->inspections->count() - $idx }}
                                        ({{ $qc->inspected_at->format('d M y') }}) - {{ $qc->label ?? 'No Label' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Comparison Table --}}
                    @if ($this->qc1 || $this->qc2)
                        @php
                            // Get all unique checklist item names from both QCs
                            $checklistNames = collect();
                            if ($this->qc1 && is_array($this->qc1->checklist_results)) {
                                foreach ($this->qc1->checklist_results as $item) {
                                    $checklistNames->push($item['name']);
                                }
                            }
                            if ($this->qc2 && is_array($this->qc2->checklist_results)) {
                                foreach ($this->qc2->checklist_results as $item) {
                                    $checklistNames->push($item['name']);
                                }
                            }
                            $checklistNames = $checklistNames->unique()->values();

                            // Helper to find item value
                            $findItem = function ($qc, $name) {
                                if (!$qc || !is_array($qc->checklist_results)) {
                                    return null;
                                }
                                return collect($qc->checklist_results)->firstWhere('name', $name);
                            };

                            $renderItem = function ($item) {
                                if (!$item) {
                                    return '<span class="text-gray-300">-</span>';
                                }
                                if ($item['type'] === 'boolean') {
                                    return $item['value']
                                        ? '<svg class="w-5 h-5 text-emerald-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>'
                                        : '<svg class="w-5 h-5 text-rose-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>';
                                }
                                return '<span class="text-sm font-medium text-gray-700">' .
                                    htmlspecialchars($item['value']) .
                                    '</span>';
                            };
                        @endphp

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 font-semibold">QC Item</th>
                                        <th class="px-6 py-3 text-center font-semibold border-l border-gray-100 w-1/3">
                                            @if ($this->qc1)
                                                QC Kiri <br><span
                                                    class="text-[10px] text-gray-400 normal-case">{{ $this->qc1->inspected_at->format('d/m/y') }}</span>
                                            @else
                                                -
                                            @endif
                                        </th>
                                        <th class="px-6 py-3 text-center font-semibold border-l border-gray-100 w-1/3">
                                            @if ($this->qc2)
                                                QC Kanan <br><span
                                                    class="text-[10px] text-gray-400 normal-case">{{ $this->qc2->inspected_at->format('d/m/y') }}</span>
                                            @else
                                                -
                                            @endif
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($checklistNames as $name)
                                        @php
                                            $item1 = $findItem($this->qc1, $name);
                                            $item2 = $findItem($this->qc2, $name);

                                            // Highlight if changed
                                            $isChanged = false;
                                            if ($item1 && $item2) {
                                                if (
                                                    $item1['type'] === 'boolean' &&
                                                    $item1['value'] !== $item2['value']
                                                ) {
                                                    $isChanged = true;
                                                }
                                                if ($item1['type'] === 'text' && $item1['value'] !== $item2['value']) {
                                                    $isChanged = true;
                                                }
                                            }
                                        @endphp
                                        <tr class="hover:bg-gray-50/50 transition">
                                            <td class="px-6 py-2.5 font-medium text-gray-700">
                                                {{ $name }}
                                                @if ($isChanged)
                                                    <span class="inline-block ml-2 w-2 h-2 bg-amber-400 rounded-full"
                                                        title="Berubah"></span>
                                                @endif
                                            </td>
                                            <td
                                                class="px-6 py-2.5 text-center border-l border-gray-100 {{ $isChanged ? 'bg-amber-50/30' : '' }}">
                                                {!! $renderItem($item1) !!}
                                            </td>
                                            <td
                                                class="px-6 py-2.5 text-center border-l border-gray-100 {{ $isChanged ? 'bg-amber-50/30' : '' }}">
                                                {!! $renderItem($item2) !!}
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- Notes --}}
                                    <tr class="bg-gray-50/50">
                                        <td class="px-6 py-4 font-bold text-gray-700 align-top">Catatan Inspektor</td>
                                        <td class="px-6 py-4 text-xs text-gray-600 border-l border-gray-100 align-top">
                                            {{ $this->qc1->inspector_notes ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-xs text-gray-600 border-l border-gray-100 align-top">
                                            {{ $this->qc2->inspector_notes ?? '-' }}
                                        </td>
                                    </tr>

                                    {{-- Photos --}}
                                    <tr>
                                        <td class="px-6 py-4 font-bold text-gray-700 align-top">Foto Fisik</td>
                                        <td class="px-6 py-4 border-l border-gray-100 align-top">
                                            @if ($this->qc1 && $this->qc1->hasMedia('qc_photos'))
                                                <div class="grid grid-cols-2 gap-2">
                                                    @foreach ($this->qc1->getMedia('qc_photos') as $media)
                                                        <a href="{{ $media->getUrl() }}" target="_blank"
                                                            class="block aspect-square rounded-lg overflow-hidden border border-gray-200 hover:border-emerald-500 transition shadow-sm">
                                                            <img src="{{ $media->getUrl() }}"
                                                                class="w-full h-full object-cover">
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-xs text-gray-400 text-center py-4">Tidak ada foto</p>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 border-l border-gray-100 align-top">
                                            @if ($this->qc2 && $this->qc2->hasMedia('qc_photos'))
                                                <div class="grid grid-cols-2 gap-2">
                                                    @foreach ($this->qc2->getMedia('qc_photos') as $media)
                                                        <a href="{{ $media->getUrl() }}" target="_blank"
                                                            class="block aspect-square rounded-lg overflow-hidden border border-gray-200 hover:border-emerald-500 transition shadow-sm">
                                                            <img src="{{ $media->getUrl() }}"
                                                                class="w-full h-full object-cover">
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-xs text-gray-400 text-center py-4">Tidak ada foto</p>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-12 text-center">
                            <p class="text-gray-400 text-sm">Pilih minimal 1 riwayat QC untuk melihat detail</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- QC Modal -->
    @if ($showQcModal && $targetSnId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden">
                <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800">Inspeksi QC Baru</h3>
                    <button wire:click="$set('showQcModal', false)"
                        class="text-gray-400 hover:text-rose-500 font-bold">&times;</button>
                </div>
                <div class="p-4 overflow-y-auto flex-1">
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Pilih Jenis Inspeksi (Label)</label>
                        <select wire:model="newQcLabel"
                            class="w-full p-2 rounded-lg border-gray-300 text-sm focus:ring-[#1c69d4] focus:border-[#1c69d4] shadow-sm mb-4">
                            <option value="QC Etalase">QC Etalase (Pengecekan Berkala)</option>
                            <option value="QC Retur">QC Retur (Barang Kembali)</option>
                            <option value="QC Service">QC Service (Masuk Servis)</option>
                            <option value="QC After Service">QC After Service (Selesai Servis)</option>
                        </select>
                    </div>

                    {{-- We use key() to force component re-render when targetSnId changes --}}
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
                <div class="p-4 border-t border-gray-100 flex justify-end">
                    <button type="button" wire:click="$set('showQcModal', false)"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-lg transition">Batal</button>
                </div>
            </div>
        </div>
    @endif
</div>
