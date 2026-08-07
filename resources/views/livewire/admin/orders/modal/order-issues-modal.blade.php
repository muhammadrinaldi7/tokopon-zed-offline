{{-- ═══════════════════════════════════════════════════════════
      MODAL: Catatan & Kesalahan Order (Order Issues)
 ═══════════════════════════════════════════════════════════ --}}
@if ($showIssueModal && $selectedOrderForIssue)
    <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 animate-fade-in">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden relative flex flex-col max-h-[90vh]"
            @click.outside="$wire.closeIssues()">
            
            {{-- Header Modal --}}
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200/60 flex items-center justify-center text-amber-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-gray-900 text-base flex items-center gap-2">
                            Catatan & Kesalahan Order
                            <span class="text-xs font-mono font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-100">
                                #{{ $selectedOrderForIssue->order_number }}
                            </span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Customer: <span class="font-semibold text-gray-700">{{ $selectedOrderForIssue->user->name ?? 'User Terhapus' }}</span> 
                            • Total: <span class="font-bold text-[#1c69d4]">Rp {{ number_format($selectedOrderForIssue->grand_total, 0, ',', '.') }}</span>
                        </p>
                    </div>
                </div>

                <button wire:click="closeIssues" class="p-2 text-gray-400 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body Scrollable Area --}}
            <div class="p-6 overflow-y-auto space-y-6 flex-1 bg-white">
                {{-- Daftar Riwayat Catatan / Issues Feed --}}
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3 flex items-center justify-between">
                        <span>Riwayat Catatan ({{ $selectedOrderForIssue->issues->count() }})</span>
                        @if($selectedOrderForIssue->openIssues->count() > 0)
                            <span class="text-[11px] font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-100">
                                {{ $selectedOrderForIssue->openIssues->count() }} Perlu Diselesaikan
                            </span>
                        @else
                            <span class="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                                Semua Selesai
                            </span>
                        @endif
                    </h4>

                    @if ($selectedOrderForIssue->issues->isEmpty())
                        <div class="p-6 text-center border-2 border-dashed border-gray-100 rounded-xl bg-gray-50/50">
                            <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-xs text-gray-500 font-medium">Belum ada catatan kendala pada pesanan ini.</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($selectedOrderForIssue->issues as $issue)
                                @php
                                    $isResolved = $issue->status === 'RESOLVED';
                                    $categoryLabels = [
                                        'SALAH_PRODUK' => ['label' => 'Salah Produk / Varian', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
                                        'SALAH_SN' => ['label' => 'Salah Serial Number (SN)', 'class' => 'bg-purple-50 text-purple-700 border-purple-200'],
                                        'SELISIH_BAYAR' => ['label' => 'Selisih / Salah Bayar', 'class' => 'bg-rose-50 text-rose-700 border-rose-200'],
                                        'SALAH_CUSTOMER' => ['label' => 'Salah Customer', 'class' => 'bg-blue-50 text-blue-700 border-blue-200'],
                                        'SALAH_PROMO' => ['label' => 'Salah Diskon / Promo', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
                                        'SYNC_ACCURATE' => ['label' => 'Kendala Accurate', 'class' => 'bg-cyan-50 text-cyan-700 border-cyan-200'],
                                        'LAINNYA' => ['label' => 'Lainnya', 'class' => 'bg-gray-100 text-gray-700 border-gray-200'],
                                    ];
                                    $catInfo = $categoryLabels[$issue->category] ?? ['label' => $issue->category, 'class' => 'bg-gray-100 text-gray-700 border-gray-200'];
                                @endphp
                                <div class="p-4 rounded-xl border {{ $isResolved ? 'bg-gray-50/70 border-gray-200 opacity-80' : 'bg-amber-50/30 border-amber-100' }} transition-all">
                                    <div class="flex items-start justify-between gap-3 mb-2">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-md border {{ $catInfo['class'] }}">
                                                {{ $catInfo['label'] }}
                                            </span>
                                            
                                            <span class="text-xs font-semibold text-gray-700">
                                                {{ $issue->user->name ?? 'User' }}
                                            </span>

                                            <span class="text-[11px] text-gray-400">
                                                • {{ $issue->created_at->diffForHumans() }} ({{ $issue->created_at->format('d/m/Y H:i') }})
                                            </span>
                                        </div>

                                        {{-- Badge & Status Action --}}
                                        <div class="flex items-center gap-2">
                                            @if ($isResolved)
                                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 border border-emerald-200">
                                                    SELESAI
                                                </span>
                                                <button wire:click="toggleIssueStatus({{ $issue->id }})"
                                                    class="text-[11px] font-bold text-gray-500 hover:text-gray-800 underline transition"
                                                    title="Buka kembali kendala ini">
                                                    Buka Lagi
                                                </button>
                                            @else
                                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded bg-rose-100 text-rose-700 border border-rose-200">
                                                    OPEN
                                                </span>
                                                <button wire:click="toggleIssueStatus({{ $issue->id }})"
                                                    class="text-xs font-bold text-emerald-600 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-1 rounded-lg transition"
                                                    title="Tandai kendala ini sudah selesai diperbaiki">
                                                    ✓ Tandai Selesai
                                                </button>
                                            @endif

                                            @if (Auth::user()->hasRole('admin'))
                                                <button wire:click="deleteIssue({{ $issue->id }})"
                                                    wire:confirm="Hapus catatan kendala ini?"
                                                    class="p-1 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded transition"
                                                    title="Hapus Catatan">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <p class="text-sm text-gray-800 whitespace-pre-line leading-relaxed pl-1">
                                        {{ $issue->comment }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Form Tambah Catatan Baru --}}
                <div class="pt-4 border-t border-gray-100">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-700 mb-3 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Catatan Kendala Baru
                    </h4>

                    <form wire:submit="saveIssue" class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Kategori Kesalahan</label>
                            <select wire:model="issueCategory"
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-[#1c69d4]/20 focus:border-[#1c69d4] focus:bg-white transition">
                                <option value="SALAH_PRODUK">Kesalahan Produk / Varian</option>
                                <option value="SALAH_SN">Kesalahan Serial Number (SN / IMEI)</option>
                                <option value="SELISIH_BAYAR">Selisih / Kesalahan Nominal Bayar</option>
                                <option value="SALAH_CUSTOMER">Kesalahan Data Customer</option>
                                <option value="SALAH_PROMO">Kesalahan Diskon / Promo</option>
                                <option value="SYNC_ACCURATE">Kendala Sinkronisasi Accurate / ERP</option>
                                <option value="LAINNYA">Lainnya</option>
                            </select>
                            @error('issueCategory')
                                <span class="text-rose-500 text-xs font-medium mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Rincian Catatan / Kronologi Masalah</label>
                            <textarea wire:model="issueComment" rows="3"
                                placeholder="Jelaskan kesalahan atau kendala yang terjadi pada pesanan ini secara detail..."
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-[#1c69d4]/20 focus:border-[#1c69d4] focus:bg-white transition"></textarea>
                            @error('issueComment')
                                <span class="text-rose-500 text-xs font-medium mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="flex justify-end gap-2 pt-1">
                            <button type="button" wire:click="closeIssues"
                                class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition">
                                Tutup
                            </button>

                            <button type="submit" wire:loading.attr="disabled"
                                class="px-4 py-2 text-xs font-bold text-white bg-[#1c69d4] hover:bg-blue-700 rounded-xl transition flex items-center gap-1.5 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg wire:loading wire:target="saveIssue" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span>Simpan Catatan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
