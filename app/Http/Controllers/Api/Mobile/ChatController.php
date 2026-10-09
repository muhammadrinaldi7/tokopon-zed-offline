<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\ProductAccurate;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Helper untuk memformat objek produk konsisten untuk mobile app dan kartu chat Shopee-style.
     */
    protected function formatProduct(?ProductAccurate $product): ?array
    {
        if (!$product) {
            return null;
        }

        $imageUrl = null;
        if ($product->product && $product->product->hasMedia('cover')) {
            $imageUrl = $product->product->getFirstMediaUrl('cover');
        } elseif ($product->productVariants && $product->productVariants->isNotEmpty()) {
            $firstVariant = $product->productVariants->first();
            if ($firstVariant && $firstVariant->hasMedia('variant_image')) {
                $imageUrl = $firstVariant->getFirstMediaUrl('variant_image');
            }
        }

        if (!$imageUrl) {
            $imageUrl = 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=300&q=80';
        }

        return [
            'id' => $product->id,
            'accurate_id' => $product->id,
            'item_no' => $product->item_no,
            'name' => $product->name,
            'brand' => $product->brandName ?? 'Gadget',
            'category' => $product->categoryName ?? 'Gadget',
            'price' => (float) $product->base_price,
            'formatted_price' => 'Rp ' . number_format($product->base_price, 0, ',', '.'),
            'thumbnail' => $imageUrl,
            'image_url' => $imageUrl,
            'total_stock' => (int) ($product->warehouseStocks?->sum('stock') ?? 0),
            'is_second' => (bool) ($product->is_second ?? false),
        ];
    }

    /**
     * Memulai atau memuat sesi percakapan (Mendukung Customer Login & Guest Chat).
     */
    public function initConversation(Request $request): JsonResponse
    {
        $user = $request->user();
        $guestToken = $request->input('guest_token');
        $guestName = $request->input('guest_name');
        $guestPhone = $request->input('guest_phone');
        $businessUnitId = $request->input('business_unit_id');
        $productAccurateId = $request->input('product_accurate_id') ?: $request->input('product_id');

        $conversation = null;

        // 1. Skenario Customer Login
        if ($user) {
            $conversation = Conversation::where('user_id', $user->id)
                ->where('status', 'open')
                ->latest()
                ->first();

            if (!$conversation) {
                $conversation = Conversation::create([
                    'user_id' => $user->id,
                    'business_unit_id' => $businessUnitId,
                    'product_accurate_id' => $productAccurateId,
                    'status' => 'open',
                ]);
            } else {
                // Update konteks produk jika ada pertanyaan tentang produk baru
                if ($productAccurateId && $conversation->product_accurate_id != $productAccurateId) {
                    $conversation->update(['product_accurate_id' => $productAccurateId]);
                }
            }
        } 
        // 2. Skenario Guest Chat (Tanpa Login)
        else {
            if ($guestToken) {
                $conversation = Conversation::where('guest_token', $guestToken)->first();
            }

            if (!$conversation) {
                $guestToken = $guestToken ?: 'guest_' . Str::random(32);
                $conversation = Conversation::create([
                    'guest_token' => $guestToken,
                    'guest_name' => $guestName ?: 'Tamu Mobile',
                    'guest_phone' => $guestPhone,
                    'business_unit_id' => $businessUnitId,
                    'product_accurate_id' => $productAccurateId,
                    'status' => 'open',
                ]);
            } else {
                $updateData = [];
                if ($guestName && $conversation->guest_name !== $guestName) $updateData['guest_name'] = $guestName;
                if ($guestPhone && $conversation->guest_phone !== $guestPhone) $updateData['guest_phone'] = $guestPhone;
                if ($productAccurateId) $updateData['product_accurate_id'] = $productAccurateId;
                if (!empty($updateData)) {
                    $conversation->update($updateData);
                }
            }
        }

        // Informasi konteks produk jika ada
        $productInfo = null;
        if ($conversation->product_accurate_id) {
            $product = ProductAccurate::with(['product', 'productVariants'])->find($conversation->product_accurate_id);
            $productInfo = $this->formatProduct($product);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesi chat berhasil diinisialisasi.',
            'data' => [
                'conversation_id' => $conversation->id,
                'guest_token' => $conversation->guest_token,
                'guest_name' => $conversation->guest_name,
                'status' => $conversation->status,
                'product_context' => $productInfo,
                'product' => $productInfo,
            ]
        ]);
    }

    /**
     * Mengambil riwayat pesan dalam percakapan.
     */
    public function getMessages(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::with(['productAccurate'])->find($conversationId);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Percakapan tidak ditemukan.',
            ], 404);
        }

        // Validasi kepemilikan percakapan
        $this->authorizeConversationAccess($request, $conversation);

        $perPage = min((int) ($request->query('per_page', 30)), 100);

        $messages = Message::where('conversation_id', $conversationId)
            ->with(['media', 'productAccurate.product', 'productAccurate.productVariants'])
            ->latest()
            ->paginate($perPage);

        $data = $messages->getCollection()->map(function ($msg) {
            $attachmentUrls = [];
            foreach ($msg->getMedia('attachments') as $media) {
                $attachmentUrls[] = [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'url' => $media->getFullUrl(),
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                ];
            }

            $productData = $this->formatProduct($msg->productAccurate);

            return [
                'id' => $msg->id,
                'sender_type' => $msg->sender_type,
                'is_me' => in_array($msg->sender_type, ['customer', 'guest']),
                'message' => $msg->message,
                'product' => $productData,
                'product_reference' => $productData,
                'product_id' => $msg->product_accurate_id,
                'product_accurate_id' => $msg->product_accurate_id,
                'attachments' => $attachmentUrls,
                'read_at' => $msg->read_at?->toIso8601String(),
                'is_read' => !is_null($msg->read_at),
                'created_at' => $msg->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'total' => $messages->total(),
            ]
        ]);
    }

    /**
     * Mengirim pesan baru ke percakapan (mendukung teks, tag produk Shopee-style, dan lampiran gambar).
     */
    public function sendMessage(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json([
                'success' => false,
                'message' => 'Percakapan tidak ditemukan.',
            ], 404);
        }

        $this->authorizeConversationAccess($request, $conversation);

        $request->validate([
            'message' => 'nullable|string|max:2000',
            'product_accurate_id' => 'nullable',
            'product_id' => 'nullable',
            'attachment' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:5120',
        ]);

        $productAccurateId = $request->input('product_accurate_id') ?: $request->input('product_id');

        if (empty($request->input('message')) && !$request->hasFile('attachment') && !$productAccurateId) {
            return response()->json([
                'success' => false,
                'message' => 'Pesan teks, tag produk, atau lampiran wajib disertakan.',
            ], 422);
        }

        $user = $request->user();
        $senderType = 'guest';

        if ($user) {
            // Cek jika user pengirim adalah CS / Admin
            if ($user->hasRole(['admin', 'superadmin', 'cs', 'manager', 'kasir_sju'])) {
                $senderType = 'cs';
            } else {
                $senderType = 'customer';
            }
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user?->id,
            'sender_type' => $senderType,
            'product_accurate_id' => $productAccurateId,
            'message' => $request->input('message') ?: '',
        ]);

        // Simpan lampiran file jika diunggah
        if ($request->hasFile('attachment')) {
            $message->addMedia($request->file('attachment'))->toMediaCollection('attachments');
            $message->refresh();
        }

        // Kumpulkan URL lampiran
        $attachmentUrls = [];
        foreach ($message->getMedia('attachments') as $media) {
            $attachmentUrls[] = [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'url' => $media->getFullUrl(),
                'mime_type' => $media->mime_type,
            ];
        }

        $productData = null;
        if ($message->product_accurate_id) {
            $product = ProductAccurate::with(['product', 'productVariants'])->find($message->product_accurate_id);
            $productData = $this->formatProduct($product);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pesan berhasil dikirim.',
            'data' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender_type' => $message->sender_type,
                'is_me' => in_array($message->sender_type, ['customer', 'guest']),
                'message' => $message->message,
                'product' => $productData,
                'product_reference' => $productData,
                'product_id' => $message->product_accurate_id,
                'product_accurate_id' => $message->product_accurate_id,
                'attachments' => $attachmentUrls,
                'created_at' => $message->created_at?->toIso8601String(),
            ]
        ], 201);
    }

    /**
     * Menandai pesan yang diterima sebagai sudah dibaca.
     */
    public function markAsRead(Request $request, int $conversationId): JsonResponse
    {
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json(['success' => false, 'message' => 'Percakapan tidak ditemukan.'], 404);
        }

        $this->authorizeConversationAccess($request, $conversation);

        $user = $request->user();
        $targetSenderTypes = $user ? ['cs', 'system'] : ['cs', 'system'];

        $updatedCount = Message::where('conversation_id', $conversationId)
            ->whereNull('read_at')
            ->whereIn('sender_type', $targetSenderTypes)
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "{$updatedCount} pesan telah ditandai dibaca.",
        ]);
    }

    /**
     * Memverifikasi hak akses pengguna / guest ke sesi percakapan.
     */
    protected function authorizeConversationAccess(Request $request, Conversation $conversation): void
    {
        $user = $request->user();

        if ($user) {
            if ($user->id === $conversation->user_id) {
                return;
            }
            if ($user->hasRole(['admin', 'superadmin', 'cs', 'manager', 'kasir_sju'])) {
                return;
            }
        }

        $guestToken = $request->header('X-Guest-Token') ?: $request->input('guest_token');
        if ($guestToken && $conversation->guest_token === $guestToken) {
            return;
        }

        abort(403, 'Akses ke percakapan ini ditolak.');
    }
}
