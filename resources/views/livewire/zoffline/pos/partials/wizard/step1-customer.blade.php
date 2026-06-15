<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-6 space-y-8">
        {{-- CUSTOMER SECTION --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                <h3 class="font-bold text-gray-700 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#1c69d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Data Customer
                </h3>
                @if (!$selectedCustomerId && !$isNewCustomer)
                    <button wire:click="$set('isNewCustomer', true)" wire:loading.attr="disabled"
                        class="text-sm text-[#1c69d4] hover:text-blue-700 font-bold transition flex items-center gap-1 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Customer Baru
                    </button>
                @endif
            </div>

            @if ($selectedCustomerId)
                @php $customer = \App\Models\User::with('profile')->find($selectedCustomerId); @endphp
                @if($customer)
                <div class="flex items-center justify-between bg-emerald-50/50 border border-emerald-200 shadow-sm rounded-xl p-4 transition-all">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-lg uppercase shadow-inner">
                            {{ substr($customer->name, 0, 2) }}
                        </div>
                        <div>
                            <p class="font-bold text-gray-800 text-base">{{ $customer->name }}</p>
                            <p class="text-sm text-gray-500 font-medium mt-0.5">
                                {{ $customer->profile->phone_number ?? $customer->email }}
                            </p>
                        </div>
                    </div>
                    <button wire:click="clearSelectedCustomer"
                        class="text-gray-500 hover:text-rose-600 text-sm font-bold px-3 py-1.5 border border-gray-200 hover:border-rose-200 hover:bg-rose-50 rounded-lg transition-all duration-200">
                        Ganti Customer
                    </button>
                </div>
                @endif
            @elseif($isNewCustomer)
                <div class="bg-blue-50/30 border border-blue-100 shadow-sm rounded-xl p-5 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-gray-600 uppercase">Nama Lengkap <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="customerName"
                                class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20 transition-all"
                                placeholder="Masukkan nama customer">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-gray-600 uppercase">Nomor HP <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="customerPhone"
                                class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20 transition-all"
                                placeholder="Contoh: 08123456789">
                        </div>
                        <div class="space-y-1.5 md:col-span-2">
                            <label class="text-xs font-bold text-gray-600 uppercase">Email <span class="text-gray-400 font-normal">(Opsional)</span></label>
                            <input type="email" wire:model="customerEmail"
                                class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-medium focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20 transition-all"
                                placeholder="customer@email.com">
                        </div>
                    </div>
                    <div class="pt-2">
                        <button wire:click="$set('isNewCustomer', false)"
                            class="text-sm text-gray-500 hover:text-gray-700 font-bold flex items-center gap-1.5 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Batal, cari customer lama
                        </button>
                    </div>
                </div>
            @else
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input type="text" wire:model.live.debounce.300ms="searchCustomer"
                        class="w-full bg-white border border-gray-300 shadow-sm rounded-xl pl-12 pr-4 py-3.5 text-sm focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20 transition"
                        placeholder="Ketik nama atau nomor HP customer untuk mencari...">
                </div>

                @if (strlen($searchCustomer) >= 2)
                    <div class="bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto divide-y divide-gray-100 mt-2 z-20 relative">
                        @forelse($this->customerResults as $user)
                            <button wire:click="selectCustomer({{ $user->id }})"
                                class="w-full p-4 hover:bg-blue-50/50 text-left flex justify-between items-center transition group">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center font-bold text-sm uppercase group-hover:bg-blue-100 group-hover:text-blue-600 transition-colors">
                                        {{ substr($user->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-800 group-hover:text-[#1c69d4] transition-colors">{{ $user->name }}</p>
                                        <p class="text-xs text-gray-500 font-medium mt-0.5">{{ $user->profile->phone_number ?? $user->email }}</p>
                                    </div>
                                </div>
                                <span class="text-sm text-gray-400 group-hover:text-blue-600 font-bold transition-all transform group-hover:translate-x-[-4px]">
                                    Pilih &rarr;
                                </span>
                            </button>
                        @empty
                            <div class="p-6 text-center">
                                <p class="text-sm font-bold text-gray-600">Customer tidak ditemukan</p>
                                <button wire:click="$set('isNewCustomer', true)" class="mt-2 text-sm text-[#1c69d4] font-bold hover:underline">
                                    Buat Customer Baru
                                </button>
                            </div>
                        @endforelse
                    </div>
                @endif
            @endif
        </div>

        {{-- SALES SECTION --}}
        <div class="space-y-4 pt-4 border-t border-gray-100">
            <h3 class="font-bold text-gray-700 flex items-center gap-2 border-b border-gray-100 pb-2">
                <svg class="w-5 h-5 text-[#1c69d4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Tenaga Penjual (Sales)
            </h3>

            @if (count($selectedSales) > 0)
                <div class="flex flex-wrap gap-2 mb-2">
                    @foreach ($selectedSales as $sales)
                        <div class="flex items-center gap-2 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg pl-3 pr-1 py-1.5 shadow-sm">
                            <span class="text-sm font-bold">{{ $sales['name'] }}</span>
                            <button wire:click="removeSales({{ $sales['id'] }})"
                                class="w-6 h-6 rounded-md flex items-center justify-center text-blue-400 hover:text-rose-600 hover:bg-rose-100 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <input type="text" wire:model.live.debounce.300ms="searchSales"
                    class="w-full bg-white border border-gray-300 shadow-sm rounded-xl pl-12 pr-4 py-3.5 text-sm focus:border-[#1c69d4] focus:ring-1 focus:ring-[#1c69d4]/20 transition"
                    placeholder="Ketik nama atau NIK sales...">

                @if (strlen($searchSales) >= 2)
                    <div class="absolute z-30 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto divide-y divide-gray-100 mt-2">
                        @forelse($this->salesResults as $sales)
                            <button wire:click="selectSales({{ $sales->id }})"
                                class="w-full p-4 hover:bg-gray-50 text-left flex justify-between items-center group transition">
                                <div>
                                    <p class="font-bold text-gray-800 text-sm group-hover:text-[#1c69d4] transition-colors">{{ $sales->name }}</p>
                                    <p class="text-xs text-gray-500 font-medium mt-0.5">NIK: {{ $sales->employee_no ?? 'N/A' }} &bull; {{ $sales->branch->name }}</p>
                                </div>
                                <span class="text-sm font-bold text-[#1c69d4] bg-blue-50 px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transform translate-x-2 group-hover:translate-x-0 transition-all">
                                    + Tambah
                                </span>
                            </button>
                        @empty
                            <p class="p-4 text-sm text-gray-500 text-center font-bold">Sales tidak ditemukan</p>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Footer Actions --}}
    <div class="p-6 border-t border-gray-100 bg-gray-50 flex justify-end gap-3">
        <button wire:click="nextStep"
            class="px-8 py-3 bg-[#1c69d4] hover:bg-blue-700 text-white font-bold rounded-xl shadow-md transition-all flex items-center gap-2">
            Lanjut ke Keranjang
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </button>
    </div>
</div>
