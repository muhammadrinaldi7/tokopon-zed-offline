<div class="space-y-6">
    <!-- Header -->
    <div
        class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
        <div>
            <h2 class="text-2xl font-black text-gray-800">Policy Engine Garansi</h2>
            <p class="text-gray-500 text-sm mt-1">Buat aturan dan kartu garansi dinamis sesuai kebutuhan toko.</p>
        </div>
        <button wire:click="openCreateModal"
            class="px-5 py-2.5 bg-[#1c69d4] hover:bg-blue-700 text-white font-bold rounded-xl shadow-sm transition-colors flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Buat Policy Baru
        </button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex justify-end">
            <input type="text" wire:model.live="search" placeholder="Cari policy..."
                class="w-full md:w-64 rounded-xl border-gray-200 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm">
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-600 font-bold border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4">Nama Policy</th>
                        <th class="px-6 py-4">Tipe & Cakupan</th>
                        <th class="px-6 py-4">Durasi</th>
                        <th class="px-6 py-4">Aturan Brand / Syarat</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($policies as $policy)
                        <tr class="hover:bg-blue-50/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-gray-800">{{ $policy->name }}</td>
                            <td class="px-6 py-4">
                                @if ($policy->type == 'store_normal')
                                    <span class="inline-flex px-2 py-1 bg-blue-100 text-blue-700 rounded-md text-xs font-bold mr-2">Normal (Tanpa Diskon)</span>
                                @elseif ($policy->type == 'store_discount')
                                    <span class="inline-flex px-2 py-1 bg-amber-100 text-amber-700 rounded-md text-xs font-bold mr-2">Harga Diskon</span>
                                @else
                                    <span class="inline-flex px-2 py-1 bg-indigo-100 text-indigo-700 rounded-md text-xs font-bold mr-2">Tambahan Asuransi</span>
                                @endif

                                <span class="text-xs font-medium text-gray-500 block mt-1">
                                    {{ $policy->coverage_type == 'full_cover' ? 'Full Cover' : 'Ganti Unit' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-bold text-[#1c69d4]">{{ $policy->duration_days }} Hari</td>
                            <td class="px-6 py-4">
                                @if ($policy->type == 'addon_warranty')
                                    <span class="text-xs font-medium text-gray-500">Terpaut pada:</span>
                                    @php
                                        $pIds = is_array($policy->addon_trigger_keywords) ? $policy->addon_trigger_keywords : (json_decode($policy->addon_trigger_keywords, true) ?? []);
                                    @endphp
                                    <span class="font-bold text-indigo-700 block mt-1">{{ count($pIds) }} Produk Asuransi</span>
                                @else
                                    @if ($policy->brand_rule == 'all_brands')
                                        <span class="inline-flex px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs font-bold mt-2">Semua Brand</span>
                                    @elseif($policy->brand_rule == 'include')
                                        <span class="inline-flex px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-bold mt-2 mb-1">Hanya Brand:</span>
                                        <div class="text-xs font-medium text-gray-600 truncate max-w-[150px]">
                                            @php
                                                $bIds = is_array($policy->brand_list)
                                                    ? $policy->brand_list
                                                    : json_decode($policy->brand_list, true);
                                                $bNames = \App\Models\Brand::whereIn('id', $bIds ?? [])
                                                    ->pluck('name')
                                                    ->toArray();
                                            @endphp
                                            {{ implode(', ', $bNames) }}
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button wire:click="toggleActive({{ $policy->id }})"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none {{ $policy->is_active ? 'bg-emerald-500' : 'bg-gray-200' }}">
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $policy->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button wire:click="edit({{ $policy->id }})"
                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button onclick="confirm('Yakin ingin menghapus?') || event.stopImmediatePropagation()"
                                    wire:click="delete({{ $policy->id }})"
                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <p class="font-medium">Belum ada policy garansi.</p>
                                <p class="text-sm mt-1">Klik tombol 'Buat Policy Baru' untuk mulai menambahkan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-gray-100">
            {{ $policies->links() }}
        </div>
    </div>

    <!-- Modal Form -->
    @if ($showModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>

            <!-- Modal Panel -->
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col mx-4">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
                    <h3 class="text-lg font-bold text-gray-800">{{ $isEdit ? 'Edit Policy' : 'Buat Policy Baru' }}</h3>
                    <button wire:click="$set('showModal', false)"
                        class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Nama Policy -->
                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nama Policy <span
                                    class="text-red-500">*</span></label>
                            <input type="text" wire:model="name"
                                class="w-full p-2 rounded-xl border-gray-200 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm"
                                placeholder="Contoh: Garansi Android Premium">
                            @error('name')
                                <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Tipe Eksekusi -->
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Tipe Eksekusi <span
                                    class="text-red-500">*</span></label>
                            <select wire:model.live="type"
                                class="w-full p-2 rounded-xl border-gray-200 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm">
                                <option value="store_normal">Garansi Toko (Tanpa Diskon Kasir)</option>
                                <option value="store_discount">Garansi Toko (Mendapat Diskon Kasir)</option>
                                <option value="addon_warranty">Tambahan Asuransi (Add-on)</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">
                                @if (in_array($type, ['store_normal', 'store_discount']))
                                    Berlaku otomatis berdasarkan input harga di kasir.
                                @else
                                    Ditambahkan jika item asuransi dipilih.
                                @endif
                            </p>
                        </div>

                        <!-- Cakupan -->
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Tipe Cakupan <span
                                    class="text-red-500">*</span></label>
                            <select wire:model="coverage_type"
                                class="w-full p-2 rounded-xl border-gray-200 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm">
                                <option value="ganti_unit">Ganti Unit (Hanya Cacat Pabrik)</option>
                                <option value="full_cover">Full Cover (Termasuk Human Error)</option>
                            </select>
                        </div>

                        <!-- Durasi & Cakupan Perbaikan -->
                        <div class="col-span-2 grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Durasi Garansi (Hari) <span
                                        class="text-red-500">*</span></label>
                                <input type="number" wire:model="duration_days"
                                    class="w-full p-2 rounded-xl border-gray-200 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm"
                                    min="1">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Proteksi Perlindungan <span
                                        class="text-red-500">*</span></label>
                                <div class="flex flex-col gap-2 mt-2">
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" wire:model="coverage_scope" value="factory_defect" class="rounded text-[#1c69d4] focus:ring-[#1c69d4]">
                                        Cacat Pabrik (Mati Total, Layar, dll)
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" wire:model="coverage_scope" value="human_error" class="rounded text-[#1c69d4] focus:ring-[#1c69d4]">
                                        Kelalaian Pengguna (Jatuh, Air, dll)
                                    </label>
                                </div>
                            </div>
                        </div>

                        @if ($type == 'addon_warranty')
                            <!-- Product Select Trigger -->
                            <div class="col-span-2 p-4 bg-indigo-50 border-indigo-100 rounded-xl border">
                                <label class="block text-sm font-bold text-indigo-800 mb-1">
                                    Pilih Produk Asuransi (Dari Accurate)
                                    <span class="text-red-500">*</span>
                                </label>
                                <p class="text-xs text-indigo-600 mb-2">
                                    Policy ini hanya akan aktif jika pelanggan membeli item yang Anda pilih di bawah ini.
                                </p>
                                
                                <input type="text" wire:model.live.debounce.300ms="searchProduct"
                                    class="w-full p-2 mb-3 rounded-xl border-indigo-200 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                    placeholder="Cari nama produk asuransi...">
                                
                                @if(strlen($searchProduct) > 2)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-40 overflow-y-auto p-2 bg-white border border-indigo-200 rounded-xl">
                                        @forelse($searchedProducts as $sp)
                                            <label class="flex items-start gap-2 cursor-pointer p-2 hover:bg-indigo-50 rounded-lg border border-transparent hover:border-indigo-100 transition-colors">
                                                <input type="checkbox" wire:model="addon_product_list" value="{{ $sp->id }}" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500">
                                                <div>
                                                    <span class="text-xs font-bold text-gray-800 block leading-tight">{{ $sp->name }}</span>
                                                    <span class="text-[10px] text-gray-500">Rp {{ number_format($sp->base_price, 0, ',', '.') }}</span>
                                                </div>
                                            </label>
                                        @empty
                                            <p class="text-xs text-gray-500 p-2 italic col-span-2">Tidak ada produk ditemukan.</p>
                                        @endforelse
                                    </div>
                                @endif

                                @if(count($addon_product_list) > 0)
                                    <div class="mt-3">
                                        <p class="text-xs font-bold text-indigo-800 mb-1">Produk Terpilih ({{ count($addon_product_list) }}):</p>
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($addon_product_list as $selId)
                                                @php
                                                    $selName = \App\Models\ProductAccurate::find($selId)->name ?? 'Unknown';
                                                @endphp
                                                <span class="inline-flex items-center px-2 py-1 bg-indigo-100 text-indigo-700 rounded-md text-[10px] font-bold">
                                                    {{ $selName }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            <!-- Brand Rules -->
                            <div class="col-span-2 p-4 bg-gray-50 rounded-xl border border-gray-200">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Aturan Filter Brand <span
                                        class="text-red-500">*</span></label>
                                <select wire:model.live="brand_rule"
                                    class="w-full p-2 rounded-xl border-gray-200 focus:ring-[#1c69d4] focus:border-[#1c69d4] text-sm mb-3">
                                    <option value="all_brands">Berlaku untuk SEMUA Brand (Default)</option>
                                    <option value="include">Berlaku Khusus untuk BRAND Tertentu</option>
                                </select>
                                <p class="text-xs text-gray-500 mb-3">
                                    Jika Anda membuat aturan khusus untuk Brand, sistem akan mengutamakannya daripada aturan "Semua Brand".
                                </p>

                                @if ($brand_rule != 'all_brands')
                                    <div class="mt-2">
                                        <label class="block text-xs font-bold text-gray-600 mb-2">Pilih Brand:</label>
                                        <div
                                            class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-40 overflow-y-auto p-2 bg-white border border-gray-200 rounded-xl">
                                            @foreach ($brands as $brand)
                                                <label
                                                    class="flex items-center gap-2 cursor-pointer p-2 hover:bg-gray-50 rounded-lg">
                                                    <input type="checkbox" wire:model="brand_list"
                                                        value="{{ $brand->id }}"
                                                        class="rounded p-2 text-[#1c69d4] focus:ring-[#1c69d4]">
                                                    <span
                                                        class="text-sm font-medium text-gray-700">{{ $brand->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3 shrink-0 bg-gray-50 rounded-b-2xl">
                    <button wire:click="$set('showModal', false)"
                        class="px-4 py-2 text-sm font-bold text-gray-600 hover:text-gray-800 transition-colors">Batal</button>
                    <button wire:click="save"
                        class="px-6 py-2.5 bg-[#1c69d4] hover:bg-blue-700 text-white text-sm font-bold rounded-xl shadow-sm transition-colors">
                        Simpan Policy
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
