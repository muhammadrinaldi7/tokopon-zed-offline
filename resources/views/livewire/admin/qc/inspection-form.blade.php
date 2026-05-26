<div>
    @if ($isSaved)
        <div class="bg-emerald-50 border-2 border-dashed border-emerald-200 rounded-2xl p-8 text-center">
            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-xl font-black text-gray-900">QC Selesai & Tersimpan</h3>
            <p class="text-gray-500 mt-2 max-w-sm mx-auto">
                Hasil inspeksi QC untuk IMEI <span class="font-bold text-gray-800">{{ $imei }}</span> telah disimpan. Anda bisa melanjutkan proses selanjutnya.
            </p>
            <div class="mt-6 flex items-center justify-center gap-3">
                <span class="px-4 py-1.5 rounded-lg text-sm font-bold bg-white shadow-sm border border-gray-100 text-gray-700">
                    Skor: {{ $this->passedCount }}/{{ $this->totalItems }} OK
                </span>
                <span class="px-4 py-1.5 rounded-lg text-sm font-bold shadow-sm border {{ $verdict === 'pass' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : ($verdict === 'fail' ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-amber-50 border-amber-200 text-amber-700') }} uppercase">
                    Status: {{ $verdict }}
                </span>
            </div>
        </div>
    @else
        <form wire:submit.prevent="saveInspection" class="space-y-8">
            {{-- Header Form --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-black text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#1c69d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        Form Inspeksi QC
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Lakukan pengecekan fisik dan fungsi perangkat dengan teliti.</p>
                </div>
                
                @if ($template)
                    <div class="bg-[#eff6ff] text-[#1c69d4] px-4 py-2 rounded-lg text-xs font-bold border border-[#1c69d4]/20 flex items-center gap-2">
                        <span>Template:</span>
                        <span class="uppercase tracking-wider">{{ $template->name }}</span>
                    </div>
                @endif
            </div>

            {{-- Info Dasar --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">IMEI Perangkat *</label>
                    <input type="text" wire:model="imei" required placeholder="Masukkan 15 digit IMEI"
                        class="w-full bg-gray-50 border-transparent rounded-lg py-3 px-4 focus:bg-white focus:border-[#1c69d4] focus:ring-2 focus:ring-[#1c69d4]/20 transition-all font-semibold text-gray-900">
                    @error('imei') <span class="text-xs font-bold text-rose-500 mt-1.5 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Label Inspeksi</label>
                    <input type="text" wire:model="label" placeholder="cth: QC Inbound"
                        class="w-full bg-gray-50 border-transparent rounded-lg py-3 px-4 focus:bg-white focus:border-[#1c69d4] focus:ring-2 focus:ring-[#1c69d4]/20 transition-all font-semibold text-gray-900">
                </div>
            </div>

            {{-- Checklist Dinamis --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="bg-gray-50/80 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h4 class="text-sm font-black text-gray-900 uppercase tracking-wide">Daftar Pengecekan</h4>
                    <div class="text-xs font-bold px-3 py-1 bg-white border border-gray-200 rounded-lg text-gray-600 shadow-sm">
                        <span class="text-[#1c69d4]">{{ $this->passedCount }}</span> / {{ $this->totalItems }} OK
                    </div>
                </div>
                
                <div class="divide-y divide-gray-100">
                    @if (empty($checklistResults))
                        <div class="p-8 text-center text-gray-400">
                            Tidak ada template QC yang aktif.
                        </div>
                    @else
                        @foreach ($checklistResults as $index => $item)
                            <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                                <div class="flex-1">
                                    <span class="text-sm font-bold text-gray-800">{{ $item['name'] }}</span>
                                </div>
                                <div class="w-1/2 flex justify-end">
                                    @if ($item['type'] === 'boolean')
                                        <label class="flex items-center gap-3 cursor-pointer select-none">
                                            <span class="text-xs font-bold {{ $item['value'] ? 'text-emerald-600' : 'text-gray-400' }}">
                                                {{ $item['value'] ? 'OK (PASS)' : 'NOT OK' }}
                                            </span>
                                            <div class="relative flex items-center">
                                                <input type="checkbox" wire:model.live="checklistResults.{{ $index }}.value" class="sr-only peer">
                                                <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-500/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-500 shadow-inner"></div>
                                            </div>
                                        </label>
                                    @else
                                        <input type="text" wire:model.live="checklistResults.{{ $index }}.value" placeholder="Input nilai (cth: 92%)"
                                            class="w-full max-w-[200px] bg-gray-50 border-transparent rounded-lg py-2 px-3 text-sm focus:bg-white focus:border-[#1c69d4] focus:ring-2 focus:ring-[#1c69d4]/20 transition-all font-semibold">
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- Keputusan & Catatan --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-6">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-3">Keputusan Akhir (Verdict) *</label>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex-1 min-w-[150px] cursor-pointer">
                            <input type="radio" wire:model="verdict" value="pass" class="peer sr-only">
                            <div class="px-4 py-4 rounded-xl border-2 border-gray-100 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 hover:bg-gray-50 transition-all text-center">
                                <span class="block text-emerald-600 mb-1">
                                    <svg class="w-6 h-6 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                </span>
                                <span class="text-sm font-bold text-gray-900 block">LAYAK BELI (PASS)</span>
                                <span class="text-[10px] text-gray-500">Unit memenuhi standar QC</span>
                            </div>
                        </label>
                        <label class="flex-1 min-w-[150px] cursor-pointer">
                            <input type="radio" wire:model="verdict" value="fail" class="peer sr-only">
                            <div class="px-4 py-4 rounded-xl border-2 border-gray-100 peer-checked:border-rose-500 peer-checked:bg-rose-50 hover:bg-gray-50 transition-all text-center">
                                <span class="block text-rose-500 mb-1">
                                    <svg class="w-6 h-6 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                </span>
                                <span class="text-sm font-bold text-gray-900 block">TIDAK LAYAK (FAIL)</span>
                                <span class="text-[10px] text-gray-500">Tolak atau kembalikan</span>
                            </div>
                        </label>
                        <label class="flex-1 min-w-[150px] cursor-pointer">
                            <input type="radio" wire:model="verdict" value="conditional" class="peer sr-only">
                            <div class="px-4 py-4 rounded-xl border-2 border-gray-100 peer-checked:border-amber-500 peer-checked:bg-amber-50 hover:bg-gray-50 transition-all text-center">
                                <span class="block text-amber-500 mb-1">
                                    <svg class="w-6 h-6 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                </span>
                                <span class="text-sm font-bold text-gray-900 block">BERSYARAT</span>
                                <span class="text-[10px] text-gray-500">Perlu negosiasi ulang harga</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Upload Foto Bukti (4-6 Foto)</label>
                    <input type="file" wire:model="photos" multiple accept="image/*"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-[#eff6ff] file:text-[#1c69d4] hover:file:bg-[#1c69d4]/10 file:transition-colors">
                    @error('photos.*') <span class="text-xs font-bold text-rose-500 mt-1.5 block">{{ $message }}</span> @enderror
                    
                    @if ($photos)
                        <div class="flex flex-wrap gap-3 mt-4">
                            @foreach ($photos as $photo)
                                <img src="{{ $photo->temporaryUrl() }}" class="h-20 w-20 object-cover rounded-lg border border-gray-200 shadow-sm">
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Catatan Inspektor</label>
                    <textarea wire:model="inspectorNotes" rows="3" placeholder="Tambahkan catatan khusus jika ada kerusakan tertentu..."
                        class="w-full bg-gray-50 border-transparent rounded-lg py-3 px-4 focus:bg-white focus:border-[#1c69d4] focus:ring-2 focus:ring-[#1c69d4]/20 transition-all text-sm font-medium"></textarea>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="px-8 py-3 rounded-xl font-bold text-white bg-gradient-to-r from-[#1c69d4] to-[#7C74F0] hover:shadow-sm hover:shadow-[#1c69d4]/40 hover:-translate-y-0.5 transition-all flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Simpan Hasil QC
                </button>
            </div>
        </form>
    @endif
</div>
