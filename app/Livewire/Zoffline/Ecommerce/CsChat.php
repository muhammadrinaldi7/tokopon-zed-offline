<?php

namespace App\Livewire\Zoffline\Ecommerce;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProductAccurate;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.z')]
class CsChat extends Component
{
    use WithFileUploads;

    public string $message = '';
    public array $messages = [];
    public ?int $activeConversationId = null;
    public array $conversations = [];
    public string $filterTab = 'all'; // all, open, deal, follow_up, lost
    public string $search = '';

    // Modal Closing State
    public bool $showClosingModal = false;
    public ?int $closingConversationId = null;
    public string $closingStatus = 'deal'; // deal, follow_up, lost
    public ?string $closingAmount = null;
    public string $closingNotes = '';

    public function mount()
    {
        $this->loadConversations();
    }

    public function loadConversations()
    {
        $query = Conversation::with(['user', 'latestMessage.user', 'productAccurate', 'closedBy', 'businessUnit'])
            ->orderByDesc('updated_at');

        if ($this->filterTab === 'open') {
            $query->where('status', 'open')->where('closing_status', 'none');
        } elseif ($this->filterTab === 'deal') {
            $query->where('closing_status', 'deal');
        } elseif ($this->filterTab === 'follow_up') {
            $query->where('closing_status', 'follow_up');
        } elseif ($this->filterTab === 'lost') {
            $query->where('closing_status', 'lost');
        }

        if (!empty($this->search)) {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('guest_name', 'like', "%{$s}%")
                  ->orWhere('guest_phone', 'like', "%{$s}%")
                  ->orWhereHas('user', function ($u) use ($s) {
                      $u->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
                  });
            });
        }

        $this->conversations = $query->take(50)->get()->map(function ($conv) {
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
                'closingStatus' => $conv->closing_status ?: 'none',
                'closingAmount' => (float) ($conv->closing_amount ?? 0),
                'closedByName' => $conv->closedBy?->name,
                'businessUnitName' => $conv->businessUnit?->name,
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
        })->toArray();
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

        // Tandai pesan sebagai terbaca
        Message::where('conversation_id', $this->activeConversationId)
            ->whereNull('read_at')
            ->whereIn('sender_type', ['customer', 'guest'])
            ->update(['read_at' => now()]);

        $dbMessages = Message::with(['user', 'media', 'productAccurate.product', 'productAccurate.productVariants'])
            ->where('conversation_id', $this->activeConversationId)
            ->latest()
            ->take(80)
            ->get()
            ->reverse()
            ->values();

        $guestName = $activeConv->guest_name ?: 'Tamu';

        $this->messages = $dbMessages->map(function ($msg) use ($guestName) {
            $isCs = $msg->sender_type === 'cs' || ($msg->user && $msg->user->hasRole('cs'));
            $senderName = $msg->user?->name ?? ($isCs ? 'CS Support' : $guestName);

            $productInfo = null;
            if ($msg->productAccurate) {
                $prod = $msg->productAccurate;
                $imageUrl = 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=300&q=80';
                if ($prod->product && $prod->product->hasMedia('cover')) {
                    $imageUrl = $prod->product->getFirstMediaUrl('cover');
                } elseif ($prod->productVariants && $prod->productVariants->isNotEmpty()) {
                    $first = $prod->productVariants->first();
                    if ($first && $first->hasMedia('variant_image')) {
                        $imageUrl = $first->getFirstMediaUrl('variant_image');
                    }
                }

                $productInfo = [
                    'id' => $prod->id,
                    'name' => $prod->name,
                    'brand' => $prod->brandName ?? '',
                    'price' => 'Rp ' . number_format($prod->base_price, 0, ',', '.'),
                    'thumbnail' => $imageUrl,
                ];
            }

            return [
                'id' => $msg->id,
                'user' => $senderName,
                'text' => $msg->message,
                'time' => $msg->created_at?->format('H:i') ?? '',
                'userId' => $msg->user_id,
                'isCs' => $isCs,
                'product' => $productInfo,
            ];
        })->toArray();

        $this->dispatch('cs-messages-loaded');
    }

    public function sendMessage()
    {
        if (trim($this->message) === '' || !$this->activeConversationId) {
            return;
        }

        $user = Auth::user();

        $createdMsg = Message::create([
            'conversation_id' => $this->activeConversationId,
            'user_id' => $user->id,
            'sender_type' => 'cs',
            'message' => trim($this->message),
        ]);

        Conversation::where('id', $this->activeConversationId)->update(['updated_at' => now()]);

        $this->messages[] = [
            'id' => $createdMsg->id,
            'user' => $user->name,
            'text' => $createdMsg->message,
            'time' => now()->format('H:i'),
            'userId' => $user->id,
            'isCs' => true,
            'product' => null,
        ];

        $sentText = $this->message;
        $this->message = '';

        try {
            broadcast(new MessageSent(
                user: $user->name,
                message: $sentText,
                time: now()->format('H:i'),
                userId: $user->id,
                conversationId: $this->activeConversationId
            ))->toOthers();
        } catch (\Throwable $e) {
            // broadcast fallback silently
        }

        $this->dispatch('cs-messages-loaded');
    }

    public function openClosingModal(int $conversationId)
    {
        $conv = Conversation::find($conversationId);
        if (!$conv) return;

        $this->closingConversationId = $conv->id;
        $this->closingStatus = in_array($conv->closing_status, ['deal', 'follow_up', 'lost']) ? $conv->closing_status : 'deal';
        $this->closingAmount = $conv->closing_amount ? (string) ((int) $conv->closing_amount) : '';
        $this->closingNotes = $conv->closing_notes ?? '';
        $this->showClosingModal = true;
    }

    public function saveClosingStatus()
    {
        if (!$this->closingConversationId) return;

        $conv = Conversation::find($this->closingConversationId);
        if (!$conv) return;

        $cleanAmount = $this->closingAmount ? (float) str_replace(['.', ','], '', $this->closingAmount) : null;

        $conv->update([
            'closing_status' => $this->closingStatus,
            'closing_amount' => $cleanAmount,
            'closing_notes' => $this->closingNotes ?: null,
            'closed_by_user_id' => Auth::id(),
            'closed_at' => now(),
            'status' => $this->closingStatus === 'deal' ? 'closed' : $conv->status,
        ]);

        $this->showClosingModal = false;
        $this->loadConversations();
        $this->dispatch('toast', title: 'Berhasil', message: 'Status Closing berhasil disimpan.', type: 'success');
    }

    public function setFilterTab(string $tab)
    {
        $this->filterTab = $tab;
        $this->loadConversations();
    }

    public function refreshData()
    {
        $this->loadConversations();
        if ($this->activeConversationId) {
            $this->loadMessages();
        }
    }

    public function render()
    {
        $activeConv = $this->activeConversationId ? Conversation::with(['user', 'productAccurate', 'closedBy'])->find($this->activeConversationId) : null;

        return view('livewire.zoffline.ecommerce.cs-chat', [
            'activeConv' => $activeConv,
        ]);
    }
}
