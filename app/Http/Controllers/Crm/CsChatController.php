<?php

namespace App\Http\Controllers\Crm;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Jobs\SendExpoPushNotificationJob;
use App\Models\BusinessUnit;
use App\Models\Conversation;
use App\Models\EcommerceCoupon;
use App\Models\Message;
use App\Models\Order;
use App\Models\ProductAccurate;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CsChatController extends Controller
{
    /**
     * Tampilan utama Live Chat CS & CRM (Inertia React)
     */
    public function index(Request $request): Response
    {
        $filterTab = $request->input('tab', 'all');
        $filterBu = $request->input('bu', null);
        $search = $request->input('search', '');
        $activeId = $request->input('active', null);

        $conversations = $this->queryConversations($filterTab, $filterBu, $search);

        // Jika tidak ada chat aktif yang dipilih, pilih chat teratas
        if (!$activeId && !empty($conversations)) {
            $activeId = $conversations[0]['id'];
        }

        $activeConv = null;
        $activeMessages = [];
        $customerCrm = null;

        if ($activeId) {
            $activeConvModel = Conversation::with([
                'user.profile',
                'user.loyaltyAccount',
                'user.addresses',
                'productAccurate.product',
                'closedBy',
                'assignedTo',
                'businessUnit',
            ])->find($activeId);

            if ($activeConvModel) {
                // Tandai pesan belum terbaca sebagai dibaca
                $updated = Message::where('conversation_id', $activeId)
                    ->whereNull('read_at')
                    ->whereIn('sender_type', ['customer', 'guest'])
                    ->update(['read_at' => now()]);

                if ($updated > 0) {
                    try {
                        broadcast(new MessageRead($activeId, 'cs'))->toOthers();
                    } catch (\Throwable $e) {}
                }

                $activeConv = $this->formatConversationDetail($activeConvModel);
                $activeMessages = $this->loadConversationMessages($activeId, $activeConvModel->guest_name ?: 'Tamu');
                $customerCrm = $this->loadCustomerCrmData($activeConvModel);
            }
        }

        // Ambil kupon voucher aktif untuk quick voucher drawer
        $activeCoupons = EcommerceCoupon::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            })
            ->latest()
            ->take(8)
            ->get(['id', 'code', 'title', 'discount_type', 'discount_amount', 'min_order_amount']);

        return Inertia::render('CsChat/Index', [
            'conversations' => $conversations,
            'activeConversation' => $activeConv,
            'messages' => $activeMessages,
            'customerCrm' => $customerCrm,
            'activeCoupons' => $activeCoupons,
            'filters' => [
                'tab' => $filterTab,
                'bu' => $filterBu,
                'search' => $search,
                'active' => $activeId,
            ],
            'quickReplies' => [
                ['label' => '👋 Sapaan', 'text' => 'Halo kak! Ada yang bisa kami bantu seputar produk atau promo Tokopon hari ini?'],
                ['label' => '📦 Stok Ready', 'text' => 'Unit yang kakak tanyakan saat ini ready siap kirim ya kak. Mau kami bantu proses pemesanannya?'],
                ['label' => '🏦 Info Rekening', 'text' => 'Pembayaran resmi Tokopon hanya melalui Rekening BCA: 123-456-7890 a/n PT Tokopon Indonesia. Mohon konfirmasi bukti transfer jika sudah ya kak.'],
                ['label' => '🛡️ Garansi Unit', 'text' => 'Semua unit gadget bergaransi resmi/toko, garansi ganti unit 7 hari jika ada kendala fungsional & garansi sinyal/IMEI seumur hidup.'],
                ['label' => '🚚 Pengiriman', 'text' => 'Kami melayani pengiriman Instant (Gosend/Grab) untuk area lokal toko kami, serta JNE/J&T berasuransi untuk kiriman luar kota.'],
                ['label' => '📍 Jam Buka Store', 'text' => 'Toko kami buka setiap hari pukul 09.00 - 21.00 WITA. Silakan berkunjung langsung ke store kami ya kak.'],
            ],
        ]);
    }

    /**
     * Query percakapan dengan optimasi performa dan multi-varian nomor HP
     */
    protected function queryConversations(string $filterTab, ?string $filterBu, string $search): array
    {
        $query = Conversation::with([
            'user.profile',
            'user.loyaltyAccount',
            'latestMessage.user',
            'productAccurate',
            'closedBy',
            'businessUnit',
            'assignedTo',
        ])
        ->withCount([
            'messages as unread_count' => function ($q) {
                $q->whereNull('read_at')->whereIn('sender_type', ['customer', 'guest']);
            }
        ])
        ->orderByDesc('updated_at');

        // Filter tab
        if ($filterTab === 'my_chats') {
            $query->where('assigned_to_user_id', Auth::id());
        } elseif ($filterTab === 'unassigned') {
            $query->whereNull('assigned_to_user_id')->where('status', 'open');
        } elseif ($filterTab === 'open') {
            $query->where('status', 'open')->where('closing_status', 'none');
        } elseif ($filterTab === 'deal') {
            $query->where('closing_status', 'deal');
        } elseif ($filterTab === 'follow_up') {
            $query->where('closing_status', 'follow_up');
        } elseif ($filterTab === 'lost') {
            $query->where('closing_status', 'lost');
        }

        // Filter Business Unit
        if (!empty($filterBu)) {
            $query->where('business_unit_id', $filterBu);
        }

        // Pencarian multi-varian nomor HP & Nama
        if (!empty(trim($search))) {
            $s = trim($search);
            $digits = preg_replace('/[^0-9]/', '', $s);

            $phonePatterns = [];
            if (!empty($digits) && strlen($digits) >= 3) {
                $phonePatterns[] = $digits;
                if (str_starts_with($digits, '628')) {
                    $phonePatterns[] = '08' . substr($digits, 3);
                    $phonePatterns[] = substr($digits, 2);
                } elseif (str_starts_with($digits, '08')) {
                    $phonePatterns[] = '628' . substr($digits, 2);
                    $phonePatterns[] = substr($digits, 1);
                } elseif (str_starts_with($digits, '8')) {
                    $phonePatterns[] = '0' . $digits;
                    $phonePatterns[] = '62' . $digits;
                }
                $phonePatterns = array_values(array_unique($phonePatterns));
            }

            $query->where(function ($q) use ($s, $phonePatterns) {
                $q->where('conversations.guest_name', 'like', "%{$s}%");
                foreach ($phonePatterns as $pat) {
                    $q->orWhere('conversations.guest_phone', 'like', "%{$pat}%");
                }

                $q->orWhereHas('user', function ($uq) use ($s, $phonePatterns) {
                    $uq->where('name', 'like', "%{$s}%")
                       ->orWhere('email', 'like', "%{$s}%");

                    $uq->orWhereHas('profile', function ($pq) use ($s, $phonePatterns) {
                        $pq->where('full_name', 'like', "%{$s}%");
                        foreach ($phonePatterns as $pat) {
                            $pq->orWhere('phone_number', 'like', "%{$pat}%");
                        }
                    });

                    $uq->orWhereHas('loyaltyAccount', function ($lq) use ($s, $phonePatterns) {
                        $lq->where('customer_name', 'like', "%{$s}%");
                        foreach ($phonePatterns as $pat) {
                            $lq->orWhere('phone_number', 'like', "%{$pat}%");
                        }
                    });
                });

                $q->orWhereHas('productAccurate', function ($prq) use ($s) {
                    $prq->where('name', 'like', "%{$s}%")
                        ->orWhere('item_no', 'like', "%{$s}%");
                });
            });
        }

        return $query->take(60)->get()->map(function ($conv) {
            $userName = $conv->user?->name ?? ($conv->guest_name ?: 'Tamu (Guest)');
            $phone = $conv->guest_phone ?: ($conv->user?->customer_phone ?? null);

            $lastSender = '';
            if ($conv->latestMessage) {
                if ($conv->latestMessage->user) {
                    $lastSender = $conv->latestMessage->user->name;
                } elseif ($conv->latestMessage->sender_type === 'guest') {
                    $lastSender = $conv->guest_name ?: 'Tamu';
                }
            }

            $loyalty = $conv->user?->loyaltyAccount;

            return [
                'id' => $conv->id,
                'userName' => $userName,
                'userInitial' => strtoupper(substr($userName, 0, 1)),
                'status' => $conv->status,
                'closingStatus' => $conv->closing_status ?: 'none',
                'closingAmount' => (float) ($conv->closing_amount ?? 0),
                'closedByName' => $conv->closedBy?->name,
                'businessUnitId' => $conv->business_unit_id,
                'businessUnitName' => $conv->businessUnit?->name,
                'isGuest' => is_null($conv->user_id),
                'guestPhone' => $phone,
                'loyaltyTier' => $loyalty?->tier ?? null,
                'loyaltyPoints' => (int) ($loyalty?->points_balance ?? 0),
                'assignedToName' => $conv->assignedTo?->name,
                'assignedToId' => $conv->assigned_to_user_id,
                'isAssignedToMe' => $conv->assigned_to_user_id === Auth::id(),
                'productName' => $conv->productAccurate?->name,
                'productPrice' => $conv->productAccurate ? 'Rp ' . number_format($conv->productAccurate->base_price, 0, ',', '.') : null,
                'lastMessage' => $conv->latestMessage?->message ?? 'Belum ada pesan',
                'lastTime' => $conv->latestMessage?->created_at?->format('H:i') ?? '',
                'lastSender' => $lastSender,
                'updatedAt' => $conv->updated_at?->diffForHumans() ?? '',
                'unreadCount' => (int) ($conv->unread_count ?? 0),
            ];
        })->toArray();
    }

    /**
     * Format detail chat yang sedang aktif
     */
    protected function formatConversationDetail(Conversation $conv): array
    {
        $userName = $conv->user?->name ?? ($conv->guest_name ?: 'Tamu (Guest)');
        $phone = $conv->guest_phone ?: ($conv->user?->customer_phone ?? null);

        // WhatsApp direct link
        $waUrl = null;
        if (!empty($phone)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', (string) $phone);
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '62' . substr($cleanPhone, 1);
            } elseif (str_starts_with($cleanPhone, '8')) {
                $cleanPhone = '62' . $cleanPhone;
            }
            $text = "Halo {$userName}, saya " . (Auth::user()?->name ?: 'Admin CS Tokopon') . " dari Tokopon.";
            if ($conv->productAccurate) {
                $text .= " Terkait pertanyaan Anda mengenai produk *{$conv->productAccurate->name}*:";
            }
            $waUrl = 'https://wa.me/' . $cleanPhone . '?text=' . urlencode($text);
        }

        return [
            'id' => $conv->id,
            'userId' => $conv->user_id,
            'userName' => $userName,
            'userEmail' => $conv->user?->email,
            'userPhone' => $phone,
            'waUrl' => $waUrl,
            'isGuest' => is_null($conv->user_id),
            'status' => $conv->status,
            'closingStatus' => $conv->closing_status ?: 'none',
            'closingAmount' => (float) ($conv->closing_amount ?? 0),
            'closingNotes' => $conv->closing_notes,
            'closedByName' => $conv->closedBy?->name,
            'closedAt' => $conv->closed_at?->format('d M Y, H:i'),
            'assignedToId' => $conv->assigned_to_user_id,
            'assignedToName' => $conv->assignedTo?->name,
            'isAssignedToMe' => $conv->assigned_to_user_id === Auth::id(),
            'businessUnitId' => $conv->business_unit_id,
            'businessUnitName' => $conv->businessUnit?->name ?? 'Toko Retail',
            'product' => $conv->productAccurate ? [
                'id' => $conv->productAccurate->id,
                'name' => $conv->productAccurate->name,
                'itemNo' => $conv->productAccurate->item_no,
                'brand' => $conv->productAccurate->brandName ?? '',
                'price' => 'Rp ' . number_format($conv->productAccurate->base_price, 0, ',', '.'),
            ] : null,
        ];
    }

    /**
     * Memuat riwayat pesan percakapan
     */
    protected function loadConversationMessages(int $conversationId, string $guestName): array
    {
        $dbMessages = Message::with(['user', 'media', 'productAccurate.product', 'productAccurate.productVariants'])
            ->where('conversation_id', $conversationId)
            ->latest('id')
            ->take(80)
            ->get()
            ->reverse()
            ->values();

        return $dbMessages->map(function ($msg) use ($guestName) {
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
                    'fileName' => $media->file_name,
                    'url' => $media->getFullUrl(),
                    'mimeType' => $media->mime_type,
                    'isImage' => str_starts_with($media->mime_type ?? '', 'image/'),
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
    }

    /**
     * Memuat wawasan lengkap Customer 360 CRM
     */
    protected function loadCustomerCrmData(Conversation $conv): array
    {
        $user = $conv->user;
        $profile = $user?->profile;
        $loyalty = $user?->loyaltyAccount;

        // Domisili atau alamat primer
        $address = $profile?->domisili;
        if (empty($address) && $user) {
            $primaryAddr = $user->addresses?->firstWhere('is_primary', true) ?? $user->addresses?->first();
            $address = $primaryAddr?->full_address ?: ($primaryAddr?->city ?? null);
        }

        // 3 Pesanan terakhir customer
        $recentOrders = [];
        if ($user) {
            $recentOrders = Order::where('user_id', $user->id)
                ->with(['items'])
                ->latest()
                ->take(3)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'orderNumber' => $order->order_number,
                        'date' => $order->created_at?->format('d M Y'),
                        'status' => $order->status,
                        'paymentStatus' => $order->payment_status,
                        'grandTotal' => 'Rp ' . number_format($order->grand_total, 0, ',', '.'),
                        'itemCount' => $order->items?->count() ?? 1,
                        'firstItemName' => $order->items?->first()?->product_name ?? 'Produk Gadget',
                    ];
                })
                ->toArray();
        }

        return [
            'hasAccount' => !is_null($user),
            'fullName' => $profile?->full_name ?: ($user?->name ?: $conv->guest_name),
            'phone' => $profile?->phone_number ?: ($loyalty?->phone_number ?: $conv->guest_phone),
            'email' => $user?->email,
            'address' => $address ?: 'Belum diisi',
            'domisili' => $profile?->domisili,
            'birthDate' => $profile?->birth_date?->format('d M Y'),
            'gender' => $profile?->gender,
            'loyalty' => [
                'tier' => $loyalty?->tier ?? 'BRONZE',
                'pointsBalance' => (int) ($loyalty?->points_balance ?? 0),
                'lifetimePoints' => (int) ($loyalty?->lifetime_points ?? 0),
                'totalSpend' => (float) ($loyalty?->total_spend ?? 0),
                'formattedTotalSpend' => 'Rp ' . number_format($loyalty?->total_spend ?? 0, 0, ',', '.'),
            ],
            'recentOrders' => $recentOrders,
            'totalOrdersCount' => $user ? Order::where('user_id', $user->id)->count() : 0,
        ];
    }

    /**
     * Kirim pesan baru dari CS
     */
    public function sendMessage(Request $request, int $conversationId): JsonResponse
    {
        $request->validate([
            'message' => 'nullable|string',
            'product_accurate_id' => 'nullable|exists:product_accurates,id',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:5120',
        ]);

        $hasText = !empty(trim($request->input('message', '')));
        $hasProduct = !empty($request->input('product_accurate_id'));
        $hasAttachment = $request->hasFile('attachment');

        if (!$hasText && !$hasProduct && !$hasAttachment) {
            return response()->json(['error' => 'Pesan tidak boleh kosong.'], 422);
        }

        $user = Auth::user();

        $message = Message::create([
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'sender_type' => 'cs',
            'product_accurate_id' => $request->input('product_accurate_id'),
            'message' => trim($request->input('message', '')),
        ]);

        if ($hasAttachment) {
            $message->addMediaFromRequest('attachment')
                ->toMediaCollection('attachments');
            $message->refresh();
        }

        Conversation::where('id', $conversationId)->update(['updated_at' => now()]);

        // Push Notification ke mobile app
        $conv = Conversation::with('user')->find($conversationId);
        if ($conv) {
            $pushToken = $conv->user?->push_token ?: $conv->push_token;
            if (!empty($pushToken)) {
                $pushBody = $message->message ?: ($hasProduct ? 'Rekomendasi produk' : 'Mengirim lampiran...');
                try {
                    SendExpoPushNotificationJob::dispatch(
                        $pushToken,
                        $user->name ?: 'Admin CS Tokopon',
                        $pushBody,
                        [
                            'url' => '/(tabs)/chat',
                            'conversation_id' => (string) $conversationId,
                        ],
                        'chat-messages'
                    )->afterResponse();
                } catch (\Throwable $e) {
                    Log::error("Gagal dispatch push notification: " . $e->getMessage());
                }
            }
        }

        // Broadcast WebSockets Reverb
        try {
            broadcast(new MessageSent(
                user: $user->name,
                message: $message->message ?: 'Mengirim lampiran...',
                time: now()->format('H:i'),
                userId: $user->id,
                conversationId: $conversationId,
                senderType: 'cs'
            ))->toOthers();
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'user' => $user->name,
                'text' => $message->message,
                'time' => now()->format('H:i'),
                'isCs' => true,
            ],
        ]);
    }

    /**
     * Klaim / Tangani percakapan oleh CS yang sedang login
     */
    public function claim(Request $request, int $conversationId): JsonResponse
    {
        $conv = Conversation::findOrFail($conversationId);
        $user = Auth::user();

        $conv->update([
            'assigned_to_user_id' => $user->id,
            'assigned_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'assignedToName' => $user->name,
            'assignedToId' => $user->id,
            'isAssignedToMe' => true,
        ]);
    }

    /**
     * Lepas percakapan kembali ke antrean bebas
     */
    public function release(Request $request, int $conversationId): JsonResponse
    {
        $conv = Conversation::findOrFail($conversationId);
        $conv->update([
            'assigned_to_user_id' => null,
            'assigned_at' => null,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Simpan status Closing Deal
     */
    public function saveClosing(Request $request, int $conversationId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:deal,follow_up,lost',
            'amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $conv = Conversation::findOrFail($conversationId);
        $status = $request->input('status');

        $conv->update([
            'closing_status' => $status,
            'closing_amount' => $request->input('amount'),
            'closing_notes' => $request->input('notes'),
            'closed_by_user_id' => Auth::id(),
            'closed_at' => now(),
            'status' => $status === 'deal' ? 'closed' : $conv->status,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Quick Voucher Generator: Buat kupon khusus closing deal di chat
     */
    public function generateQuickVoucher(Request $request): JsonResponse
    {
        $request->validate([
            'discount_amount' => 'required|numeric|min:1000',
            'min_order_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'required|in:FIXED,PERCENTAGE',
            'title' => 'nullable|string|max:100',
        ]);

        $code = 'CSDEAL-' . strtoupper(Str::random(6));

        $coupon = EcommerceCoupon::create([
            'code' => $code,
            'title' => $request->input('title') ?: 'Kupon Spesial Closing CS',
            'description' => 'Voucher diskon khusus dari Customer Service Tokopon',
            'discount_type' => $request->input('discount_type'),
            'discount_amount' => $request->input('discount_amount'),
            'min_order_amount' => $request->input('min_order_amount', 0),
            'usage_limit_per_user' => 1,
            'total_usage_limit' => 1,
            'is_active' => true,
            'start_date' => now(),
            'end_date' => now()->addDays(2),
        ]);

        return response()->json([
            'success' => true,
            'coupon' => $coupon,
            'shareText' => "Kak, ini kupon diskon spesial khusus hari ini dari kami ya! Gunakan kode: *{$coupon->code}* saat checkout.",
        ]);
    }

    /**
     * Pencarian produk untuk Product Picker
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $s = trim($request->input('q', ''));
        $query = ProductAccurate::with(['product', 'productVariants']);

        if (!empty($s)) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('item_no', 'like', "%{$s}%");
            });
        }

        $results = $query->take(8)->get()->map(function ($p) {
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
        });

        return response()->json($results);
    }
}
