<div>
    @if ($isSaved)
        <div class="bg-emerald-50 border-2 border-dashed border-emerald-200 rounded-2xl p-8 text-center">
            <div
                class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-xl font-black text-gray-900">QC Selesai & Tersimpan</h3>
            <p class="text-gray-500 mt-2 max-w-sm mx-auto">
                Hasil inspeksi QC untuk IMEI <span class="font-bold text-gray-800">{{ $imei }}</span> telah
                disimpan. Anda bisa melanjutkan proses selanjutnya.
            </p>
            <div class="mt-6 flex items-center justify-center gap-3">
                <span
                    class="px-4 py-1.5 rounded-lg text-sm font-bold bg-white shadow-sm border border-gray-100 text-gray-700">
                    Skor: {{ $this->passedCount }}/{{ $this->totalItems }} OK
                </span>
                <span
                    class="px-4 py-1.5 rounded-lg text-sm font-bold shadow-sm border {{ $verdict === 'pass' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : ($verdict === 'fail' ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-amber-50 border-amber-200 text-amber-700') }} uppercase">
                    Status: {{ $verdict }}
                </span>
            </div>
        </div>
    @else
        <form wire:submit.prevent="saveInspection" class="space-y-6" x-data="{ qcStep: 1 }">
            @if(!$hideHeader)
                {{-- Header Form --}}
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-100 pb-4">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#1c69d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            Inspeksi Kelayakan
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">Lakukan pengecekan fisik dan fungsi perangkat dengan teliti.</p>
                    </div>

                    @if ($template)
                        <div class="bg-[#eff6ff] text-[#1c69d4] px-4 py-2 rounded-lg text-xs font-bold border border-[#1c69d4]/20 flex items-center gap-2">
                            <span class="uppercase tracking-wider">{{ $template->name }}</span>
                        </div>
                    @endif
                </div>
            @endif

            @php
                $groupedChecklist = collect($checklistResults)->map(function($item, $index) {
                    $item['_index'] = $index;
                    return $item;
                })->groupBy('category');
                
                $categories = $groupedChecklist->keys();
                $maxQcStep = $categories->count() + 1;
            @endphp

            {{-- Progress Bar QC --}}
            <div class="mb-6 relative">
                <div class="h-2 bg-neutral-100 rounded-full overflow-hidden">
                    <div class="h-full bg-violet-500 transition-all duration-300" :style="'width: ' + ((qcStep / {{ $maxQcStep }}) * 100) + '%'"></div>
                </div>
                <div class="mt-2 text-xs font-bold text-neutral-500 text-right">Tahap <span x-text="qcStep"></span> dari <span>{{ $maxQcStep }}</span></div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 min-h-[400px]">
                
                {{-- Kategori Dinamis --}}
                @foreach ($categories as $catIndex => $category)
                    <div x-show="qcStep === {{ $catIndex + 1 }}" x-transition.opacity class="space-y-6" style="display: none;">
                        <div class="flex items-center justify-between mb-4 border-b pb-2">
                            <h4 class="text-lg font-black text-gray-900 uppercase tracking-wide text-violet-700">{{ $category }}</h4>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($groupedChecklist[$category] as $item)
                                @php $index = $item['_index']; @endphp
                                <div class="p-4 border rounded-xl hover:bg-gray-50 transition-colors {{ $item['value'] ? 'border-emerald-200 bg-emerald-50/30' : 'border-gray-200' }}">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-bold text-gray-800">{{ $item['name'] }}</span>
                                        <div>
                                            @if ($item['type'] === 'boolean')
                                                <label class="flex items-center gap-3 cursor-pointer select-none">
                                                    <span class="text-xs font-bold {{ $item['value'] ? 'text-emerald-600' : 'text-gray-400' }}">
                                                        {{ $item['value'] ? 'OK' : 'TIDAK OK' }}
                                                    </span>
                                                    <div class="relative flex items-center">
                                                        <input type="checkbox" wire:model.live="checklistResults.{{ $index }}.value" class="sr-only peer">
                                                        <div class="w-12 h-6 bg-gray-200 border-2 border-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-500/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500 peer-checked:border-emerald-500 shadow-inner">
                                                        </div>
                                                    </div>
                                                </label>
                                            @else
                                                <input type="text" wire:model.live="checklistResults.{{ $index }}.value"
                                                    placeholder="Input (cth: 92%)"
                                                    class="w-full max-w-[120px] bg-white border border-gray-300 rounded-lg py-1.5 px-3 text-sm focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-all font-semibold">
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-8 flex justify-between items-center pt-4 border-t border-gray-100">
                            @if ($catIndex > 0)
                                <button type="button" @click="qcStep--" class="px-6 py-2 border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition">Kembali</button>
                            @else
                                <div></div>
                            @endif
                            <button type="button" @click="if (qcStep + 1 === {{ $maxQcStep }}) { $wire.calculateAutoVerdict(); }; qcStep++" class="px-6 py-2 bg-violet-600 text-white font-bold rounded-xl hover:bg-violet-700 transition shadow-md shadow-violet-200">Selanjutnya <i class="fas fa-arrow-right ml-1"></i></button>
                        </div>
                    </div>
                @endforeach

                {{-- Step Akhir: Kesimpulan & Foto --}}
                <div x-show="qcStep === {{ $maxQcStep }}" x-transition.opacity class="space-y-8" style="display: none;">
                    
                    <div>
                        <h4 class="text-lg font-black text-gray-900 uppercase tracking-wide text-violet-700 border-b pb-2 mb-4">Informasi Tambahan & Keputusan</h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">IMEI Perangkat *</label>
                                <input type="text" wire:model="imei" required placeholder="Masukkan 15 digit IMEI"
                                    class="w-full bg-gray-50 border-transparent rounded-lg py-3 px-4 focus:bg-white focus:border-violet-500 focus:ring-2 focus:ring-violet-200 transition-all font-semibold text-gray-900">
                                @error('imei') <span class="text-xs font-bold text-rose-500 mt-1.5 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Catatan Inspektor</label>
                                <textarea wire:model="inspectorNotes" rows="4" placeholder="Catatan kerusakan..."
                                    class="w-full bg-gray-50 border-transparent rounded-lg py-3 px-4 focus:bg-white focus:border-violet-500 focus:ring-2 focus:ring-violet-200 transition-all text-sm font-medium"></textarea>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Upload Foto Bukti (4-6 Foto)</label>
                            <input type="file" wire:model="photos" multiple accept="image/*"
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 transition-colors">
                            @if ($photos)
                                <div class="flex flex-wrap gap-3 mt-4">
                                    @foreach ($photos as $photo)
                                        <img src="{{ $photo->temporaryUrl() }}" class="h-20 w-20 object-cover rounded-lg border border-gray-200 shadow-sm">
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    @if (!$hideVerdict)
                        <div>
                            <h4 class="text-lg font-black text-violet-700 uppercase tracking-wider border-b border-neutral-100 pb-2">Kesimpulan Sistem (Auto-Verdict)</h4>
                            <p class="text-sm text-neutral-500 mt-1 mb-4">Berdasarkan data inspeksi yang Anda masukkan, sistem menentukan bahwa perangkat ini:</p>
                            
                            <div class="mt-4">
                                {{-- Tampilan Dinamis Berdasarkan verdict --}}
                                <div wire:loading.remove wire:target="calculateAutoVerdict">
                                    @if($verdict === 'pass')
                                        <div class="p-6 border-2 border-emerald-500 bg-emerald-50 rounded-2xl flex flex-col items-center justify-center text-center">
                                            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        </div>
                                        <h5 class="text-xl font-black text-emerald-700 mb-1">LAYAK BELI (PASS)</h5>
                                        <p class="text-sm text-emerald-600 font-medium">Seluruh komponen perangkat berfungsi dengan baik.</p>
                                    </div>
                                @elseif($verdict === 'conditional')
                                    <div class="p-6 border-2 border-amber-500 bg-amber-50 rounded-2xl flex flex-col items-center justify-center text-center">
                                        <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        </div>
                                        <h5 class="text-xl font-black text-amber-700 mb-1">BERSYARAT (NEEDS SERVICE)</h5>
                                        <p class="text-sm text-amber-700/80 font-medium whitespace-pre-line">{{ $inspectorNotes }}</p>
                                    </div>
                                @elseif($verdict === 'fail')
                                    <div class="p-6 border-2 border-rose-500 bg-rose-50 rounded-2xl flex flex-col items-center justify-center text-center">
                                        <div class="w-16 h-16 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </div>
                                        <h5 class="text-xl font-black text-rose-700 mb-1">TIDAK LAYAK (FAIL)</h5>
                                        <p class="text-sm text-rose-700/80 font-medium whitespace-pre-line">{{ $inspectorNotes }}</p>
                                    </div>
                                    @endif
                                </div>
                                
                                <div wire:loading wire:target="calculateAutoVerdict" class="w-full">
                                    <div class="p-10 border-2 border-dashed border-neutral-200 bg-neutral-50 rounded-2xl flex flex-col items-center justify-center text-center">
                                        <span class="animate-spin w-8 h-8 border-4 border-violet-600 border-t-transparent rounded-full mb-4"></span>
                                        <p class="text-sm text-neutral-500 font-bold">Sistem sedang menganalisa data inspeksi...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="mt-8 flex justify-between items-center pt-4 border-t border-gray-100">
                        <button type="button" @click="qcStep--" class="px-6 py-3 border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition">Kembali</button>
                        <button type="submit" class="px-8 py-3 rounded-xl font-bold text-white bg-gradient-to-r from-violet-600 to-indigo-600 hover:shadow-lg hover:shadow-violet-200 transition-all flex items-center gap-2">
                            <i class="fas fa-save"></i> Simpan Hasil QC
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @endif
</div>
