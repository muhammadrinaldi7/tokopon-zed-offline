{{-- ERP-Style Dynamic Filter Bar Component --}}
<div class="w-full bg-white rounded-2xl border border-gray-200/80 shadow-sm p-4 mb-4 transition-all">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            {{-- Tombol Buka Modal Tambah Filter --}}
            <button type="button" wire:click="openFilterModal"
                class="inline-flex items-center gap-2 px-3.5 py-2 bg-gradient-to-r from-[#1c69d4] to-[#1654a8] hover:from-[#1654a8] hover:to-[#124285] text-white text-xs font-semibold rounded-xl shadow-sm shadow-blue-500/20 active:scale-95 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <span>+ Tambah Filter Dinamis</span>
                @if(count($dynamicFilters) > 0)
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-black bg-white text-[#1c69d4] rounded-full">
                        {{ count($dynamicFilters) }}
                    </span>
                @endif
            </button>

            @if(count($dynamicFilters) === 0)
                <span class="text-xs text-gray-400 italic">
                    Belum ada filter dinamis aktif. Klik tombol untuk memfilter dimensi apa saja (Cabang, Proyek, Brand, Metode Bayar, dll).
                </span>
            @endif

            {{-- Chip / Tag Filter yang sedang aktif --}}
            @foreach($dynamicFilters as $index => $filter)
                <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50/70 border border-blue-200/80 rounded-xl text-xs text-gray-800 shadow-xs animate-fadeIn">
                    <span class="font-bold text-[#1c69d4]">{{ $filter['label'] ?? ucfirst($filter['field']) }}</span>
                    <span class="px-1.5 py-0.5 bg-white border border-blue-200 text-[10px] font-semibold text-blue-700 rounded-md">
                        {{ $filter['operator_label'] ?? $filter['operator'] }}
                    </span>
                    <span class="font-semibold text-gray-900 bg-white/80 px-1.5 py-0.5 rounded border border-gray-200/50">
                        {{ $filter['display_value'] }}
                    </span>
                    <button type="button" wire:click="removeDynamicFilter({{ $index }})"
                        class="p-0.5 hover:bg-red-100 text-gray-400 hover:text-red-600 rounded-md transition-colors"
                        title="Hapus filter ini">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endforeach
        </div>

        {{-- Tombol Hapus Semua --}}
        @if(count($dynamicFilters) > 0)
            <button type="button" wire:click="clearAllDynamicFilters"
                class="text-xs font-semibold text-red-500 hover:text-red-700 hover:bg-red-50 px-2.5 py-1.5 rounded-lg transition-colors flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Hapus Semua Filter ({{ count($dynamicFilters) }})</span>
            </button>
        @endif
    </div>
</div>

{{-- Modal Dialog Tambah Filter Dinamis (Custom Search-Select & Non-HTML Native) --}}
@if($showFilterModal)
    @php
        $rawFields = $this->getFilterableFields();
        $fieldsData = [];
        foreach ($rawFields as $key => $field) {
            $normalizedOptions = [];
            if (!empty($field['options'])) {
                foreach ($field['options'] as $optKey => $optVal) {
                    $normalizedOptions[] = [
                        'value' => is_numeric($optKey) ? (string)$optVal : (string)$optKey,
                        'label' => (string)$optVal,
                    ];
                }
            }
            $fieldsData[$key] = [
                'key' => $key,
                'label' => $field['label'] ?? ucfirst($key),
                'type' => $field['type'] ?? 'text',
                'description' => $field['description'] ?? '',
                'options' => $normalizedOptions,
            ];
        }
        $operatorsData = $this->getOperatorDefinitions();
    @endphp

    <div x-data="{
        fields: {{ json_encode($fieldsData) }},
        operators: {{ json_encode($operatorsData) }},
        currentField: '{{ $newFilterField ?: array_key_first($fieldsData) }}',
        currentOp: '{{ $newFilterOperator ?: 'in' }}',
        currentVal: {!! json_encode(is_array($newFilterValue) ? $newFilterValue : ($newFilterValue ? [$newFilterValue] : [])) !!},
        currentValEnd: '{{ $newFilterValueEnd ?: '' }}',
        isSubmitting: false,

        openFieldDropdown: false,
        searchField: '',

        openOpDropdown: false,

        searchOption: '',

        get activeFieldDef() {
            return this.fields[this.currentField] || Object.values(this.fields)[0];
        },

        get filteredFieldsList() {
            if (!this.searchField) return Object.values(this.fields);
            const q = this.searchField.toLowerCase();
            return Object.values(this.fields).filter(f => 
                f.label.toLowerCase().includes(q) || 
                (f.description && f.description.toLowerCase().includes(q))
            );
        },

        get allowedOperators() {
            const type = this.activeFieldDef ? this.activeFieldDef.type : 'text';
            const result = [];
            for (const [key, op] of Object.entries(this.operators)) {
                if (op.types.includes(type)) {
                    result.push({ key: key, label: op.label });
                }
            }
            return result;
        },

        get activeOpLabel() {
            return (this.operators[this.currentOp] && this.operators[this.currentOp].label) 
                ? this.operators[this.currentOp].label 
                : this.currentOp;
        },

        selectField(key) {
            this.currentField = key;
            this.openFieldDropdown = false;
            this.searchField = '';

            const ops = this.allowedOperators;
            if (ops.length > 0) {
                this.currentOp = ops[0].key;
            }

            if (this.activeFieldDef && this.activeFieldDef.type === 'select') {
                this.currentVal = [];
            } else {
                this.currentVal = '';
            }
            this.currentValEnd = '';
            this.searchOption = '';
        },

        selectOperator(key) {
            this.currentOp = key;
            this.openOpDropdown = false;
        },

        get filteredOptionsList() {
            const opts = (this.activeFieldDef && this.activeFieldDef.options) ? this.activeFieldDef.options : [];
            if (!this.searchOption) return opts;
            const q = this.searchOption.toLowerCase();
            return opts.filter(o => o.label.toLowerCase().includes(q) || o.value.toLowerCase().includes(q));
        },

        selectAllOptions() {
            if (!Array.isArray(this.currentVal)) this.currentVal = [];
            this.currentVal = this.filteredOptionsList.map(o => o.value);
        },

        deselectAllOptions() {
            this.currentVal = [];
        },

        submitFilter() {
            if (this.isSubmitting) return;
            this.isSubmitting = true;
            $wire.addDynamicFilter(this.currentField, this.currentOp, this.currentVal, this.currentValEnd)
                .then(() => {
                    this.isSubmitting = false;
                })
                .catch(() => {
                    this.isSubmitting = false;
                });
        }
    }" class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-gray-100 overflow-hidden transform transition-all animate-fadeIn">
            {{-- Modal Header --}}
            <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50 to-blue-50/30">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-100 text-[#1c69d4] flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-800">Tambah Filter Dinamis</h3>
                        <p class="text-xs text-gray-500">Pilih parameter data & kriteria filter yang Anda butuhkan</p>
                    </div>
                </div>
                <button type="button" wire:click="closeFilterModal" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <form @submit.prevent="submitFilter" class="p-6 space-y-4">
                {{-- 1. Pilih Parameter Data (Custom Search-Select) --}}
                <div class="relative">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        1. Pilih Parameter / Dimensi Data
                    </label>

                    {{-- Search-Select Trigger Button --}}
                    <div @click.outside="openFieldDropdown = false" class="relative">
                        <button type="button" @click="openFieldDropdown = !openFieldDropdown; if(openFieldDropdown) $nextTick(() => $refs.fieldSearchInput.focus())"
                            class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl shadow-xs hover:border-[#1c69d4] focus:outline-none focus:ring-2 focus:ring-[#1c69d4]/20 transition-all text-left cursor-pointer">
                            <div class="flex items-center gap-2.5 truncate">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#1c69d4] shrink-0"></span>
                                <span class="text-sm font-bold text-gray-800 truncate" x-text="activeFieldDef ? activeFieldDef.label : 'Pilih Parameter'"></span>
                                <span class="px-2 py-0.5 text-[10px] font-semibold bg-blue-50 text-[#1c69d4] rounded-md shrink-0 uppercase tracking-wider"
                                    x-text="activeFieldDef ? activeFieldDef.type : ''"></span>
                            </div>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0 ml-2"
                                :class="{ 'rotate-180 text-[#1c69d4]': openFieldDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Searchable Dropdown Panel --}}
                        <div x-show="openFieldDropdown" x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute left-0 top-full mt-1.5 w-full bg-white rounded-xl shadow-2xl border border-gray-200 py-2 z-50 flex flex-col max-h-72"
                            style="display: none;">
                            
                            {{-- Field Search Input --}}
                            <div class="px-3 pb-2 border-b border-gray-100">
                                <div class="relative">
                                    <input x-model="searchField" x-ref="fieldSearchInput" @keydown.escape="openFieldDropdown = false"
                                        type="text" placeholder="Ketik cari parameter (misal: Cabang, Proyek, Brand)..."
                                        class="w-full pl-8 pr-3 py-1.5 text-xs bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#1c69d4] focus:border-[#1c69d4]">
                                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>

                            {{-- Field Options List --}}
                            <div class="overflow-y-auto flex-1 divide-y divide-gray-50">
                                <template x-for="f in filteredFieldsList" :key="f.key">
                                    <div @click="selectField(f.key)"
                                        class="px-3.5 py-2.5 cursor-pointer hover:bg-blue-50/70 flex items-center justify-between transition-colors group select-none"
                                        :class="{ 'bg-blue-50/90 font-bold text-[#1c69d4]': currentField === f.key }">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-gray-800 group-hover:text-[#1c69d4]" x-text="f.label"></span>
                                            <span class="text-[11px] text-gray-400 font-normal" x-text="f.description || (f.options ? f.options.length + ' opsi master' : 'Input data')"></span>
                                        </div>
                                        <span x-show="currentField === f.key" class="text-xs font-bold text-[#1c69d4]">✓</span>
                                    </div>
                                </template>
                                <div x-show="filteredFieldsList.length === 0" class="px-3 py-4 text-center text-xs text-gray-400 italic">
                                    Parameter tidak ditemukan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. Pilih Kriteria / Operator (Custom Dropdown) --}}
                <div class="relative">
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">
                        2. Pilih Kriteria / Operator
                    </label>

                    <div @click.outside="openOpDropdown = false" class="relative">
                        <button type="button" @click="openOpDropdown = !openOpDropdown"
                            class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white border border-gray-300 rounded-xl shadow-xs hover:border-[#1c69d4] focus:outline-none focus:ring-2 focus:ring-[#1c69d4]/20 transition-all text-left cursor-pointer">
                            <span class="text-sm font-medium text-gray-800 truncate" x-text="activeOpLabel"></span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0 ml-2"
                                :class="{ 'rotate-180 text-[#1c69d4]': openOpDropdown }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="openOpDropdown" x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute left-0 top-full mt-1.5 w-full bg-white rounded-xl shadow-2xl border border-gray-200 py-1.5 z-50 flex flex-col max-h-60 overflow-y-auto divide-y divide-gray-50"
                            style="display: none;">
                            <template x-for="op in allowedOperators" :key="op.key">
                                <div @click="selectOperator(op.key)"
                                    class="px-3.5 py-2.5 cursor-pointer hover:bg-blue-50/70 flex items-center justify-between transition-colors select-none"
                                    :class="{ 'bg-blue-50/90 font-bold text-[#1c69d4]': currentOp === op.key }">
                                    <span class="text-xs text-gray-700" :class="{ 'font-bold text-[#1c69d4]': currentOp === op.key }" x-text="op.label"></span>
                                    <span x-show="currentOp === op.key" class="text-xs font-bold text-[#1c69d4]">✓</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- 3. Nilai Filter (Searchable Multi-Select Checklist / Inputs) --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                            3. Nilai Filter
                        </label>
                        <template x-if="activeFieldDef && activeFieldDef.type === 'select'">
                            <span class="text-[11px] font-semibold text-[#1c69d4]" x-text="(Array.isArray(currentVal) ? currentVal.length : 0) + ' opsi dipilih'"></span>
                        </template>
                    </div>

                    {{-- Kasus Operator Empty / Not Empty --}}
                    <div x-show="currentOp === 'empty' || currentOp === 'not_empty'"
                        class="p-3 bg-blue-50/60 border border-blue-200 rounded-xl text-xs text-blue-800">
                        Operator ini tidak memerlukan nilai. Sistem akan langsung menyaring baris data yang kondisinya <strong x-text="currentOp === 'empty' ? 'Kosong / Belum diisi' : 'Terisi / Memiliki data'"></strong>.
                    </div>

                    {{-- Kasus Range Between --}}
                    <div x-show="currentOp === 'between'" class="grid grid-cols-2 gap-2">
                        <div>
                            <span class="text-[11px] text-gray-500 mb-1 block">Dari Nilai:</span>
                            <input type="number" x-model="currentVal" placeholder="Nilai awal..."
                                class="w-full text-sm border-gray-300 rounded-xl focus:ring-[#1c69d4] focus:border-[#1c69d4] p-2.5">
                        </div>
                        <div>
                            <span class="text-[11px] text-gray-500 mb-1 block">Sampai Nilai:</span>
                            <input type="number" x-model="currentValEnd" placeholder="Nilai akhir..."
                                class="w-full text-sm border-gray-300 rounded-xl focus:ring-[#1c69d4] focus:border-[#1c69d4] p-2.5">
                        </div>
                    </div>

                    {{-- Kasus Master Data (Dropdown / Select) -> Selalu Searchable Multi-Select Checklist --}}
                    <div x-show="activeFieldDef && activeFieldDef.type === 'select' && currentOp !== 'empty' && currentOp !== 'not_empty'"
                        class="border border-gray-200 rounded-xl bg-gray-50/40 p-3 space-y-2.5">
                        
                        {{-- Search & Bulk Actions Bar --}}
                        <div class="flex items-center justify-between gap-2 pb-2 border-b border-gray-200/80">
                            <div class="relative flex-1">
                                <input type="text" x-model="searchOption" placeholder="Ketik cari dalam master data..."
                                    class="w-full pl-8 pr-3 py-1.5 text-xs bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#1c69d4] focus:border-[#1c69d4]">
                                <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button" @click="selectAllOptions()"
                                    class="px-2 py-1 text-[11px] font-bold text-blue-600 hover:bg-blue-50 rounded-md transition-colors cursor-pointer">
                                    Pilih Semua
                                </button>
                                <span class="text-gray-300 text-xs">|</span>
                                <button type="button" @click="deselectAllOptions()"
                                    class="px-2 py-1 text-[11px] font-semibold text-gray-500 hover:bg-gray-100 rounded-md transition-colors cursor-pointer">
                                    Batal Semua
                                </button>
                            </div>
                        </div>

                        {{-- Options Checklist with scroll --}}
                        <div class="max-h-56 overflow-y-auto space-y-1 pr-1 divide-y divide-gray-100">
                            <template x-for="opt in filteredOptionsList" :key="opt.value">
                                <label class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg cursor-pointer transition-colors hover:bg-blue-50/70 select-none"
                                    :class="{ 'bg-blue-50 text-[#1c69d4] font-semibold': Array.isArray(currentVal) && currentVal.includes(opt.value) }">
                                    <input type="checkbox" :value="opt.value" x-model="currentVal"
                                        class="rounded text-[#1c69d4] focus:ring-[#1c69d4] w-4 h-4 border-gray-300 cursor-pointer">
                                    <span x-text="opt.label" class="text-xs text-gray-800 truncate"></span>
                                </label>
                            </template>
                            <div x-show="filteredOptionsList.length === 0" class="py-4 text-center text-xs text-gray-400 italic">
                                Opsi tidak ditemukan
                            </div>
                        </div>

                        {{-- Footer Info --}}
                        <div class="pt-2 border-t border-gray-200/80 flex items-center justify-between text-[11px] text-gray-500">
                            <span>Menampilkan <strong x-text="filteredOptionsList.length"></strong> opsi</span>
                            <span>Terpilih: <strong class="text-[#1c69d4]" x-text="Array.isArray(currentVal) ? currentVal.length : 0"></strong> opsi</span>
                        </div>
                    </div>

                    {{-- Kasus Number Input --}}
                    <div x-show="activeFieldDef && activeFieldDef.type === 'number' && currentOp !== 'between'">
                        <input type="number" x-model="currentVal" placeholder="Masukkan angka/nominal..."
                            class="w-full text-sm border-gray-300 rounded-xl focus:ring-[#1c69d4] focus:border-[#1c69d4] p-2.5">
                    </div>

                    {{-- Kasus Text Input --}}
                    <div x-show="activeFieldDef && activeFieldDef.type === 'text' && currentOp !== 'empty' && currentOp !== 'not_empty'">
                        <input type="text" x-model="currentVal" placeholder="Ketik kata kunci pencarian..."
                            class="w-full text-sm border-gray-300 rounded-xl focus:ring-[#1c69d4] focus:border-[#1c69d4] p-2.5">
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-2.5">
                    <button type="button" wire:click="closeFilterModal"
                        class="px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 rounded-xl transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" :disabled="isSubmitting"
                        class="px-5 py-2 text-xs font-bold text-white bg-[#1c69d4] hover:bg-[#1654a8] disabled:opacity-50 disabled:cursor-wait rounded-xl shadow-sm active:scale-95 transition-all cursor-pointer inline-flex items-center gap-1.5">
                        <svg x-show="isSubmitting" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isSubmitting ? 'Menerapkan...' : 'Terapkan Filter'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
