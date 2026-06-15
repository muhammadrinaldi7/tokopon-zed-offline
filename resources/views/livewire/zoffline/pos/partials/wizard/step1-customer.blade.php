<div class="bg-white rounded-3xl shadow-[0_2px_15px_-3px_rgba(0,0,0,0.07),0_10px_20px_-2px_rgba(0,0,0,0.04)] border border-gray-100 p-8 md:p-10">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        {{-- Customer Name/Search Input --}}
        <div class="relative md:col-span-2">
            <label class="block text-sm font-black text-gray-700 mb-3 uppercase tracking-widest">1. Pilih / Masukkan Nama Pelanggan</label>
            <div class="relative">
                <span class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </span>
                <input type="text" wire:model.live.debounce.300ms="searchCustomer"
                    {{ $selectedCustomerId ? 'disabled' : '' }}
                    class="w-full bg-gray-50/50 border-2 border-gray-200 rounded-2xl pl-16 pr-6 py-5 text-xl focus:border-[#1c69d4] focus:bg-white focus:ring-4 focus:ring-[#1c69d4]/10 transition-all font-black text-gray-800 placeholder-gray-400 {{ $selectedCustomerId ? 'opacity-70 bg-gray-100 cursor-not-allowed' : '' }}"
                    placeholder="{{ $selectedCustomerId ? ($customerName ?: 'Pelanggan Terpilih') : 'Ketik nama pelanggan di sini...' }}">
            </div>
            
            @if (strlen($searchCustomer) >= 2 && !$selectedCustomerId)
                <div class="absolute top-full left-0 w-full mt-3 bg-white border border-gray-100 rounded-2xl shadow-2xl max-h-80 overflow-y-auto z-50">
                    @forelse($this->customerResults as $user)
                        <button wire:click="selectCustomer({{ $user->id }})"
                            class="w-full p-5 hover:bg-blue-50/80 text-left flex flex-col transition border-b border-gray-50 last:border-0 group">
                            <span class="font-black text-lg text-gray-800 group-hover:text-[#1c69d4]">{{ $user->name }}</span>
                            <span class="text-sm font-bold text-gray-500 mt-1">{{ $user->profile->phone_number ?? $user->email }}</span>
                        </button>
                    @empty
                        <div class="p-6 text-left border-t-4 border-[#1c69d4] bg-white rounded-b-2xl">
                            <p class="text-lg font-black text-gray-800">Pelanggan Belum Terdaftar</p>
                            <p class="text-sm font-bold text-gray-500 mt-1">Kami tidak menemukan data untuk <span class="text-gray-800">"{{ $searchCustomer }}"</span>.</p>
                            <div class="mt-4 bg-blue-50 border border-blue-100 rounded-2xl p-5 flex items-start gap-4 shadow-sm">
                                <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shrink-0 shadow-sm text-[#1c69d4]">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <p class="text-sm text-[#1c69d4] font-bold leading-relaxed">
                                    Lanjutkan dengan mengisi <span class="font-black underline">Nomor HP</span> di bawah, lalu pilih Sales. Sistem akan otomatis mendaftarkannya sebagai pelanggan baru saat Anda menekan tombol "Lanjutkan".
                                </p>
                            </div>
                        </div>
                    @endforelse
                </div>
            @endif
        </div>

        {{-- Phone Number Input --}}
        <div class="relative">
            <label class="block text-sm font-black text-gray-700 mb-3 uppercase tracking-widest">2. Nomor WhatsApp / HP</label>
            <div class="relative">
                <span class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </span>
                <input type="text" wire:model="customerPhone" {{ $selectedCustomerId ? 'readonly' : '' }}
                    class="w-full bg-gray-50/50 border-2 border-gray-200 rounded-2xl pl-14 pr-6 py-4 text-lg focus:border-[#1c69d4] focus:bg-white focus:ring-4 focus:ring-[#1c69d4]/10 transition-all font-bold text-gray-800 placeholder-gray-400 {{ $selectedCustomerId ? 'opacity-70 bg-gray-100 cursor-not-allowed' : '' }}"
                    placeholder="0812...">
            </div>
        </div>

        {{-- Email Input --}}
        <div class="relative">
            <label class="block text-sm font-black text-gray-700 mb-3 uppercase tracking-widest">Email (Opsional)</label>
            <div class="relative">
                <span class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </span>
                <input type="email" wire:model="customerEmail" {{ $selectedCustomerId ? 'readonly' : '' }}
                    class="w-full bg-gray-50/50 border-2 border-gray-200 rounded-2xl pl-14 pr-6 py-4 text-lg focus:border-[#1c69d4] focus:bg-white focus:ring-4 focus:ring-[#1c69d4]/10 transition-all font-bold text-gray-800 placeholder-gray-400 {{ $selectedCustomerId ? 'opacity-70 bg-gray-100 cursor-not-allowed' : '' }}"
                    placeholder="nama@email.com">
            </div>
        </div>

        {{-- Sales Input --}}
        <div class="relative md:col-span-2">
            <label class="block text-sm font-black text-gray-700 mb-3 uppercase tracking-widest">3. Pilih Tenaga Penjual (Sales)</label>
            <div class="relative">
                <span class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </span>
                <input type="text" wire:model.live.debounce.300ms="searchSales"
                    class="w-full bg-gray-50/50 border-2 border-gray-200 rounded-2xl pl-16 pr-6 py-5 text-xl focus:border-[#1c69d4] focus:bg-white focus:ring-4 focus:ring-[#1c69d4]/10 transition-all font-black text-gray-800 placeholder-gray-400"
                    placeholder="Ketik nama sales...">
            </div>
            
            @if (strlen($searchSales) >= 2)
                <div class="absolute top-full left-0 w-full mt-3 bg-white border border-gray-100 rounded-2xl shadow-2xl max-h-60 overflow-y-auto z-50">
                    @forelse($this->salesResults as $sales)
                        <button wire:click="selectSales({{ $sales->id }})"
                            class="w-full p-5 hover:bg-blue-50/80 text-left flex flex-col transition border-b border-gray-50 last:border-0 group">
                            <span class="font-black text-lg text-gray-800 group-hover:text-[#1c69d4]">{{ $sales->name }}</span>
                            <span class="text-sm font-bold text-gray-500 mt-1">{{ $sales->employee_no ?? 'N/A' }} &bull; {{ $sales->branch->name }}</span>
                        </button>
                    @empty
                        <p class="p-6 text-lg text-gray-500 text-center font-bold">Sales tidak ditemukan</p>
                    @endforelse
                </div>
            @endif
        </div>
    </div>

    {{-- Info Area & Selected Badges --}}
    <div class="mt-8 flex flex-col md:flex-row gap-4 items-center justify-between bg-gray-50 border border-gray-100 p-5 rounded-2xl">
        
        {{-- Selected Customer Info --}}
        <div class="w-full md:w-auto">
            @if ($selectedCustomerId)
                @php $customer = \App\Models\User::with('profile')->find($selectedCustomerId); @endphp
                @if($customer)
                    <div class="inline-flex items-center gap-4 bg-white border-2 border-[#1c69d4] px-5 py-3 rounded-xl shadow-sm w-full md:w-auto">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-[#1c69d4] flex items-center justify-center font-black text-lg uppercase shrink-0">
                            {{ substr($customer->name, 0, 1) }}
                        </div>
                        <div>
                            <span class="block text-sm font-black text-gray-800">{{ $customer->name }}</span>
                            <span class="block text-xs font-bold text-gray-500">{{ $customer->profile->phone_number ?? '-' }}</span>
                        </div>
                        <div class="w-px h-8 bg-gray-200 mx-2"></div>
                        <button wire:click="clearSelectedCustomer" class="text-xs font-black text-[#1c69d4] hover:text-rose-500 transition-colors uppercase tracking-widest shrink-0">
                            Ganti
                        </button>
                    </div>
                @endif
            @elseif($isNewCustomer)
                <div class="inline-flex items-center gap-3 bg-emerald-50 border-2 border-emerald-500 text-emerald-700 px-5 py-3 rounded-xl w-full md:w-auto shadow-sm">
                    <svg class="w-6 h-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    <div>
                        <span class="block text-sm font-black text-emerald-800">Pelanggan Baru Disimpan</span>
                        <span class="block text-xs font-bold text-emerald-600">Sistem otomatis mendaftarkan</span>
                    </div>
                </div>
            @else
                <p class="text-sm font-bold text-gray-400 italic">Pilih pelanggan dan sales untuk melanjutkan...</p>
            @endif
        </div>

        {{-- Selected Sales Badges --}}
        @if (count($selectedSales) > 0)
            <div class="flex flex-wrap gap-3 items-center w-full md:w-auto">
                <span class="text-xs font-black text-gray-400 uppercase tracking-widest">Sales:</span>
                @foreach ($selectedSales as $sales)
                    <div class="flex items-center gap-2 bg-white text-gray-800 border-2 border-gray-200 rounded-xl pl-4 pr-2 py-2 shadow-sm">
                        <span class="font-black text-sm">{{ $sales['name'] }}</span>
                        <button wire:click="removeSales({{ $sales['id'] }})"
                            class="w-6 h-6 rounded flex items-center justify-center text-gray-400 hover:text-rose-500 hover:bg-rose-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Lanjutkan Button (Massive) --}}
    <button wire:click="nextStep"
        class="w-full mt-8 px-8 py-5 bg-[#1c69d4] hover:bg-blue-700 text-white font-black text-xl rounded-2xl shadow-[0_8px_15px_-3px_rgba(28,105,212,0.3)] hover:shadow-[0_12px_20px_-3px_rgba(28,105,212,0.4)] hover:-translate-y-1 transition-all flex items-center justify-center gap-3">
        Lanjutkan Transaksi
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
        </svg>
    </button>
</div>
