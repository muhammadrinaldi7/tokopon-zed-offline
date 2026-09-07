<?php

namespace App\Services;

use App\Models\BusinessUnit;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderAccurateDoc;
use App\Models\OrderPayment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductAccurate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccurateReturnSyncService
{
    protected AccurateService $accurateService;

    public function __construct(AccurateService $accurateService)
    {
        $this->accurateService = $accurateService;
    }

    /**
     * Preview Sales Returns from Accurate Online and check sync status against POS.
     *
     * @param string|null $startDate (Y-m-d)
     * @param string|null $endDate (Y-m-d)
     * @param string $buCode
     * @return array
     */
    public function previewReturns(?string $startDate = null, ?string $endDate = null, string $buCode = 'syihab'): array
    {
        $bu = BusinessUnit::where('code', $buCode)->first();
        if (!$bu) {
            throw new \Exception("Unit Bisnis dengan kode '{$buCode}' tidak ditemukan.");
        }

        $rawReturns = $this->accurateService->getSalesReturns($startDate, $endDate, $buCode);

        $items = [];
        $totalCount = 0;
        $alreadySyncedCount = 0;
        $readyToSyncCount = 0;
        $readyToSyncTotalAmount = 0;

        foreach ($rawReturns as $sr) {
            $totalCount++;
            $srNumber = $sr['number'] ?? '';
            $srId = $sr['id'] ?? null;
            $totalAmount = (float)($sr['totalAmount'] ?? 0);
            $customerName = $sr['customer']['name'] ?? 'Pelanggan Umum';
            $branchName = $sr['branch']['name'] ?? 'Cabang Utama';
            $transDate = $sr['transDate'] ?? '';
            $description = $sr['description'] ?? '';

            // Check if this Sales Return is already registered in POS database
            $existingDoc = OrderAccurateDoc::where('doc_type', 'SALES_RETURN')
                ->where('doc_number', $srNumber)
                ->first();

            $existingOrder = null;
            if ($existingDoc) {
                $existingOrder = $existingDoc->order;
            } else {
                $existingOrder = Order::where('accurate_invoice_no', $srNumber)
                    ->orWhere('order_number', 'RET-ACC-' . $srNumber)
                    ->orWhere('order_number', 'RET-CLM-' . $srNumber)
                    ->orWhere('order_number', 'RET-' . $srNumber)
                    ->first();
            }

            if ($existingOrder) {
                $status = 'ALREADY_SYNCED';
                $posOrderNumber = $existingOrder->order_number;
                $alreadySyncedCount++;
            } else {
                $status = 'READY_TO_SYNC';
                $posOrderNumber = null;
                $readyToSyncCount++;
                $readyToSyncTotalAmount += $totalAmount;
            }

            $items[] = [
                'id' => $srId,
                'number' => $srNumber,
                'trans_date' => $transDate,
                'customer_name' => $customerName,
                'branch_name' => $branchName,
                'total_amount' => $totalAmount,
                'description' => $description,
                'status' => $status,
                'pos_order_number' => $posOrderNumber,
                'raw' => $sr,
            ];
        }

        return [
            'summary' => [
                'business_unit' => $bu->name ?? $buCode,
                'bu_code' => $buCode,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_count' => $totalCount,
                'already_synced_count' => $alreadySyncedCount,
                'ready_to_sync_count' => $readyToSyncCount,
                'ready_to_sync_total_amount' => $readyToSyncTotalAmount,
            ],
            'items' => $items,
        ];
    }

    /**
     * Sync a single Sales Return into POS database safely within an atomic transaction.
     *
     * @param array $srItem (from Accurate or preview)
     * @param string $buCode
     * @param int|null $handledById
     * @return array
     */
    public function syncSingleReturn(array $srItem, string $buCode = 'syihab', ?int $handledById = null): array
    {
        $bu = BusinessUnit::where('code', $buCode)->first();
        if (!$bu) {
            throw new \Exception("Unit Bisnis dengan kode '{$buCode}' tidak ditemukan.");
        }

        $srNumber = $srItem['number'] ?? '';
        $srId = $srItem['id'] ?? null;
        $totalAmount = (float)($srItem['totalAmount'] ?? 0);

        if (empty($srNumber)) {
            throw new \Exception("Nomor Sales Return Accurate tidak valid.");
        }

        // Idempotency Check: Don't duplicate if already synced
        $existingDoc = OrderAccurateDoc::where('doc_type', 'SALES_RETURN')
            ->where('doc_number', $srNumber)
            ->first();

        if ($existingDoc) {
            return [
                'status' => 'skipped',
                'reason' => 'Already synced in OrderAccurateDoc',
                'order_number' => $existingDoc->order?->order_number,
            ];
        }

        $existingOrder = Order::where('accurate_invoice_no', $srNumber)
            ->orWhere('order_number', 'RET-ACC-' . $srNumber)
            ->first();

        if ($existingOrder) {
            // Re-link OrderAccurateDoc if missing
            OrderAccurateDoc::firstOrCreate(
                [
                    'order_id' => $existingOrder->id,
                    'doc_type' => 'SALES_RETURN',
                    'doc_number' => $srNumber,
                ],
                [
                    'accurate_id' => $srId,
                    'amount' => -$totalAmount,
                    'status' => 'SUCCESS',
                ]
            );

            return [
                'status' => 'skipped',
                'reason' => 'Order already exists',
                'order_number' => $existingOrder->order_number,
            ];
        }

        // Fetch complete detail if available and detailItem is missing
        $detail = $srItem;
        if (empty($srItem['detailItem']) && $srId) {
            try {
                $fetched = $this->accurateService->getSalesReturnDetail($srId, $buCode);
                if ($fetched) {
                    $detail = array_merge($srItem, $fetched);
                }
            } catch (\Exception $e) {
                Log::warning("Could not fetch full detail for SR {$srNumber}: " . $e->getMessage());
            }
        }

        // Parse Date
        $transDateStr = $detail['transDate'] ?? now()->format('d/m/Y');
        try {
            $orderDate = Carbon::createFromFormat('d/m/Y', $transDateStr)->startOfDay();
        } catch (\Exception $e) {
            try {
                $orderDate = Carbon::parse($transDateStr)->startOfDay();
            } catch (\Exception $e2) {
                $orderDate = now()->startOfDay();
            }
        }

        // Resolve Branch (must belong to the target business unit)
        $branchName = $detail['branch']['name'] ?? ($detail['branchName'] ?? 'Cabang Utama');
        $branch = Branch::where('business_unit_id', $bu->id)
            ->where('name', 'like', '%' . $branchName . '%')
            ->first()
            ?? Branch::where('business_unit_id', $bu->id)->first()
            ?? Branch::first();

        // Resolve Customer User
        $customerNo = $detail['customer']['customerNo'] ?? ($detail['customerNo'] ?? null);
        $customerName = $detail['customer']['name'] ?? ($detail['customerName'] ?? 'Pelanggan Retur Accurate');
        
        $customerUser = null;
        if ($customerNo) {
            $customerUser = User::where('name', 'like', '%' . $customerName . '%')->first();
        }
        if (!$customerUser) {
            $customerUser = User::find($handledById ?? 1) ?? User::first();
        }

        // Resolve Salesperson (Employe) if provided
        $salespersonNo = $detail['salesman']['no'] ?? ($detail['salesmanNo'] ?? null);
        $salesEmploye = null;
        if ($salespersonNo) {
            $salesEmploye = \App\Models\Employe::where('accurate_employee_id', $salespersonNo)
                ->orWhere('name', 'like', '%' . $salespersonNo . '%')
                ->first();
        }

        $targetOrderNumber = 'RET-ACC-' . $srNumber;

        return DB::transaction(function () use (
            $bu,
            $branch,
            $customerUser,
            $salesEmploye,
            $targetOrderNumber,
            $srNumber,
            $srId,
            $orderDate,
            $totalAmount,
            $handledById,
            $branchName,
            $detail
        ) {
            $description = $detail['description'] ?? 'Retur Penjualan Accurate';

            // 1. Create Offset Order
            $order = Order::create([
                'business_unit_id' => $bu->id,
                'user_id' => $customerUser?->id ?? 1,
                'order_number' => $targetOrderNumber,
                'accurate_invoice_no' => $srNumber,
                'order_date' => $orderDate,
                'total_amount' => -$totalAmount,
                'shipping_cost' => 0,
                'discount_amount' => 0,
                'mdr_percentage' => 0,
                'mdr_amount' => 0,
                'grand_total' => -$totalAmount,
                'order_status' => 'COMPLETED',
                'order_channel' => 'ACCURATE_SYNC',
                'handled_by' => $handledById ?? 1,
                'sales_id' => $salesEmploye?->id,
                'shipping_address_snapshot' => [
                    'type' => 'ACCURATE_RETURN_SYNC',
                    'store' => $branchName,
                    'is_legacy_return' => true,
                    'accurate_sr_id' => $srId,
                    'accurate_sr_number' => $srNumber,
                ],
                'notes' => "[Sync Retur Accurate] " . $description,
                'branch_id' => $branch?->id,
            ]);

            // 2. Create OrderItems
            $detailItems = $detail['detailItem'] ?? [];
            $defaultAccurate = ProductAccurate::first();
            $defaultFallbackType = $defaultAccurate ? ProductAccurate::class : ProductVariant::class;
            $defaultFallbackId = $defaultAccurate ? $defaultAccurate->id : (ProductVariant::first()?->id ?? 1);
            $defaultProductId = $defaultAccurate?->product_id ?? ProductVariant::first()?->product_id;

            if (empty($detailItems)) {
                // Fallback single line item if detail items array is empty
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $defaultProductId,
                    'product_variant_type' => $defaultFallbackType,
                    'product_variant_id' => $defaultFallbackId,
                    'product_name' => "Retur Penjualan: {$srNumber}",
                    'qty' => -1,
                    'price_at_checkout' => $totalAmount,
                    'subtotal' => -$totalAmount,
                    'discount_amount' => 0,
                    'promo_discount_amount' => 0,
                ]);
            } else {
                foreach ($detailItems as $item) {
                    $itemName = $item['item']['name'] ?? ($item['itemName'] ?? ($item['name'] ?? 'Barang Retur'));
                    $itemNo = $item['item']['no'] ?? ($item['itemNo'] ?? ($item['no'] ?? null));
                    $qty = abs((int)($item['quantity'] ?? 1));
                    $unitPrice = (float)($item['unitPrice'] ?? ($item['price'] ?? 0));
                    $itemSubtotal = (float)($item['totalPrice'] ?? ($qty * $unitPrice));
                    if ($itemSubtotal == 0 && $unitPrice > 0) {
                        $itemSubtotal = $qty * $unitPrice;
                    }

                    // Extract Serial Number if present
                    $snList = [];
                    if (!empty($item['detailSerialNumber'])) {
                        foreach ($item['detailSerialNumber'] as $dsn) {
                            if (!empty($dsn['serialNumber'])) {
                                $snList[] = $dsn['serialNumber'];
                            }
                        }
                    } elseif (!empty($item['serialNumber'])) {
                        $snList[] = $item['serialNumber'];
                    }
                    $snString = !empty($snList) ? implode(', ', $snList) : null;

                    // Attempt product/variant matching (read-only lookup)
                    $matchedVariant = null;
                    $variantType = null;
                    $variantId = null;
                    $productId = null;

                    if ($itemNo) {
                        $productAccurate = ProductAccurate::where('no', $itemNo)->first();
                        if ($productAccurate) {
                            $variantType = ProductAccurate::class;
                            $variantId = $productAccurate->id;
                            $productId = $productAccurate->product_id ?? null;
                        } else {
                            $productVariant = ProductVariant::where('sku', $itemNo)->first();
                            if ($productVariant) {
                                $variantType = ProductVariant::class;
                                $variantId = $productVariant->id;
                                $productId = $productVariant->product_id;
                            } else {
                                $product = Product::where('sku', $itemNo)->first();
                                if ($product) {
                                    $productId = $product->id;
                                    $v = $product->variants()->first();
                                    if ($v) {
                                        $variantType = ProductVariant::class;
                                        $variantId = $v->id;
                                    }
                                }
                            }
                        }
                    }

                    // Fallback to default variant if no match found
                    if (!$variantId) {
                        $variantType = $defaultFallbackType;
                        $variantId = $defaultFallbackId;
                        $productId = $defaultProductId;
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $productId,
                        'product_variant_type' => $variantType,
                        'product_variant_id' => $variantId,
                        'product_name' => $itemName,
                        'serial_number' => $snString,
                        'qty' => -$qty,
                        'price_at_checkout' => $unitPrice,
                        'discount_amount' => 0,
                        'promo_discount_amount' => 0,
                        'subtotal' => -$itemSubtotal,
                    ]);
                }
            }

            // 3. Create OrderAccurateDoc
            OrderAccurateDoc::create([
                'order_id' => $order->id,
                'doc_type' => 'SALES_RETURN',
                'doc_number' => $srNumber,
                'accurate_id' => $srId,
                'amount' => -$totalAmount,
                'status' => 'SUCCESS',
            ]);

            // 4. Create OrderPayment (Negative payment for Sales Return)
            $returnPm = PaymentMethod::where('business_unit_id', $bu->id)
                ->where(function ($q) {
                    $q->where('name', 'like', '%Retur%')
                      ->orWhere('name', 'like', '%Return%');
                })->first()
                ?? PaymentMethod::where('business_unit_id', $bu->id)->first()
                ?? PaymentMethod::first();

            OrderPayment::create([
                'order_id' => $order->id,
                'xendit_external_id' => 'RET-PAY-' . date('YmdHis') . rand(1000, 9999),
                'payment_method_id' => $returnPm?->id,
                'amount' => -$totalAmount,
                'status' => 'PAID',
                'paid_at' => $orderDate,
            ]);

            Log::info("Successfully synced Sales Return {$srNumber} to POS Order {$targetOrderNumber}");

            return [
                'status' => 'synced',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => -$totalAmount,
            ];
        });
    }

    /**
     * Sync all pending returns in a date range for a specific business unit.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @param string $buCode
     * @param int|null $handledById
     * @return array
     */
    public function syncAllReturns(?string $startDate = null, ?string $endDate = null, string $buCode = 'syihab', ?int $handledById = null): array
    {
        $preview = $this->previewReturns($startDate, $endDate, $buCode);
        
        $synced = [];
        $skipped = [];
        $failed = [];
        $totalSyncedAmount = 0;

        foreach ($preview['items'] as $item) {
            if ($item['status'] === 'ALREADY_SYNCED') {
                $skipped[] = [
                    'number' => $item['number'],
                    'reason' => 'Already synced (' . ($item['pos_order_number'] ?? 'POS Order') . ')',
                ];
                continue;
            }

            try {
                $result = $this->syncSingleReturn($item['raw'], $buCode, $handledById);
                if ($result['status'] === 'synced') {
                    $synced[] = $result;
                    $totalSyncedAmount += abs($result['total_amount'] ?? 0);
                } else {
                    $skipped[] = [
                        'number' => $item['number'],
                        'reason' => $result['reason'] ?? 'Skipped',
                    ];
                }
            } catch (\Exception $e) {
                Log::error("Failed to sync SR {$item['number']}: " . $e->getMessage());
                $failed[] = [
                    'number' => $item['number'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => true,
            'bu_code' => $buCode,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_found' => count($preview['items']),
            'synced_count' => count($synced),
            'skipped_count' => count($skipped),
            'failed_count' => count($failed),
            'total_synced_amount' => $totalSyncedAmount,
            'synced_items' => $synced,
            'skipped_items' => $skipped,
            'failed_items' => $failed,
        ];
    }
}
