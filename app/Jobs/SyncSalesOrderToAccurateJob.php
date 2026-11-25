<?php

namespace App\Jobs;

use App\Models\BusinessUnit;
use App\Models\BusinessUnitProject;
use App\Models\Employe;
use App\Models\Order;
use App\Models\OrderAccurateDoc;
use App\Models\ProductAccurate;
use App\Services\AccurateService;
use App\Services\SettingService;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncSalesOrderToAccurateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $orderId;

    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = 15;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Terbitkan Sales Order (SO) ke Accurate Online TANPA mengikat IMEI/Serial Number.
     * Dipicu HANYA setelah pembayaran pesanan Mobile App terverifikasi lunas.
     */
    public function handle(AccurateService $accurateService): void
    {
        $order = Order::with([
            'items.variant',
            'user',
            'businessUnit',
            'branch',
            'salesBy',
            'warehouse',
            'pickupWarehouse',
        ])->find($this->orderId);

        if (!$order) {
            Log::channel('pos_accurate')->warning("SyncSalesOrderToAccurateJob: Order #{$this->orderId} tidak ditemukan.");
            return;
        }

        // Idempotent: jika SO sudah pernah terbuat di Accurate, skip
        if (!empty($order->accurate_so_number)) {
            Log::channel('pos_accurate')->info("SyncSalesOrderToAccurateJob: Order #{$order->order_number} sudah memiliki SO #{$order->accurate_so_number}.");
            return;
        }

        // Hanya proses pesanan yang sudah dibayar / dikonfirmasi (tidak untuk WAITING_PAYMENT / CANCELLED)
        if (!in_array($order->order_status, ['PROCESSING', 'COMPLETED', 'SHIPPED', 'PAID'], true)) {
            Log::channel('pos_accurate')->info("SyncSalesOrderToAccurateJob: Order #{$order->order_number} berstatus {$order->order_status}, skip pembuatan SO.");
            return;
        }

        $bu = $order->businessUnit ?: BusinessUnit::find($order->business_unit_id);
        if (!$bu || empty($bu->accurate_token)) {
            Log::channel('pos_accurate')->warning("SyncSalesOrderToAccurateJob: Kredensial Accurate BU tidak tersedia untuk Order #{$order->order_number}.");
            return;
        }

        $dbSource = $bu->code ?: 'syihab';

        // 1. Sync Customer ke Accurate
        $customerUser = $order->user;
        if ($customerUser) {
            try {
                $accurateService->syncCustomer($customerUser, $dbSource);
                $customerUser->refresh();
            } catch (Exception $e) {
                Log::channel('pos_accurate')->warning("SyncSalesOrderToAccurateJob: Gagal sync customer Order #{$order->order_number}: " . $e->getMessage());
            }
        }

        $customerNo = $customerUser
            ? ($customerUser->getAccurateCustomerNo($dbSource) ?: 'PELANGGAN-UMUM')
            : 'PELANGGAN-UMUM';

        // 2. Tentukan Cabang Header Accurate
        $settingService = app(SettingService::class);
        $defaultBranch = $settingService->get(
            "ecommerce_branch_{$bu->id}",
            $settingService->get('ecommerce_default_branch_name', 'Banjarbaru')
        );
        $rawBranchName = $order->branch?->name ?: $defaultBranch;
        $accurateBranchName = $bu->resolveAccurateBranchName($rawBranchName);

        // 3. Salesman
        $detailSalesman = [];
        $salesId = $order->sales_id ?: $order->salesBy?->id;
        if ($salesId) {
            $sales = Employe::find($salesId);
            if ($sales && !empty($sales->employee_no)) {
                $detailSalesman[] = (string) $sales->employee_no;
            }
        }

        // 4. Rakit detailItem TANPA detailSerialNumber (SO berbasis Qty saja!)
        $detailItems = [];
        foreach ($order->items as $item) {
            $sku = 'ITEM-UNKNOWN';
            $projectNo = '';

            $pa = ProductAccurate::find($item->product_variant_id);
            if ($pa) {
                $sku = $pa->item_no;
                $projectNo = BusinessUnitProject::getProjectNoByBusinessUnit($bu->id, $pa->proyek, $pa->proyek ?? '');
            } elseif ($item->variant && !empty($item->variant->item_no)) {
                $sku = $item->variant->item_no;
            }

            $itemRow = [
                'itemNo' => $sku,
                'unitPrice' => (float) $item->price_at_checkout,
                'quantity' => (float) $item->qty,
                'itemCashDiscount' => (float) (($item->discount_amount ?? 0) + ($item->promo_discount_amount ?? 0)),
            ];

            if (!empty($detailSalesman)) {
                $itemRow['salesmanListNumber'] = $detailSalesman;
            }
            if (!empty($projectNo)) {
                $itemRow['projectNo'] = $projectNo;
            }

            // Sengaja TIDAK mengirim detailSerialNumber di Sales Order!
            // IMEI fisik akan dikirim saat Sales Invoice (SI) diterbitkan pada tahap Fulfillment.
            $detailItems[] = $itemRow;
        }

        $isTaxable = (bool) ($bu->is_taxable ?? false);
        $fulfillmentLabel = $order->fulfillment_type === 'PICK_UP'
            ? 'PICK UP (' . ($order->pickupWarehouse?->name ?? 'Toko') . ')'
            : 'DELIVERY';

        $soData = [
            'customerNo' => $customerNo,
            'branchName' => $accurateBranchName,
            'transDate' => $order->order_date ? Carbon::parse($order->order_date)->format('d/m/Y') : now()->format('d/m/Y'),
            'detailItem' => $detailItems,
            'inclusiveTax' => $isTaxable,
            'taxable' => $isTaxable,
            'useTax1' => $isTaxable,
            'description' => "SO Mobile App #{$order->order_number} [{$fulfillmentLabel}]" . ($order->notes ? " - {$order->notes}" : ''),
        ];

        $headerDiscount = (float) (($order->coupon_discount_amount ?? 0) + ($order->loyalty_discount_amount ?? 0));
        if ($headerDiscount > 0) {
            $soData['cashDiscount'] = $headerDiscount;
        }

        Log::channel('pos_accurate')->info("SyncSalesOrderToAccurateJob Payload Order #{$order->order_number}: " . json_encode($soData));

        $soResult = $accurateService->postSalesOrder($soData, $dbSource);

        $soNumber = $soResult['r']['number'] ?? null;
        $soId = $soResult['r']['id'] ?? null;

        if ($soNumber) {
            $order->update([
                'accurate_so_id' => $soId,
                'accurate_so_number' => $soNumber,
            ]);

            OrderAccurateDoc::create([
                'order_id' => $order->id,
                'doc_type' => 'SALES_ORDER',
                'doc_number' => $soNumber,
                'accurate_id' => $soId,
                'amount' => $order->grand_total,
                'status' => 'SUCCESS',
                'payload' => $soData,
            ]);

            Log::channel('pos_accurate')->info("SyncSalesOrderToAccurateJob SUKSES Order #{$order->order_number} -> SO #{$soNumber}");
        }
    }
}
