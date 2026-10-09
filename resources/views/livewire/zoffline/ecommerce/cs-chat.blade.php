<div class="p-4 sm:p-6 min-h-screen bg-neutral-100" wire:poll.5s="refreshData">
    {{-- Header Modul E-Commerce --}}
    @include('livewire.zoffline.ecommerce.partials.header', ['title' => 'Live Chat CS & Penjualan'])

    {{-- Main Chat Container --}}
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden flex flex-col md:flex-row"
        style="height: calc(100vh - 190px); min-height: 560px;">

        {{-- 1. Sidebar: List Percakapan Pelanggan --}}
        <div class="w-full md:w-88 border-r border-gray-200 flex flex-col bg-gray-50/60 shrink-0">
            {{-- Search & Filter Bar --}}
            <div class="p-3.5 border-b border-gray-200 bg-white space-y-2.5">
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau nomor HP..."
                        class="w-full pl-9 pr-3 py-2 bg-gray-100 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all outline-none">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                {{-- Status Filter Pills --}}
                <div class="flex items-center gap-1 overflow-x-auto pb-1 text-[11px] font-bold">
                    <button wire:click="setFilterTab('all')"
                        class="px-2.5 py-1 rounded-lg transition-all {{ $filterTab === 'all' ? 'bg-neutral-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        Semua
                    </button>
                    <button wire:click="setFilterTab('open')"
                        class="px-2.5 py-1 rounded-lg transition-all {{ $filterTab === 'open' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        Open
                    </button>
                    <button wire:click="setFilterTab('deal')"
                        class="px-2.5 py-1 rounded-lg transition-all {{ $filterTab === 'deal' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        Deal 🎉
                    </button>
                    <button wire:click="setFilterTab('follow_up')"
                        class="px-2.5 py-1 rounded-lg transition-all {{ $filterTab === 'follow_up' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        Follow Up
                    </button>
                    <button wire:click="setFilterTab('lost')"
                        class="px-2.5 py-1 rounded-lg transition-all {{ $filterTab === 'lost' ? 'bg-rose-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        Batal
                    </button>
                </div>
            </div>

            {{-- Conversation Items List --}}
            <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                @forelse($conversations as $conv)
                    @php
                        $isActive = $activeConversationId === $conv['id'];
                        $unread = $conv['unreadCount'] ?? 0;
                    @endphp
                    <div wire:click="selectConversation({{ $conv['id'] }})"
                        class="p-3.5 cursor-pointer transition-all duration-150 relative border-l-4 {{ $isActive ? 'bg-white shadow-xs border-indigo-600' : 'hover:bg-white/80 border-transparent' }}">
                        <div class="flex items-start gap-2.5">
                            {{-- User Avatar --}}
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center font-black text-xs shrink-0 shadow-2xs {{ $conv['isGuest'] ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                                {{ $conv['userInitial'] }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1">
                                    <div class="flex items-center gap-1 min-w-0">
                                        <span class="text-xs font-bold text-gray-800 truncate">{{ $conv['userName'] }}</span>
                                        @if($conv['isGuest'])
                                            <span class="text-[9px] bg-amber-50 text-amber-600 border border-amber-200 px-1 rounded font-medium shrink-0">Guest</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-gray-400 font-mono shrink-0">{{ $conv['lastTime'] }}</span>
                                </div>

                                <p class="text-xs text-gray-500 truncate mt-0.5">{{ $conv['lastMessage'] }}</p>

                                {{-- Badges Row --}}
                                <div class="flex items-center justify-between mt-1.5 pt-0.5">
                                    <div class="flex items-center gap-1">
                                        {{-- Closing Badge --}}
                                        @if($conv['closingStatus'] === 'deal')
                                            <span class="px-1.5 py-0.2 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                Deal {{ $conv['closingAmount'] > 0 ? 'Rp' . number_format($conv['closingAmount'], 0, ',', '.') : '' }}
                                            </span>
                                        @elseif($conv['closingStatus'] === 'follow_up')
                                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                                Follow Up
                                            </span>
                                        @elseif($conv['closingStatus'] === 'lost')
                                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                Batal
                                            </span>
                                        @else
                                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-gray-100 text-gray-600">
                                                Aktif
                                            </span>
                                        @endif

                                        @if($conv['businessUnitName'])
                                            <span class="text-[9px] font-mono text-gray-400 uppercase">
                                                {{ Str::limit($conv['businessUnitName'], 12) }}
                                            </span>
                                        @endif
                                    </div>

                                    @if($unread > 0)
                                        <span class="bg-indigo-600 text-white text-[10px] font-bold w-4.5 h-4.5 rounded-full flex items-center justify-center shrink-0">
                                            {{ $unread }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-400 text-xs">
                        Tidak ada percakapan ditemukan.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- 2. Chat Conversation Window --}}
        <div class="flex-1 flex flex-col bg-white">
            @if($activeConv)
                {{-- Chat Header --}}
                <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between bg-white shadow-2xs z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm {{ is_null($activeConv->user_id) ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                            {{ strtoupper(substr($activeConv->user?->name ?? ($activeConv->guest_name ?: 'T'), 0, 1)) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-gray-900 text-sm">
                                    {{ $activeConv->user?->name ?? ($activeConv->guest_name ?: 'Tamu (Guest)') }}
                                </h3>
                                @if($activeConv->guest_phone)
                                    <span class="text-xs font-mono text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">
                                        📱 {{ $activeConv->guest_phone }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-400 mt-0.5">
                                <span>Toko: <strong class="text-gray-600">{{ $activeConv->businessUnit?->name ?? 'Toko Retail' }}</strong></span>
                                @if($activeConv->closing_status && $activeConv->closing_status !== 'none')
                                    <span>• Status Closing: <strong class="text-indigo-600 uppercase">{{ $activeConv->closing_status }}</strong></span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Action Button: Tag Closing & Info --}}
                    <div class="flex items-center gap-2">
                        <button wire:click="openClosingModal({{ $activeConv->id }})"
                            class="px-3.5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Tandai Closing (Deal)</span>
                        </button>
                    </div>
                </div>

                {{-- Message Streams Area --}}
                <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4 bg-slate-50/50" id="chat-messages-container">
                    @forelse($messages as $msg)
                        <div class="flex flex-col {{ $msg['isCs'] ? 'items-end' : 'items-start' }}">
                            <div class="flex items-center gap-1.5 mb-1 text-[11px] text-gray-400">
                                <span class="font-bold {{ $msg['isCs'] ? 'text-indigo-600' : 'text-gray-700' }}">{{ $msg['user'] }}</span>
                                <span>•</span>
                                <span>{{ $msg['time'] }}</span>
                            </div>

                            {{-- Product Card Attachment --}}
                            @if(!empty($msg['product']))
                                <div class="mb-2 p-2.5 bg-white border border-gray-200 rounded-2xl shadow-xs max-w-sm flex items-center gap-3">
                                    <img src="{{ $msg['product']['thumbnail'] }}" class="w-14 h-14 rounded-xl object-cover border border-gray-100" />
                                    <div class="flex-1 min-w-0">
                                        <span class="text-[10px] font-bold text-indigo-600 uppercase">{{ $msg['product']['brand'] }}</span>
                                        <h5 class="text-xs font-bold text-gray-900 truncate">{{ $msg['product']['name'] }}</h5>
                                        <p class="text-xs font-black text-emerald-600 mt-0.5">{{ $msg['product']['price'] }}</p>
                                    </div>
                                </div>
                            @endif

                            {{-- Message Text Bubble --}}
                            <div class="px-4 py-2.5 rounded-2xl max-w-md text-xs leading-relaxed shadow-xs {{ $msg['isCs'] ? 'bg-indigo-600 text-white rounded-tr-xs' : 'bg-white text-gray-800 border border-gray-200/80 rounded-tl-xs' }}">
                                {{ $msg['text'] }}
                            </div>
                        </div>
                    @empty
                        <div class="h-full flex flex-col items-center justify-center text-gray-400 space-y-2">
                            <span class="text-3xl">💬</span>
                            <p class="text-xs">Belum ada riwayat percakapan.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Chat Input Bar --}}
                <div class="p-3.5 bg-white border-t border-gray-100">
                    <form wire:submit.prevent="sendMessage" class="flex items-center gap-2">
                        <input type="text" wire:model="message" placeholder="Ketik balasan Anda ke pelanggan..."
                            class="flex-1 px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all outline-none">
                        <button type="submit"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition-all shadow-xs flex items-center gap-1.5">
                            <span>Kirim</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            @else
                <div class="h-full flex flex-col items-center justify-center text-gray-400 p-8 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center mb-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-gray-700">Pilih Percakapan di Sidebar</h4>
                    <p class="text-xs text-gray-400 mt-1 max-w-sm">Klik salah satu pelanggan di sebelah kiri untuk melihat riwayat chat, menjawab pertanyaan, dan mencatat closing penjualan.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL DIALOG: TANDAI STATUS CLOSING --}}
    @if($showClosingModal)
        <div class="fixed inset-0 bg-neutral-900/60 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl shadow-xl w-full max-w-md p-6 space-y-4" @click.away="$wire.set('showClosingModal', false)">
                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                    <h3 class="text-base font-extrabold text-gray-900">Catat Analisa Closing CS</h3>
                    <button wire:click="$set('showClosingModal', false)" class="text-gray-400 hover:text-gray-600 text-sm">✕</button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Status Prospek / Closing</label>
                        <select wire:model="closingStatus" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold">
                            <option value="deal">🎉 Deal (Closing Berhasil / Beli)</option>
                            <option value="follow_up">⏳ Follow Up (Masih Pikir-pikir / Tanya Spek)</option>
                            <option value="lost">❌ Lost (Batal / Kemahalan / Stok Habis)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Estimasi Nominal Closing (Rp)</label>
                        <input type="number" wire:model="closingAmount" placeholder="Contoh: 12500000"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold">
                        <span class="text-[10px] text-gray-400">Masukkan nilai transaksi jika closing berhasil.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Catatan Closing / Alasan Drop-off</label>
                        <textarea wire:model="closingNotes" rows="2.5" placeholder="Contoh: Transfer BCA, beli iPhone 13 128GB Starlight, kirim gosend..."
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium"></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button wire:click="$set('showClosingModal', false)" class="px-4 py-2 text-xs font-bold text-gray-500 hover:bg-gray-100 rounded-xl">Batal</button>
                    <button wire:click="saveClosingStatus" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs">Simpan Data Closing</button>
                </div>
            </div>
        </div>
    @endif
</div>
