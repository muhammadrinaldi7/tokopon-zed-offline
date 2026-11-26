<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\MobileOrderService;
use App\Support\InputSanitizer;
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
     * Mengambil daftar riwayat pesanan milik pelanggan yang sedang terautentikasi (Anti-IDOR).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu untuk melihat riwayat pesanan.',
            ], 401);
        }

        $query = Order::with(['items.productAccurate', 'payments.paymentMethod', 'warehouse', 'pickupWarehouse', 'shipping'])
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc');

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

            $items = $order->items->map(function ($item) use ($order) {
                $rawSns = !empty($item->serial_number) ? array_values(array_filter(array_map('trim', explode(',', $item->serial_number)))) : [];
                // Tampilkan IMEI utuh hanya jika order sudah COMPLETED/terkirim; selain itu samarkan 4 digit terakhir
                $displaySns = $order->order_status === 'COMPLETED'
                    ? $rawSns
                    : array_map(fn($sn) => strlen($sn) > 4 ? '****' . substr($sn, -4) : '****' . $sn, $rawSns);

                return [
                    'id' => $item->id,
                    'product_id' => $item->product_accurate_id,
                    'product_name' => $item->product_name,
                    'product_thumbnail' => $item->productAccurate?->getFirstMediaUrl('cover') ?: ($item->productAccurate?->getFirstMediaUrl('products') ?: 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?auto=format&fit=crop&w=400&q=80'),
                    'price' => (float) $item->price_at_checkout,
                    'quantity' => (int) $item->qty,
                    'serial_numbers' => $displaySns,
                    'subtotal' => (float) $item->subtotal,
                ];
            });

            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->order_status,
                'fulfillment_type' => $order->fulfillment_type ?? 'DELIVERY',
                'fulfillment_status' => $order->fulfillment_status,
                'accurate_so_number' => $order->accurate_so_number,
                'customer_name' => $order->shipping_address_snapshot['name'] ?? $order->user?->name ?? 'Pelanggan',
                'customer_phone' => $order->shipping_address_snapshot['phone'] ?? '',
                'customer_email' => $order->shipping_address_snapshot['email'] ?? $order->user?->email,
                'shipping_address' => is_array($order->shipping_address_snapshot) ? ($order->shipping_address_snapshot['address'] ?? '') : (string) $order->shipping_address_snapshot,
                'warehouse' => $order->warehouse ? [
                    'id' => $order->warehouse->id,
                    'name' => $order->warehouse->name,
                    'is_online_store' => (bool) $order->warehouse->is_online_store,
                ] : null,
                'pickup_warehouse' => $order->pickupWarehouse ? [
                    'id' => $order->pickupWarehouse->id,
                    'name' => $order->pickupWarehouse->name,
                    'city' => $order->pickupWarehouse->city,
                    'address' => $order->pickupWarehouse->address,
                    'operating_hours' => $order->pickupWarehouse->operating_hours,
                ] : null,
                'shipping' => $order->shipping ? [
                    'courier_company' => $order->shipping->courier_company,
                    'courier_type' => $order->shipping->courier_type,
                    'tracking_number' => $order->shipping->tracking_number,
                    'status' => $order->shipping->status,
                ] : null,
                'branch_name' => $order->branch?->name ?? 'Banjarbaru',
                'payment_method' => $paymentMethod,
                'payment_expired_at' => $order->payment_expired_at?->toIso8601String(),
                'payment_proof_url' => $latestPayment?->getFirstMediaUrl('payment_proof'),
                'subtotal' => (float) $order->total_amount,
                'shipping_cost' => (float) $order->shipping_cost,
                'coupon_code' => $order->coupon_code,
                'coupon_discount_amount' => (float) ($order->coupon_discount_amount ?? 0),
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
        // 0. Normalisasi fleksibel items (dukung product_id, quantity, dan opsional unit_id untuk GSK Second)
        $rawItems = $request->input('items', []);
        if (is_array($rawItems)) {
            $normalizedItems = [];
            foreach ($rawItems as $item) {
                $pId = $item['product_accurate_id'] ?? $item['product_id'] ?? null;
                $qty = $item['qty'] ?? $item['quantity'] ?? 1;
                $sns = $item['serial_numbers'] ?? [];
                $unitId = $item['unit_id'] ?? $item['product_serial_number_id'] ?? null;
                $normalizedItems[] = [
                    'product_accurate_id' => $pId ? (int) $pId : null,
                    'qty' => (int) $qty,
                    'unit_id' => $unitId ? (int) $unitId : null,
                    'serial_numbers' => is_array($sns) ? $sns : [],
                ];
            }
            $request->merge(['items' => $normalizedItems]);
        }

        // 0.1 Normalisasi fleksibel shipping_address (dukung string tunggal dari mobile app)
        $rawShipping = $request->input('shipping_address');
        if (is_string($rawShipping)) {
            $customerName = $request->input('customer_name') ?? $request->user()?->name ?? 'Pelanggan Tokopon';
            $customerPhone = $request->input('customer_phone') ?? $request->user()?->customer_phone ?? '';
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
            'items.*.unit_id' => 'nullable|integer|exists:product_serial_numbers,id',
            'items.*.serial_numbers' => 'nullable|array',
            'fulfillment_type' => 'nullable|string|in:DELIVERY,PICK_UP,delivery,pickup',
            'pickup_warehouse_id' => 'nullable|integer|exists:warehouses,id',
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
            'coupon_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            // 1. Wajib Customer User terautentikasi (mencegah pembuatan user spam & pencurian poin loyalty via nomor HP)
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan login terlebih dahulu untuk melakukan checkout.',
                ], 401);
            }

            // 1.1 Anti-XSS: Tolak tag HTML / payload skrip pada nama penerima, alamat, kota, dan catatan
            $shipping = $validated['shipping_address'];
            if (
                InputSanitizer::containsHtmlOrScript($shipping['name'] ?? null)
                || InputSanitizer::containsHtmlOrScript($shipping['address'] ?? null)
                || InputSanitizer::containsHtmlOrScript($shipping['city'] ?? null)
                || InputSanitizer::containsHtmlOrScript($validated['notes'] ?? null)
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nama, alamat, atau catatan tidak boleh mengandung tag HTML atau skrip berbahaya.',
                ], 422);
            }

            $sanitizedShipping = [
                'name' => InputSanitizer::cleanPlainText($shipping['name'] ?? $user->name, 120),
                'phone' => preg_replace('/[^0-9+]/', '', (string) ($shipping['phone'] ?? '')),
                'address' => InputSanitizer::cleanPlainText($shipping['address'] ?? '', 500),
                'city' => InputSanitizer::cleanPlainText($shipping['city'] ?? 'Indonesia', 100),
            ];
            $sanitizedNotes = isset($validated['notes'])
                ? (InputSanitizer::cleanPlainText($validated['notes'], 500) ?: null)
                : null;

            // 2. Siapkan payload checkout untuk MobileOrderService
            $rawFulfillment = strtoupper((string) ($validated['fulfillment_type'] ?? 'DELIVERY'));
            if ($rawFulfillment === 'PICKUP') {
                $rawFulfillment = 'PICK_UP';
            }

            $checkoutPayload = [
                'user_id' => $user->id,
                'items' => $validated['items'],
                'fulfillment_type' => $rawFulfillment,
                'pickup_warehouse_id' => $validated['pickup_warehouse_id'] ?? ($rawFulfillment === 'PICK_UP' ? ($validated['warehouse_id'] ?? null) : null),
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'business_unit_id' => $validated['business_unit_id'] ?? null,
                'payment_method_id' => $validated['payment_method_id'] ?? null,
                'shipping_cost' => $validated['shipping_cost'] ?? 0,
                'redeem_points' => (int) ($validated['redeem_points'] ?? 0),
                'coupon_code' => $validated['coupon_code'] ?? null,
                'notes' => $sanitizedNotes,
                'shipping_address_snapshot' => $sanitizedShipping,
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
                    'fulfillment_type' => $order->fulfillment_type ?? 'DELIVERY',
                    'fulfillment_status' => $order->fulfillment_status,
                    'pickup_warehouse' => $order->pickupWarehouse ? [
                        'id' => $order->pickupWarehouse->id,
                        'name' => $order->pickupWarehouse->name,
                        'city' => $order->pickupWarehouse->city,
                        'address' => $order->pickupWarehouse->address,
                        'operating_hours' => $order->pickupWarehouse->operating_hours,
                    ] : null,
                    'grand_total' => (float) $order->grand_total,
                    'formatted_grand_total' => 'Rp ' . number_format($order->grand_total, 0, ',', '.'),
                    'coupon_code' => $order->coupon_code,
                    'coupon_discount_amount' => (float) ($order->coupon_discount_amount ?? 0),
                    'loyalty_discount_amount' => (float) ($order->loyalty_discount_amount ?? 0),
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
     * Mengambil informasi detail pesanan berdasarkan nomor order (Anti-IDOR: wajib milik user login atau staf/admin).
     */
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['items.variant', 'payments.paymentMethod', 'payments.media', 'warehouse', 'pickupWarehouse', 'shipping'])
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => "Pesanan dengan nomor {$orderNumber} tidak ditemukan.",
            ], 404);
        }

        if ($denied = $this->checkOrderOwnership($request, $order)) {
            return $denied;
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

        $items = $order->items->map(function ($item) use ($order) {
            $rawSns = !empty($item->serial_number) ? array_values(array_filter(array_map('trim', explode(',', $item->serial_number)))) : [];
            $displaySns = $order->order_status === 'COMPLETED'
                ? $rawSns
                : array_map(fn($sn) => strlen($sn) > 4 ? '****' . substr($sn, -4) : '****' . $sn, $rawSns);

            return [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'qty' => (int) $item->qty,
                'price' => (float) $item->price_at_checkout,
                'formatted_price' => 'Rp ' . number_format($item->price_at_checkout, 0, ',', '.'),
                'subtotal' => (float) $item->subtotal,
                'formatted_subtotal' => 'Rp ' . number_format($item->subtotal, 0, ',', '.'),
                'serial_numbers' => $displaySns,
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
                'fulfillment_type' => $order->fulfillment_type ?? 'DELIVERY',
                'fulfillment_status' => $order->fulfillment_status,
                'accurate_so_number' => $order->accurate_so_number,
                'order_date' => $order->order_date?->format('Y-m-d'),
                'total_amount' => (float) $order->total_amount,
                'discount_amount' => (float) $order->discount_amount,
                'coupon_code' => $order->coupon_code,
                'coupon_discount_amount' => (float) ($order->coupon_discount_amount ?? 0),
                'loyalty_points_redeemed' => (int) ($order->loyalty_points_redeemed ?? 0),
                'loyalty_discount_amount' => (float) ($order->loyalty_discount_amount ?? 0),
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
                'pickup_warehouse' => $order->pickupWarehouse ? [
                    'id' => $order->pickupWarehouse->id,
                    'name' => $order->pickupWarehouse->name,
                    'city' => $order->pickupWarehouse->city,
                    'address' => $order->pickupWarehouse->address,
                    'phone' => $order->pickupWarehouse->phone,
                    'operating_hours' => $order->pickupWarehouse->operating_hours,
                ] : null,
                'shipping' => $order->shipping ? [
                    'courier_company' => $order->shipping->courier_company,
                    'courier_type' => $order->shipping->courier_type,
                    'tracking_number' => $order->shipping->tracking_number,
                    'status' => $order->shipping->status,
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

        if ($denied = $this->checkOrderOwnership($request, $order)) {
            return $denied;
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

        if ($denied = $this->checkOrderOwnership($request, $order)) {
            return $denied;
        }

        $user = $request->user();

        if ($order->order_status !== 'WAITING_PAYMENT') {
            return response()->json([
                'success' => false,
                'message' => "Pesanan dalam status {$order->order_status} tidak dapat dibatalkan secara mandiri.",
            ], 422);
        }

        try {
            $cancelledOrder = $this->orderService->cancelOrExpireOrder($order, 'DIBATALKAN_PELANGGAN', $user);

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibatalkan. Poin loyalitas dan stok barang telah dikembalikan.',
                'data' => [
                    'order_number' => $cancelledOrder->order_number,
                    'order_status' => $cancelledOrder->order_status,
                    'loyalty_points_refunded' => (int) ($cancelledOrder->loyalty_points_redeemed ?? 0),
                    'loyalty_points_revoked' => (int) ($cancelledOrder->loyalty_points_earned ?? 0),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan pesanan: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Verifikasi kepemilikan order (Anti-IDOR): wajib login dan milik user tersebut (kecuali admin/staf CS).
     */
    protected function checkOrderOwnership(Request $request, Order $order): ?JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan login terlebih dahulu untuk mengakses pesanan ini.',
            ], 401);
        }

        $isOwner = $order->user_id !== null && (int) $order->user_id === (int) $user->id;
        $isStaff = method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['superadmin', 'admin', 'director', 'manager', 'cs']);

        if (!$isOwner && !$isStaff) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses ke pesanan ini.',
            ], 403);
        }

        return null;
    }
}
