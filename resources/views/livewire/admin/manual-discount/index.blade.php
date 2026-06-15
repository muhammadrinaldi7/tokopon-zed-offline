<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Pengaturan Promo Internal Diskon</h2>
            <p class="text-sm text-gray-500 mt-1">Atur opsi diskon preset yang bisa dipilih kasir secara instan.</p>
        </div>
        <a href="{{ route('admin.manual-discount.create') }}" wire:navigate
            class="bg-[#4E44DB] hover:bg-[#3d35b3] text-white px-4 py-2.5 rounded-xl text-sm font-medium transition-colors flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Preset
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-50 text-green-700 p-4 rounded-xl text-sm font-medium flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <div class="relative w-64">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nominal atau brand..."
                    class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:border-[#4E44DB] focus:ring-[#4E44DB] transition-shadow">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-3" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 bg-gray-50/50 uppercase">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Nominal Diskon</th>
                        <th class="px-6 py-4 font-semibold">Berlaku Untuk Brand</th>
                        <th class="px-6 py-4 font-semibold text-center">Status</th>
                        <th class="px-6 py-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($presets as $preset)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-[#4E44DB]">
                                Rp {{ number_format($preset->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-gray-600">
                                @if($preset->brand)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-purple-50 text-purple-700 text-xs font-medium border border-purple-100">
                                        {{ $preset->brand->name }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-gray-100 text-gray-600 text-xs font-medium border border-gray-200">
                                        Semua Brand
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button wire:click="toggleActive({{ $preset->id }})" class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-[#4E44DB] focus:ring-offset-2 {{ $preset->is_active ? 'bg-[#4E44DB]' : 'bg-gray-200' }}" role="switch" aria-checked="false">
                                    <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $preset->is_active ? 'translate-x-4' : 'translate-x-0' }}"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.manual-discount.edit', $preset->id) }}" wire:navigate
                                        class="p-2 text-gray-400 hover:text-[#4E44DB] hover:bg-indigo-50 rounded-lg transition-colors"
                                        title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </a>
                                    <button wire:click="delete({{ $preset->id }})"
                                        wire:confirm="Yakin ingin menghapus preset diskon ini?"
                                        class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Hapus">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400">
                                    <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-base font-medium text-gray-500">Belum ada preset diskon</p>
                                    <p class="text-sm mt-1">Tambahkan preset agar kasir bisa menggunakannya di POS.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($presets->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $presets->links() }}
            </div>
        @endif
    </div>
</div>
