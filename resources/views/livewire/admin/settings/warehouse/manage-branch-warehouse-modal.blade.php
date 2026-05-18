<div>
    @if ($isOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm transition-opacity" wire:click="closeModal">
            </div>

            <!-- Modal Box -->
            <div
                class="relative bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900">Kelola Penempatan Cabang</h3>
                    <button wire:click="closeModal"
                        class="text-gray-400 hover:text-gray-600 transition-colors p-1 bg-gray-50 rounded-full hover:bg-gray-100 border border-gray-200 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveBranch">
                    <div class="p-6 space-y-5">
                        <!-- Info User -->
                        <div class="p-4 bg-gray-50 rounded-2xl flex items-center gap-4 border border-gray-100">
                            <div
                                class="w-10 h-10 rounded-full bg-gradient-to-br from-[#4E44DB] to-[#766bf2] text-white flex items-center justify-center font-bold text-sm shrink-0">
                                {{ strtoupper(substr($name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 text-sm">{{ $name }}</p>
                                <p class="text-xs text-gray-500">{{ $email }}</p>
                            </div>
                        </div>

                        <!-- Pilih Branch -->
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-gray-700">Pilih Cabang (Branch)</label>
                            <select wire:model="branch_id"
                                class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all">
                                <option value="">-- Tidak ditempatkan di cabang --</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Pilih Warehouse -->
                        <div class="space-y-1">
                            <label class="block text-sm font-semibold text-gray-700">Pilih Gudang (Warehouse)</label>
                            <select wire:model="warehouse_id"
                                class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#4E44DB]/20 focus:border-[#4E44DB] transition-all">
                                <option value="">-- Tidak ditempatkan di gudang --</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 rounded-b-3xl">
                        <button type="button" wire:click="closeModal"
                            class="px-5 py-2.5 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:text-gray-900 transition-colors shadow-sm cursor-pointer">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-5 py-2.5 text-sm font-bold text-white bg-[#4E44DB] rounded-xl hover:bg-[#3c34af] shadow-md shadow-[#4E44DB]/20 transition-all flex items-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            Simpan Penempatan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
