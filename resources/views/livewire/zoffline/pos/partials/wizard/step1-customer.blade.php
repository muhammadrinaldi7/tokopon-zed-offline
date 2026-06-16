<div>
    <div class="mb-6 px-2">
        <div class="flex gap-2 items-center">
            <div class="rounded-full w-8 h-8 bg-[#DFE7FF] flex items-center justify-center text-black">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-auto" viewBox="0 0 24 24">
                    <path d="M0 0h24v24H0z" fill="none" />
                    <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                        stroke-width="1.2">
                        <path d="M14.5 10.5a2.5 2.5 0 1 1-5 0a2.5 2.5 0 0 1 5 0" />
                        <path
                            d="M16 3.5c2.48 0 4.19.384 5.133.676c.543.169.867.683.867 1.251v9.755c0 1.115-1.228 1.954-2.324 1.748c-.94-.178-2.165-.32-3.676-.32c-4.75 0-5.89 1.805-12.855.27A1.47 1.47 0 0 1 2 15.437V5.421c0-.976.92-1.687 1.878-1.497C10.197 5.177 11.421 3.5 16 3.5" />
                        <path
                            d="M2 7.5c1.951 0 3.705-1.595 3.929-3.246M18.5 4c0 2.04 1.765 3.969 3.5 3.969m0 5.531c-1.9 0-3.74 1.31-3.898 3.098M6 16.996a4 4 0 0 0-4-4m17 6.737a18.5 18.5 0 0 0-3-.233c-4.294 0-5.638 1.66-11 .703" />
                    </g>
                </svg>
            </div>
            <p class="text-sm text-neutral-500">Transaksi Penjualan</p>
        </div>
        <h1 class="text-3xl font-semibold  text-neutral-800 mt-4">Siapa Pelanggan Anda Hari ini?</h1>
    </div>

    <div class="bg-white rounded-3xl shadow-sm p-8 md:p-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Customer Name/Search Input --}}
            <div class="relative md:col-span-1">
                <label class="block text-xs lg:text-sm  text-gray-700 mb-3 font-semibold uppercase tracking-widest">1.
                    Cari Nama /
                    No
                    Telpon</label>
                <div class="relative flex items-center">
                    {{-- Input Field --}}
                    <input type="text" wire:model.live.debounce.300ms="searchCustomer"
                        {{ $selectedCustomerId ? 'disabled' : '' }}
                        class="peer w-full bg-neutral-50/80 border-2 border-neutral-200 hover:border-neutral-300 rounded-xl pl-14 pr-12 py-3 text-lg focus:border-[#1c69d4] focus:bg-white focus:ring-4 focus:ring-[#1c69d4]/10 transition-all font-normal text-gray-700 placeholder-gray-400 outline-none {{ $selectedCustomerId ? 'opacity-70 bg-gray-100 border-gray-200 cursor-not-allowed hover:border-gray-200' : '' }}"
                        placeholder="{{ $selectedCustomerId ? ($customerName ?: 'Pelanggan Terpilih') : 'Ketik nama pelanggan di sini...' }}">

                    {{-- Icon User (Kiri) --}}
                    <span
                        class="absolute left-5 text-gray-400 transition-colors duration-200 peer-focus:text-neutral-800 pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24">
                            <path d="M0 0h24v24H0z" fill="none" />
                            <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M19.523 21.99H4.488c-1.503 0-2.663-1.134-2.466-2.624l.114-.869c.207-1.2 1.305-1.955 2.497-2.214L11.928 15h.144l7.295 1.283c1.212.28 2.29.993 2.497 2.214l.114.88c.197 1.49-.963 2.623-2.466 2.623zM17 7A5 5 0 1 1 7 7a5 5 0 0 1 10 0" />
                        </svg>

                    </span>

                    {{-- Loading Spinner Livewire (Kanan) --}}
                    <div wire:loading wire:target="searchCustomer" class="absolute right-5">
                        <svg class="animate-spin w-5 h-5 text-neutral-800" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="3"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </div>
                </div>

                @if (strlen($searchCustomer) >= 2 && !$selectedCustomerId)
                    <div
                        class="absolute top-full left-0 w-full mt-3 bg-white border border-gray-100 rounded-2xl shadow-2xl max-h-80 overflow-y-auto z-50">
                        @forelse($this->customerResults as $user)
                            <button wire:click="selectCustomer({{ $user->id }})"
                                class="w-full p-3 hover:bg-blue-50/80 text-left flex flex-col transition border-b border-gray-50 last:border-0 group">
                                <span class="text-neutral-800 group-hover:text-[#1c69d4]">{{ $user->name }}</span>
                                <span
                                    class="text-sm font-bold text-gray-500 mt-1">{{ $user->profile->phone_number ?? $user->email }}</span>
                            </button>
                        @empty
                            {{-- Floating Info Pojok Kanan Atas --}}
                            <div x-data="{ showInfo: true }" x-show="showInfo"
                                class="fixed top-6 right-6 w-full max-w-[420px] bg-white rounded-2xl shadow-[0_20px_50px_-12px_rgba(0,0,0,0.15)] border border-gray-100 p-5 z-[100] animate-[slide-in-right_0.3s_ease-out] ring-1 ring-black/5">

                                <div class="flex items-start gap-4">
                                    {{-- Main Icon (User Not Found) --}}


                                    {{-- Content Wrapper --}}
                                    <div class="flex-1 pt-1">
                                        {{-- Header & Close Button --}}
                                        <div class="flex justify-between items-start">
                                            <p class="font-semibold text-gray-800 leading-none">Pelanggan Baru?</p>

                                            {{-- Close Button --}}
                                            <button type="button" @click="showInfo = false"
                                                class="text-gray-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg p-1 transition-colors -mt-1 -mr-1">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        {{-- Search Term Info --}}
                                        <p class="text-sm text-neutral-400">
                                            Data <span class="text-neutral-800">"{{ $searchCustomer }}"</span>
                                            belum terdaftar di sistem.
                                        </p>

                                        {{-- Actionable Instruction Box --}}
                                        <div class="mt-2 b rounded-xl flex items-start gap-2 relative overflow-hidden">

                                            <div class="text-[#1c69d4] mt-0.5 shrink-0 pl-1">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="3">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <p class="text-xs text-gray-700 font-bold leading-relaxed">
                                                Silakan isi <span class="text-[#1c69d4] font-black">Nomor HP</span> &
                                                <span class="text-[#1c69d4] font-black">Email</span>, lalu pilih Sales.
                                                Sistem akan <span
                                                    class="underline decoration-[#1c69d4]/30 decoration-2 underline-offset-2">otomatis
                                                    mendaftarkannya</span> saat klik "Lanjutkan".
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>

            {{-- Phone Number Input --}}
            <div class="relative md:col-span-1">
                <label class="block text-xs lg:text-sm font-semibold text-gray-700 mb-3 uppercase tracking-widest">2.
                    Nomor
                    WhatsApp</label>

                <div class="relative flex items-center">
                    {{-- Input Field --}}
                    <input type="text" wire:model="customerPhone" {{ $selectedCustomerId ? 'readonly' : '' }}
                        class="peer w-full bg-gray-50/80 border-2 border-gray-200 hover:border-gray-300 rounded-xl pl-14 pr-6 py-3 text-lg focus:border-[#1c69d4] focus:bg-white focus:ring-4 focus:ring-[#1c69d4]/10 transition-all font-normal text-gray-700 placeholder-gray-400 outline-none {{ $selectedCustomerId ? 'opacity-70 bg-gray-100 border-gray-200 cursor-not-allowed hover:border-gray-200' : '' }}"
                        placeholder="0812...">

                    {{-- Icon Phone (Kiri) --}}
                    <span
                        class="absolute left-5 text-gray-400 transition-colors duration-200 peer-focus:text-[#1c69d4] pointer-events-none">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </span>
                </div>
            </div>

            {{-- Email Input --}}
            <div class="relative md:col-span-1">
                <label class="block text-xs lg:text-sm font-semibold text-gray-700 mb-3 uppercase tracking-widest">Email
                    (Opsional)</label>

                <div class="relative flex items-center">
                    {{-- Input Field --}}
                    <input type="email" wire:model="customerEmail" {{ $selectedCustomerId ? 'readonly' : '' }}
                        class="peer w-full bg-gray-50/80 border-2 border-gray-200 hover:border-gray-300 rounded-xl pl-14 pr-6 py-3 text-lg focus:border-[#1c69d4] focus:bg-white focus:ring-4 focus:ring-[#1c69d4]/10 transition-all font-normal text-gray-700 placeholder-gray-400 outline-none {{ $selectedCustomerId ? 'opacity-70 bg-gray-100 border-gray-200 cursor-not-allowed hover:border-gray-200' : '' }}"
                        placeholder="nama@email.com">

                    {{-- Icon Email (Kiri) --}}
                    <span
                        class="absolute left-5 text-gray-400 transition-colors duration-200 peer-focus:text-[#1c69d4] pointer-events-none">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </span>
                </div>
            </div>

            {{-- Sales Input --}}
            <div class="relative md:col-span-3">
                <label class="block text-xs lg:text-sm font-semibold text-gray-700 mb-3 uppercase tracking-widest">3.
                    Pilih Tenaga
                    Penjual (Sales)</label>

                <div class="relative flex items-center">
                    {{-- Input Field --}}
                    <input type="text" wire:model.live.debounce.300ms="searchSales"
                        class="peer w-full bg-gray-50/80 border-2 border-gray-200 hover:border-gray-300 rounded-2xl pl-14 pr-12 py-3 text-lg focus:border-[#1c69d4] focus:bg-white focus:ring-4 focus:ring-[#1c69d4]/10 transition-all font-normal text-gray-700 placeholder-gray-400 outline-none"
                        placeholder="Ketik nama sales...">

                    {{-- Icon Users (Kiri) --}}
                    <span
                        class="absolute left-5 text-gray-400 transition-colors duration-200 peer-focus:text-[#1c69d4] pointer-events-none">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </span>

                    {{-- Loading Spinner Livewire (Kanan) --}}
                    <div wire:loading wire:target="searchSales" class="absolute right-5">
                        <svg class="animate-spin w-5 h-5 text-[#1c69d4]" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="3"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </div>
                </div>

                {{-- Dropdown Hasil Pencarian Sales --}}
                @if (strlen($searchSales) >= 2)
                    <div
                        class="absolute top-full left-0 w-full mt-3 bg-white border border-gray-100 rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.1)] max-h-60 overflow-y-auto z-50 ring-1 ring-black/5">
                        @forelse($this->salesResults as $sales)
                            <button wire:click="selectSales({{ $sales->id }})"
                                class="w-full px-5 py-4 hover:bg-blue-50/80 text-left flex flex-col transition border-b border-gray-50 last:border-0 group">
                                <span
                                    class="font-bold text-gray-800 group-hover:text-[#1c69d4]">{{ $sales->name }}</span>
                                <span
                                    class="text-sm font-medium text-gray-500 mt-1">{{ $sales->employee_no ?? 'N/A' }}
                                    &bull; {{ $sales->branch->name }}</span>
                            </button>
                        @empty
                            <p class="p-6 text-gray-500 text-center font-medium">Data sales tidak ditemukan</p>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>

        {{-- Info Area & Selected Badges --}}
        <div
            class="mt-8 flex flex-col md:flex-row gap-4 items-center justify-between bg-gray-50 border border-gray-100 p-5 rounded-2xl">
            {{-- Selected Customer Info --}}
            <div class="w-full md:w-auto">
                @if ($selectedCustomerId)
                    @php $customer = \App\Models\User::with('profile')->find($selectedCustomerId); @endphp
                    @if ($customer)
                        <div
                            class="inline-flex items-center gap-4 bg-gradient-to-r from-blue-50/50 to-white border border-blue-200 hover:border-[#1c69d4]/40 hover:shadow-md transition-all px-5 py-3.5 rounded-2xl w-full md:w-auto group">
                            <div
                                class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#1c69d4] to-[#458af0] text-white flex items-center justify-center font-black text-xl uppercase shrink-0 shadow-[0_4px_10px_rgba(28,105,212,0.3)]">
                                {{ substr($customer->name, 0, 1) }}
                            </div>
                            <div class="flex-1 pr-4">
                                <span
                                    class="block text-base font-black text-gray-800 tracking-tight">{{ $customer->name }}</span>
                                <span
                                    class="block text-sm font-bold text-blue-600/80">{{ $customer->profile->phone_number ?? '-' }}</span>
                            </div>

                            <button wire:click="clearSelectedCustomer" title="Ganti Pelanggan"
                                class="w-8 h-8 rounded-full bg-red-50/80 text-red-400 hover:bg-rose-100 hover:text-rose-500 flex items-center justify-center transition-all group-hover:scale-105 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    @endif
                @elseif($isNewCustomer)
                    <div
                        class="inline-flex items-center gap-3 bg-emerald-50 border-2 border-emerald-500 text-emerald-700 px-5 py-3 rounded-xl w-full md:w-auto shadow-sm">
                        <svg class="w-6 h-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <div>
                            <span class="block text-sm font-black text-emerald-800">Pelanggan Baru Disimpan</span>
                            <span class="block text-xs font-bold text-emerald-600">Sistem otomatis mendaftarkan</span>
                        </div>
                    </div>
                @else
                    <p class="text-sm font-bold text-gray-400 italic">Pilih pelanggan dan sales untuk melanjutkan...
                    </p>
                @endif
            </div>

            {{-- Selected Sales Badges --}}
            @if (count($selectedSales) > 0)
                <div class="flex flex-wrap gap-3 items-center w-full md:w-auto">
                    <span class="text-xs font-black text-gray-400 uppercase tracking-widest">Sales:</span>
                    @foreach ($selectedSales as $sales)
                        <div
                            class="flex items-center gap-2 bg-white text-gray-800 border-2 border-gray-200 rounded-xl pl-4 pr-2 py-2 shadow-sm">
                            <span class="font-black text-sm">{{ $sales['name'] }}</span>
                            <button wire:click="removeSales({{ $sales['id'] }})"
                                class="w-6 h-6 rounded flex items-center justify-center text-gray-400 hover:text-rose-500 hover:bg-rose-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Lanjutkan Button (Massive) --}}
        @php
            $isReady = ($selectedCustomerId || !empty($customerPhone)) && count($selectedSales) > 0;
        @endphp
        <button wire:click="nextStep" @if (!$isReady) disabled @endif wire:loading.attr="disabled" wire:target="nextStep"
            class="w-full mt-8 px-8 py-5 font-semibold text-xl rounded-2xl transition-all flex items-center justify-center gap-3 {{ $isReady ? 'bg-[#668DFF] hover:bg-[#1c69d4] text-white shadow-[0_8px_15px_-3px_rgba(28,105,212,0.3)] hover:shadow-[0_12px_20px_-3px_rgba(28,105,212,0.4)] hover:-translate-y-1' : 'bg-gray-200 text-gray-400 cursor-not-allowed' }} disabled:opacity-75 disabled:cursor-wait">
            <span wire:loading.remove wire:target="nextStep">Lanjutkan</span>
            <span wire:loading.inline-flex wire:target="nextStep" class="items-center gap-2">
                <svg class="animate-spin h-6 w-6 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Memproses...
            </span>
        </button>
    </div>

    {{-- Modal Confirm Update Customer --}}
    @if ($showConfirmUpdateCustomerModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-blue-50/50">
                    <h3 class="text-xl font-black text-gray-800">Nomor HP Terdaftar</h3>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-4 leading-relaxed">
                        Nomor HP <span class="font-bold text-gray-900">{{ $customerPhone }}</span> sudah terdaftar atas nama <span class="font-bold text-gray-900">{{ $existingCustomerToUpdate->name ?? '' }}</span>.<br><br>
                        Yakin ingin mengubah nama pelanggan tersebut menjadi <span class="font-bold text-[#1c69d4]">{{ $customerName }}</span>?
                    </p>
                    <div class="flex flex-col gap-3">
                        <button wire:click="confirmUpdateCustomer"
                            class="w-full py-3.5 bg-[#1c69d4] hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg transition-all">
                            Ya, Ubah Nama
                        </button>
                        <button wire:click="cancelUpdateCustomer"
                            class="w-full py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-colors">
                            Tidak, Gunakan Data Lama
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
