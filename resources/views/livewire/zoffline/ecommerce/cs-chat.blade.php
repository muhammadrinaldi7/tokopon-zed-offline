<div class="p-4 sm:p-6 min-h-screen bg-neutral-100" wire:poll.25s="refreshData">
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
                    <button wire:click="setFilterTab('my_chats')"
                        class="px-2.5 py-1 rounded-lg transition-all flex items-center gap-1 {{ $filterTab === 'my_chats' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                        title="Hanya obrolan yang ditangani oleh saya">
                        <span>👤</span>
                        <span>Chat Saya</span>
                    </button>
                    <button wire:click="setFilterTab('unassigned')"
                        class="px-2.5 py-1 rounded-lg transition-all flex items-center gap-1 {{ $filterTab === 'unassigned' ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                        title="Obrolan terbuka yang belum di-handle CS">
                        <span>⏳</span>
                        <span>Belum Diambil</span>
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
                                    <div class="flex items-center gap-1 flex-wrap">
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

                                        {{-- CS Assignment Badge --}}
                                        @if($conv['assignedToName'])
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-medium {{ $conv['isAssignedToMe'] ? 'bg-indigo-100 text-indigo-700 font-bold border border-indigo-200' : 'bg-gray-100 text-gray-600 border border-gray-200' }}"
                                                title="Ditangani oleh {{ $conv['assignedToName'] }}">
                                                👤 {{ $conv['isAssignedToMe'] ? 'Saya' : Str::limit($conv['assignedToName'], 10) }}
                                            </span>
                                        @else
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200" title="Belum ada CS yang mengambil">
                                                ⏳ Bebas
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
                                @php
                                    $custPhone = $activeConv->guest_phone ?: ($activeConv->user?->customer_phone ?? null);
                                @endphp
                                @if($custPhone)
                                    <span class="text-xs font-mono text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">
                                        📱 {{ $custPhone }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-400 mt-0.5 flex-wrap">
                                <span>Toko: <strong class="text-gray-600">{{ $activeConv->businessUnit?->name ?? 'Toko Retail' }}</strong></span>
                                @if($activeConv->closing_status && $activeConv->closing_status !== 'none')
                                    <span>• Status: <strong class="text-indigo-600 uppercase">{{ $activeConv->closing_status }}</strong></span>
                                @endif
                                @if($activeConv->assignedTo)
                                    <span>• CS: <strong class="{{ $activeConv->assigned_to_user_id === auth()->id() ? 'text-indigo-600 font-bold' : 'text-gray-700' }}">{{ $activeConv->assigned_to_user_id === auth()->id() ? 'Anda Sendiri' : $activeConv->assignedTo->name }}</strong></span>
                                @else
                                    <span class="text-amber-600 font-medium bg-amber-50 px-1.5 py-0.2 rounded border border-amber-200">• Belum Diambil CS</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Action Button: Claim/Release, WhatsApp & Tag Closing --}}
                    <div class="flex items-center gap-2">
                        {{-- Tombol CS Handle / Claim / Release --}}
                        @if(!$activeConv->assigned_to_user_id)
                            <button wire:click="claimConversation"
                                class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all flex items-center gap-1.5"
                                title="Ambil dan tangani percakapan pelanggan ini">
                                <span>🙋 Ambil Chat Ini</span>
                            </button>
                        @elseif($activeConv->assigned_to_user_id === auth()->id())
                            <button wire:click="releaseConversation"
                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1"
                                title="Lepas ke antrean umum agar staf CS lain bisa mengambil alih">
                                <span>Lepas Handle</span>
                            </button>
                        @else
                            <button wire:click="claimConversation"
                                class="px-3 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold shadow-xs transition-all flex items-center gap-1"
                                title="Ambil alih dari {{ $activeConv->assignedTo?->name }}">
                                <span>Ambil Alih</span>
                            </button>
                        @endif

                        @php
                            $waUrl = $this->getWhatsAppUrl($activeConv);
                        @endphp

                        @if($waUrl)
                            <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer"
                                class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all flex items-center gap-1.5"
                                title="Lanjut chat ke WhatsApp pelanggan">
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.861.174.086.275.072.376-.044.101-.116.433-.506.549-.68.116-.173.231-.145.39-.086.159.058 1.011.477 1.184.564.173.087.289.13.332.203.043.072.043.419-.101.824z"/>
                                </svg>
                                <span>WhatsApp</span>
                            </a>
                        @endif

                        <button wire:click="openClosingModal({{ $activeConv->id }})"
                            class="px-3.5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Closing (Deal)</span>
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

                            {{-- File / Image Attachments --}}
                            @if(!empty($msg['attachments']))
                                <div class="mb-2 space-y-1.5 max-w-sm">
                                    @foreach($msg['attachments'] as $att)
                                        @if(!empty($att['is_image']))
                                            <a href="{{ $att['url'] }}" target="_blank" class="block group overflow-hidden rounded-2xl border border-gray-200 shadow-2xs">
                                                <img src="{{ $att['url'] }}" class="max-h-56 w-auto object-cover rounded-2xl hover:scale-102 transition-transform" />
                                            </a>
                                        @else
                                            <a href="{{ $att['url'] }}" target="_blank" class="flex items-center gap-2 p-2.5 bg-white border border-gray-200 rounded-xl text-xs text-indigo-600 hover:underline shadow-2xs">
                                                <span class="text-sm">📎</span>
                                                <span class="truncate font-semibold">{{ $att['file_name'] }}</span>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            {{-- Message Text Bubble --}}
                            @if(!empty(trim($msg['text'])))
                                <div class="px-4 py-2.5 rounded-2xl max-w-md text-xs leading-relaxed shadow-xs {{ $msg['isCs'] ? 'bg-indigo-600 text-white rounded-tr-xs' : 'bg-white text-gray-800 border border-gray-200/80 rounded-tl-xs' }}">
                                    {{ $msg['text'] }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="h-full flex flex-col items-center justify-center text-gray-400 space-y-2">
                            <span class="text-3xl">💬</span>
                            <p class="text-xs">Belum ada riwayat percakapan.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Quick Replies Chips Bar --}}
                <div class="px-3.5 pt-2 pb-1 border-t border-gray-100 flex items-center gap-1.5 overflow-x-auto text-[11px] bg-slate-50/70">
                    <span class="text-gray-400 font-bold shrink-0 text-[10px] uppercase tracking-wider">Balas Cepat:</span>
                    @foreach($quickReplies as $qr)
                        <button type="button" wire:click="applyQuickReply(@js($qr['text']))"
                            class="px-2.5 py-1 rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-600 transition-all shrink-0 font-medium shadow-2xs">
                            {{ $qr['label'] }}
                        </button>
                    @endforeach
                </div>

                {{-- Attached Product / Uploaded Image Preview Chips (Sebelum Dikirim) --}}
                @if($selectedProductPreview || $attachment)
                    <div class="px-3.5 py-2 bg-indigo-50/50 border-t border-indigo-100 flex items-center gap-3">
                        @if($selectedProductPreview)
                            <div class="flex items-center gap-2 bg-white px-2.5 py-1.5 rounded-xl border border-indigo-200 shadow-2xs">
                                <img src="{{ $selectedProductPreview['thumbnail'] }}" class="w-7 h-7 rounded-lg object-cover" />
                                <div class="text-[11px] min-w-0">
                                    <span class="font-bold text-gray-800 truncate block max-w-xs">{{ $selectedProductPreview['name'] }}</span>
                                    <span class="font-extrabold text-indigo-600">{{ $selectedProductPreview['price'] }}</span>
                                </div>
                                <button type="button" wire:click="removeSelectedProduct" class="text-gray-400 hover:text-rose-500 font-bold ml-1 text-xs">✕</button>
                            </div>
                        @endif

                        @if($attachment)
                            <div class="flex items-center gap-2 bg-white px-2.5 py-1.5 rounded-xl border border-indigo-200 shadow-2xs">
                                <span class="text-sm">📷</span>
                                <span class="text-[11px] font-medium text-gray-700 max-w-xs truncate">{{ $attachment->getClientOriginalName() }}</span>
                                <button type="button" wire:click="removeAttachment" class="text-gray-400 hover:text-rose-500 font-bold ml-1 text-xs">✕</button>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Chat Input Bar --}}
                <div class="p-3.5 bg-white border-t border-gray-100">
                    <form wire:submit.prevent="sendMessage" class="flex items-center gap-2">
                        {{-- Tombol Tag / Rekomendasi Produk --}}
                        <button type="button" wire:click="openProductPicker"
                            class="p-2.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all border border-gray-200"
                            title="Rekomendasikan Produk Shopee-style">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </button>

                        {{-- Tombol Unggah Foto / File --}}
                        <label for="cs-chat-file-input"
                            class="p-2.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all border border-gray-200 cursor-pointer"
                            title="Unggah Foto / Gambar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </label>
                        <input type="file" wire:model="attachment" id="cs-chat-file-input" class="hidden" accept="image/*,application/pdf">

                        {{-- Input Teks --}}
                        <input type="text" wire:model="message" placeholder="Ketik balasan Anda ke pelanggan..."
                            class="flex-1 px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all outline-none">

                        {{-- Tombol Kirim --}}
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

    {{-- MODAL DIALOG: PILIH REKOMENDASI PRODUK (SHOPEE-STYLE) --}}
    @if($showProductPickerModal)
        <div class="fixed inset-0 bg-neutral-900/60 backdrop-blur-xs flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-3xl shadow-xl w-full max-w-lg p-6 space-y-4" @click.away="$wire.set('showProductPickerModal', false)">
                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                    <div>
                        <h3 class="text-base font-extrabold text-gray-900">Rekomendasikan Produk</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Pilih produk inventori untuk dikirimkan sebagai kartu rekomendasi ke chat customer.</p>
                    </div>
                    <button wire:click="$set('showProductPickerModal', false)" class="text-gray-400 hover:text-gray-600 text-sm">✕</button>
                </div>

                {{-- Search Bar --}}
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="productSearch" placeholder="Cari nama produk atau kode item..."
                        class="w-full pl-9 pr-4 py-2.5 bg-gray-100 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all outline-none">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                {{-- Search Results List --}}
                <div class="max-h-72 overflow-y-auto space-y-2 divide-y divide-gray-50 pr-1">
                    @forelse($productSearchResults as $prod)
                        <div wire:click="selectProduct({{ $prod['id'] }})"
                            class="pt-2 flex items-center justify-between p-2 rounded-xl hover:bg-indigo-50/70 cursor-pointer transition-all border border-transparent hover:border-indigo-100">
                            <div class="flex items-center gap-3 min-w-0">
                                <img src="{{ $prod['thumbnail'] }}" class="w-12 h-12 rounded-xl object-cover border border-gray-100 shrink-0" />
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-gray-900 truncate">{{ $prod['name'] }}</h4>
                                    <span class="text-[10px] text-gray-400 font-mono">{{ $prod['item_no'] }}</span>
                                    <p class="text-xs font-extrabold text-indigo-600 mt-0.5">{{ $prod['formatted_price'] }}</p>
                                </div>
                            </div>
                            <button type="button" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shrink-0">
                                Pilih
                            </button>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-400 text-xs">
                            Tidak ada produk ditemukan.
                        </div>
                    @endforelse
                </div>

                <div class="flex items-center justify-end pt-2 border-t border-gray-100">
                    <button wire:click="$set('showProductPickerModal', false)" class="px-4 py-2 text-xs font-bold text-gray-500 hover:bg-gray-100 rounded-xl">Batal</button>
                </div>
            </div>
        </div>
    @endif

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

    {{-- AUDIO SYNTHESIZER & REALTIME REVERB ECHO INTEGRATION --}}
    @script
    <script>
        // Synthesize notification chime using Web Audio API (smooth 2-tone melodic chime)
        function playNotificationChime() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                if (ctx.state === 'suspended') {
                    ctx.resume();
                }
                const now = ctx.currentTime;

                // First chime note: 587.33 Hz (D5)
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(587.33, now);
                gain1.gain.setValueAtTime(0.18, now);
                gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start(now);
                osc1.stop(now + 0.35);

                // Second chime note: 880 Hz (A5)
                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(880, now + 0.12);
                gain2.gain.setValueAtTime(0.22, now + 0.12);
                gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.6);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(now + 0.12);
                osc2.stop(now + 0.6);
            } catch (err) {
                console.warn('Notification chime skipped:', err);
            }
        }

        function scrollMessagesBottom() {
            const container = document.getElementById('chat-messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }

        // Setup Laravel Reverb Echo Listeners
        if (window.Echo) {
            // Channel 1: Global CS Chats channel for new incoming messages & sidebar updates
            window.Echo.private('cs-chats')
                .listen('.message.sent', (e) => {
                    if (e.senderType !== 'cs') {
                        playNotificationChime();
                    }
                    $wire.refreshData();
                });

            // Channel 2: Active Conversation specific channel
            let activeSubscribedId = null;

            function subscribeToActiveConv(convId) {
                if (activeSubscribedId && activeSubscribedId !== convId) {
                    window.Echo.leave(`conversation.${activeSubscribedId}`);
                    activeSubscribedId = null;
                }

                if (convId && activeSubscribedId !== convId) {
                    activeSubscribedId = convId;
                    window.Echo.private(`conversation.${convId}`)
                        .listen('.message.sent', (e) => {
                            $wire.refreshData().then(() => {
                                setTimeout(scrollMessagesBottom, 60);
                            });
                        });
                }
            }

            // Initial subscription
            const initConvId = @js($activeConversationId);
            if (initConvId) {
                subscribeToActiveConv(initConvId);
            }

            // Watch for changes in active conversation
            $wire.$watch('activeConversationId', (newId) => {
                subscribeToActiveConv(newId);
                setTimeout(scrollMessagesBottom, 120);
            });
        }

        // Auto-scroll when messages loaded or view mounted
        setTimeout(scrollMessagesBottom, 150);
        $wire.on('cs-messages-loaded', () => {
            setTimeout(scrollMessagesBottom, 50);
        });
    </script>
    @endscript
</div>
