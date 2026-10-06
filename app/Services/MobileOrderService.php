<?php

namespace App\Services;

use App\Jobs\SyncOrderToAccurateJob;
use App\Models\BusinessUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\ProductAccurate;
use App\Models\ProductSerialNumber;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MobileOrderService
{
    /**
     * Membuat order baru dari checkout Mobile App dengan penguncian stok atomik (lockForUpdate).
     *
     * @param array $data
     * @return Order
     * @throws Exception
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

        $businessUnitId = $data['business_unit_id'] ?? null;
        $warehouseId = $data['warehouse_id'] ?? null;

        // 1. Tentukan Gudang Online Store yang valid
        $warehouse = null;
        if ($warehouseId) {
            $warehouse = Warehouse::where('id', $warehouseId)->where('is_online_store', true)->first();
        }

        if (!$warehouse && $businessUnitId) {
            $warehouse = Warehouse::where('is_online_store', true)
                ->where('business_unit_id', $businessUnitId)
                ->first();
        }

        if (!$warehouse) {
            $warehouse = Warehouse::where('is_online_store', true)->first();
        }

        if (!$warehouse) {
            throw new Exception("Tidak ada gudang online store (is_online_store) yang aktif untuk melayani pesanan mobile.");
        }

        // Jika BU belum ditentukan, gunakan BU milik gudang online
        if (!$businessUnitId) {
            $businessUnitId = $warehouse->business_unit_id ?? 1;
        }

        $expiryMinutes = (int) ($data['expiry_minutes'] ?? 60);

        return DB::transaction(function () use ($data, $userId, $businessUnitId, $warehouse, $items, $expiryMinutes) {
            $subtotal = 0;
            $totalDiscount = 0;
            $orderItemsToCreate = [];

            // 2. Kunci & Validasi Stok setiap item
            foreach ($items as $item) {
                $productAccurateId = $item['product_accurate_id'] ?? null;
                $qty = (int) ($item['qty'] ?? 1);
                if ($qty <= 0) {
                    throw new Exception("Jumlah item harus lebih dari 0.");
                }

                $product = ProductAccurate::findOrFail($productAccurateId);
                $price = isset($item['price']) ? (float) $item['price'] : (float) $product->base_price;
                $discount = (float) ($item['discount_amount'] ?? 0);

                // Kunci aggregate stock di warehouse_stocks
                $stockRecord = WarehouseStock::where('warehouse_id', $warehouse->id)
                    ->where('variant_id', $product->id)
                    ->whereIn('variant_type', [ProductAccurate::class, 'App\\Models\\ProductAccurate', 'ProductAccurate'])
                    ->lockForUpdate()
                    ->first();

                $availableStock = $stockRecord ? $stockRecord->stock : 0;
                if ($availableStock < $qty) {
                    throw new Exception("Stok untuk produk '{$product->name}' tidak mencukupi (sisa: {$availableStock}, diminta: {$qty}).");
                }

                // Pengurangan stok agregat
                $stockRecord->decrement('stock', $qty);

                // Reservasi Serial Number jika produk memiliki SN/IMEI
                $cleanSns = [];
                if ($product->has_sn) {
                    $requestedSns = $item['serial_numbers'] ?? [];

                    if (!empty($requestedSns)) {
                        $cleanRequested = array_values(array_filter(array_map('trim', (array) $requestedSns)));
                        $lockedSns = ProductSerialNumber::where('warehouse_id', $warehouse->id)
                            ->where('product_accurate_id', $product->id)
                            ->whereIn('serial_number', $cleanRequested)
                            ->where('status', 'Available')
                            ->lockForUpdate()
                            ->get();

                        if ($lockedSns->count() < count($cleanRequested)) {
                            throw new Exception("Satu atau lebih IMEI/Serial Number yang dipilih untuk '{$product->name}' sudah tidak tersedia.");
                        }

                        ProductSerialNumber::whereIn('id', $lockedSns->pluck('id'))->update(['status' => 'Reserved']);
                        $cleanSns = $lockedSns->pluck('serial_number')->toArray();
                    } else {
                        // Auto-assign SN Available
                        $lockedSns = ProductSerialNumber::where('warehouse_id', $warehouse->id)
                            ->where('product_accurate_id', $product->id)
                            ->where('status', 'Available')
                            ->limit($qty)
                            ->lockForUpdate()
                            ->get();

                        if ($lockedSns->count() < $qty) {
                            throw new Exception("Unit nomor seri (IMEI/SN) berstatus 'Available' untuk '{$product->name}' tidak mencukupi.");
                        }

                        ProductSerialNumber::whereIn('id', $lockedSns->pluck('id'))->update(['status' => 'Reserved']);
                        $cleanSns = $lockedSns->pluck('serial_number')->toArray();
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
            }

            // 3. Generate Order Number
            $dateNow = now();
            $orderNumber = 'MOB-' . $dateNow->format('Ymd') . '-' . mt_rand(1000, 9999) . '-' . str_pad(
                Order::whereDate('order_date', $dateNow->format('Y-m-d'))
                    ->where('order_channel', 'MOBILE_APP')
                    ->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );

            $shippingCost = (float) ($data['shipping_cost'] ?? 0);
            $grandTotal = max(0, $subtotal - $totalDiscount) + $shippingCost;

            $shippingSnapshot = $data['shipping_address_snapshot'] ?? [
                'type' => 'ONLINE_DELIVERY',
                'store' => $warehouse->name,
                'customer_name' => User::find($userId)?->name ?? 'Customer',
            ];

            // 4. Create Order
            $order = Order::create([
                'business_unit_id' => $businessUnitId,
                'user_id' => $userId,
                'warehouse_id' => $warehouse->id,
                'order_number' => $orderNumber,
                'order_date' => $dateNow->format('Y-m-d'),
                'total_amount' => $subtotal,
                'shipping_cost' => $shippingCost,
                'discount_amount' => $totalDiscount,
                'grand_total' => $grandTotal,
                'order_status' => 'WAITING_PAYMENT',
                'order_channel' => 'MOBILE_APP',
                'payment_expired_at' => now()->addMinutes($expiryMinutes),
                'shipping_address_snapshot' => $shippingSnapshot,
                'notes' => $data['notes'] ?? null,
            ]);

            // 5. Create Order Items
            foreach ($orderItemsToCreate as $itemData) {
                $itemData['order_id'] = $order->id;
                OrderItem::create($itemData);
            }

            // 6. Create Initial Pending Payment Record
            OrderPayment::create([
                'order_id' => $order->id,
                'xendit_external_id' => 'ORD-MOB-' . date('YmdHis') . rand(1000, 9999),
                'amount' => $grandTotal,
                'status' => 'PENDING',
                'payment_method_id' => $data['payment_method_id'] ?? null,
            ]);

            return $order->load(['items', 'payments', 'warehouse']);
        });
    }

    /**
     * Upload bukti transfer pembayaran manual oleh pelanggan via Spatie MediaLibrary.
     * Mengubah status order menjadi WAITING_VERIFICATION dan mengosongkan payment_expired_at.
     *
     * @param Order $order
     * @param UploadedFile|string $file
     * @param int|null $paymentMethodId
     * @return Order
     * @throws Exception
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

        // Lampirkan bukti transfer ke Spatie MediaLibrary
        if ($file instanceof UploadedFile) {
            $payment->addMedia($file)->toMediaCollection('payment_proof');
        } elseif (is_string($file) && file_exists($file)) {
            $payment->addMedia($file)->toMediaCollection('payment_proof');
        }

        // Hapus timer expire agar order tidak hangus saat menunggu verifikasi admin/CS
        $order->update([
            'order_status' => 'WAITING_VERIFICATION',
            'payment_expired_at' => null,
        ]);

        // Kirim notifikasi webhook Telegram jika dikonfigurasi
        $this->sendTelegramNotification($order, 'WAITING_VERIFICATION');

        return $order->refresh()->load(['payments.media']);
    }

    /**
     * Verifikasi dan setujui pembayaran (oleh Admin Manual atau Webhook Xendit).
     * Mengubah order menjadi COMPLETED, SN menjadi Sold, dan men-dispatch Accurate Queue Job.
     *
     * @param Order $order
     * @param User|null $actor
     * @return Order
     * @throws Exception
     */
    public function approvePayment(Order $order, ?User $actor = null): Order
    {
        if ($order->order_status === 'COMPLETED') {
            return $order;
        }

        DB::transaction(function () use ($order, $actor) {
            // 1. Update Order Status
            $order->update([
                'order_status' => 'COMPLETED',
                'handled_by' => $actor ? $actor->id : $order->handled_by,
                'payment_expired_at' => null,
            ]);

            // 2. Update Order Payments status PAID
            $order->payments()
                ->where('status', '!=', 'PAID')
                ->update([
                    'status' => 'PAID',
                    'paid_at' => now(),
                ]);

            // 3. Update status Serial Number dari 'Reserved' menjadi 'Sold'
            $orderItems = $order->items;
            $allSns = [];
            foreach ($orderItems as $item) {
                if (!empty($item->serial_number)) {
                    $sns = array_values(array_filter(array_map('trim', explode(',', $item->serial_number))));
                    $allSns = array_merge($allSns, $sns);
                }
            }

            if (!empty($allSns)) {
                ProductSerialNumber::whereIn('serial_number', $allSns)
                    ->where('status', 'Reserved')
                    ->update(['status' => 'Sold']);
            }
        });

        // 4. Dispatch Queue Job untuk sinkronisasi ke Accurate Online di background
        SyncOrderToAccurateJob::dispatch($order->id);

        return $order->refresh();
    }

    /**
     * Membatalkan atau meng-expire pesanan Mobile.
     * Mengembalikan kuota warehouse_stocks dan memulihkan status ProductSerialNumber ke 'Available'.
     *
     * @param Order $order
     * @param string $reason
     * @return Order
     * @throws Exception
     */
    public function cancelOrExpireOrder(Order $order, string $reason = 'EXPIRED'): Order
    {
        if ($order->order_status === 'COMPLETED') {
            throw new Exception("Order #{$order->order_number} sudah selesai (COMPLETED) dan tidak dapat dibatalkan.");
        }

        if ($order->order_status === 'CANCELLED') {
            return $order;
        }

        return DB::transaction(function () use ($order, $reason) {
            $warehouseId = $order->warehouse_id;

            // 1. Kembalikan stok agregat di warehouse_stocks
            foreach ($order->items as $item) {
                if ($warehouseId) {
                    $stockRecord = WarehouseStock::where('warehouse_id', $warehouseId)
                        ->where('variant_id', $item->product_variant_id)
                        ->whereIn('variant_type', [ProductAccurate::class, 'App\\Models\\ProductAccurate', 'ProductAccurate'])
                        ->first();

                    if ($stockRecord) {
                        $stockRecord->increment('stock', (int) $item->qty);
                    }
                }
            }

            // 2. Pulihkan status ProductSerialNumber dari 'Reserved' ke 'Available'
            $allSns = [];
            foreach ($order->items as $item) {
                if (!empty($item->serial_number)) {
                    $sns = array_values(array_filter(array_map('trim', explode(',', $item->serial_number))));
                    $allSns = array_merge($allSns, $sns);
                }
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
                'notes' => trim(($order->notes ? $order->notes . ' ' : '') . $notesAddition),
            ]);

            return $order->refresh();
        });
    }

    /**
     * Kirim notifikasi Telegram ke webhook Business Unit terkait.
     *
     * @param Order $order
     * @param string $event
     * @return void
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
