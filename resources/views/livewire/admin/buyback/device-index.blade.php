<div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Daftar Perangkat Buyback</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola data harga dasar HP untuk fitur Tukar Tambah & Jual HP.</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="openSyncAccurateModal" wire:loading.attr="disabled"
                class="flex items-center gap-2 bg-[#f59e0b] hover:bg-[#d97706] text-white px-4 py-2.5 rounded-lg text-sm font-bold shadow-sm transition-all">
                <svg wire:loading.remove wire:target="openSyncAccurateModal" class="w-4 h-4" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                <svg wire:loading wire:target="openSyncAccurateModal" class="w-4 h-4 animate-spin" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Sync Master Accurate
            </button>
            <button wire:click="syncTierDevice" wire:loading.attr="disabled"
                wire:confirm="Anda yakin ingin menyesuaikan tier semua perangkat dengan aturan harga saat ini?"
                class="bg-white border border-gray-200 text-gray-700 px-5 py-2.5 rounded-lg font-bold hover:bg-gray-50 hover:text-#1c69d4 transition shadow-sm flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Sync Tier
                <svg wire:loading wire:target="syncTierDevice" class="animate-spin -ml-1 mr-2 h-4 w-4 text-#1c69d4"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </button>
            {{-- <a href="{{ route('admin.buyback.create') }}" wire:navigate
                class="bg-[#1c69d4] text-white px-5 py-2.5 rounded-lg font-bold hover:bg-[#3f36b8] transition shadow-sm shadow-[#1c69d4]/30 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Perangkat
            </a> --}}
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-neutral-100-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="bg-gray-50 border-b border-gray-100 text-xs text-gray-400 font-bold uppercase tracking-wider">
                        <th class="p-4">Merek & Model</th>
                        <th class="p-4">Kapasitas</th>
                        <th class="p-4">Harga Dasar (Mulus 100%)</th>
                        <th class="p-4">Kategori Tier</th>
                        <th class="p-4 text-center">Status</th>
                        <th class="p-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($devices as $device)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center font-bold text-gray-400">
                                        {{ substr($device->brand->name ?? '?', 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900">{{ $device->model_name }}</p>
                                        <p class="text-xs text-gray-500 font-semibold">
                                            {{ $device->brand->name ?? 'Unknown' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold">
                                    {{ $device->ram ? $device->ram . ' / ' : '' }}{{ $device->storage }}
                                </span>
                            </td>
                            <td class="p-4">
                                <p class="font-bold text-gray-800">Rp
                                    {{ number_format($device->base_price, 0, ',', '.') }}</p>
                            </td>
                            <td class="p-4">
                                @if ($device->tier)
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 bg-[#1c69d4]/10 text-[#1c69d4] rounded-lg text-xs font-bold">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        {{ $device->tier->name }}
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 text-amber-600 rounded-lg text-xs font-bold">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        Tidak Ada Tier
                                    </span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                @if ($device->is_active)
                                    <span
                                        class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[10px] uppercase tracking-wider font-black rounded-full">Aktif</span>
                                @else
                                    <span
                                        class="px-3 py-1 bg-gray-100 text-gray-500 text-[10px] uppercase tracking-wider font-black rounded-full">Nonaktif</span>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                <button wire:click="editDevice({{ $device->id }})"
                                    class="p-2 text-[#1c69d4] hover:bg-[#eff6ff] rounded-lg transition"
                                    title="Edit">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <p class="font-medium text-gray-900">Belum ada perangkat yang dikonfigurasi</p>
                                    <p class="text-sm mt-1">Tambahkan perangkat pertama Anda untuk memulai fitur
                                        Buyback.</p>
                                    <a href="{{ route('admin.buyback.create') }}" wire:navigate
                                        class="mt-4 px-4 py-2 bg-white border border-gray-200 text-gray-700 font-bold rounded-lg hover:bg-gray-50 transition text-sm">
                                        Tambah Perangkat
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Edit Modal --}}
    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div wire:click="closeEditModal" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm"></div>

            <div
                class="relative transform overflow-hidden rounded-2xl bg-white/90 backdrop-blur-2xl border border-white shadow-xl text-left w-full max-w-md">

                {{-- Header --}}
                <div class="px-6 py-5 border-b border-gray-200/50 flex justify-between items-center bg-white/40">
                    <h2 class="text-[17px] font-semibold tracking-tight text-gray-900">Edit Perangkat Buyback</h2>
                    <button wire:click="closeEditModal"
                        class="text-gray-400 hover:text-gray-600 bg-gray-100/50 hover:bg-gray-200/50 rounded-full p-1.5 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <form wire:submit.prevent="updateDevice" class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Model</label>
                        <input type="text" wire:model="editModelName"
                            class="w-full text-[15px] bg-white/60 border border-gray-200/70 focus:bg-white focus:border-[#1c69d4] focus:ring-4 focus:ring-[#1c69d4]/10 rounded-lg px-4 py-3 shadow-sm transition-all text-gray-800"
                            required>
                        @error('editModelName')
                            <span class="text-xs text-rose-500 font-medium mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Kapasitas RAM</label>
                            <input type="text" wire:model="editRam" placeholder="Cth: 8GB"
                                class="w-full text-[15px] bg-white/60 border border-gray-200/70 focus:bg-white focus:border-[#1c69d4] focus:ring-4 focus:ring-[#1c69d4]/10 rounded-lg px-4 py-3 shadow-sm transition-all text-gray-800">
                            @error('editRam')
                                <span class="text-xs text-rose-500 font-medium mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Kapasitas Storage</label>
                            <input type="text" wire:model="editStorage" placeholder="Cth: 256GB"
                                class="w-full text-[15px] bg-white/60 border border-gray-200/70 focus:bg-white focus:border-[#1c69d4] focus:ring-4 focus:ring-[#1c69d4]/10 rounded-lg px-4 py-3 shadow-sm transition-all text-gray-800">
                            @error('editStorage')
                                <span class="text-xs text-rose-500 font-medium mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Harga Dasar Beli (Kondisi
                            Mulus)</label>
                        <div class="relative">
                            <span
                                class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">Rp</span>
                            <input type="number" wire:model="editBasePrice" min="0" step="1000"
                                class="w-full text-[15px] bg-white/60 border border-gray-200/70 focus:bg-white focus:border-[#1c69d4] focus:ring-4 focus:ring-[#1c69d4]/10 rounded-lg pl-10 pr-4 py-3 shadow-sm transition-all text-gray-800"
                                required>
                        </div>
                        @error('editBasePrice')
                            <span class="text-xs text-rose-500 font-medium mt-1 block">{{ $message }}</span>
                        @enderror
                        @if ($editBasePrice > 0)
                            <p class="text-xs text-gray-400 mt-1.5 ml-1">= Rp
                                {{ number_format($editBasePrice, 0, ',', '.') }}</p>
                        @endif
                    </div>

                    <div
                        class="bg-gray-50 border border-gray-200/70 rounded-lg p-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-gray-800">Status Perangkat</p>
                            <p class="text-[11px] text-gray-500">Nonaktifkan jika tidak lagi menerima HP ini</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="editIsActive" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-[#1c69d4]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1c69d4]">
                            </div>
                        </label>
                    </div>

                    {{-- Actions --}}
                    <div class="pt-2 flex gap-3">
                        <button type="button" wire:click="closeEditModal"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-lg text-[15px] font-semibold transition-all">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 bg-[#1c69d4] text-white py-3 rounded-lg text-[15px] font-semibold hover:bg-[#3f36b8] hover:shadow-sm hover:shadow-[#1c69d4]/30 active:scale-[0.98] transition-all">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Sync Accurate Modal --}}
    @if ($showSyncAccurateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div wire:click="$set('showSyncAccurateModal', false)" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm"></div>

            <div class="relative transform overflow-hidden rounded-2xl bg-white border border-white shadow-xl text-left w-full max-w-lg">
                {{-- Header --}}
                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Sync Master Data Accurate</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Tarik data item Accurate secara massal menjadi Perangkat Buyback</p>
                    </div>
                    <button wire:click="$set('showSyncAccurateModal', false)" class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <form wire:submit.prevent="processSyncAccurate" class="p-6 space-y-5">
                    <div class="bg-blue-50 border border-blue-100 p-4 rounded-xl text-sm text-blue-800">
                        Pilih <strong>Business Unit</strong> target, lalu masukkan kata kunci (misal: <code class="font-bold">Bekas</code> atau <code class="font-bold">iPhone</code>) untuk memfilter produk yang akan ditarik.
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Target Business Unit</label>
                        <select wire:model="syncTargetBuId" required class="w-full rounded-lg border-gray-200 py-2.5 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm text-gray-800">
                            <option value="">-- Pilih BU --</option>
                            @foreach(\App\Models\BusinessUnit::all() as $bu)
                                <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1.5">Kata Kunci Filter (Opsional)</label>
                        <input type="text" wire:model="syncKeyword" placeholder="Cth: Bekas, Samsung..."
                            class="w-full rounded-lg border-gray-200 py-2.5 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm text-gray-800">
                        <p class="text-xs text-gray-400 mt-1">Kosongkan jika ingin menarik SEMUA item di BU tersebut (Tidak disarankan jika data ribuan).</p>
                    </div>

                    {{-- Actions --}}
                    <div class="pt-4 flex gap-3 border-t border-gray-100">
                        <button type="button" wire:click="$set('showSyncAccurateModal', false)"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl text-[15px] font-bold transition-all">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="flex-[2] flex items-center justify-center gap-2 bg-[#f59e0b] text-white py-3 rounded-xl text-[15px] font-bold hover:bg-[#d97706] shadow-sm transition-all">
                            <svg wire:loading wire:target="processSyncAccurate" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="processSyncAccurate">Mulai Sinkronisasi Massal</span>
                            <span wire:loading wire:target="processSyncAccurate">Menyinkronkan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

