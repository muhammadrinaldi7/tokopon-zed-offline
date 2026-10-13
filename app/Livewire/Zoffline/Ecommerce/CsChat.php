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

    public array $quickReplies = [
        [
            'label' => '👋 Sapaan',
            'text' => 'Halo kak! Ada yang bisa kami bantu seputar produk atau promo Tokopon hari ini?',
        ],
        [
            'label' => '📦 Stok Ready',
            'text' => 'Unit yang kakak tanyakan saat ini ready siap kirim ya kak. Mau kami bantu proses pemesanannya?',
        ],
        [
            'label' => '🏦 Info Rekening',
            'text' => 'Pembayaran resmi Tokopon hanya melalui Rekening BCA: 123-456-7890 a/n PT Tokopon Indonesia. Mohon konfirmasi bukti transfer jika sudah ya kak.',
        ],
        [
            'label' => '🛡️ Garansi Unit',
            'text' => 'Semua unit gadget bergaransi resmi/toko, garansi ganti unit 7 hari jika ada kendala fungsional & garansi sinyal/IMEI seumur hidup.',
        ],
        [
            'label' => '🚚 Pengiriman',
            'text' => 'Kami melayani pengiriman Instant (Gosend/Grab) untuk area lokal toko kami, serta JNE/J&T berasuransi untuk kiriman luar kota.',
        ],
        [
            'label' => '📍 Jam Buka Toko',
            'text' => 'Toko kami buka setiap hari pukul 09.00 - 21.00 WITA. Silakan berkunjung langsung ke store kami ya kak.',
        ],
    ];

    // Lampiran File / Gambar oleh CS
    public $attachment = null;

    // Rekomendasi Produk untuk CS (Product Picker)
    public bool $showProductPickerModal = false;
    public string $productSearch = '';
    public array $productSearchResults = [];
    public ?int $selectedProductAccurateId = null;
    public ?array $selectedProductPreview = null;

    public function mount()
    {
        $this->loadConversations();
    }

    public function loadConversations()
    {
        $query = Conversation::with(['user', 'latestMessage.user', 'productAccurate', 'closedBy', 'businessUnit', 'assignedTo'])
            ->orderByDesc('updated_at');

        if ($this->filterTab === 'my_chats') {
            $query->where('assigned_to_user_id', Auth::id());
        } elseif ($this->filterTab === 'unassigned') {
            $query->whereNull('assigned_to_user_id')->where('status', 'open');
        } elseif ($this->filterTab === 'open') {
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

            $phone = $conv->guest_phone ?: ($conv->user?->customer_phone ?? null);

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
                'guestPhone' => $phone,
                'assignedToName' => $conv->assignedTo?->name,
                'assignedToId' => $conv->assigned_to_user_id,
                'isAssignedToMe' => $conv->assigned_to_user_id === Auth::id(),
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
        $this->removeAttachment();
        $this->removeSelectedProduct();
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

            $attachmentUrls = [];
            foreach ($msg->getMedia('attachments') as $media) {
                $attachmentUrls[] = [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'url' => $media->getFullUrl(),
                    'mime_type' => $media->mime_type,
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
                'product' => $productInfo,
                'attachments' => $attachmentUrls,
            ];
        })->toArray();

        $this->dispatch('cs-messages-loaded');
    }

    public function applyQuickReply(string $text)
    {
        if (empty(trim($this->message))) {
            $this->message = $text;
        } else {
            $this->message = trim($this->message) . ' ' . $text;
        }
    }

    public function openProductPicker()
    {
        $this->productSearch = '';
        $this->productSearchResults = [];
        $this->showProductPickerModal = true;
        $this->searchProducts();
    }

    public function updatedProductSearch()
    {
        $this->searchProducts();
    }

    public function searchProducts()
    {
        $query = ProductAccurate::with(['product', 'productVariants']);
        if (!empty(trim($this->productSearch))) {
            $s = trim($this->productSearch);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('item_no', 'like', "%{$s}%");
            });
        }
        $this->productSearchResults = $query->take(8)->get()->map(function ($p) {
            $img = 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=300&q=80';
            if ($p->product && $p->product->hasMedia('cover')) {
                $img = $p->product->getFirstMediaUrl('cover');
            } elseif ($p->productVariants && $p->productVariants->isNotEmpty()) {
                $first = $p->productVariants->first();
                if ($first && $first->hasMedia('variant_image')) {
                    $img = $first->getFirstMediaUrl('variant_image');
                }
            }
            return [
                'id' => $p->id,
                'name' => $p->name,
                'item_no' => $p->item_no,
                'price' => (float) $p->base_price,
                'formatted_price' => 'Rp ' . number_format($p->base_price, 0, ',', '.'),
                'thumbnail' => $img,
            ];
        })->toArray();
    }

    public function selectProduct(int $productId)
    {
        $p = ProductAccurate::with(['product', 'productVariants'])->find($productId);
        if ($p) {
            $img = 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=300&q=80';
            if ($p->product && $p->product->hasMedia('cover')) {
                $img = $p->product->getFirstMediaUrl('cover');
            } elseif ($p->productVariants && $p->productVariants->isNotEmpty()) {
                $first = $p->productVariants->first();
                if ($first && $first->hasMedia('variant_image')) {
                    $img = $first->getFirstMediaUrl('variant_image');
                }
            }
            $this->selectedProductAccurateId = $p->id;
            $this->selectedProductPreview = [
                'id' => $p->id,
                'name' => $p->name,
                'price' => 'Rp ' . number_format($p->base_price, 0, ',', '.'),
                'thumbnail' => $img,
            ];
        }
        $this->showProductPickerModal = false;
    }

    public function removeSelectedProduct()
    {
        $this->selectedProductAccurateId = null;
        $this->selectedProductPreview = null;
    }

    public function removeAttachment()
    {
        $this->attachment = null;
    }

    public function getWhatsAppUrl(?Conversation $conv): ?string
    {
        if (!$conv) return null;

        $phone = $conv->guest_phone ?: ($conv->user?->customer_phone ?? null);
        if (empty($phone)) return null;

        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        } elseif (str_starts_with($cleanPhone, '8')) {
            $cleanPhone = '62' . $cleanPhone;
        }

        $custName = $conv->user?->name ?? ($conv->guest_name ?: 'Kak');
        $csName = Auth::user()?->name ?: 'Admin CS Tokopon';
        $prodName = $conv->productAccurate?->name ?? '';

        $text = "Halo {$custName}, saya {$csName} dari Customer Service Tokopon.";
        if (!empty($prodName)) {
            $text .= " Terkait pertanyaan Anda mengenai produk *{$prodName}* di Tokopon:";
        } else {
            $text .= " Terkait konsultasi Anda di Tokopon:";
        }

        return 'https://wa.me/' . $cleanPhone . '?text=' . urlencode($text);
    }

    public function sendMessage()
    {
        $hasText = trim($this->message) !== '';
        $hasAttachment = !is_null($this->attachment);
        $hasProduct = !is_null($this->selectedProductAccurateId);

        if ((!$hasText && !$hasAttachment && !$hasProduct) || !$this->activeConversationId) {
            return;
        }

        if ($this->attachment) {
            $this->validate([
                'attachment' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:5120',
            ]);
        }

        $user = Auth::user();

        $createdMsg = Message::create([
            'conversation_id' => $this->activeConversationId,
            'user_id' => $user->id,
            'sender_type' => 'cs',
            'product_accurate_id' => $this->selectedProductAccurateId,
            'message' => trim($this->message),
        ]);

        if ($this->attachment) {
            $createdMsg->addMedia($this->attachment->getRealPath())
                ->usingFileName($this->attachment->getClientOriginalName())
                ->toMediaCollection('attachments');
            $createdMsg->refresh();
        }

        Conversation::where('id', $this->activeConversationId)->update(['updated_at' => now()]);

        $sentText = $this->message;
        $attachedProduct = $this->selectedProductPreview;

        $this->message = '';
        $this->attachment = null;
        $this->selectedProductAccurateId = null;
        $this->selectedProductPreview = null;

        // Reload messages to include media and products
        $this->loadMessages();

        // Dapatkan percakapan dan kirim push notification ke mobile app jika token tersedia
        $conversation = Conversation::with('user')->find($this->activeConversationId);
        if ($conversation) {
            $recipientPushToken = $conversation->user?->push_token ?: $conversation->push_token;

            if (!empty($recipientPushToken)) {
                $pushBody = $sentText ?: ($attachedProduct ? 'Rekomendasi produk: ' . $attachedProduct['name'] : 'Mengirim lampiran...');
                try {
                    \App\Jobs\SendExpoPushNotificationJob::dispatch(
                        $recipientPushToken,
                        $user->name ?: 'Admin CS Tokopon',
                        $pushBody,
                        [
                            'url' => '/(tabs)/chat',
                            'conversation_id' => (string) $this->activeConversationId,
                        ],
                        'chat-messages'
                    )->afterResponse();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Gagal dispatch push notification di CsChat: " . $e->getMessage());
                }
            }
        }

        try {
            broadcast(new MessageSent(
                user: $user->name,
                message: $sentText ?: 'Mengirim lampiran...',
                time: now()->format('H:i'),
                userId: $user->id,
                conversationId: $this->activeConversationId,
                senderType: 'cs'
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

    public function claimConversation(?int $id = null)
    {
        $convId = $id ?: $this->activeConversationId;
        if (!$convId) return;

        $conv = Conversation::find($convId);
        if (!$conv) return;

        $user = Auth::user();
        $conv->update([
            'assigned_to_user_id' => $user->id,
            'assigned_at' => now(),
        ]);

        $this->loadConversations();
        $this->dispatch('toast', title: 'Berhasil', message: "Obrolan berhasil di-handle oleh {$user->name}.", type: 'success');
    }

    public function releaseConversation(?int $id = null)
    {
        $convId = $id ?: $this->activeConversationId;
        if (!$convId) return;

        $conv = Conversation::find($convId);
        if (!$conv) return;

        $conv->update([
            'assigned_to_user_id' => null,
            'assigned_at' => null,
        ]);

        $this->loadConversations();
        $this->dispatch('toast', title: 'Info', message: 'Penugasan obrolan dilepas ke antrean umum.', type: 'info');
    }

    public function render()
    {
        $activeConv = $this->activeConversationId ? Conversation::with(['user', 'productAccurate', 'closedBy', 'assignedTo'])->find($this->activeConversationId) : null;

        return view('livewire.zoffline.ecommerce.cs-chat', [
            'activeConv' => $activeConv,
        ]);
    }
}
