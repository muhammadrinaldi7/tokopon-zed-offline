<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Layout;
use App\Models\Conversation;
use App\Models\Message;
use App\Events\MessageSent;

new #[Layout('layouts.admin', ['title' => 'CS Dashboard - TokoPun'])] class extends Component {
    public string $message = '';
    public array $messages = [];
    public ?int $activeConversationId = null;
    public array $conversations = [];

    public function mount()
    {
        $this->loadConversations();
    }

    public function loadConversations()
    {
        $this->conversations = Conversation::with(['user', 'latestMessage.user', 'productAccurate'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(
                function ($conv) {
                    $userName = $conv->user?->name ?? ($conv->guest_name ?: 'Tamu (Guest)');
                    $userInitial = strtoupper(substr($userName, 0, 1));

                    $lastSender = '';
                    if ($conv->latestMessage) {
                        if ($conv->latestMessage->user) {
                            $lastSender = $conv->latestMessage->user->name;
                        } elseif ($conv->latestMessage->sender_type === 'guest') {
                            $lastSender = $conv->guest_name ?: 'Tamu';
                        }
                    }

                    return [
                        'id' => $conv->id,
                        'userName' => $userName,
                        'userInitial' => $userInitial,
                        'status' => $conv->status,
                        'isGuest' => is_null($conv->user_id),
                        'guestPhone' => $conv->guest_phone,
                        'productName' => $conv->productAccurate?->name,
                        'productPrice' => $conv->productAccurate ? 'Rp ' . number_format($conv->productAccurate->base_price, 0, ',', '.') : null,
                        'lastMessage' => $conv->latestMessage?->message ?? 'Belum ada pesan',
                        'lastTime' => $conv->latestMessage?->created_at?->format('H:i') ?? '',
                        'lastSender' => $lastSender,
                        'updatedAt' => $conv->updated_at?->diffForHumans() ?? '',
                        'unreadCount' => $conv->unreadCountForCs(),
                    ];
                }
            )
            ->toArray();
    }

    public function selectConversation(int $id)
    {
        $this->activeConversationId = $id;
        $this->loadMessages();
    }

    public function loadMessages()
    {
        if (!$this->activeConversationId) {
            return;
        }

        $activeConv = Conversation::with(['user', 'productAccurate'])->find($this->activeConversationId);
        if (!$activeConv) {
            return;
        }

        // Tandai pesan dari customer / guest sebagai sudah dibaca oleh CS
        Message::where('conversation_id', $this->activeConversationId)
            ->whereNull('read_at')
            ->whereIn('sender_type', ['customer', 'guest'])
            ->update(['read_at' => now()]);

        $dbMessages = Message::with(['user', 'media'])
            ->where('conversation_id', $this->activeConversationId)
            ->latest()
            ->take(100)
            ->get()
            ->reverse()
            ->values();

        $guestName = $activeConv->guest_name ?: 'Tamu';

        $this->messages = $dbMessages
            ->map(
                function ($msg) use ($guestName) {
                    $isCs = $msg->sender_type === 'cs' || ($msg->user && $msg->user->hasRole('cs'));
                    $senderName = $msg->user?->name ?? ($isCs ? 'CS Admin' : $guestName);

                    $attachments = [];
                    foreach ($msg->getMedia('attachments') as $media) {
                        $attachments[] = [
                            'url' => $media->getFullUrl(),
                            'file_name' => $media->file_name,
                            'is_image' => str_starts_with($media->mime_type ?? '', 'image/'),
                        ];
                    }

                    return [
                        'id' => $msg->id,
                        'user' => $senderName,
                        'text' => $msg->message,
                        'time' => $msg->created_at?->format('H:i') ?? '',
                        'userId' => $msg->user_id,
                        'isCs' => $isCs,
                        'attachments' => $attachments,
                    ];
                }
            )
            ->toArray();

        $this->dispatch('cs-messages-loaded');
    }

    public function sendMessage()
    {
        if (trim($this->message) === '' || !$this->activeConversationId) {
            return;
        }

        $user = auth()->user();

        $createdMsg = Message::create([
            'conversation_id' => $this->activeConversationId,
            'user_id' => $user->id,
            'sender_type' => 'cs',
            'message' => $this->message,
        ]);

        // Update conversation timestamp
        Conversation::where('id', $this->activeConversationId)->update(['updated_at' => now()]);

        $this->messages[] = [
            'id' => $createdMsg->id,
            'user' => $user->name,
            'text' => $this->message,
            'time' => now()->format('H:i'),
            'userId' => $user->id,
            'isCs' => true,
            'attachments' => [],
        ];

        try {
            broadcast(new MessageSent(user: $user->name, message: $this->message, time: now()->format('H:i'), userId: $user->id, conversationId: $this->activeConversationId))->toOthers();
        } catch (\Throwable $e) {
            // Ignore broadcast failure if pusher/reverb is not configured
        }

        $this->message = '';
        $this->dispatch('cs-message-sent');
        $this->loadConversations();
    }

    public function closeConversation(int $id)
    {
        Conversation::where('id', $id)->update(['status' => 'closed']);
        if ($this->activeConversationId === $id) {
            $this->activeConversationId = null;
            $this->messages = [];
        }
        $this->loadConversations();
    }

    public function reopenConversation(int $id)
    {
        Conversation::where('id', $id)->update(['status' => 'open']);
        $this->loadConversations();
    }

    // Listen untuk pesan baru di semua conversation (polling sederhana)
    public function refreshData()
    {
        $this->loadConversations();
        if ($this->activeConversationId) {
            $this->loadMessages();
        }
    }
};
?>

<div class="min-h-screen bg-white" style="font-family: 'Inter', sans-serif;" wire:poll.5s="refreshData">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6">
        {{-- Page Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <span
                    class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </span>
                CS Chat
            </h1>
            <p class="text-sm text-gray-500 mt-1">Kelola percakapan customer service & pesan pengunjung mobile</p>
        </div>

        <div class="flex gap-4" style="height: calc(100vh - 200px);">

            {{-- Sidebar: Conversation List --}}
            <div
                class="w-80 flex-shrink-0 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        Percakapan ({{ count($conversations) }})
                    </h2>
                </div>

                <div class="flex-1 overflow-y-auto divide-y divide-gray-50">
                    @forelse($conversations as $conv)
                        <button wire:click="selectConversation({{ $conv['id'] }})"
                            class="w-full text-left p-4 hover:bg-gray-50/80 transition-colors flex items-start gap-3
                                {{ $activeConversationId === $conv['id'] ? 'bg-emerald-50/60 border-l-4 border-l-emerald-500' : '' }}">
                            {{-- Avatar --}}
                            <div
                                class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center text-white font-bold text-sm
                                {{ $conv['status'] === 'open' ? 'bg-gradient-to-br from-emerald-400 to-teal-500' : 'bg-gray-400' }}">
                                {{ $conv['userInitial'] }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5 truncate">
                                        <span class="font-semibold text-sm text-gray-800 truncate">{{ $conv['userName'] }}</span>
                                        @if ($conv['isGuest'])
                                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shrink-0">Tamu</span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] text-gray-400 shrink-0">{{ $conv['lastTime'] }}</span>
                                </div>
                                <p class="text-xs text-gray-500 truncate mt-0.5">{{ $conv['lastMessage'] }}</p>
                                <div class="flex items-center justify-between mt-1.5">
                                    <span
                                        class="inline-block text-[10px] px-2 py-0.5 rounded-full font-medium
                                        {{ $conv['status'] === 'open' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $conv['status'] === 'open' ? '● Open' : '● Closed' }}
                                    </span>
                                    @if (($conv['unreadCount'] ?? 0) > 0)
                                        <span class="px-1.5 py-0.5 text-[10px] font-extrabold rounded-full bg-rose-500 text-white animate-pulse">
                                            {{ $conv['unreadCount'] }} baru
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </button>
                    @empty
                        <div class="p-8 text-center text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-2 text-gray-300"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="text-sm">Belum ada percakapan</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Main: Chat Panel --}}
            <div class="flex-1 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">
                @if ($activeConversationId)
                    @php
                        $activeConv = collect($conversations)->firstWhere('id', $activeConversationId);
                    @endphp

                    {{-- Chat Header --}}
                    <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-white">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center text-white font-bold shadow-sm">
                                {{ $activeConv['userInitial'] ?? '?' }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-semibold text-gray-800">{{ $activeConv['userName'] ?? 'User' }}</p>
                                    @if (!empty($activeConv['isGuest']))
                                        <span class="px-1.5 py-0.5 text-[10px] font-bold bg-amber-50 text-amber-700 rounded border border-amber-200">Pengunjung Tamu</span>
                                    @endif
                                    @if (!empty($activeConv['guestPhone']))
                                        <span class="text-xs text-gray-500 font-mono">📱 {{ $activeConv['guestPhone'] }}</span>
                                    @endif
                                </div>
                                @if (!empty($activeConv['productName']))
                                    <p class="text-xs text-emerald-600 font-medium flex items-center gap-1 mt-0.5">
                                        <span>📦 Menanyakan produk: <strong>{{ $activeConv['productName'] }}</strong></span>
                                        @if (!empty($activeConv['productPrice']))
                                            <span class="text-gray-500">({{ $activeConv['productPrice'] }})</span>
                                        @endif
                                    </p>
                                @else
                                    <p class="text-xs text-gray-400">{{ $activeConv['updatedAt'] ?? '' }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($activeConv && $activeConv['status'] === 'open')
                                <button wire:click="closeConversation({{ $activeConversationId }})"
                                    class="text-xs px-3 py-1.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors font-medium">
                                    Tutup Chat
                                </button>
                            @else
                                <button wire:click="reopenConversation({{ $activeConversationId }})"
                                    class="text-xs px-3 py-1.5 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors font-medium">
                                    Buka Kembali
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Messages --}}
                    <div x-ref="csChat" x-init="$nextTick(() => $refs.csChat.scrollTop = $refs.csChat.scrollHeight)"
                        @cs-messages-loaded.window="$nextTick(() => $refs.csChat.scrollTop = $refs.csChat.scrollHeight)"
                        @cs-message-sent.window="$nextTick(() => $refs.csChat.scrollTop = $refs.csChat.scrollHeight)"
                        class="flex-1 p-6 overflow-y-auto bg-gray-50 flex flex-col gap-3">
                        @forelse($messages as $msg)
                            @php $isCs = $msg['isCs']; @endphp
                            <div class="flex flex-col {{ $isCs ? 'items-end' : 'items-start' }}">
                                <div
                                    class="px-4 py-2.5 rounded-2xl max-w-[70%] text-sm
                                    {{ $isCs
                                        ? 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white rounded-br-md shadow-sm'
                                        : 'bg-white text-gray-800 border border-gray-200 rounded-bl-md shadow-sm' }}">
                                    @unless ($isCs)
                                        <span
                                            class="block text-xs font-bold text-emerald-600 mb-1">{{ $msg['user'] }}</span>
                                    @endunless

                                    @if (!empty($msg['attachments']))
                                        <div class="mb-2 space-y-1.5">
                                            @foreach ($msg['attachments'] as $att)
                                                @if ($att['is_image'])
                                                    <a href="{{ $att['url'] }}" target="_blank" class="block">
                                                        <img src="{{ $att['url'] }}" alt="Lampiran" class="max-h-48 rounded-lg object-cover hover:opacity-95 transition">
                                                    </a>
                                                @else
                                                    <a href="{{ $att['url'] }}" target="_blank" class="text-xs underline flex items-center gap-1 {{ $isCs ? 'text-white' : 'text-blue-600' }}">
                                                        📎 {{ $att['file_name'] }}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif

                                    @if (!empty($msg['text']))
                                        <span class="whitespace-pre-line">{{ $msg['text'] }}</span>
                                    @endif
                                </div>
                                <span class="text-[10px] text-gray-400 mt-1 px-1">
                                    {{ $isCs ? 'Anda' : $msg['user'] }} • {{ $msg['time'] }}
                                </span>
                            </div>
                        @empty
                            <div class="flex items-center justify-center h-full text-gray-400">
                                <p class="text-sm">Belum ada pesan dalam percakapan ini</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Input (only if conversation is open) --}}
                    @if ($activeConv && $activeConv['status'] === 'open')
                        <div class="p-4 bg-white border-t border-gray-100">
                            <form wire:submit="sendMessage" class="flex gap-3 items-center">
                                <input type="text" wire:model="message" placeholder="Ketik balasan..."
                                    class="flex-1 border border-gray-200 rounded-full px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent bg-gray-50 transition-all"
                                    autocomplete="off">
                                <button type="submit"
                                    class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-teal-600 text-white rounded-full flex items-center justify-center hover:from-emerald-600 hover:to-teal-700 transition-all duration-200 hover:scale-105 shadow-md">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24"
                                        fill="currentColor">
                                        <path
                                            d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="p-4 bg-gray-50 border-t border-gray-100 text-center">
                            <p class="text-sm text-gray-400">Percakapan ini sudah ditutup</p>
                        </div>
                    @endif
                @else
                    {{-- Empty State --}}
                    <div class="flex-1 flex flex-col items-center justify-center text-gray-400">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-300" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <p class="text-lg font-medium text-gray-500">Pilih Percakapan</p>
                        <p class="text-sm mt-1">Klik percakapan di sidebar untuk mulai membalas</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
