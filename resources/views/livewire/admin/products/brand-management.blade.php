<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Manajemen Merek</h1>
        <button wire:click="create"
            class="bg-[#1c69d4] text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-opacity-90 transition">
            Tambah Merek
        </button>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-neutral-100-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 text-gray-600 font-semibold border-b border-gray-100">
                <tr>
                    <th class="px-6 py-4">Nama Merek</th>
                    <th class="px-6 py-4">Slug</th>
                    <th class="px-6 py-4">Logo</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($brands as $brand)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-medium">{{ $brand->name }}</td>
                        <td class="px-6 py-4 text-gray-500">{{ $brand->slug }}</td>
                        <td class="px-6 py-4 text-gray-500">
                            @if ($brand->hasMedia('logo'))
                                <img src="{{ $brand->getFirstMediaUrl('logo') }}" alt="{{ $brand->name }}"
                                    class="h-8 w-auto object-contain">
                            @else
                                <span class="text-xs italic">Tidak ada logo</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="edit({{ $brand->id }})"
                                class="text-gray-500 hover:text-gray-800 transition mr-3">
                                Edit
                            </button>
                            <button wire:click="delete({{ $brand->id }})"
                                class="text-rose-500 hover:text-rose-700 transition"
                                onclick="confirm('Yakin ingin menghapus merek ini?') || event.stopImmediatePropagation()">
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">Belum ada merek.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($brands->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $brands->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Create/Edit --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-0 transition-opacity"
            aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div wire:click="$set('showModal', false)"
                class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity"></div>

            <div
                class="relative transform overflow-hidden rounded-4xl bg-white/80 backdrop-blur-2xl border border-white shadow-sm shadow-[#1c69d4]/15 text-left transition-all sm:my-8 sm:w-full sm:max-w-md">
                <div
                    class="px-6 py-5 border-b border-gray-200/50 flex justify-between items-center backdrop-blur-md bg-white/40">
                    <h2 class="text-[17px] font-semibold tracking-tight text-gray-900">
                        {{ $isEditing ? 'Edit Merek' : 'Tambah Merek Baru' }}</h2>
                    <button wire:click="$set('showModal', false)"
                        class="text-gray-400 hover:text-gray-600 bg-gray-100/50 hover:bg-gray-200/50 rounded-full p-1.5 transition-colors focus:outline-none">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="store" class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5 ml-1">Nama Merek</label>
                        <input type="text" wire:model="name" placeholder="Contoh: Apple"
                            class="w-full text-[15px] bg-white/60 border border-gray-200/70 focus:bg-white focus:border-[#1c69d4] focus:ring-4 focus:ring-[#1c69d4]/10 rounded-lg px-4 py-3 shadow-sm transition-all text-gray-800 placeholder-gray-400"
                            required>
                        @error('name')
                            <span class="text-xs text-rose-500 font-medium ml-1 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5 ml-1">Logo Brand</label>
                        <div class="flex items-center gap-4">
                            @if ($logo)
                                <div
                                    class="w-16 h-16 rounded-lg overflow-hidden border-2 border-[#1c69d4] shadow-sm shrink-0">
                                    <img src="{{ $logo->temporaryUrl() }}" class="w-full h-full object-cover">
                                </div>
                            @elseif($currentLogoUrl)
                                <div
                                    class="w-16 h-16 rounded-lg overflow-hidden border border-gray-200 shadow-sm shrink-0">
                                    <img src="{{ $currentLogoUrl }}" class="w-full h-full object-cover">
                                </div>
                            @else
                                <div
                                    class="w-16 h-16 rounded-lg bg-gray-50 border-2 border-dashed border-gray-200 flex items-center justify-center shrink-0">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif

                            <div class="flex-1">
                                <label
                                    class="relative cursor-pointer bg-white rounded-lg border border-gray-200 px-4 py-2 text-xs font-bold text-gray-600 hover:bg-gray-50 transition-colors inline-block overflow-hidden">
                                    <span>{{ $logo ? 'Ganti' : 'Pilih Logo' }}</span>
                                    <input type="file" wire:model="logo" class="sr-only" accept="image/*">
                                </label>
                                <div wire:loading wire:target="logo" class="text-[10px] text-[#1c69d4] font-bold mt-1">
                                    Mengupload...</div>
                            </div>
                        </div>
                        @error('logo')
                            <span class="text-xs text-rose-500 font-medium ml-1 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button type="button" wire:click="$set('showModal', false)"
                            class="flex-1 bg-gray-100/50 hover:bg-gray-200/70 text-gray-700 py-3 rounded-lg text-[15px] font-semibold transition-all">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 bg-[#1c69d4] text-white py-3 rounded-lg text-[15px] font-semibold hover:bg-[#3f36b8] hover:shadow-sm hover:shadow-[#1c69d4]/30 active:scale-[0.98] transition-all">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

