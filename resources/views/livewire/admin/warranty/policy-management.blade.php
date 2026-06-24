<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">Pengaturan Kebijakan Garansi</h2>
        <button wire:click="openCreateModal" class="btn btn-primary">
            + Tambah Kebijakan Baru
        </button>
    </div>

    <!-- Tabel Data -->
    <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="mb-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama kebijakan..." class="w-full md:w-1/3 input input-bordered">
        </div>

        <div class="overflow-x-auto">
            <table class="table w-full">
                <thead>
                    <tr class="bg-base-200">
                        <th>Nama / Tipe</th>
                        <th>Brand / Produk</th>
                        <th>Durasi & Klaim</th>
                        <th>Kategori Asuransi</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($policies as $policy)
                        <tr wire:key="policy-{{ $policy->id }}">
                            <td>
                                <div class="font-bold">{{ $policy->name }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $policy->type === 'insurance' ? 'Asuransi Berbayar' : 'Garansi Default Toko' }}
                                </div>
                            </td>
                            <td>
                                @if($policy->brand_id)
                                    <span class="badge badge-outline">{{ $policy->brand->name }}</span>
                                @else
                                    <span class="badge badge-ghost">Semua Brand</span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $policy->duration_days }} Hari</div>
                                <div class="text-xs text-gray-500">Maks {{ $policy->max_claims }} Klaim</div>
                            </td>
                            <td>
                                {{ $policy->item_category ?: '-' }}
                            </td>
                            <td>
                                <button wire:click="toggleActive({{ $policy->id }})" class="badge badge-{{ $policy->is_active ? 'success' : 'error' }} border-none cursor-pointer">
                                    {{ $policy->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </td>
                            <td class="text-right">
                                <button wire:click="edit({{ $policy->id }})" class="btn btn-sm btn-ghost text-blue-600">Edit</button>
                                <button wire:click="delete({{ $policy->id }})" wire:confirm="Yakin ingin menghapus kebijakan ini?" class="btn btn-sm btn-ghost text-red-600">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-6 text-gray-500">Belum ada kebijakan garansi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $policies->links() }}
        </div>
    </div>

    <!-- Modal Form -->
    <dialog class="modal {{ $showModal ? 'modal-open' : '' }}">
        <div class="modal-box w-11/12 max-w-3xl">
            <h3 class="font-bold text-lg mb-4">{{ $isEdit ? 'Edit Kebijakan Garansi' : 'Tambah Kebijakan Garansi Baru' }}</h3>
            
            <form wire:submit.prevent="save">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <!-- Nama Kebijakan -->
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Nama Kebijakan</span></label>
                        <input type="text" wire:model="name" class="input input-bordered w-full" placeholder="Contoh: Garansi Resmi Apple 1 Tahun" required>
                        @error('name') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Tipe Garansi -->
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Tipe Kebijakan</span></label>
                        <select wire:model.live="type" class="select select-bordered w-full" required>
                            <option value="store_default">Garansi Default Toko (Gratis)</option>
                            <option value="insurance">Asuransi Berbayar (Add-on)</option>
                        </select>
                    </div>

                    <!-- Brand (Opsional) -->
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Berlaku Untuk Brand (Opsional)</span></label>
                        <select wire:model="brand_id" class="select select-bordered w-full">
                            <option value="">Semua Brand</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        <label class="label"><span class="label-text-alt">Kosongkan jika berlaku untuk semua.</span></label>
                    </div>

                    <!-- Kategori Item Accurate (Untuk Asuransi) -->
                    @if($type === 'insurance')
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Kategori Item (Deteksi Asuransi)</span></label>
                        <input type="text" wire:model="item_category" class="input input-bordered w-full" placeholder="Contoh: Asuransi">
                        <label class="label"><span class="label-text-alt">Kategori barang di Accurate untuk dihubungkan.</span></label>
                    </div>
                    @else
                    <div></div>
                    @endif

                    <!-- Durasi -->
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Durasi Garansi (Hari)</span></label>
                        <input type="number" wire:model="duration_days" class="input input-bordered w-full" required min="1">
                    </div>

                    <!-- Max Klaim -->
                    <div class="form-control">
                        <label class="label"><span class="label-text font-semibold">Maksimal Jumlah Klaim</span></label>
                        <input type="number" wire:model="max_claims" class="input input-bordered w-full" required min="1">
                    </div>
                </div>

                <div class="divider">Cakupan Kerusakan (Coverage)</div>
                
                <div class="space-y-2 mb-4">
                    @foreach($coverageItems as $index => $item)
                        <div class="flex items-center gap-2">
                            <input type="text" wire:model="coverageItems.{{ $index }}.name" class="input input-bordered input-sm flex-1" placeholder="Nama Kerusakan" required>
                            <select wire:model="coverageItems.{{ $index }}.covered" class="select select-bordered select-sm w-32">
                                <option value="1">Tercover</option>
                                <option value="0">Tdk Tercover</option>
                            </select>
                            <button type="button" wire:click="removeCoverageItem({{ $index }})" class="btn btn-sm btn-circle btn-ghost text-error">✕</button>
                        </div>
                    @endforeach
                    <button type="button" wire:click="addCoverageItem" class="btn btn-sm btn-outline btn-primary mt-2">+ Tambah Cakupan</button>
                </div>

                <!-- Status Aktif -->
                <div class="form-control">
                    <label class="cursor-pointer label justify-start gap-4">
                        <span class="label-text font-semibold">Status Aktif</span> 
                        <input type="checkbox" wire:model="is_active" class="toggle toggle-primary" />
                    </label>
                </div>

                <div class="modal-action">
                    <button type="button" wire:click="$set('showModal', false)" class="btn">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button wire:click="$set('showModal', false)">close</button>
        </form>
    </dialog>
</div>
