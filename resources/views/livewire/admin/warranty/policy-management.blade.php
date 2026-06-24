<div class="space-y-6">
    <!-- Header -->
    <div
        class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div>
            <h2 class="text-2xl font-black text-gray-800">Pengaturan Kebijakan Garansi</h2>
            <p class="text-gray-500 text-sm mt-1">Kelola durasi, cakupan, dan batas klaim untuk garansi toko maupun
                asuransi berbayar.</p>
        </div>
        <button wire:click="openCreateModal"
            class="px-4 py-2 bg-[#1c69d4] text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-bold flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Kebijakan Baru
        </button>
    </div>

    <!-- Tabel Data -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50/50">
            <div class="relative w-full md:w-96">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama kebijakan..."
                    class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors shadow-sm">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-sm border-b border-gray-100">
                        <th class="py-4 px-6 font-semibold">Nama / Tipe</th>
                        <th class="py-4 px-6 font-semibold">Brand / Produk</th>
                        <th class="py-4 px-6 font-semibold">Durasi & Klaim</th>
                        <th class="py-4 px-6 font-semibold">Kategori Asuransi</th>
                        <th class="py-4 px-6 font-semibold">Status</th>
                        <th class="py-4 px-6 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($policies as $policy)
                        <tr wire:key="policy-{{ $policy->id }}"
                            class="hover:bg-blue-50/30 transition-colors duration-200">
                            <td class="py-4 px-6">
                                <div class="font-bold text-gray-800">{{ $policy->name }}</div>
                                <div class="text-xs font-medium mt-1">
                                    @if ($policy->type === 'insurance')
                                        <span class="text-purple-600 bg-purple-100 px-2 py-0.5 rounded-full">Asuransi
                                            Berbayar</span>
                                    @else
                                        <span class="text-blue-600 bg-blue-100 px-2 py-0.5 rounded-full">Garansi Default
                                            Toko</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                @if ($policy->brand_id)
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                        {{ $policy->brand->name }}
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-gray-50 text-gray-500 border border-gray-200">
                                        Semua Brand
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-semibold text-gray-800">{{ $policy->duration_days }} Hari</div>
                                <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Maks {{ $policy->max_claims }} Klaim
                                </div>
                            </td>
                            <td class="py-4 px-6 text-gray-600 font-medium">
                                {{ $policy->item_category ?: '-' }}
                            </td>
                            <td class="py-4 px-6">
                                <button wire:click="toggleActive({{ $policy->id }})"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold transition-colors cursor-pointer {{ $policy->is_active ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-rose-100 text-rose-700 hover:bg-rose-200' }}">
                                    <span
                                        class="w-1.5 h-1.5 rounded-full {{ $policy->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                    {{ $policy->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="edit({{ $policy->id }})"
                                        class="btn btn-sm btn-circle btn-ghost text-blue-600 hover:bg-blue-100"
                                        title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button wire:click="delete({{ $policy->id }})"
                                        wire:confirm="Yakin ingin menghapus kebijakan ini?"
                                        class="btn btn-sm btn-circle btn-ghost text-red-600 hover:bg-red-100"
                                        title="Hapus">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-4 text-gray-300"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-lg font-medium text-gray-500">Belum ada kebijakan garansi</p>
                                    <p class="text-sm mt-1">Silakan tambah kebijakan baru untuk memulai.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($policies->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50">
                {{ $policies->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form -->
    @if ($showModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                wire:click="$set('showModal', false)"></div>

            <!-- Modal Content -->
            <div
                class="relative bg-gray-50 rounded-2xl w-full max-w-4xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                <!-- Modal Header -->
                <div class="bg-white px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-50 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="font-bold text-xl text-gray-800">
                            {{ $isEdit ? 'Edit Kebijakan Garansi' : 'Tambah Kebijakan Garansi Baru' }}
                        </h3>
                    </div>
                    <button wire:click="$set('showModal', false)"
                        class="text-gray-400 hover:text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-full p-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1">
                    <form wire:submit.prevent="save" id="policyForm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Nama Kebijakan -->
                            <div class="form-control">
                                <label class="block text-sm font-bold text-gray-700 mb-1.5">Nama Kebijakan</label>
                                <input type="text" wire:model="name"
                                    class="w-full rounded-lg border-gray-200 px-4 py-2.5 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm text-gray-800 bg-white shadow-sm"
                                    placeholder="Contoh: Garansi Resmi Apple 1 Tahun" required>
                                @error('name')
                                    <span class="text-rose-500 text-xs mt-1 font-medium">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Tipe Garansi -->
                            <div class="form-control">
                                <label class="block text-sm font-bold text-gray-700 mb-1.5">Tipe Kebijakan</label>
                                <select wire:model.live="type"
                                    class="w-full rounded-lg border-gray-200 px-4 py-2.5 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm text-gray-800 bg-white shadow-sm"
                                    required>
                                    <option value="store_default">Garansi Default Toko (Gratis)</option>
                                    <option value="insurance">Asuransi Berbayar (Add-on)</option>
                                </select>
                            </div>

                            <!-- Brand (Opsional) -->
                            <div class="form-control">
                                <label class="block text-sm font-bold text-gray-700 mb-1.5">
                                    Berlaku Untuk Brand <span
                                        class="text-gray-400 font-normal italic">(Opsional)</span>
                                </label>
                                <select wire:model="brand_id"
                                    class="w-full rounded-lg border-gray-200 px-4 py-2.5 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm text-gray-800 bg-white shadow-sm">
                                    <option value="">Semua Brand (Global)</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Kategori Item Accurate (Untuk Asuransi) -->
                            @if ($type === 'insurance')
                                <div class="form-control">
                                    <label class="block text-sm font-bold text-gray-700 mb-1.5">
                                        Kategori Item di Accurate
                                    </label>
                                    <input type="text" wire:model="item_category"
                                        class="w-full rounded-lg border-purple-300 px-4 py-2.5 focus:ring-purple-500 focus:border-purple-500 text-sm text-gray-800 bg-white shadow-sm"
                                        placeholder="Contoh: Asuransi">
                                    <p class="text-[11px] text-gray-500 mt-1">Kategori barang di Accurate yang memicu
                                        asuransi.</p>
                                </div>
                            @else
                                <div class="hidden md:block"></div>
                            @endif

                            <!-- Durasi -->
                            <div class="form-control">
                                <label class="block text-sm font-bold text-gray-700 mb-1.5">Durasi Garansi
                                    (Hari)</label>
                                <div class="relative">
                                    <input type="number" wire:model="duration_days"
                                        class="w-full rounded-lg border-gray-200 px-4 py-2.5 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm text-gray-800 bg-white shadow-sm pr-16"
                                        required min="1">
                                    <span
                                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-500 font-medium pointer-events-none">Hari</span>
                                </div>
                            </div>

                            <!-- Max Klaim -->
                            <div class="form-control">
                                <label class="block text-sm font-bold text-gray-700 mb-1.5">Maksimal Jumlah
                                    Klaim</label>
                                <div class="relative">
                                    <input type="number" wire:model="max_claims"
                                        class="w-full rounded-lg border-gray-200 px-4 py-2.5 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm text-gray-800 bg-white shadow-sm pr-16"
                                        required min="1">
                                    <span
                                        class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-500 font-medium pointer-events-none">Kali</span>
                                </div>
                            </div>
                        </div>

                        <!-- Coverage Section -->
                        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm mb-6">
                            <div class="flex items-center justify-between mb-4 border-b border-gray-100 pb-3">
                                <div>
                                    <h4 class="font-bold text-gray-800 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Cakupan Kerusakan (Coverage)
                                    </h4>
                                    <p class="text-xs text-gray-500 mt-0.5">Tentukan jenis kerusakan apa saja yang
                                        ditanggung atau tidak ditanggung.</p>
                                </div>
                                <button type="button" wire:click="addCoverageItem"
                                    class="bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold px-3 py-1.5 rounded-lg text-sm flex items-center gap-1 transition-colors border border-blue-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah
                                </button>
                            </div>

                            <div class="space-y-3">
                                @foreach ($coverageItems as $index => $item)
                                    <div
                                        class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg group transition-colors border border-transparent hover:border-gray-100">
                                        <div class="flex-1 relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <input type="text" wire:model="coverageItems.{{ $index }}.name"
                                                class="w-full pl-9 pr-4 py-2 rounded-lg border-gray-200 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm bg-white shadow-sm"
                                                placeholder="Contoh: Kerusakan LCD, Kena Air, dll" required>
                                        </div>
                                        <div class="w-40 relative">
                                            <select wire:model="coverageItems.{{ $index }}.covered"
                                                class="w-full px-4 py-2 rounded-lg border-gray-200 text-sm bg-white shadow-sm font-bold {{ $item['covered'] ? 'text-emerald-600 focus:ring-emerald-500 focus:border-emerald-500' : 'text-rose-600 focus:ring-rose-500 focus:border-rose-500' }}">
                                                <option value="1">✓ Tercover</option>
                                                <option value="0">✕ Tidak Tercover</option>
                                            </select>
                                        </div>
                                        <button type="button" wire:click="removeCoverageItem({{ $index }})"
                                            class="p-2 text-rose-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                            title="Hapus baris">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                                @if (count($coverageItems) === 0)
                                    <div
                                        class="text-center py-6 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                                        <p class="text-gray-500 font-medium">Belum ada cakupan kerusakan.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Status Aktif -->
                        <div
                            class="bg-gray-50 border border-gray-200 rounded-xl p-4 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-bold text-gray-800">Status Kebijakan Aktif</p>
                                <p class="text-[11px] text-gray-500 mt-0.5">Jika nonaktif, kebijakan ini tidak akan
                                    digunakan untuk transaksi baru.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="is_active" class="sr-only peer">
                                <div
                                    class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-[#1c69d4]/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#1c69d4]">
                                </div>
                            </label>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="bg-white px-6 py-4 border-t border-gray-100 flex justify-end gap-3 shrink-0">
                    <button type="button" wire:click="$set('showModal', false)"
                        class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" form="policyForm"
                        class="px-6 py-2.5 bg-[#1c69d4] hover:bg-[#3f36b8] text-white font-bold rounded-xl shadow-sm transition-all flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13l4 4L19 7" />
                        </svg>
                        Simpan Data
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
