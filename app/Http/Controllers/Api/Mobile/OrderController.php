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
     * Mengambil daftar riwayat pesanan pelanggan (User Login atau Nomor HP Pelanggan).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $phone = $request->input('phone') ?? $request->header('X-Customer-Phone');

        $query = Order::with(['items.productAccurate', 'payments.paymentMethod', 'warehouse'])
            ->orderBy('id', 'desc');

        if ($user) {
            $query->where('user_id', $user->id);
        } elseif (!empty($phone)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', (string) $phone);
            $variations = [$cleanPhone];
            if (str_starts_with($cleanPhone, '0')) {
                $variations[] = '62' . substr($cleanPhone, 1);
            } elseif (str_starts_with($cleanPhone, '62')) {
                $variations[] = '0' . substr($cleanPhone, 2);
            }
            $userIds = UserProfile::whereIn('phone_number', $variations)->pluck('user_id')->filter();
            $query->where(function ($q) use ($userIds, $cleanPhone) {
                if ($userIds->isNotEmpty()) {
                    $q->whereIn('user_id', $userIds);
                }
                $q->orWhere('shipping_address_snapshot->phone', 'like', "%{$cleanPhone}%");
            });
        } else {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        if ($status = $request->input('status')) {
            $query->where('order_status', $status);
        }

        $orders = $query->paginate($request->input('limit', 20));

        $formattedOrders = $orders->getCollection()->map(function ($order) {
            $latestPayment = $order->payments->last();
            $pm = $latestPayment?->paymentMethod;
            $paymentMethod = $pm ? [
                'id' => $pm->id,
                'code' => $pm->code ?? 'MANUAL_TRANSFER',
                'name' => $pm->name,
                'bank_name' => $pm->bank_name ?: $pm->name,
                'account_number' => $pm->account_number,
                'account_holder' => $pm->account_owner ?? 'Toko',
                'instructions' => [],
            ] : null;

            $items = $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_accurate_id,
                    'product_name' => $item->product_name,
                    'product_thumbnail' => $item->productAccurate?->getFirstMediaUrl('products') ?: 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&w=400&q=80',
                    'price' => (float) $item->price_at_checkout,
                    'quantity' => (int) $item->qty,
                    'serial_numbers' => !empty($item->serial_number) ? explode(', ', $item->serial_number) : [],
                    'subtotal' => (float) $item->subtotal,
                ];
            });

            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->order_status,
                'customer_name' => $order->shipping_address_snapshot['name'] ?? $order->user?->name ?? 'Pelanggan',
                'customer_phone' => $order->shipping_address_snapshot['phone'] ?? '',
                'customer_email' => $order->shipping_address_snapshot['email'] ?? $order->user?->email,
                'shipping_address' => is_array($order->shipping_address_snapshot) ? ($order->shipping_address_snapshot['address'] ?? '') : (string) $order->shipping_address_snapshot,
                'warehouse' => $order->warehouse ? [
                    'id' => $order->warehouse->id,
                    'name' => $order->warehouse->name,
                    'is_online_store' => (bool) $order->warehouse->is_online_store,
                ] : null,
                'branch_name' => $order->branch?->name ?? 'Banjarbaru',
                'payment_method' => $paymentMethod,
                'payment_expired_at' => $order->payment_expired_at?->toIso8601String(),
                'payment_proof_url' => $latestPayment?->getFirstMediaUrl('payment_proof'),
                'subtotal' => (float) $order->total_amount,
                'shipping_cost' => (float) $order->shipping_cost,
                'unique_code' => 0,
                'total_amount' => (float) $order->grand_total,
                'notes' => $order->notes,
                'items' => $items,
                'created_at' => $order->created_at?->toIso8601String(),
                'updated_at' => $order->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedOrders,
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ]
        ]);
    }

    /**
     * Checkout keranjang belanja mobile dan kunci stok pesanan.
     */
    public function checkout(Request $request): JsonResponse
    {
        // 0. Normalisasi fleksibel items (dukung product_id dan quantity dari mobile app)
        $rawItems = $request->input('items', []);
        if (is_array($rawItems)) {
            $normalizedItems = [];
            foreach ($rawItems as $item) {
                $pId = $item['product_accurate_id'] ?? $item['product_id'] ?? null;
                $qty = $item['qty'] ?? $item['quantity'] ?? 1;
                $sns = $item['serial_numbers'] ?? [];
                $normalizedItems[] = [
                    'product_accurate_id' => $pId ? (int) $pId : null,
                    'qty' => (int) $qty,
                    'serial_numbers' => is_array($sns) ? $sns : [],
                ];
            }
            $request->merge(['items' => $normalizedItems]);
        }

        // 0.1 Normalisasi fleksibel shipping_address (dukung string tunggal dari mobile app)
        $rawShipping = $request->input('shipping_address');
        if (is_string($rawShipping)) {
            $customerName = $request->input('customer_name') ?? $request->user()?->name ?? 'Pelanggan Tokopon';
            $customerPhone = $request->input('customer_phone') ?? $request->user()?->profile?->phone_number ?? '';
            $request->merge([
                'shipping_address' => [
                    'name' => $customerName,
                    'phone' => $customerPhone,
                    'address' => $rawShipping,
                    'city' => $request->input('city', 'Indonesia'),
                ]
            ]);
        }

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
            'redeem_points' => 'nullable|integer|min:0',
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
                    $existingUser = User::where('email', $cleanPhone . '@tokopon.internal')->first();

                    if ($existingUser) {
                        $user = $existingUser;
                        if (!$user->profile) {
                            UserProfile::create([
                                'user_id' => $user->id,
                                'full_name' => $customerName,
                                'phone_number' => $cleanPhone,
                                'address' => $shipping['address'] ?? null,
                            ]);
                        }
                    } else {
                        $user = User::create([
                            'name' => $customerName,
                            'email' => $cleanPhone . '@tokopon.internal',
                            'password' => bcrypt(Str::random(20)),
                        ]);

                        UserProfile::create([
                            'user_id' => $user->id,
                            'full_name' => $customerName,
                            'phone_number' => $cleanPhone,
                            'address' => $shipping['address'] ?? null,
                        ]);

                        if (Role::where('name', 'customer')->exists()) {
                            $user->assignRole('customer');
                        }
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
                'redeem_points' => (int) ($validated['redeem_points'] ?? 0),
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
                'warehouse' => $order->warehouse ? [
                    'id' => $order->warehouse->id,
                    'name' => $order->warehouse->name,
                    'is_online_store' => (bool) $order->warehouse->is_online_store,
                ] : null,
                'branch_name' => $order->branch?->name ?? 'Banjarbaru',
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
        // Dukung key proof_image dari React Native FormData
        if (!$request->hasFile('proof') && $request->hasFile('proof_image')) {
            $request->files->set('proof', $request->file('proof_image'));
        }

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
