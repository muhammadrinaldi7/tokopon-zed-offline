<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\ProductVariant;
use App\Models\SecondProductVariant;
use App\Models\WarehouseStock;
use App\Services\AccurateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccurateWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Validasi Token Verifikasi
        $token = $request->header('X-Accurate-Notification-Token');
        if ($token && $token !== env('ACCURATE_WEBHOOK_TOKEN')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        Log::info('Accurate Webhook Received: ', $payload);

        $eventType = $payload['event'] ?? ''; // Contoh: 'ITEM_SAVE', 'INVENTORY_TRANSFER_SAVE', dll.
        $dbSource = $payload['database_source'] ?? 'syihab'; // Menangani multi-db ('syihab'/'second')

        // 2. Proses Berdasarkan Tipe Event
        switch ($eventType) {
            case 'ITEM_SAVE':
            case 'INVENTORY_ADJUSTMENT_SAVE':
            case 'INVENTORY_TRANSFER_SAVE':
            case 'PURCHASE_INVOICE_SAVE':
            case 'SALES_INVOICE_SAVE':
                $itemNo = $payload['item_no'] ?? ($payload['itemNo'] ?? ($payload['no'] ?? null));
                if ($itemNo) {
                    $this->syncItemStockFromAccurate($itemNo, $dbSource);
                }
                break;
        }

        return response()->json(['status' => 'processed']);
    }

    private function syncItemStockFromAccurate($itemNo, $dbSource)
    {
        try {
            $service = app(AccurateService::class);
            $stockData = $service->getItemStockPerWarehouse($itemNo, $dbSource);

            foreach ($stockData as $stockItem) {
                $warehouseName = $stockItem['warehouseName'] ?? ($stockItem['warehouse']['name'] ?? null);
                $qty = $stockItem['quantity'] ?? ($stockItem['qty'] ?? 0);

                if (!$warehouseName) continue;

                $warehouse = Warehouse::where('name', $warehouseName)->first();
                if (!$warehouse) continue;

                $variant = ProductVariant::where('sku', $itemNo)->first() 
                    ?? SecondProductVariant::where('sku', $itemNo)->first();

                if ($variant) {
                    WarehouseStock::updateOrCreate(
                        [
                            'warehouse_id' => $warehouse->id,
                            'variant_id' => $variant->id,
                            'variant_type' => get_class($variant),
                        ],
                        [
                            'stock' => $qty
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync stock on webhook for item $itemNo: " . $e->getMessage());
        }
    }
}
