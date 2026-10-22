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
                        <option value="USED">USED (Terpakai Penuh)</option>
                        <option value="REFUNDED">REFUNDED (Dikembalikan)</option>
                        <option value="CANCELLED">CANCELLED (Dibatalkan / Void)</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Tanggal & Kasir</th>
                            <th scope="col"
                                class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Pelanggan & Sumber</th>
                            <th scope="col"
                                class="px-5 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Nominal & Saldo</th>
                            <th scope="col"
                                class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Pembayaran</th>
                            <th scope="col"
                                class="px-5 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th scope="col"
                                class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Accurate</th>
                            <th scope="col"
                                class="px-5 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($deposits as $dep)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $dep->created_at->format('d M Y, H:i') }}</div>
                                    <div class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                        <span>{{ $dep->createdBy->name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm font-bold text-gray-900">{{ $dep->user->name ?? 'Pelanggan Umum' }}</div>
                                    <div class="text-xs text-gray-500">{{ $dep->user->profile->phone_number ?? ($dep->user->phone ?? '-') }}</div>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                        @if ($dep->originOrder)
                                            <a href="{{ route('admin.orders.sales-orders.show', $dep->originOrder->id) }}" target="_blank"
                                               class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-xs font-semibold border border-blue-200 transition"
                                               title="Buka detail Sales Order">
                                                <svg class="w-3 h-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                                #{{ $dep->originOrder->order_number }}
                                            </a>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 text-gray-600 rounded-lg text-xs font-medium">Deposit Langsung</span>
                                        @endif
                                        @if ($dep->businessUnit)
                                            <span class="inline-flex items-center px-1.5 py-0.5 bg-slate-50 text-slate-600 rounded text-[11px] font-medium border border-slate-200">
                                                {{ $dep->businessUnit->name ?? $dep->businessUnit->code }}
                                            </span>
                                        @endif
                                    </div>
                                    @if ($dep->notes)
                                        <div class="text-xs text-gray-400 mt-1 max-w-[240px] truncate" title="{{ $dep->notes }}">
                                            {{ $dep->notes }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="text-xs text-gray-400">Sisa Saldo:</div>
                                    <div class="text-base font-black {{ (float)$dep->balance > 0 ? 'text-emerald-600' : 'text-gray-400' }}">
                                        Rp {{ number_format($dep->balance, 0, ',', '.') }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">
                                        dari Rp {{ number_format($dep->amount, 0, ',', '.') }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <div class="font-medium">{{ $dep->paymentMethod->name ?? 'Kas / Lainnya' }}</div>
                                    @if ($dep->usages && $dep->usages->count() > 0)
                                        <div class="text-xs text-blue-600 mt-0.5 font-medium flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                                            Dipakai {{ $dep->usages->count() }}x di POS
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    @if ($dep->status === 'AVAILABLE')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                            Tersedia
                                        </span>
                                    @elseif($dep->status === 'USED')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700 border border-gray-200">
                                            Terpakai
                                        </span>
                                    @elseif($dep->status === 'REFUNDED')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                            Dikembalikan
                                        </span>
                                    @elseif($dep->status === 'CANCELLED')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">
                                            Dibatalkan (Void)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ $dep->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex flex-col gap-1 text-xs">
                                        @if ($dep->accurate_invoice_no)
                                            <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md border border-blue-100 font-mono text-[11px] font-semibold">
                                                INV: {{ $dep->accurate_invoice_no }}
                                            </span>
                                        @endif
                                        @if ($dep->accurate_receipt_no)
                                            <span class="px-2 py-0.5 bg-purple-50 text-purple-700 rounded-md border border-purple-100 font-mono text-[11px] font-semibold">
                                                REC: {{ $dep->accurate_receipt_no }}
                                            </span>
                                        @endif
                                        @if (!$dep->accurate_invoice_no && !$dep->accurate_receipt_no)
                                            <span class="text-xs text-gray-400 italic">Lokal (Tanpa Sync)</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{-- Tombol Detail & Riwayat --}}
                                        <button type="button" wire:click="openDetailModal({{ $dep->id }})"
                                                class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all"
                                                title="Lihat Detail & Riwayat Pemakaian">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>

                                        {{-- Tombol Refund Dana (Pencairan) --}}
                                        @if ($dep->status === 'AVAILABLE' && (float)$dep->balance > 0)
                                            <button type="button" wire:click="openRefundModal({{ $dep->id }})"
                                                    class="px-2.5 py-1.5 bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-200 rounded-xl text-xs font-bold transition flex items-center gap-1 active:scale-95"
                                                    title="Cairkan Refund DP ke Customer">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                                                </svg>
                                                Refund
                                            </button>
                                        @endif

                                        {{-- Tombol Void / Batalkan Input --}}
                                        @if ($dep->status === 'AVAILABLE' && (float)$dep->balance == (float)$dep->amount && $dep->usages->count() == 0)
                                            <button type="button" wire:click="openVoidModal({{ $dep->id }})"
                                                    class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all"
                                                    title="Batalkan / Void Salah Input">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">
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

                                        <div class="mt-4 p-4 bg-yellow-50 rounded-xl border border-yellow-200">
                                            <label class="flex items-center space-x-3 cursor-pointer">
                                                <input type="checkbox" wire:model.live="isLocalOnly"
                                                    class="form-checkbox h-5 w-5 text-yellow-600 border-yellow-300 rounded focus:ring-yellow-500">
                                                <span class="text-sm font-bold text-yellow-800">
                                                    Hanya Rekam Lokal (Sudah Ada di Accurate)
                                                </span>
                                            </label>
                                            <p class="mt-1 ml-8 text-xs text-yellow-700">
                                                Centang jika Uang Muka sudah diinput secara manual oleh Tim Finance di Accurate.
                                            </p>
                                            
                                            @if($isLocalOnly)
                                                <div class="mt-4 ml-8 space-y-3">
                                                    <div>
                                                        <label class="block text-xs font-bold text-gray-700 mb-1">No. Faktur Accurate <span class="text-red-500">*</span></label>
                                                        <input type="text" wire:model="manual_invoice_no" placeholder="Contoh: SI.2024.123"
                                                            class="block w-full pl-3 pr-3 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:ring-yellow-500 focus:border-yellow-500">
                                                        @error('manual_invoice_no')
                                                            <span class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-bold text-gray-700 mb-1">No. Penerimaan Accurate <span class="text-red-500">*</span></label>
                                                        <input type="text" wire:model="manual_receipt_no" placeholder="Contoh: CR.2024.456"
                                                            class="block w-full pl-3 pr-3 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:ring-yellow-500 focus:border-yellow-500">
                                                        @error('manual_receipt_no')
                                                            <span class="text-red-500 text-xs mt-1 font-medium">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
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

    {{-- Modal Refund DP --}}
    @if ($showRefundModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-150">
            <div class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-gray-100 animate-in zoom-in-95 duration-150">
                <div class="p-6 bg-gradient-to-r from-rose-600 to-rose-700 text-white flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black tracking-tight flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                            </svg>
                            Pencairan Refund Uang Muka (DP)
                        </h3>
                        <p class="text-xs text-rose-100 mt-1">Pengembalian saldo deposit pelanggan ke kas/rekening customer</p>
                    </div>
                    <button type="button" wire:click="closeRefundModal" class="p-1.5 rounded-full hover:bg-white/20 text-white transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                    {{-- Info Saldo --}}
                    <div class="p-4 bg-rose-50 rounded-2xl border border-rose-100 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-rose-700 font-medium">Sisa Saldo Deposit:</span>
                            <span class="text-lg font-black text-rose-700">Rp {{ number_format($refundMaxAmount, 0, ',', '.') }}</span>
                        </div>
                        <p class="text-xs text-rose-500 mt-1">Pelanggan: <strong class="text-rose-800">{{ $refundCustomerAccountName ?: 'Pelanggan Umum' }}</strong></p>
                    </div>

                    {{-- Form Nominal --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nominal Refund (Rp) <span class="text-rose-500">*</span></label>
                        <input type="number" wire:model="refundAmount" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-base font-bold text-gray-800 focus:ring-2 focus:ring-rose-500 outline-none" min="1" max="{{ $refundMaxAmount }}">
                        @error('refundAmount') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Akun Kas/Bank Toko --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Akun Kas / Bank Pengeluaran Toko <span class="text-rose-500">*</span></label>
                        <select wire:model="refundBankNo" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-semibold text-gray-800 focus:ring-2 focus:ring-rose-500 outline-none">
                            <option value="">-- Pilih Akun Bank Toko --</option>
                            @foreach ($this->paymentMethods->whereNotNull('accurate_bank_no') as $pm)
                                <option value="{{ $pm->accurate_bank_no }}">{{ $pm->name }} (Accurate: {{ $pm->accurate_bank_no }})</option>
                            @endforeach
                            <option value="KAS-CASH">Kas Tunai Kasir (KAS-CASH)</option>
                        </select>
                        @error('refundBankNo') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Data Rekening Tujuan Customer --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Bank Customer</label>
                            <input type="text" wire:model="refundCustomerBankName" placeholder="BCA / Mandiri / Tunai" class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 focus:ring-2 focus:ring-rose-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">No. Rekening / No. HP</label>
                            <input type="text" wire:model="refundCustomerAccountNo" placeholder="Nomor rekening / e-wallet" class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 focus:ring-2 focus:ring-rose-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Nama Pemilik Rekening</label>
                        <input type="text" wire:model="refundCustomerAccountName" placeholder="Nama penerima transfer" class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 focus:ring-2 focus:ring-rose-500 outline-none">
                    </div>

                    {{-- Bukti Transfer / Kwitansi --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Bukti Struk / Transfer Pengeluaran</label>
                        <input type="file" wire:model="refundReceipt" accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-rose-50 file:text-rose-700 hover:file:bg-rose-100">
                        <div wire:loading wire:target="refundReceipt" class="text-xs text-rose-600 mt-1 font-medium">Mengunggah file bukti...</div>
                        @if ($refundReceipt)
                            <div class="mt-2 p-2 border border-gray-100 rounded-xl bg-gray-50 flex items-center gap-3">
                                <img src="{{ $refundReceipt->temporaryUrl() }}" class="w-16 h-16 object-cover rounded-lg border border-gray-200">
                                <span class="text-xs text-gray-600">File bukti siap disimpan</span>
                            </div>
                        @endif
                        @error('refundReceipt') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    {{-- Catatan --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Catatan / Alasan Refund</label>
                        <textarea wire:model="refundNotes" rows="2" placeholder="Alasan pencairan refund..." class="w-full px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 focus:ring-2 focus:ring-rose-500 outline-none"></textarea>
                    </div>
                </div>

                <div class="p-6 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" wire:click="closeRefundModal" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl font-bold text-sm hover:bg-gray-50 transition">
                        Batal
                    </button>
                    <button type="button" wire:click="processRefund" wire:loading.attr="disabled" class="px-6 py-2.5 bg-rose-600 text-white rounded-xl font-bold text-sm hover:bg-rose-700 transition flex items-center gap-2 shadow-sm shadow-rose-600/30 disabled:opacity-50">
                        <span wire:loading.remove wire:target="processRefund">Konfirmasi & Cairkan Refund</span>
                        <span wire:loading wire:target="processRefund" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Memproses...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Void / Pembatalan DP --}}
    @if ($showVoidModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-150">
            <div class="bg-white rounded-3xl max-w-md w-full overflow-hidden shadow-2xl border border-gray-100 animate-in zoom-in-95 duration-150">
                <div class="p-6 bg-gradient-to-r from-red-600 to-red-700 text-white flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black tracking-tight flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Batalkan / Void Deposit
                        </h3>
                        <p class="text-xs text-red-100 mt-1">Koreksi kesalahan input data deposit</p>
                    </div>
                    <button type="button" wire:click="closeVoidModal" class="p-1.5 rounded-full hover:bg-white/20 text-white transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="p-4 bg-amber-50 rounded-2xl border border-amber-200 text-xs text-amber-800 leading-relaxed flex gap-3">
                        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        <div>
                            <strong>PERHATIAN:</strong> Fitur ini digunakan khusus untuk <strong>salah input</strong>. Sistem akan membatalkan deposit dan menghapus dokumen <em>Faktur Uang Muka</em> & <em>Penerimaan Kas</em> di Accurate Online.
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Alasan Pembatalan / Void <span class="text-red-500">*</span></label>
                        <textarea wire:model="voidReason" rows="3" placeholder="Contoh: Salah pilih nama pelanggan / salah ketik nominal deposit" class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-800 focus:ring-2 focus:ring-red-500 outline-none"></textarea>
                        @error('voidReason') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="p-6 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" wire:click="closeVoidModal" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl font-bold text-sm hover:bg-gray-50 transition">
                        Batal
                    </button>
                    <button type="button" wire:click="processVoid" wire:loading.attr="disabled" class="px-6 py-2.5 bg-red-600 text-white rounded-xl font-bold text-sm hover:bg-red-700 transition flex items-center gap-2 shadow-sm shadow-red-600/30 disabled:opacity-50">
                        <span wire:loading.remove wire:target="processVoid">Ya, Batalkan Deposit</span>
                        <span wire:loading wire:target="processVoid" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Membatalkan...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Detail & Riwayat Pemakaian --}}
    @if ($showDetailModal && $this->selectedDeposit)
        @php $selDep = $this->selectedDeposit; @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-150">
            <div class="bg-white rounded-3xl max-w-xl w-full overflow-hidden shadow-2xl border border-gray-100 animate-in zoom-in-95 duration-150">
                <div class="p-6 bg-gradient-to-r from-blue-600 to-indigo-700 text-white flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black tracking-tight flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Detail & Riwayat Deposit #{{ $selDep->id }}
                        </h3>
                        <p class="text-xs text-blue-100 mt-1">{{ $selDep->created_at->format('d F Y, H:i') }} • Dibuat oleh {{ $selDep->createdBy->name ?? '-' }}</p>
                    </div>
                    <button type="button" wire:click="closeDetailModal" class="p-1.5 rounded-full hover:bg-white/20 text-white transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    {{-- Grid Ringkasan --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 p-4 bg-gray-50 rounded-2xl border border-gray-100 text-xs">
                        <div>
                            <span class="text-gray-400 block uppercase font-bold text-[10px]">Pelanggan</span>
                            <span class="font-bold text-gray-800 text-sm block mt-0.5">{{ $selDep->user->name ?? '-' }}</span>
                            <span class="text-gray-500">{{ $selDep->user->profile->phone_number ?? ($selDep->user->phone ?? '-') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block uppercase font-bold text-[10px]">Nominal Awal</span>
                            <span class="font-bold text-gray-800 text-sm block mt-0.5">Rp {{ number_format($selDep->amount, 0, ',', '.') }}</span>
                            <span class="text-gray-500">{{ $selDep->paymentMethod->name ?? 'Kas' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block uppercase font-bold text-[10px]">Sisa Saldo</span>
                            <span class="font-black text-sm block mt-0.5 {{ (float)$selDep->balance > 0 ? 'text-emerald-600' : 'text-gray-500' }}">
                                Rp {{ number_format($selDep->balance, 0, ',', '.') }}
                            </span>
                            <span class="font-semibold text-[11px] {{ $selDep->status === 'AVAILABLE' ? 'text-emerald-600' : 'text-gray-500' }}">{{ $selDep->status }}</span>
                        </div>
                    </div>

                    {{-- Informasi Accurate & Sumber --}}
                    <div class="p-4 bg-blue-50/60 rounded-2xl border border-blue-100 text-xs space-y-2">
                        <div class="font-bold text-blue-900 flex items-center justify-between">
                            <span>Sinkronisasi Accurate:</span>
                            @if ($selDep->originOrder)
                                <a href="{{ route('admin.orders.sales-orders.show', $selDep->originOrder->id) }}" target="_blank" class="text-blue-700 underline font-semibold">
                                    Sumber: SO #{{ $selDep->originOrder->order_number }}
                                </a>
                            @else
                                <span class="text-gray-600 font-normal">Sumber: Penerimaan Langsung</span>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-[11px]">
                            <div>Faktur DP Accurate: <strong class="text-blue-800 font-mono">{{ $selDep->accurate_invoice_no ?? '-' }}</strong></div>
                            <div>Penerimaan (Receipt): <strong class="text-purple-800 font-mono">{{ $selDep->accurate_receipt_no ?? '-' }}</strong></div>
                        </div>
                    </div>

                    {{-- Riwayat Pemakaian di POS --}}
                    <div>
                        <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                            Riwayat Pemakaian di Transaksi POS ({{ $selDep->usages->count() }})
                        </h4>
                        @if ($selDep->usages && $selDep->usages->count() > 0)
                            <div class="border border-gray-100 rounded-xl overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-100 text-xs">
                                    <thead class="bg-gray-50 text-gray-500 font-semibold">
                                        <tr>
                                            <th class="px-3.5 py-2 text-left">Tanggal</th>
                                            <th class="px-3.5 py-2 text-left">No. Order / Faktur</th>
                                            <th class="px-3.5 py-2 text-right">Nominal Dipakai</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach ($selDep->usages as $usage)
                                            <tr>
                                                <td class="px-3.5 py-2 text-gray-600 whitespace-nowrap">{{ $usage->created_at->format('d/m/Y H:i') }}</td>
                                                <td class="px-3.5 py-2 font-bold text-gray-800">
                                                    {{ $usage->order->order_number ?? ('Order #' . $usage->order_id) }}
                                                    @if ($usage->accurate_si_number)
                                                        <span class="block text-[10px] text-gray-400 font-normal font-mono">SI: {{ $usage->accurate_si_number }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-3.5 py-2 text-right font-black text-rose-600">
                                                    - Rp {{ number_format($usage->amount_used, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-3 text-center text-xs text-gray-400 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                                Belum pernah digunakan pada transaksi belanja POS.
                            </div>
                        @endif
                    </div>

                    {{-- Catatan & Log --}}
                    @if ($selDep->notes)
                        <div>
                            <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Catatan & Histori Log</h4>
                            <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 text-xs text-gray-700 whitespace-pre-line font-mono text-[11px] leading-relaxed">
                                {{ $selDep->notes }}
                            </div>
                        </div>
                    @endif
                </div>

                <div class="p-6 bg-gray-50 border-t border-gray-100 flex justify-end">
                    <button type="button" wire:click="closeDetailModal" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl font-bold text-sm hover:bg-gray-50 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
