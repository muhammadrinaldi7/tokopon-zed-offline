<div class="p-6 bg-gray-50 min-h-screen">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Manajemen Unit Usaha</h1>
            <p class="text-sm text-gray-500">Kelola master data unit usaha dan pengaturan integrasi Accurate.</p>
        </div>
        <button wire:click="openModal"
            class="bg-neutral-800 hover:bg-neutral-900 transition-all text-white px-5 py-2.5 rounded-lg font-medium shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Tambah Unit Usaha
        </button>
    </div>

    @if (session()->has('message'))
        <div class="bg-emerald-50 text-emerald-800 p-4 rounded-xl mb-6 border border-emerald-200 flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-medium">{{ session('message') }}</span>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama & Kode</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Accurate Info</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Prefix / Awalan</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse ($units as $unit)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-gray-900">{{ $unit->name }}</div>
                                <div class="text-xs text-gray-500 font-mono mt-0.5">{{ $unit->code }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-800 font-medium">{{ $unit->accurate_host ?: '-' }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">DB ID: <span class="font-mono">{{ $unit->accurate_database_id ?: '-' }}</span></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1">
                                    @if($unit->prefix) <span class="text-xs text-gray-600">Global: <strong class="font-mono">{{ $unit->prefix }}</strong></span> @endif
                                    @if($unit->order_prefix) <span class="text-xs text-gray-600">Order: <strong class="font-mono">{{ $unit->order_prefix }}</strong></span> @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <button wire:click="toggleActive({{ $unit->id }})"
                                        class="px-3 py-1 rounded-full text-xs font-bold transition-colors {{ $unit->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                        {{ $unit->is_active ? 'Aktif' : 'Non-Aktif' }}
                                    </button>
                                    @if($unit->is_taxable)
                                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800">Taxable</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button wire:click="edit({{ $unit->id }})" class="text-indigo-600 hover:text-indigo-900 font-semibold px-3 py-1 rounded-md hover:bg-indigo-50 transition-colors">Edit</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400 space-y-2">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                    <span class="font-medium text-gray-500">Belum ada unit usaha.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Form --}}
    @if ($showModal)
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4 sm:p-6" style="display: flex;">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto flex flex-col relative" @click.away="window.Livewire.find('{{ $_instance->getId() }}').closeModal()">
                
                {{-- Header --}}
                <div class="px-8 py-5 border-b border-gray-100 flex justify-between items-center sticky top-0 bg-white/95 backdrop-blur z-10">
                    <h3 class="text-xl font-bold text-gray-800">{{ $unitId ? 'Edit Unit Usaha' : 'Tambah Unit Usaha' }}</h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors p-2 rounded-full hover:bg-gray-100">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Body Form --}}
                <div class="p-8">
                    <form wire:submit.prevent="save" class="space-y-8">
                        
                        {{-- Seksi 1: Info Dasar --}}
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wider flex items-center gap-2">
                                <span class="w-6 h-6 rounded bg-indigo-100 text-indigo-700 flex items-center justify-center"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></span>
                                Informasi Dasar
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50/50 p-5 rounded-xl border border-gray-100">
                                <div class="col-span-1 md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Toko (Judul Setruk)</label>
                                    <input type="text" wire:model="store_title" placeholder="Contoh: SYIHAB STORE / ZEDPOS BANJARBARU"
                                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium">
                                    @error('store_title') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Unit Usaha</label>
                                    <input type="text" wire:model="name" required placeholder="Contoh: Syihab Phone"
                                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                                    @error('name') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Kode Unik</label>
                                    <input type="text" wire:model="code" required placeholder="Contoh: syihab"
                                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-mono text-sm">
                                    <p class="text-[11px] text-gray-500 mt-1">Gunakan huruf kecil tanpa spasi.</p>
                                    @error('code') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Seksi 2: Prefix Penomoran --}}
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wider flex items-center gap-2">
                                <span class="w-6 h-6 rounded bg-emerald-100 text-emerald-700 flex items-center justify-center"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path></svg></span>
                                Konfigurasi Prefix (Awalan)
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-gray-50/50 p-5 rounded-xl border border-gray-100">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Prefix Global</label>
                                    <input type="text" wire:model="prefix" placeholder="Misal: SYB"
                                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-mono text-sm">
                                    @error('prefix') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Order Prefix</label>
                                    <input type="text" wire:model="order_prefix" placeholder="Misal: POS-SYB-"
                                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-mono text-sm">
                                    @error('order_prefix') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Draft Prefix</label>
                                    <input type="text" wire:model="draft_prefix" placeholder="Misal: DRF-SYB-"
                                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-mono text-sm">
                                    @error('draft_prefix') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Customer Prefix</label>
                                    <input type="text" wire:model="customer_prefix" placeholder="Misal: C-SYB-"
                                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-mono text-sm">
                                    @error('customer_prefix') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Seksi 3: API Accurate --}}
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wider flex items-center gap-2">
                                <span class="w-6 h-6 rounded bg-blue-100 text-blue-700 flex items-center justify-center"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg></span>
                                Integrasi Accurate (Opsional)
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 bg-gray-50/50 p-5 rounded-xl border border-gray-100">
                                <div class="col-span-1 md:col-span-2 flex gap-4">
                                    <div class="flex-1">
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Accurate Host</label>
                                        <input type="text" wire:model="accurate_host" placeholder="https://zeus.accurate.id/..."
                                            class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-mono text-sm">
                                    </div>
                                    <div class="w-1/3">
                                        <label class="block text-sm font-semibold text-gray-700 mb-1">Database ID</label>
                                        <input type="text" wire:model="accurate_database_id" placeholder="Misal: 123456"
                                            class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-mono text-sm">
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">OAuth Token</label>
                                    <input type="password" wire:model="accurate_token" placeholder="••••••••••••••"
                                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-mono text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Secret Key / Webhook Token</label>
                                    <input type="password" wire:model="accurate_secret_key" placeholder="••••••••••••••"
                                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-mono text-sm">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Gudang Retur ID</label>
                                    <input type="text" wire:model="accurate_return_warehouse_id" placeholder="ID Gudang Retur"
                                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-mono text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Gudang Retur</label>
                                    <input type="text" wire:model="accurate_return_warehouse_name" placeholder="Misal: GSK - Return"
                                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
                                </div>
                            </div>
                        </div>

                        {{-- Seksi 4: Webhook Telegram --}}
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wider flex items-center gap-2">
                                <span class="w-6 h-6 rounded bg-sky-100 text-sky-700 flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                </span>
                                Notifikasi Telegram (n8n Webhook)
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 bg-gray-50/50 p-5 rounded-xl border border-gray-100">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Webhook URL Approval</label>
                                    <input type="text" wire:model="telegram_approval_webhook" placeholder="https://n8n.zedgroup.tech/webhook/..."
                                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-mono text-sm">
                                    @error('telegram_approval_webhook') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-1">Webhook URL Group Log</label>
                                    <input type="text" wire:model="telegram_log_webhook" placeholder="https://n8n.zedgroup.tech/webhook/..."
                                        class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-mono text-sm">
                                    @error('telegram_log_webhook') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Seksi 5: Pengaturan Fitur (Toggles) --}}
                        <div class="bg-gray-50/50 p-5 rounded-xl border border-gray-100">
                            <h4 class="text-sm font-bold text-gray-900 mb-4 uppercase tracking-wider flex items-center gap-2">
                                <span class="w-6 h-6 rounded bg-amber-100 text-amber-700 flex items-center justify-center"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg></span>
                                Opsi Fitur Tambahan
                            </h4>
                            <div class="flex flex-wrap gap-8">
                                <label class="flex items-center cursor-pointer group">
                                    <div class="relative flex items-center">
                                        <input type="checkbox" wire:model="is_taxable" class="peer sr-only">
                                        <div class="w-10 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                    </div>
                                    <span class="ml-3 text-sm font-semibold text-gray-700 group-hover:text-gray-900 transition-colors">Gunakan Pajak (Taxable)</span>
                                </label>

                                <label class="flex items-center cursor-pointer group">
                                    <div class="relative flex items-center">
                                        <input type="checkbox" wire:model="receipt_show_discount" class="peer sr-only">
                                        <div class="w-10 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                    </div>
                                    <span class="ml-3 text-sm font-semibold text-gray-700 group-hover:text-gray-900 transition-colors">Tampilkan Info Diskon di Setruk</span>
                                </label>

                                <label class="flex items-center cursor-pointer group">
                                    <div class="relative flex items-center">
                                        <input type="checkbox" wire:model="is_active" class="peer sr-only">
                                        <div class="w-10 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                    </div>
                                    <span class="ml-3 text-sm font-semibold text-gray-700 group-hover:text-gray-900 transition-colors">Status Aktif</span>
                                </label>
                            </div>
                        </div>

                        {{-- Footer / Actions --}}
                        <div class="mt-8 flex justify-end gap-3 pt-6 border-t border-gray-100">
                            <button type="button" wire:click="closeModal"
                                class="px-5 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200">
                                Batal
                            </button>
                            <button type="submit"
                                class="px-6 py-2.5 text-sm font-bold text-white bg-indigo-600 border border-transparent rounded-xl hover:bg-indigo-700 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex items-center gap-2">
                                <svg wire:loading.remove wire:target="save" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
                                <span wire:loading wire:target="save">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
