<?php

namespace App\Services;

use App\Jobs\SyncOrderToAccurateJob;
use App\Jobs\SyncSalesOrderToAccurateJob;
use App\Models\Branch;
use App\Models\OnlineStockReservation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\OrderShipping;
use App\Models\ProductAccurate;
use App\Models\ProductSerialNumber;
use App\Models\User;
use App\Models\Warehouse;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MobileOrderService
{
    /**
     * Membuat order baru dari checkout Mobile App (SO-First Architecture).
     *
     * - Melakukan Live Accurate Stock Check (cached 30 dtk) sebelum transaksi DB.
     * - Mendukung Multi-Warehouse:
     *   - DELIVERY: memvalidasi stok gabungan (Total ATP) seluruh gudang Delivery aktif.
     *   - PICK_UP : memvalidasi stok spesifik pada cabang `pickup_warehouse_id`.
     * - TIDAK mengunci IMEI di awal untuk barang Baru / pesanan umum (IMEI di-scan saat Fulfillment).
     * - Jika customer GSK Second memilih `unit_id` spesifik, unit tersebut dikunci secara atomik.
     * - TIDAK membuat SO di Accurate sebelum pembayaran dikonfirmasi lunas (menjaga Accurate bersih).
     */
    public function createCheckoutOrder(array $data): Order
    {
        $userId = $data['user_id'] ?? null;
        if (!$userId) {
            throw new Exception("Customer user_id wajib disertakan untuk checkout mobile.");
        }

        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new Exception("Keranjang belanja kosong.");
        }

        $fulfillmentType = strtoupper(trim((string) ($data['fulfillment_type'] ?? 'DELIVERY')));
        if (!in_array($fulfillmentType, ['DELIVERY', 'PICK_UP'], true)) {
            $fulfillmentType = 'DELIVERY';
        }

        $businessUnitId = isset($data['business_unit_id']) ? (int) $data['business_unit_id'] : null;
        $pickupWarehouseId = isset($data['pickup_warehouse_id'])
            ? (int) $data['pickup_warehouse_id']
            : (isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : null);

        // Auto-detect businessUnitId dari produk pertama jika belum dikirim
        if (!$businessUnitId && !empty($items)) {
            $firstProductAccurateId = $items[0]['product_accurate_id'] ?? $items[0]['product_id'] ?? null;
            if ($firstProductAccurateId) {
                $p = ProductAccurate::find($firstProductAccurateId);
                if ($p && $p->business_unit_id) {
                    $businessUnitId = (int) $p->business_unit_id;
                }
            }
        }

        $onlineStockService = app(OnlineStockService::class);
        $onlineStockService->releaseExpiredReservations();

        // 1. Validasi Gudang Online sesuai Fulfillment Type
        $pickupWarehouse = null;
        $defaultOrderWarehouse = null;

        if ($fulfillmentType === 'PICK_UP') {
            if ($pickupWarehouseId) {
                $pickupWarehouse = Warehouse::where('id', $pickupWarehouseId)
                    ->storePickup()
                    ->when($businessUnitId, fn($q) => $q->where('business_unit_id', $businessUnitId))
                    ->first();
            }

            if (!$pickupWarehouse) {
                throw new Exception("Cabang pengambilan (Store Pick Up) tidak valid atau belum diaktifkan untuk Pick Up.");
            }

            $defaultOrderWarehouse = $pickupWarehouse;
            if (!$businessUnitId && $pickupWarehouse->business_unit_id) {
                $businessUnitId = (int) $pickupWarehouse->business_unit_id;
            }
        } else {
            // Mode DELIVERY: pastikan ada minimal 1 gudang delivery aktif untuk BU ini
            $deliveryWarehouses = $onlineStockService->getOnlineWarehouses($businessUnitId, 'delivery');
            if ($deliveryWarehouses->isEmpty()) {
                throw new Exception("Tidak ada gudang online Delivery yang aktif untuk melayani pesanan toko ini.");
            }
            $defaultOrderWarehouse = $deliveryWarehouses->first();
            if (!$businessUnitId && $defaultOrderWarehouse->business_unit_id) {
                $businessUnitId = (int) $defaultOrderWarehouse->business_unit_id;
            }
        }

        // 2. Live Stock Check ke Accurate (di luar DB::transaction agar tidak menahan lock saat HTTP call)
        $productsMap = [];
        foreach ($items as $item) {
            $pId = (int) ($item['product_accurate_id'] ?? 0);
            if ($pId && !isset($productsMap[$pId])) {
                $product = ProductAccurate::with('businessUnit')->findOrFail($pId);
                $onlineStockService->refreshLiveStockForProduct($product);
                $productsMap[$pId] = $product;
            }
        }

        $expiryMinutes = (int) ($data['expiry_minutes'] ?? 60);

        $order = DB::transaction(function () use (
            $data,
            $userId,
            $businessUnitId,
            $fulfillmentType,
            $pickupWarehouse,
            $defaultOrderWarehouse,
            $items,
            $productsMap,
            $expiryMinutes,
            $onlineStockService
        ) {
            $subtotal = 0;
            $totalDiscount = 0;
            $orderItemsToCreate = [];
            $reservationsToCreate = [];

            // 3. Validasi Stok ATP & Siapkan Reservasi Atomik
            foreach ($items as $item) {
                $productAccurateId = (int) ($item['product_accurate_id'] ?? 0);
                $qty = (int) ($item['qty'] ?? 1);
                if ($qty <= 0) {
                    throw new Exception("Jumlah item harus lebih dari 0.");
                }

                $product = $productsMap[$productAccurateId] ?? ProductAccurate::with('businessUnit')->findOrFail($productAccurateId);
                $price = isset($item['price']) ? (float) $item['price'] : (float) $product->base_price;
                $discount = (float) ($item['discount_amount'] ?? 0);

                // Hitung ATP aktual (setelah live refresh & dikurangi reservasi aktif)
                $breakdown = $onlineStockService->getProductStockBreakdown($product, refreshLiveFromAccurate: false);

                $selectedSnModel = null;
                $reservationWarehouseId = null;
                $cleanSns = [];

                // Cek apakah customer secara eksplisit memilih 1 unit spesifik (GSK Second Showcase)
                $requestedUnitId = !empty($item['unit_id']) ? (int) $item['unit_id'] : null;

                if ($requestedUnitId) {
                    if ($qty !== 1) {
                        throw new Exception("Pemilihan unit spesifik ('{$product->name}') hanya berlaku untuk qty = 1 per baris.");
                    }

                    $selectedSnModel = ProductSerialNumber::where('id', $requestedUnitId)
                        ->where('status', 'Available')
                        ->where(function ($q) use ($product) {
                            $q->where('product_accurate_id', $product->id)
                                ->orWhere('item_no', $product->item_no);
                        })
                        ->lockForUpdate()
                        ->first();

                    if (!$selectedSnModel) {
                        throw new Exception("Unit spesifik yang dipilih untuk '{$product->name}' sudah tidak tersedia atau baru saja dipesan.");
                    }

                    // Pastikan tidak ada reservasi aktif lain untuk SN ini
                    $snAlreadyReserved = OnlineStockReservation::active()
                        ->where('product_serial_number_id', $selectedSnModel->id)
                        ->exists();

                    if ($snAlreadyReserved) {
                        throw new Exception("Unit spesifik yang dipilih untuk '{$product->name}' sedang dalam proses pembayaran pelanggan lain.");
                    }

                    if ($fulfillmentType === 'PICK_UP' && $pickupWarehouse && (int) $selectedSnModel->warehouse_id !== (int) $pickupWarehouse->id) {
                        throw new Exception("Unit spesifik yang dipilih tidak berada di cabang pengambilan '{$pickupWarehouse->name}'.");
                    }

                    // Kunci unit spesifik GSK Second
                    $selectedSnModel->update(['status' => 'Reserved']);
                    $reservationWarehouseId = (int) $selectedSnModel->warehouse_id;
                    $cleanSns = [(string) $selectedSnModel->serial_number];
                } else {
                    // Alur SO-First: Validasi berbasis KUOTA QTY (TANPA mengunci IMEI di tahap checkout!)
                    if ($fulfillmentType === 'PICK_UP') {
                        $whStock = (int) ($breakdown['per_warehouse_map'][$pickupWarehouse->id] ?? 0);
                        if ($whStock < $qty) {
                            throw new Exception("Stok '{$product->name}' di cabang {$pickupWarehouse->name} tidak mencukupi (tersedia: {$whStock}, diminta: {$qty}).");
                        }
                        $reservationWarehouseId = (int) $pickupWarehouse->id;
                    } else {
                        $deliveryStock = (int) $breakdown['total_delivery_stock'];
                        if ($deliveryStock < $qty) {
                            throw new Exception("Stok online '{$product->name}' tidak mencukupi (tersedia: {$deliveryStock}, diminta: {$qty}).");
                        }
                        // Pool-level reservation (warehouse_id = null) agar admin bebas pilih cabang pengirim saat fulfillment!
                        $reservationWarehouseId = null;
                    }
                }

                $itemSubtotal = $price * $qty;
                $subtotal += $itemSubtotal;
                $totalDiscount += $discount;

                $orderItemsToCreate[] = [
                    'product_variant_id' => $product->id,
                    'product_variant_type' => ProductAccurate::class,
                    'product_name' => $product->name,
                    'qty' => $qty,
                    'price_at_checkout' => $price,
                    'subtotal' => $itemSubtotal,
                    'discount_amount' => $discount,
                    'promo_discount_amount' => 0,
                    'serial_number' => !empty($cleanSns) ? implode(', ', $cleanSns) : null,
                ];

                $reservationsToCreate[] = [
                    'product_accurate_id' => $product->id,
                    'business_unit_id' => $businessUnitId,
                    'warehouse_id' => $reservationWarehouseId,
                    'product_serial_number_id' => $selectedSnModel?->id,
                    'qty' => $qty,
                ];
            }

            // 4. Generate Order Number
            $dateNow = now();
            $orderNumber = 'MOB-' . $dateNow->format('Ymd') . '-' . mt_rand(1000, 9999) . '-' . str_pad(
                Order::whereDate('order_date', $dateNow->format('Y-m-d'))
                    ->where('order_channel', 'MOBILE_APP')
                    ->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            $shippingCost = $fulfillmentType === 'PICK_UP' ? 0.0 : (float) ($data['shipping_cost'] ?? 0);

            $shippingSnapshot = $data['shipping_address_snapshot'] ?? [
                'type' => $fulfillmentType,
                'store' => $pickupWarehouse?->name ?? $defaultOrderWarehouse?->name,
                'customer_name' => User::find($userId)?->name ?? 'Customer',
            ];

            $userModel = User::find($userId);

            // 4.1 Validasi Kupon Diskon
            $couponCode = !empty($data['coupon_code']) ? trim((string) $data['coupon_code']) : null;
            $coupon = null;
            $couponDiscount = 0.0;
            $couponService = app(EcommerceCouponService::class);

            if ($couponCode) {
                $netSubtotal = max(0, $subtotal - $totalDiscount);
                $validation = $couponService->validateCoupon($couponCode, $userModel, $netSubtotal, $shippingCost, $businessUnitId);
                if (!$validation['valid']) {
                    throw new Exception($validation['message']);
                }
                $coupon = $validation['coupon'];
                $couponDiscount = (float) $validation['discount'];
            }

            // 4.2 Validasi Poin Loyalty
            $redeemPoints = (int) ($data['redeem_points'] ?? 0);
            $loyaltyService = app(LoyaltyService::class);
            $loyaltyDiscount = 0;

            if ($redeemPoints > 0 && $loyaltyService->isActive()) {
                $customerPhone = $shippingSnapshot['phone'] ?? $userModel?->profile?->phone_number;
                $loyaltyAccount = $customerPhone ? $loyaltyService->getOrCreateAccount($customerPhone, $shippingSnapshot['name'] ?? null, $userModel) : null;

                if (!$loyaltyAccount || !$loyaltyAccount->canRedeem($redeemPoints)) {
                    $sisa = $loyaltyAccount ? $loyaltyAccount->points_balance : 0;
                    throw new Exception("Saldo poin loyalty tidak mencukupi (Tersedia: {$sisa} poin, Diminta: {$redeemPoints} poin).");
                }

                $loyaltyDiscount = $loyaltyService->calculatePointDiscount($redeemPoints);
                $netBill = max(0, ($subtotal - $totalDiscount + $shippingCost) - $couponDiscount);
                $loyaltyDiscount = min($loyaltyDiscount, $netBill);
            } else {
                $redeemPoints = 0;
            }

            $grandTotal = max(0, ($subtotal - $totalDiscount + $shippingCost) - $couponDiscount - $loyaltyDiscount);
            $isZeroBill = $grandTotal <= 0;
            $initialStatus = $isZeroBill ? 'PROCESSING' : 'WAITING_PAYMENT';
            $initialFulfillmentStatus = $isZeroBill ? 'PENDING_ALLOCATION' : null;
            $expiresAt = $isZeroBill ? null : now()->addMinutes($expiryMinutes);

            $settingService = app(SettingService::class);
            $defaultSalesId = $settingService->get("ecommerce_sales_{$businessUnitId}", $settingService->get('ecommerce_default_sales_id'));
            $salesId = $data['sales_id'] ?? (!empty($defaultSalesId) ? (int) $defaultSalesId : null);

            $defaultBranchName = $settingService->get("ecommerce_branch_{$businessUnitId}", $settingService->get('ecommerce_default_branch_name', 'Banjarbaru'));
            $branchId = $data['branch_id'] ?? Branch::where('name', $defaultBranchName)->value('id');

            $defaultHandledById = $settingService->get("ecommerce_handled_by_{$businessUnitId}", $settingService->get('ecommerce_default_handled_by_id'));
            $handledBy = $data['handled_by'] ?? (!empty($defaultHandledById) ? (int) $defaultHandledById : null);

            // Tentukan warehouse_id awal: untuk PICK_UP = pickupWarehouse, untuk DELIVERY = warehouse rekomendasi (atau unit spesifik Second)
            $initialWarehouseId = $pickupWarehouse?->id;
            if (!$initialWarehouseId && !empty($reservationsToCreate[0]['warehouse_id'])) {
                $initialWarehouseId = $reservationsToCreate[0]['warehouse_id'];
            }
            if (!$initialWarehouseId) {
                $suggested = $onlineStockService->suggestFulfillmentWarehouse(
                    (int) $orderItemsToCreate[0]['product_variant_id'],
                    (int) $orderItemsToCreate[0]['qty'],
                    (int) $businessUnitId
                );
                $initialWarehouseId = $suggested?->id ?? $defaultOrderWarehouse?->id;
            }

            // 5. Create Order
            $order = Order::create([
                'business_unit_id' => $businessUnitId,
                'user_id' => $userId,
                'warehouse_id' => $initialWarehouseId,
                'pickup_warehouse_id' => $pickupWarehouse?->id,
                'branch_id' => $branchId,
                'sales_id' => $salesId,
                'handled_by' => $handledBy,
                'order_number' => $orderNumber,
                'order_date' => $dateNow->format('Y-m-d'),
                'total_amount' => $subtotal,
                'shipping_cost' => $shippingCost,
                'discount_amount' => $totalDiscount,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'coupon_discount_amount' => $couponDiscount,
                'loyalty_points_redeemed' => $redeemPoints,
                'loyalty_discount_amount' => $loyaltyDiscount,
                'grand_total' => $grandTotal,
                'order_status' => $initialStatus,
                'fulfillment_type' => $fulfillmentType,
                'fulfillment_status' => $initialFulfillmentStatus,
                'order_channel' => 'MOBILE_APP',
                'payment_expired_at' => $expiresAt,
                'shipping_address_snapshot' => $shippingSnapshot,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($coupon && $couponDiscount > 0) {
                $couponService->applyCouponToOrder($coupon, $order, $userModel, $couponDiscount);
            }

            if ($redeemPoints > 0) {
                $loyaltyService->redeemPoints($order, $redeemPoints, $userModel);
            }

            // 6. Create Order Items & Online Stock Reservations (TIDAK menyentuh warehouse_stocks!)
            foreach ($orderItemsToCreate as $idx => $itemData) {
                $itemData['order_id'] = $order->id;
                $orderItem = OrderItem::create($itemData);

                $resPayload = $reservationsToCreate[$idx];
                OnlineStockReservation::create([
                    'order_id' => $order->id,
                    'order_item_id' => $orderItem->id,
                    'product_accurate_id' => $resPayload['product_accurate_id'],
                    'business_unit_id' => $resPayload['business_unit_id'],
                    'warehouse_id' => $resPayload['warehouse_id'],
                    'product_serial_number_id' => $resPayload['product_serial_number_id'],
                    'qty' => $resPayload['qty'],
                    'status' => 'ACTIVE',
                    'expires_at' => $expiresAt,
                ]);
            }

            // 7. Create Initial Payment Record
            OrderPayment::create([
                'order_id' => $order->id,
                'xendit_external_id' => 'ORD-MOB-' . date('YmdHis') . rand(1000, 9999),
                'amount' => $grandTotal,
                'status' => $isZeroBill ? 'PAID' : 'PENDING',
                'paid_at' => $isZeroBill ? now() : null,
                'payment_method_id' => $data['payment_method_id'] ?? null,
            ]);

            // 8. Catat komisi referral jika ada
            try {
                app(ReferralService::class)->recordOrderCommission($order);
            } catch (\Throwable $e) {
                Log::warning("Gagal mencatat komisi referral untuk pesanan #{$order->order_number}: " . $e->getMessage());
            }

            return $order->load(['items', 'payments', 'warehouse', 'pickupWarehouse']);
        });

        // Jika tagihan Rp 0 (lunas via kupon/poin), langsung terbitkan Sales Order ke Accurate
        if ($order->order_status === 'PROCESSING') {
            SyncSalesOrderToAccurateJob::dispatch($order->id);
        }

        return $order;
    }

    /**
     * Upload bukti transfer pembayaran manual oleh pelanggan.
     * Menjadikan reservasi stok permanen (tidak hangus) selama menunggu verifikasi admin.
     */
    public function uploadPaymentProof(Order $order, $file, ?int $paymentMethodId = null): Order
    {
        if ($order->order_status !== 'WAITING_PAYMENT' && $order->order_status !== 'WAITING_VERIFICATION') {
            throw new Exception("Order #{$order->order_number} tidak berada dalam status menunggu pembayaran.");
        }

        $payment = $order->payments()->latest()->first();
        if (!$payment) {
            $payment = OrderPayment::create([
                'order_id' => $order->id,
                'xendit_external_id' => 'ORD-MOB-' . date('YmdHis') . rand(1000, 9999),
                'amount' => $order->grand_total,
                'status' => 'PENDING',
                'payment_method_id' => $paymentMethodId,
            ]);
        } elseif ($paymentMethodId) {
            $payment->update(['payment_method_id' => $paymentMethodId]);
        }

        if ($file instanceof UploadedFile) {
            $payment->addMedia($file)->toMediaCollection('payment_proof');
        } elseif (is_string($file) && file_exists($file)) {
            $payment->addMedia($file)->toMediaCollection('payment_proof');
        }

        $order->update([
            'order_status' => 'WAITING_VERIFICATION',
            'payment_expired_at' => null,
        ]);

        // Lepaskan timer expiry pada reservasi stok agar tidak hangus saat menunggu verifikasi admin
        OnlineStockReservation::where('order_id', $order->id)
            ->where('status', 'ACTIVE')
            ->update(['expires_at' => null]);

        $this->sendTelegramNotification($order, 'WAITING_VERIFICATION');

        return $order->refresh()->load(['payments.media']);
    }

    /**
     * TAHAP 1 ADMIN: Verifikasi & Setujui Pembayaran Lunas (Manual Admin / Webhook Xendit).
     *
     * - Mengubah status order menjadi `PROCESSING` & `fulfillment_status = PENDING_ALLOCATION`.
     * - Men-dispatch `SyncSalesOrderToAccurateJob` untuk membuat Sales Order (SO) di Accurate TANPA IMEI.
     * - Jika `$overrides['immediate_fulfill'] = true` (opsional), langsung lanjutkan ke `fulfillOrder()`.
     */
    public function approvePayment(Order $order, ?User $actor = null, array $overrides = []): Order
    {
        if ($order->order_status === 'COMPLETED') {
            return $order;
        }

        if ($order->order_status === 'PROCESSING' && !empty($overrides['immediate_fulfill'])) {
            return $this->fulfillOrder($order, $actor, $overrides);
        }

        DB::transaction(function () use ($order, $actor, $overrides) {
            $warehouseId = $overrides['warehouse_id'] ?? $order->warehouse_id;
            $salesId = array_key_exists('sales_id', $overrides) ? $overrides['sales_id'] : $order->sales_id;
            $branchId = array_key_exists('branch_id', $overrides) ? $overrides['branch_id'] : $order->branch_id;
            $branchName = $overrides['branch_name'] ?? null;

            if ($branchName && !$branchId) {
                $b = Branch::where('name', $branchName)->first();
                if ($b) {
                    $branchId = $b->id;
                }
            }

            $orderUpdates = [
                'order_status' => 'PROCESSING',
                'fulfillment_status' => $order->fulfillment_status ?: 'PENDING_ALLOCATION',
                'handled_by' => $actor ? $actor->id : $order->handled_by,
                'payment_expired_at' => null,
            ];

            if ($warehouseId) {
                $orderUpdates['warehouse_id'] = (int) $warehouseId;
            }
            if ($salesId !== $order->sales_id) {
                $orderUpdates['sales_id'] = $salesId;
            }
            if ($branchId !== $order->branch_id) {
                $orderUpdates['branch_id'] = $branchId;
            }

            $order->update($orderUpdates);

            // Tandai pembayaran sebagai PAID
            $order->payments()
                ->where('status', '!=', 'PAID')
                ->update([
                    'status' => 'PAID',
                    'paid_at' => now(),
                ]);

            // Pastikan reservasi stok tidak pernah kadaluarsa
            OnlineStockReservation::where('order_id', $order->id)
                ->where('status', 'ACTIVE')
                ->update(['expires_at' => null]);

            // Catat perolehan Poin Loyalty (idempotent di LoyaltyService)
            try {
                app(LoyaltyService::class)->earnPoints($order);
            } catch (\Throwable $e) {
                Log::warning("Gagal menambahkan poin loyalty untuk order #{$order->order_number}: " . $e->getMessage());
            }
        });

        // Terbitkan Sales Order (SO) di Accurate di background (TANPA IMEI!)
        SyncSalesOrderToAccurateJob::dispatch($order->id);

        if (!empty($overrides['immediate_fulfill'])) {
            return $this->fulfillOrder($order->refresh(), $actor, $overrides);
        }

        return $order->refresh();
    }

    /**
     * TAHAP 2 ADMIN (FULFILLMENT): Alokasi Cabang Pengirim, Scan IMEI Aktual & Konfirmasi Kirim / Siap Diambil.
     *
     * - Admin bebas memilih gudang cabang mana pun yang memiliki stok (untuk Delivery).
     * - Memvalidasi & menyimpan IMEI aktual yang di-scan untuk item `has_sn = true`.
     * - Menyimpan info kurir & resi ke tabel `order_shippings` (jika Delivery).
     * - Mengubah order menjadi `COMPLETED` & men-dispatch `SyncOrderToAccurateJob`
     *   untuk menerbitkan Sales Invoice (SI) terhubung ke `salesOrderNumber` + `detailSerialNumber` + SR.
     */
    public function fulfillOrder(Order $order, ?User $actor = null, array $fulfillmentData = []): Order
    {
        if ($order->order_status === 'COMPLETED' && !empty($order->fulfilled_at)) {
            return $order;
        }

        if ($order->order_status === 'CANCELLED') {
            throw new Exception("Order #{$order->order_number} sudah dibatalkan dan tidak dapat dipenuhi.");
        }

        DB::transaction(function () use ($order, $actor, $fulfillmentData) {
            $order->loadMissing(['items.variant', 'payments', 'shipping']);

            $chosenWarehouseId = isset($fulfillmentData['warehouse_id']) && $fulfillmentData['warehouse_id']
                ? (int) $fulfillmentData['warehouse_id']
                : ($order->pickup_warehouse_id ?: $order->warehouse_id);

            if (!$chosenWarehouseId) {
                throw new Exception("Gudang cabang pemenuhan wajib dipilih sebelum menerbitkan Faktur (SI).");
            }

            $salesId = array_key_exists('sales_id', $fulfillmentData) ? $fulfillmentData['sales_id'] : $order->sales_id;
            $branchId = array_key_exists('branch_id', $fulfillmentData) ? $fulfillmentData['branch_id'] : $order->branch_id;
            $branchName = $fulfillmentData['branch_name'] ?? null;

            if ($branchName && !$branchId) {
                $b = Branch::where('name', $branchName)->first();
                if ($b) {
                    $branchId = $b->id;
                }
            }

            $itemSns = $fulfillmentData['item_sns'] ?? [];
            $soldSerialNumbers = [];

            foreach ($order->items as $item) {
                $pa = ProductAccurate::find($item->product_variant_id);
                $needsSn = (bool) ($pa?->has_sn ?? false);

                $rawSnInput = array_key_exists($item->id, $itemSns)
                    ? (string) $itemSns[$item->id]
                    : (string) ($item->serial_number ?? '');

                $newSns = array_values(array_filter(array_map('trim', explode(',', $rawSnInput))));

                if ($needsSn && count($newSns) < (int) $item->qty) {
                    throw new Exception("Produk '{$item->product_name}' wajib memiliki {$item->qty} nomor IMEI/SN sebelum dikirim/diselesaikan.");
                }

                // Jika sebelumnya ada SN yang di-reserve tapi diganti oleh admin, lepaskan SN lama ke Available
                $oldSns = array_values(array_filter(array_map('trim', explode(',', (string) ($item->serial_number ?? '')))));
                $releasedOldSns = array_diff($oldSns, $newSns);
                if (!empty($releasedOldSns)) {
                    ProductSerialNumber::whereIn('serial_number', $releasedOldSns)
                        ->where('status', 'Reserved')
                        ->update(['status' => 'Available']);
                }

                $joinedNewSn = !empty($newSns) ? implode(', ', $newSns) : null;
                if ($joinedNewSn !== $item->serial_number) {
                    $item->update(['serial_number' => $joinedNewSn]);
                }

                if (!empty($newSns)) {
                    $soldSerialNumbers = array_merge($soldSerialNumbers, $newSns);
                }

                // Tandai reservasi item sebagai FULFILLED dan catat gudang pemenuhan aktual
                $firstSnId = !empty($newSns)
                    ? ProductSerialNumber::where('serial_number', $newSns[0])->value('id')
                    : null;

                OnlineStockReservation::where('order_item_id', $item->id)
                    ->where('status', 'ACTIVE')
                    ->update([
                        'warehouse_id' => $chosenWarehouseId,
                        'product_serial_number_id' => $firstSnId,
                        'status' => 'FULFILLED',
                        'expires_at' => null,
                    ]);
            }

            // Tandai seluruh IMEI aktual yang dikirim menjadi Sold
            if (!empty($soldSerialNumbers)) {
                ProductSerialNumber::whereIn('serial_number', $soldSerialNumbers)
                    ->update([
                        'status' => 'Sold',
                        'warehouse_id' => $chosenWarehouseId,
                    ]);
            }

            // Simpan informasi pengiriman (Kurir & Resi) ke tabel `order_shippings` yang sudah ada
            $courierCompany = trim((string) ($fulfillmentData['courier_company'] ?? ''));
            $courierType = trim((string) ($fulfillmentData['courier_type'] ?? ''));
            $trackingNumber = trim((string) ($fulfillmentData['tracking_number'] ?? ''));

            if ($courierCompany !== '' || $trackingNumber !== '') {
                OrderShipping::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'courier_company' => $courierCompany ?: ($order->shipping?->courier_company ?? 'KURIR_TOKO'),
                        'courier_type' => $courierType ?: ($order->shipping?->courier_type ?? 'REGULER'),
                        'tracking_number' => $trackingNumber ?: $order->shipping?->tracking_number,
                        'shipping_cost' => (float) ($order->shipping_cost ?? 0),
                        'status' => $order->fulfillment_type === 'PICK_UP' ? 'PICKED_UP' : 'SHIPPED',
                    ]
                );
            }

            $defaultFulfillmentStatus = $order->fulfillment_type === 'PICK_UP' ? 'READY_FOR_PICKUP' : 'SHIPPED';
            $targetFulfillmentStatus = strtoupper(trim((string) ($fulfillmentData['fulfillment_status'] ?? $defaultFulfillmentStatus)));

            // Pastikan payment sudah PAID
            $order->payments()
                ->where('status', '!=', 'PAID')
                ->update([
                    'status' => 'PAID',
                    'paid_at' => now(),
                ]);

            $order->update([
                'warehouse_id' => $chosenWarehouseId,
                'branch_id' => $branchId,
                'sales_id' => $salesId,
                'handled_by' => $actor ? $actor->id : $order->handled_by,
                'order_status' => 'COMPLETED',
                'fulfillment_status' => $targetFulfillmentStatus,
                'fulfilled_at' => now(),
                'fulfilled_by' => $actor?->id,
                'payment_expired_at' => null,
            ]);

            // Pastikan poin loyalty sudah tercatat (idempotent)
            try {
                app(LoyaltyService::class)->earnPoints($order);
            } catch (\Throwable $e) {
                Log::warning("Gagal menambahkan poin loyalty saat fulfillment order #{$order->order_number}: " . $e->getMessage());
            }
        });

        // Terbitkan Sales Invoice (SI link SO + IMEI aktual) & Sales Receipt (SR) ke Accurate di background
        SyncOrderToAccurateJob::dispatch($order->id);

        return $order->refresh()->load(['items', 'warehouse', 'pickupWarehouse', 'shipping']);
    }

    /**
     * Membatalkan atau meng-expire pesanan Mobile.
     * Melepaskan `online_stock_reservations` (tanpa mengotak-atik `warehouse_stocks`),
     * memulihkan SN jika ada unit Second spesifik yang sempat di-reserve, dan menutup SO Accurate jika sudah dibuat.
     */
    public function cancelOrExpireOrder(Order $order, string $reason = 'EXPIRED', ?User $actor = null): Order
    {
        if ($order->order_status === 'COMPLETED') {
            throw new Exception("Order #{$order->order_number} sudah selesai (COMPLETED) dan tidak dapat dibatalkan.");
        }

        if ($order->order_status === 'CANCELLED') {
            return $order;
        }

        $soNumberToClose = $order->accurate_so_number;
        $dbSource = $order->businessUnit?->code ?: 'syihab';

        $updated = DB::transaction(function () use ($order, $reason, $actor) {
            $newResStatus = $reason === 'PAYMENT_EXPIRED' ? 'EXPIRED' : 'RELEASED';

            // 1. Lepaskan reservasi kuota stok online
            $reservedSnIds = OnlineStockReservation::where('order_id', $order->id)
                ->where('status', 'ACTIVE')
                ->whereNotNull('product_serial_number_id')
                ->pluck('product_serial_number_id')
                ->all();

            OnlineStockReservation::where('order_id', $order->id)
                ->where('status', 'ACTIVE')
                ->update(['status' => $newResStatus]);

            // 2. Pulihkan status ProductSerialNumber dari 'Reserved' ke 'Available'
            $allSns = [];
            foreach ($order->items as $item) {
                if (!empty($item->serial_number)) {
                    $sns = array_values(array_filter(array_map('trim', explode(',', $item->serial_number))));
                    $allSns = array_merge($allSns, $sns);
                }
            }

            if (!empty($reservedSnIds)) {
                ProductSerialNumber::whereIn('id', $reservedSnIds)
                    ->where('status', 'Reserved')
                    ->update(['status' => 'Available']);
            }
            if (!empty($allSns)) {
                ProductSerialNumber::whereIn('serial_number', $allSns)
                    ->where('status', 'Reserved')
                    ->update(['status' => 'Available']);
            }

            // 3. Update status pembayaran menjadi FAILED/EXPIRED
            $order->payments()
                ->where('status', 'PENDING')
                ->update(['status' => $reason === 'PAYMENT_EXPIRED' ? 'EXPIRED' : 'FAILED']);

            // 4. Update status order menjadi CANCELLED
            $notesAddition = "[Dibatalkan: {$reason}]";
            $order->update([
                'order_status' => 'CANCELLED',
                'fulfillment_status' => 'CANCELLED',
                'notes' => trim(($order->notes ? $order->notes . ' ' : '') . $notesAddition),
            ]);

            // 5. Kembalikan poin loyalty jika pernah menukar atau memperoleh poin
            try {
                app(LoyaltyService::class)->refundPoints($order, $actor);
            } catch (\Throwable $e) {
                Log::warning("Gagal memulihkan poin loyalty untuk order #{$order->order_number}: " . $e->getMessage());
            }

            // 6. Kembalikan kuota voucher jika pernah digunakan
            try {
                app(EcommerceCouponService::class)->rollbackCoupon($order);
            } catch (\Throwable $e) {
                Log::warning("Gagal rollback kupon untuk order #{$order->order_number}: " . $e->getMessage());
            }

            return $order->refresh();
        });

        // Jika order dibatalkan SETELAH SO sempat terbuat di Accurate, tutup SO secara bersih
        if (!empty($soNumberToClose)) {
            try {
                app(AccurateService::class)->closeSalesOrder($soNumberToClose, $dbSource);
            } catch (\Throwable $e) {
                Log::warning("Gagal menutup SO Accurate #{$soNumberToClose} saat cancel Order #{$order->order_number}: " . $e->getMessage());
            }
        }

        return $updated;
    }

    /**
     * Kirim notifikasi Telegram ke webhook Business Unit terkait.
     */
    protected function sendTelegramNotification(Order $order, string $event): void
    {
        try {
            $bu = $order->businessUnit;
            if (!$bu || empty($bu->telegram_approval_webhook)) {
                return;
            }

            $payment = $order->payments()->latest()->first();
            $paymentMethodName = $payment?->paymentMethod?->name ?? 'Transfer Bank Manual';

            $payload = [
                'event' => $event,
                'judul' => "🔔 Bukti Pembayaran Baru (Mobile App)",
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer' => $order->user?->name ?? 'Pelanggan Mobile',
                'customer_phone' => $order->user?->profile?->phone_number ?? '-',
                'grand_total' => 'Rp ' . number_format($order->grand_total, 0, ',', '.'),
                'metode_bayar' => $paymentMethodName,
                'waktu' => now()->format('d M Y H:i'),
                'keterangan' => "Pelanggan telah mengunggah bukti bayar untuk pesanan #{$order->order_number}. Harap diverifikasi di dashboard admin.",
            ];

            Http::timeout(5)->post($bu->telegram_approval_webhook, $payload);
        } catch (Exception $e) {
            Log::warning("Gagal mengirim webhook Telegram Mobile Order #{$order->order_number}: " . $e->getMessage());
        }
    }
}
