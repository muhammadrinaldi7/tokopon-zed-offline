<div class="p-4 sm:p-6 min-h-screen bg-neutral-100" wire:poll.keep-alive.2500ms="pollNewMessages">
    {{-- Audio Chime Player (Dapat Berbunyi di Background Tab) --}}
    <audio id="chat-chime-player" preload="auto" src="{{ asset('sounds/notification.wav') }}" class="hidden"></audio>

    {{-- Header Modul E-Commerce --}}
    @include('livewire.zoffline.ecommerce.partials.header', ['title' => 'Live Chat CS & Penjualan'])

    {{-- Notification & Live Sync Control Bar --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 bg-white px-4 py-2.5 rounded-2xl border border-gray-200/80 shadow-xs text-xs">
        <div class="flex items-center gap-2">
            <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
            </span>
            <span class="font-bold text-gray-800">Status Live Chat:</span>
            <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-lg border border-emerald-200 font-semibold text-[11px] flex items-center gap-1">
                <span>⚡</span>
                <span>Realtime Aktif (Background Guard)</span>
            </span>
            <span class="text-gray-400 text-[11px] hidden sm:inline">• Pesan tetap masuk & berbunyi saat buka tab lain / YouTube.</span>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            {{-- Tombol Toggle Suara --}}
            <button type="button" id="toggle-sound-btn" onclick="toggleChatSound()"
                class="px-3 py-1.5 rounded-xl border border-gray-200 bg-gray-50 hover:bg-gray-100 text-gray-700 font-bold flex items-center gap-1.5 transition-all shadow-2xs text-[11px]"
                title="Aktifkan atau bisukan nada lonceng pesan masuk">
                <span id="sound-icon">🔊</span>
                <span id="sound-label">Bunyi: Aktif</span>
            </button>

            {{-- Tombol Tes Bunyi Lonceng --}}
            <button type="button" onclick="testNotificationSoundOnly()"
                class="px-3 py-1.5 rounded-xl border border-indigo-200 bg-indigo-50/70 hover:bg-indigo-100 text-indigo-700 font-bold flex items-center gap-1.5 transition-all shadow-2xs text-[11px]"
                title="Putar uji coba nada lonceng untuk mengecek volume suara">
                <span>🎵</span>
                <span>Tes Suara</span>
            </button>

            {{-- Tombol Status Izin Notifikasi Desktop Browser --}}
            <button type="button" id="notif-perm-btn" onclick="requestDesktopNotificationPermission()"
                class="px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-extrabold flex items-center gap-1.5 transition-all shadow-xs text-[11px]"
                title="Klik untuk mengizinkan popup notifikasi di layar Windows saat buka YouTube/aplikasi lain">
                <span id="notif-perm-icon">🔔</span>
                <span id="notif-perm-label">Izinkan Notif Desktop</span>
            </button>
        </div>
    </div>

    {{-- Main Chat Container --}}
    <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden flex flex-col md:flex-row"
        style="height: calc(100vh - 240px); min-height: 540px;">

        {{-- 1. Sidebar: List Percakapan Pelanggan --}}
        <div class="w-full md:w-88 border-r border-gray-200 flex flex-col bg-gray-50/60 shrink-0">
            {{-- Search & Filter Bar --}}
            <div class="p-3.5 border-b border-gray-200 bg-white space-y-2.5">
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama, nomor HP (08/62), atau produk..."
                        class="w-full pl-9 pr-8 py-2 bg-gray-100 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all outline-none">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>

                    {{-- Loading indicator --}}
                    <div wire:loading wire:target="search,clearSearch" class="absolute right-7 top-2.5">
                        <svg class="animate-spin h-3.5 w-3.5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    {{-- Clear Search Button --}}
                    @if(!empty($search))
                        <button type="button" wire:click="clearSearch"
                            class="absolute right-2.5 top-2 text-gray-400 hover:text-rose-500 transition-colors p-0.5"
                            title="Hapus pencarian">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    @endif
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

                                {{-- Customer Phone & Loyalty Badge --}}
                                @if(!empty($conv['guestPhone']))
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <span class="text-[10.5px] font-mono text-gray-600 bg-gray-100/90 px-1.5 py-0.2 rounded font-medium">
                                            📱 {{ $conv['guestPhone'] }}
                                        </span>
                                        @if(!empty($conv['loyaltyTier']))
                                            <span class="text-[9px] font-extrabold px-1 rounded bg-amber-50 text-amber-700 border border-amber-200">
                                                ⭐ {{ $conv['loyaltyTier'] }} ({{ number_format($conv['loyaltyPoints']) }}p)
                                            </span>
                                        @endif
                                    </div>
                                @endif

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
                                    $loyaltyAcc = $activeConv->user?->loyaltyAccount;
                                    $domisili = $activeConv->user?->profile?->domisili ?? null;
                                    $addresses = $activeConv->user?->addresses ?? null;
                                    $city = $domisili ?: ($addresses?->firstWhere('is_primary', true)?->city ?? $addresses?->first()?->city ?? null);
                                @endphp
                                @if($custPhone)
                                    <span class="text-xs font-mono text-gray-700 bg-gray-100 px-2 py-0.5 rounded-lg border border-gray-200">
                                        📱 {{ $custPhone }}
                                    </span>
                                @endif
                                @if($loyaltyAcc)
                                    <span class="text-xs font-extrabold bg-amber-50 text-amber-800 border border-amber-200 px-2 py-0.5 rounded-lg flex items-center gap-1"
                                        title="Total Belanja: Rp {{ number_format($loyaltyAcc->total_spend, 0, ',', '.') }}">
                                        <span>👑 Tier: {{ $loyaltyAcc->tier }}</span>
                                        <span>•</span>
                                        <span class="text-amber-700">{{ number_format($loyaltyAcc->points_balance) }} Poin</span>
                                    </span>
                                @endif
                                @if($city)
                                    <span class="text-xs text-gray-500 bg-gray-50 px-2 py-0.5 rounded-lg border border-gray-200 flex items-center gap-1">
                                        <span>📍</span>
                                        <span>{{ $city }}</span>
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

    {{-- AUDIO SYNTHESIZER, WEB WORKER & REALTIME REVERB ECHO INTEGRATION --}}
    @script
    <script>
        // --- 1. STATE SUARA & DOKUMEN ---
        let isSoundMuted = localStorage.getItem('tokopon_cs_sound_muted') === 'true';
        let sharedAudioCtx = null;
        let titleFlashingTimer = null;
        const originalDocTitle = document.title;
        const audioElement = document.getElementById('chat-chime-player');

        // Inisialisasi tampilan tombol suara saat halaman dimuat
        function initSoundUI() {
            const icon = document.getElementById('sound-icon');
            const label = document.getElementById('sound-label');
            const btn = document.getElementById('toggle-sound-btn');
            if (icon && label && btn) {
                if (isSoundMuted) {
                    icon.textContent = '🔇';
                    label.textContent = 'Bunyi: Bisu';
                    btn.classList.add('bg-rose-50', 'text-rose-700', 'border-rose-200');
                    btn.classList.remove('bg-gray-50', 'text-gray-700');
                } else {
                    icon.textContent = '🔊';
                    label.textContent = 'Bunyi: Aktif';
                    btn.classList.remove('bg-rose-50', 'text-rose-700', 'border-rose-200');
                    btn.classList.add('bg-gray-50', 'text-gray-700');
                }
            }
        }
        setTimeout(initSoundUI, 80);

        // Toggle mute/unmute
        window.toggleChatSound = function() {
            isSoundMuted = !isSoundMuted;
            localStorage.setItem('tokopon_cs_sound_muted', isSoundMuted ? 'true' : 'false');
            initSoundUI();
            if (!isSoundMuted) {
                playNotificationSound(true);
            }
        };

        // --- 2. AUDIO PLAYBACK & UNLOCK UNTUK BACKGROUND TAB ---
        function getSharedAudioContext() {
            try {
                if (!sharedAudioCtx) {
                    const AudioCtx = window.AudioContext || window.webkitAudioContext;
                    if (AudioCtx) {
                        sharedAudioCtx = new AudioCtx();
                    }
                }
                if (sharedAudioCtx && sharedAudioCtx.state === 'suspended') {
                    sharedAudioCtx.resume();
                }
                return sharedAudioCtx;
            } catch (e) {
                return null;
            }
        }

        // Gesture unlock audio pada interaksi klik pertama agar browser mengizinkan audio berbunyi di background
        const unlockAllAudio = () => {
            if (audioElement) {
                audioElement.muted = true;
                const p = audioElement.play();
                if (p !== undefined) {
                    p.then(() => {
                        audioElement.pause();
                        audioElement.currentTime = 0;
                        audioElement.muted = false;
                    }).catch(() => {});
                }
            }
            getSharedAudioContext();
            document.removeEventListener('click', unlockAllAudio);
            document.removeEventListener('keydown', unlockAllAudio);
        };
        document.addEventListener('click', unlockAllAudio, { passive: true });
        document.addEventListener('keydown', unlockAllAudio, { passive: true });

        // Sintesis suara lonceng cadangan via Web Audio API jika file audio dicegah
        function playWebAudioSynthesizer() {
            try {
                const ctx = getSharedAudioContext();
                if (!ctx) return;

                const now = ctx.currentTime;
                // Nada 1: 587.33 Hz (D5)
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(587.33, now);
                gain1.gain.setValueAtTime(0.22, now);
                gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start(now);
                osc1.stop(now + 0.3);

                // Nada 2: 880 Hz (A5)
                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(880, now + 0.1);
                gain2.gain.setValueAtTime(0.28, now + 0.1);
                gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.6);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(now + 0.1);
                osc2.stop(now + 0.6);
            } catch (err) {
                console.warn('Fallback Web Audio synthesizer gagal:', err);
            }
        }

        // Putar audio lonceng utama (menggunakan file WAV yang dapat berbunyi di background tab)
        function playNotificationSound(forcePlay = false) {
            if (isSoundMuted && !forcePlay) return;

            if (audioElement) {
                audioElement.currentTime = 0;
                audioElement.volume = 0.9;
                const promise = audioElement.play();
                if (promise !== undefined) {
                    promise.catch(() => {
                        // Jika audio tag diblokir browser background, jalankan Web Audio synthesizer
                        playWebAudioSynthesizer();
                    });
                    return;
                }
            }
            playWebAudioSynthesizer();
        }

        window.testNotificationSoundOnly = function() {
            playNotificationSound(true);
        };

        // --- 3. STATUS & IZIN DESKTOP NOTIFICATION BROWSER ---
        function updateNotificationPermissionUI() {
            const btn = document.getElementById('notif-perm-btn');
            const icon = document.getElementById('notif-perm-icon');
            const label = document.getElementById('notif-perm-label');
            if (!btn || !label) return;

            if (!('Notification' in window)) {
                label.textContent = 'Notif: Tidak Didukung';
                btn.className = "px-3 py-1.5 rounded-xl bg-gray-100 border border-gray-200 text-gray-500 font-bold flex items-center gap-1.5 text-[11px] cursor-not-allowed";
                return;
            }

            if (Notification.permission === 'granted') {
                btn.className = "px-3.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 text-emerald-800 font-bold flex items-center gap-1.5 transition-all shadow-2xs text-[11px]";
                if (icon) icon.textContent = '🔔';
                label.textContent = 'Notif Desktop: Aktif';
                btn.title = "Notifikasi desktop aktif! Anda akan mendapatkan popup di Windows saat buka YouTube / aplikasi lain.";
            } else if (Notification.permission === 'denied') {
                btn.className = "px-3.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-300 text-rose-700 font-bold flex items-center gap-1.5 transition-all shadow-2xs text-[11px]";
                if (icon) icon.textContent = '🚫';
                label.textContent = 'Notif Diblokir Browser';
                btn.title = "Izin notifikasi diblokir di browser. Klik ikon gembok di sebelah URL bar untuk mengubah izin ke 'Izinkan / Allow'.";
            } else {
                btn.className = "px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-extrabold flex items-center gap-1.5 transition-all shadow-sm text-[11px] animate-pulse";
                if (icon) icon.textContent = '⚠️';
                label.textContent = 'Klik Izinkan Notif Desktop';
                btn.title = "PENTING: Klik tombol ini dan pilih 'Izinkan / Allow' agar pesan tetap masuk saat Anda membuka YouTube / app lain!";
            }
        }
        setTimeout(updateNotificationPermissionUI, 120);

        window.requestDesktopNotificationPermission = function() {
            playNotificationSound(true);

            if (!('Notification' in window)) {
                alert('Browser Anda tidak mendukung Web Notifications API.');
                return;
            }

            if (Notification.permission === 'default') {
                Notification.requestPermission().then((permission) => {
                    updateNotificationPermissionUI();
                    if (permission === 'granted') {
                        showDesktopNotification({
                            sender: 'Tokopon Live Chat CS',
                            text: 'Notifikasi desktop aktif! Anda akan diberi tahu setiap ada pesan masuk dari pembeli.',
                            conversationId: null
                        });
                    }
                });
            } else if (Notification.permission === 'granted') {
                showDesktopNotification({
                    sender: 'Tokopon Live Chat (Tes)',
                    text: 'Bunyi dan notifikasi desktop aktif! Popup ini akan muncul walau Anda membuka YouTube / aplikasi lain.',
                    conversationId: null
                });
            } else {
                alert('Izin notifikasi desktop diblokir di browser. Silakan klik ikon gembok/setelan situs di sebelah kiri URL bar browser lalu ubah izin Notifikasi ke "Izinkan / Allow".');
            }
        };

        function showDesktopNotification(data) {
            if (!('Notification' in window) || Notification.permission !== 'granted') return;

            try {
                const notif = new Notification(`💬 ${data.sender || 'Pesan Baru'}`, {
                    body: data.text || 'Membalas percakapan Anda...',
                    icon: '/favicon.ico',
                    tag: data.conversationId ? 'conv-' + data.conversationId : 'tokopon-cs',
                    renotify: true,
                    requireInteraction: true // Menjaga notifikasi tetap tampil di pojok layar Windows
                });

                notif.onclick = () => {
                    window.focus();
                    if (data.conversationId) {
                        $wire.selectConversation(data.conversationId);
                    }
                    notif.close();
                };
            } catch (e) {
                console.warn('Gagal menampilkan desktop notification:', e);
            }
        }

        // --- 4. FLASHING TITLE PADA TAB BROWSER ---
        function startFlashingTitle(sender) {
            if (!document.hidden) return; // Hanya kedipkan jika sedang di background tab

            let isAlert = true;
            if (titleFlashingTimer) clearInterval(titleFlashingTimer);

            titleFlashingTimer = setInterval(() => {
                document.title = isAlert ? `🔔 (1) Pesan Baru: ${sender}!` : originalDocTitle;
                isAlert = !isAlert;
            }, 1000);
        }

        function stopFlashingTitle() {
            if (titleFlashingTimer) {
                clearInterval(titleFlashingTimer);
                titleFlashingTimer = null;
            }
            document.title = originalDocTitle;
        }

        window.addEventListener('focus', stopFlashingTitle);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) stopFlashingTitle();
        });

        // --- 5. HELPER SCROLL KE BAWAH ---
        function scrollMessagesBottom() {
            const container = document.getElementById('chat-messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }

        // --- 6. EVENT LISTENER DARI LIVEWIRE (POLLING & BROADCAST) ---
        $wire.on('incoming-customer-message', (data) => {
            const payload = Array.isArray(data) ? data[0] : data;
            if (!payload) return;

            // 1. Bunyikan nada lonceng
            playNotificationSound();

            // 2. Tampilkan notifikasi desktop browser (muncul di layar Windows saat buka YouTube/aplikasi lain)
            showDesktopNotification(payload);

            // 3. Kedipkan judul tab jika CS sedang di tab lain
            startFlashingTitle(payload.sender);

            // 4. Auto scroll jika berada di percakapan aktif
            if (payload.isActiveConv) {
                setTimeout(scrollMessagesBottom, 60);
            }
        });

        // --- 7. INLINE WEB WORKER (PENGAMAN AGAR TIDAK DI-THROTTLE DI BACKGROUND TAB) ---
        // Browser seperti Chrome/Edge membatasi timer JavaScript saat membuka YouTube.
        // Web Worker berjalan di thread terpisah sehingga timer 2.5s tetap berjalan 100% tanpa henti!
        let bgWorker = null;
        try {
            const workerCode = `
                let timer = null;
                self.onmessage = function(e) {
                    if (e.data === 'start') {
                        if (timer) clearInterval(timer);
                        timer = setInterval(() => {
                            self.postMessage('poll');
                        }, 2500);
                    } else if (e.data === 'stop') {
                        if (timer) clearInterval(timer);
                        timer = null;
                    }
                };
            `;
            const blob = new Blob([workerCode], { type: 'application/javascript' });
            bgWorker = new Worker(URL.createObjectURL(blob));
            bgWorker.onmessage = function(e) {
                if (e.data === 'poll') {
                    $wire.pollNewMessages();
                }
            };
            bgWorker.postMessage('start');
        } catch (err) {
            console.warn('Web Worker timer tidak aktif:', err);
        }

        // --- 8. WEBSOCKET LARAVEL REVERB ECHO (INSTANT ZERO-LATENCY JIKA AKTIF) ---
        if (window.Echo) {
            window.Echo.private('cs-chats')
                .listen('.message.sent', (e) => {
                    if (e.senderType !== 'cs') {
                        $wire.pollNewMessages();
                    }
                })
                .listen('.message.read', (e) => {
                    $wire.refreshData();
                });

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
                            $wire.pollNewMessages().then(() => {
                                setTimeout(scrollMessagesBottom, 60);
                            });
                        })
                        .listen('.message.read', (e) => {
                            $wire.refreshData();
                        });
                }
            }

            const initConvId = @js($activeConversationId);
            if (initConvId) {
                subscribeToActiveConv(initConvId);
            }

            $wire.$watch('activeConversationId', (newId) => {
                subscribeToActiveConv(newId);
                setTimeout(scrollMessagesBottom, 120);
            });
        }

        // Auto-scroll saat chat pertama kali dimuat
        setTimeout(scrollMessagesBottom, 150);
        $wire.on('cs-messages-loaded', () => {
            setTimeout(scrollMessagesBottom, 50);
        });
    </script>
    @endscript
</div>
