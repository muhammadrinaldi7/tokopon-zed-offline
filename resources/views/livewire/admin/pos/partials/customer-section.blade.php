                {{-- Customer Section --}}
                <div class="px-4 py-3">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Customer</p>
                    @if ($selectedCustomerId)
                        @php $customer = \App\Models\User::with('profile')->find($selectedCustomerId); @endphp
                        <div
                            class="flex items-center justify-between bg-emerald-50 rounded-lg p-2.5 border border-emerald-100">
                            <div>
                                <p class="font-bold text-gray-800 text-xs">{{ $customer->name }}</p>
                                <p class="text-[10px] text-gray-500">
                                    {{ $customer->profile->phone_number ?? $customer->email }}</p>
                            </div>
                            <button wire:click="clearSelectedCustomer"
                                class="text-rose-400 hover:text-rose-600 text-[11px] font-bold">Ganti</button>
                        </div>
                    @elseif($isNewCustomer)
                        <div class="space-y-1.5">
                            <input type="text" wire:model="customerName"
                                class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs focus:border-[#1c69d4] focus:ring-0"
                                placeholder="Nama Customer *">
                            <input type="text" wire:model="customerPhone"
                                class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs focus:border-[#1c69d4] focus:ring-0"
                                placeholder="No HP *">
                            <input type="email" wire:model="customerEmail"
                                class="w-full bg-white border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs focus:border-[#1c69d4] focus:ring-0"
                                placeholder="Email (opsional)">
                            <button wire:click="$set('isNewCustomer', false)"
                                class="text-[10px] text-gray-400 hover:text-gray-600 font-bold">← Cari customer
                                lama</button>
                        </div>
                    @else
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="searchCustomer"
                                class="w-full bg-white border border-gray-200 rounded-lg pl-8 pr-3 py-1.5 text-xs focus:border-[#1c69d4] focus:ring-0"
                                placeholder="Cari nama / no HP...">
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        @if (strlen($searchCustomer) >= 2)
                            <div class="bg-white border rounded-lg shadow-lg max-h-32 overflow-y-auto divide-y mt-1">
                                @forelse($this->customerResults as $user)
                                    <button wire:click="selectCustomer({{ $user->id }})"
                                        class="w-full p-2 hover:bg-gray-50 text-left flex justify-between items-center">
                                        <div>
                                            <p class="font-bold text-gray-800 text-xs">{{ $user->name }}</p>
                                            <p class="text-[10px] text-gray-400">
                                                {{ $user->profile->phone_number ?? $user->email }}</p>
                                        </div>
                                        <span class="text-emerald-500 text-[10px] font-bold">Pilih</span>
                                    </button>
                                @empty
                                    <p class="p-2 text-xs text-gray-400 text-center">Tidak ditemukan</p>
                                @endforelse
                            </div>
                        @endif
                        <button wire:click="$set('isNewCustomer', true)"
                            class="text-[10px] text-[#1c69d4] hover:underline font-bold mt-1.5 block">+ Customer
                            Baru</button>
                    @endif
                </div>
                {{-- Sales Section --}}
                <div class="px-4 py-3">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Tenaga Penjual
                        (Sales)</p>

                    {{-- Selected Sales Tags --}}
                    @if (count($selectedSales) > 0)
                        <div class="flex flex-wrap gap-2 mb-2">
                            @foreach ($selectedSales as $sales)
                                <div
                                    class="flex items-center gap-1.5 bg-[#1c69d4]/10 text-[#1c69d4] border border-[#1c69d4]/20 rounded-md px-2 py-1">
                                    <span class="text-[11px] font-bold">{{ $sales['name'] }}</span>
                                    <button wire:click="removeSales({{ $sales['id'] }})"
                                        class="text-[#1c69d4]/70 hover:text-rose-500 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="relative">
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="searchSales"
                                class="w-full bg-white border border-gray-200 rounded-lg pl-8 pr-3 py-1.5 text-xs focus:border-[#1c69d4] focus:ring-0"
                                placeholder="Cari nama / NIK (tambah sales)...">
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        @if (strlen($searchSales) >= 2)
                            <div
                                class="absolute z-10 w-full bg-white border rounded-lg shadow-lg max-h-40 overflow-y-auto divide-y mt-1">
                                @forelse($this->salesResults as $sales)
                                    <button wire:click="selectSales({{ $sales->id }})"
                                        class="w-full p-2 hover:bg-gray-50 text-left flex justify-between items-center group transition">
                                        <div>
                                            <p class="font-bold text-gray-800 text-xs">{{ $sales->name }}</p>
                                            <p class="text-[9px] text-gray-400">{{ $sales->employee_no ?? 'N/A' }}</p>
                                        </div>
                                        <span
                                            class="text-[#1c69d4] text-[10px] font-bold opacity-0 group-hover:opacity-100 transition">Pilih</span>
                                    </button>
                                @empty
                                    <p class="p-2 text-xs text-gray-400 text-center">Tidak ditemukan</p>
                                @endforelse
                            </div>
                        @endif
                    </div>
                </div>
