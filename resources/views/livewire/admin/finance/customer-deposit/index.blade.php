<div>
    <div
        class="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4 bg-white">
        <div>
            <h1 class="text-xl font-bold text-gray-800">Deposit Pelanggan</h1>
            <p class="text-sm text-gray-500 mt-1">Kelola daftar uang muka / deposit pelanggan.</p>
        </div>
        <button wire:click="$set('showCreateModal', true)"
            class="px-4 py-2.5 bg-[#1c69d4] text-white hover:bg-blue-700 font-bold rounded-xl text-sm transition-all shadow-sm shadow-blue-500/20 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Terima Deposit
        </button>
    </div>

    <div class="p-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div
                class="p-4 border-b border-gray-100 flex flex-col sm:flex-row gap-4 justify-between items-center bg-gray-50/50">
                <div class="w-full sm:w-1/3 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Cari pelanggan atau catatan...">
                </div>
                <div class="w-full sm:w-auto">
                    <select wire:model.live="status_filter"
                        class="block w-full pl-3 pr-10 py-2 text-base border-gray-200 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-xl">
                        <option value="">Semua Status</option>
                        <option value="AVAILABLE">AVAILABLE (Tersedia)</option>
                        <option value="USED">USED (Terpakai)</option>
                        <option value="REFUNDED">REFUNDED (Dikembalikan)</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal & Kasir</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pelanggan</th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nominal</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pembayaran</th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Accurate</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($deposits as $dep)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $dep->created_at->format('d M Y, H:i') }}</div>
                                    <div class="text-xs text-gray-500">By: {{ $dep->createdBy->name ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-900">{{ $dep->user->name ?? '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ $dep->user->phone ?? '-' }}</div>
                                    @if ($dep->notes)
                                        <div class="text-xs text-gray-400 mt-1 truncate max-w-[200px]"
                                            title="{{ $dep->notes }}">
                                            Catatan: {{ $dep->notes }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-bold text-gray-900">Rp
                                        {{ number_format($dep->amount, 0, ',', '.') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $dep->paymentMethod->name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if ($dep->status === 'AVAILABLE')
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Tersedia</span>
                                    @elseif($dep->status === 'USED')
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Terpakai</span>
                                    @elseif($dep->status === 'REFUNDED')
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Dikembalikan</span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $dep->status }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex flex-col gap-1 text-xs">
                                        @if ($dep->accurate_invoice_no)
                                            <span
                                                class="px-2 py-1 bg-blue-50 text-blue-700 rounded-md border border-blue-100">INV:
                                                {{ $dep->accurate_invoice_no }}</span>
                                        @endif
                                        @if ($dep->accurate_receipt_no)
                                            <span
                                                class="px-2 py-1 bg-purple-50 text-purple-700 rounded-md border border-purple-100">REC:
                                                {{ $dep->accurate_receipt_no }}</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Belum ada data deposit pelanggan.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($deposits->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $deposits->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Create Deposit --}}
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-transparent bg-opacity-75 backdrop-blur-sm transition-opacity"
                    wire:click="$set('showCreateModal', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-2xl font-black text-gray-800" id="modal-title">
                                    Terima Deposit Pelanggan
                                </h3>
                                <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-8">
                                    <div class="space-y-5">
                                        <div x-data="{ showDropdown: false }" @click.outside="showDropdown = false"
                                            class="relative">
                                            <label class="block text-sm font-bold text-gray-700 mb-2">Pilih Pelanggan
                                                <span class="text-red-500">*</span></label>

                                            <div
                                                class="relative flex items-center bg-gray-50 border-2 border-gray-200 rounded-2xl min-h-[52px] transition-all focus-within:border-[#1c69d4] focus-within:bg-white focus-within:ring-4 focus-within:ring-[#1c69d4]/10">
                                                <span class="absolute left-4 text-gray-400 pointer-events-none">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5"
                                                        viewBox="0 0 24 24">
                                                        <path d="M0 0h24v24H0z" fill="none" />
                                                        <path fill="none" stroke="currentColor"
                                                            stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="1.5"
                                                            d="M19.523 21.99H4.488c-1.503 0-2.663-1.134-2.466-2.624l.114-.869c.207-1.2 1.305-1.955 2.497-2.214L11.928 15h.144l7.295 1.283c1.212.28 2.29.993 2.497 2.214l.114.88c.197 1.49-.963 2.623-2.466 2.623zM17 7A5 5 0 1 1 7 7a5 5 0 0 1 10 0" />
                                                    </svg>
                                                </span>

                                                @if ($customer_id)
                                                    <div class="pl-12 pr-4 py-2 w-full flex items-center">
                                                        <div
                                                            class="inline-flex items-center gap-2 bg-blue-100/50 text-blue-900 border border-blue-200 px-3 py-1.5 rounded-lg shadow-sm">
                                                            <span
                                                                class="font-bold text-sm">{{ $selectedCustomerName }}</span>
                                                            <button type="button" wire:click="clearCustomer"
                                                                class="hover:text-red-500 transition-colors">
                                                                <svg class="w-4 h-4" fill="none"
                                                                    viewBox="0 0 24 24" stroke="currentColor"
                                                                    stroke-width="2.5">
                                                                    <path stroke-linecap="round"
                                                                        stroke-linejoin="round"
                                                                        d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                @else
                                                    <input type="text"
                                                        wire:model.live.debounce.300ms="searchCustomer"
                                                        @focus="showDropdown = true"
                                                        class="w-full bg-transparent pl-12 pr-12 py-3 text-base font-medium text-gray-700 placeholder-gray-400 outline-none border-none ring-0 focus:ring-0"
                                                        placeholder="Cari nama atau No. HP...">
                                                @endif

                                                @if (!$customer_id)
                                                    <div wire:loading wire:target="searchCustomer"
                                                        class="absolute right-4">
                                                        <svg class="animate-spin w-5 h-5 text-gray-400"
                                                            xmlns="http://www.w3.org/2000/svg" fill="none"
                                                            viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12"
                                                                r="10" stroke="currentColor" stroke-width="3">
                                                            </circle>
                                                            <path class="opacity-75" fill="currentColor"
                                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                            </path>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>

                                            @if (strlen($searchCustomer) >= 3 && !$customer_id)
                                                <div x-show="showDropdown" x-transition
                                                    class="absolute top-full left-0 w-full mt-2 bg-white border border-gray-100 rounded-2xl shadow-xl max-h-60 overflow-y-auto z-50">
                                                    @forelse($this->customerSearchResults as $user)
                                                        <button type="button"
                                                            wire:click="selectCustomer({{ $user['id'] }}, '{{ addslashes($user['name']) }}', '{{ addslashes($user['phone']) }}')"
                                                            class="w-full p-3 hover:bg-blue-50/80 text-left flex flex-col transition border-b border-gray-50 last:border-0 group">
                                                            <span
                                                                class="text-gray-800 font-bold group-hover:text-[#1c69d4]">{{ $user['name'] }}</span>
                                                            <span
                                                                class="text-sm font-medium text-gray-500">{{ $user['phone'] }}</span>
                                                        </button>
                                                    @empty
                                                        <div class="p-4 text-center text-gray-500 text-sm">
                                                            Pelanggan tidak ditemukan.
                                                        </div>
                                                    @endforelse
                                                </div>
                                            @endif
                                            @error('customer_id')
                                                <span
                                                    class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="grid grid-cols-1 gap-5 mb-4">
                                            <div>
                                                <label class="block text-sm font-bold text-gray-700 mb-2">Tanggal
                                                    Terima Deposit <span class="text-red-500">*</span></label>
                                                <input type="date" wire:model="payment_date"
                                                    class="block w-full pl-4 pr-4 py-3 text-sm font-medium bg-gray-50 border-2 border-gray-200 rounded-2xl focus:ring-0 focus:border-[#1c69d4] focus:bg-white transition-all">
                                                @error('payment_date')
                                                    <span
                                                        class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                        {{-- <div class="mt-4">
                                            <label class="block text-sm font-bold text-gray-700 mb-2">Catatan
                                                Tambahan</label>
                                            <textarea wire:model="notes" rows="2"
                                                class="block w-full pl-4 pr-4 py-3 text-sm font-medium bg-gray-50 border-2 border-gray-200 rounded-2xl focus:ring-0 focus:border-[#1c69d4] focus:bg-white transition-all placeholder-gray-400"
                                                placeholder="Keterangan deposit (Opsional)"></textarea>
                                            @error('notes')
                                                <span
                                                    class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</span>
                                            @enderror
                                        </div> --}}
                                    </div>
                                    <div class="space-y-5">
                                        @include('livewire.zoffline.pos.partials.wizard.step4-payment', [
                                            'hideSplit' => true,
                                            'allowEditAmount' => true,
                                            'hideFooter' => true,
                                        ])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="bg-gray-50 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-3 border-t-2 border-gray-100">
                        <button wire:click="$set('showCreateModal', false)" type="button"
                            class="w-full sm:w-auto inline-flex justify-center rounded-xl border-2 border-gray-200 px-6 py-3 bg-white text-base font-bold text-gray-700 hover:bg-gray-50 hover:border-gray-300 focus:outline-none transition-all">
                            Batal
                        </button>
                        <button wire:click="createDeposit" wire:loading.attr="disabled" type="button"
                            class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl border-2 border-transparent px-8 py-3 bg-[#1c69d4] text-base font-bold text-white hover:bg-blue-700 hover:shadow-lg hover:shadow-blue-500/30 focus:outline-none transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="createDeposit">Simpan Deposit</span>
                            <span wire:loading wire:target="createDeposit" class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
