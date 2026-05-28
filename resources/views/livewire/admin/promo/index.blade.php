<div>
    <div class="mb-6 flex justify-between items-end">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Manajemen Promo & Voucher</h1>
            <p class="text-gray-500 text-sm mt-1">Kelola diskon internal dan sponsor brand untuk digunakan pada transaksi.</p>
        </div>
        <a href="{{ route('admin.promos.create') }}" wire:navigate
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Promo
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex gap-4 bg-gray-50/50">
            <div class="relative flex-1 max-w-md">
                <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau kode promo..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white">
            </div>
            
            <select wire:model.live="category" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white text-gray-600 font-medium">
                <option value="">Semua Kategori</option>
                <option value="internal">Internal Toko</option>
                <option value="brand">Sponsor Brand</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500">
                        <th class="px-6 py-4 font-semibold">Nama & Kode</th>
                        <th class="px-6 py-4 font-semibold">Kategori</th>
                        <th class="px-6 py-4 font-semibold">Tipe Diskon</th>
                        <th class="px-6 py-4 font-semibold">Accurate GL</th>
                        <th class="px-6 py-4 font-semibold">Masa Berlaku</th>
                        <th class="px-6 py-4 font-semibold text-center">Status</th>
                        <th class="px-6 py-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($promos as $promo)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800">{{ $promo->name }}</div>
                                <div class="text-xs font-mono text-indigo-600 mt-1 font-semibold">{{ $promo->code ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($promo->category === 'brand')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-blue-50 text-blue-700">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        {{ $promo->brand->name ?? 'Brand' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-bold bg-purple-50 text-purple-700">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        Internal
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($promo->discount_type === 'fixed')
                                    <span class="font-bold text-gray-700">Rp {{ number_format($promo->discount_value, 0, ',', '.') }}</span>
                                @else
                                    <span class="font-bold text-gray-700">{{ number_format($promo->discount_value, 0) }}%</span>
                                    @if($promo->max_discount)
                                        <div class="text-[10px] text-gray-400 mt-0.5">Maks: Rp{{ number_format($promo->max_discount,0,',','.') }}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-mono bg-gray-100 text-gray-600 px-2 py-1 rounded">{{ $promo->accurate_account_no ?? 'Default' }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs text-gray-600">
                                @if($promo->start_date && $promo->end_date)
                                    <div>{{ $promo->start_date->format('d M Y') }}</div>
                                    <div>s/d {{ $promo->end_date->format('d M Y') }}</div>
                                @else
                                    <span class="text-gray-400 italic">Selamanya</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button wire:click="toggleStatus({{ $promo->id }})" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 {{ $promo->is_active ? 'bg-indigo-600' : 'bg-gray-200' }}" role="switch" aria-checked="{{ $promo->is_active ? 'true' : 'false' }}">
                                    <span aria-hidden="true" class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $promo->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('admin.promos.edit', $promo->id) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-50 text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </a>
                                <button wire:click="delete({{ $promo->id }})" wire:confirm="Yakin ingin menghapus promo ini?" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-50 text-gray-600 hover:bg-rose-50 hover:text-rose-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-bold text-gray-900">Belum ada Promo</h3>
                                <p class="text-sm text-gray-500 mt-1">Tambahkan promo pertama Anda untuk digunakan pada transaksi.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($promos->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $promos->links() }}
            </div>
        @endif
    </div>
</div>
