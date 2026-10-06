<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\MobileOrderService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class OrderController extends Controller
{
    protected MobileOrderService $orderService;

    public function __construct(MobileOrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Checkout keranjang belanja mobile dan kunci stok pesanan.
     */
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_accurate_id' => 'required|integer|exists:product_accurates,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.serial_numbers' => 'nullable|array',
            'payment_method_id' => 'nullable|integer|exists:payment_methods,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'business_unit_id' => 'nullable|integer|exists:business_units,id',
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string|max:255',
            'shipping_address.phone' => 'required|string|max:30',
            'shipping_address.address' => 'required|string',
            'shipping_address.city' => 'nullable|string|max:100',
            'shipping_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            // 1. Tentukan Customer User (dari Token Auth atau Buat/Temukan profil nomor HP)
            $user = $request->user();

            if (!$user) {
                $shipping = $validated['shipping_address'];
                $cleanPhone = preg_replace('/[^0-9]/', '', (string) ($shipping['phone'] ?? ''));

                if (empty($cleanPhone) || strlen($cleanPhone) < 9) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Nomor HP pelanggan tidak valid (minimal 9 digit).',
                    ], 422);
                }

                // Cek variasi awalan 0 / 62
                $phoneVariations = [$cleanPhone];
                if (str_starts_with($cleanPhone, '0')) {
                    $phoneVariations[] = '62' . substr($cleanPhone, 1);
                } elseif (str_starts_with($cleanPhone, '62')) {
                    $phoneVariations[] = '0' . substr($cleanPhone, 2);
                }

                $existingProfile = UserProfile::whereIn('phone_number', $phoneVariations)->first();

                if ($existingProfile && $existingProfile->user) {
                    $user = $existingProfile->user;
                } else {
                    $customerName = trim($shipping['name'] ?: 'Pelanggan Mobile');
                    $user = User::create([
                        'name' => $customerName,
                        'email' => $cleanPhone . '@tokopon.internal',
                        'password' => bcrypt(Str::random(20)),
                    ]);

                    UserProfile::create([
                        'user_id' => $user->id,
                        'phone_number' => $cleanPhone,
                        'address' => $shipping['address'] ?? null,
                    ]);

                    if (Role::where('name', 'customer')->exists()) {
                        $user->assignRole('customer');
                    }
                }
            }

            // 2. Siapkan payload checkout untuk MobileOrderService
            $checkoutPayload = [
                'user_id' => $user->id,
                'items' => $validated['items'],
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'business_unit_id' => $validated['business_unit_id'] ?? null,
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'shipping_cost' => $validated['shipping_cost'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'shipping_address_snapshot' => $validated['shipping_address'],
                'expiry_minutes' => 60, // Batas waktu 60 menit
            ];

            // 3. Eksekusi transaksi checkout dengan lock stok
            $order = $this->orderService->createCheckoutOrder($checkoutPayload);

            // Informasi rekening transfer
            $paymentMethod = null;
            if ($order->payment_method_id) {
                $pm = PaymentMethod::find($order->payment_method_id);
                if ($pm) {
                    $paymentMethod = [
                        'id' => $pm->id,
                        'name' => $pm->name,
                        'bank_name' => $pm->bank_name ?: $pm->name,
                        'account_number' => $pm->account_number,
                        'account_owner' => $pm->account_owner,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat. Silakan lakukan pembayaran sebelum batas waktu berakhir.',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_status' => $order->order_status,
                    'grand_total' => (float) $order->grand_total,
                    'formatted_grand_total' => 'Rp ' . number_format($order->grand_total, 0, ',', '.'),
                    'payment_expired_at' => $order->payment_expired_at?->toIso8601String(),
                    'seconds_remaining' => $order->payment_expired_at ? max(0, now()->diffInSeconds($order->payment_expired_at, false)) : 0,
                    'payment_method' => $paymentMethod,
                    'items_count' => $order->items->count(),
                ]
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses checkout: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Mengambil informasi detail pesanan berdasarkan nomor order.
     */
    public function show(string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['items.variant', 'payments.paymentMethod', 'payments.media', 'warehouse'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => "Pesanan dengan nomor {$orderNumber} tidak ditemukan.",
            ], 404);
        }

        $latestPayment = $order->payments->last();
        $proofUrl = null;
        if ($latestPayment && $latestPayment->hasMedia('payment_proof')) {
            $proofUrl = $latestPayment->getFirstMediaUrl('payment_proof');
        }

        $paymentMethodInfo = null;
        if ($latestPayment && $latestPayment->paymentMethod) {
            $pm = $latestPayment->paymentMethod;
            $paymentMethodInfo = [
                'id' => $pm->id,
                'name' => $pm->name,
                'bank_name' => $pm->bank_name ?: $pm->name,
                'account_number' => $pm->account_number,
                'account_owner' => $pm->account_owner,
            ];
        }

        $items = $order->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'qty' => (int) $item->qty,
                'price' => (float) $item->price_at_checkout,
                'formatted_price' => 'Rp ' . number_format($item->price_at_checkout, 0, ',', '.'),
                'subtotal' => (float) $item->subtotal,
                'formatted_subtotal' => 'Rp ' . number_format($item->subtotal, 0, ',', '.'),
                'serial_numbers' => !empty($item->serial_number) ? explode(', ', $item->serial_number) : [],
            ];
        });

        $secondsRemaining = 0;
        if ($order->order_status === 'WAITING_PAYMENT' && $order->payment_expired_at) {
            $secondsRemaining = max(0, now()->diffInSeconds($order->payment_expired_at, false));
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'order_status' => $order->order_status,
                'order_date' => $order->order_date?->format('Y-m-d'),
                'total_amount' => (float) $order->total_amount,
                'discount_amount' => (float) $order->discount_amount,
                'shipping_cost' => (float) $order->shipping_cost,
                'grand_total' => (float) $order->grand_total,
                'formatted_grand_total' => 'Rp ' . number_format($order->grand_total, 0, ',', '.'),
                'payment_expired_at' => $order->payment_expired_at?->toIso8601String(),
                'seconds_remaining' => $secondsRemaining,
                'shipping_address' => $order->shipping_address_snapshot,
                'notes' => $order->notes,
                'payment_method' => $paymentMethodInfo,
                'payment_proof_url' => $proofUrl,
                'items' => $items,
            ]
        ]);
    }

    /**
     * Upload foto bukti transfer pembayaran oleh pelanggan.
     */
    public function uploadProof(Request $request, string $orderNumber): JsonResponse
    {
        $request->validate([
            'proof' => 'required|file|image|mimes:jpeg,png,jpg,webp|max:5120', // Maksimal 5MB
            'payment_method_id' => 'nullable|integer|exists:payment_methods,id',
        ]);

        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => "Pesanan dengan nomor {$orderNumber} tidak ditemukan.",
            ], 404);
        }

        try {
            $updatedOrder = $this->orderService->uploadPaymentProof(
                $order,
                $request->file('proof'),
                $request->input('payment_method_id')
            );

            $latestPayment = $updatedOrder->payments->last();
            $proofUrl = $latestPayment?->getFirstMediaUrl('payment_proof');

            return response()->json([
                'success' => true,
                'message' => 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.',
                'data' => [
                    'order_number' => $updatedOrder->order_number,
                    'order_status' => $updatedOrder->order_status,
                    'proof_url' => $proofUrl,
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah bukti pembayaran: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Batalkan pesanan oleh pelanggan jika masih berstatus WAITING_PAYMENT.
     */
    public function cancel(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => "Pesanan dengan nomor {$orderNumber} tidak ditemukan.",
            ], 404);
        }

        if ($order->order_status !== 'WAITING_PAYMENT') {
            return response()->json([
                'success' => false,
                'message' => "Pesanan dalam status {$order->order_status} tidak dapat dibatalkan secara mandiri.",
            ], 422);
        }

        try {
            $cancelledOrder = $this->orderService->cancelOrExpireOrder($order, 'DIBATALKAN_PELANGGAN');

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibatalkan dan stok telah dikembalikan.',
                'data' => [
                    'order_number' => $cancelledOrder->order_number,
                    'order_status' => $cancelledOrder->order_status,
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan pesanan: ' . $e->getMessage(),
            ], 422);
        }
    }
}
