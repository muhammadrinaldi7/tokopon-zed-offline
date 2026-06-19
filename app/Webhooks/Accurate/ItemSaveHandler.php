<?php

namespace App\Webhooks\Accurate;

use App\Models\AccurateWebhookLog;
use App\Models\ProductVariant;
use App\Models\SecondProductVariant;
use App\Services\AccurateService;
use App\Services\SerialNumberSyncService;
use Illuminate\Support\Facades\Log;

class ItemSaveHandler implements WebhookHandlerInterface
{
    public function handle(AccurateWebhookLog $log): void
    {
        $payload = $log->payload;
        $dbSource = $log->database_source;

        if (isset($payload['data']) && is_array($payload['data'])) {
            foreach ($payload['data'] as $itemData) {
                $itemNo = $itemData['itemNo'] ?? null;
                $action = $itemData['action'] ?? 'WRITE';

                if ($itemNo) {
                    if ($action === 'WRITE') {
                        // Jika dibuat/diupdate, sinkronisasi detailnya
                        $this->syncItemDetail($itemNo, $dbSource);
                    } elseif ($action === 'DELETE') {
                        // (Opsional) Jika barang dihapus di Accurate, Anda bisa menonaktifkannya di POS
                        $this->handleDeletedItem($itemNo);
                    }
                }
            }
        }
    }
    private function syncItemDetail($itemNo, $dbSource)
    {
        // 1. Tarik detail terbaru dari Accurate
        $service = app(AccurateService::class);
        $accurateItem = $service->itemDetailDo($itemNo, $dbSource);

        if (!$accurateItem) return;

        // 2. Mapping field dari response Accurate
        $newName = $accurateItem['name'];
        $newPrice = (int) ($accurateItem['unitPrice'] ?? 0);
        $newCost = (int) ($accurateItem['balanceUnitCost'] ?? 0);
        $stock = (int) ($accurateItem['availableToSell'] ?? 0);
        $hasSnAccurate = (bool) (isset($accurateItem['manageSN']) && $accurateItem['manageSN'] === true);
        
        // Pengecekan fallback untuk has_sn dari serialNumberType jika manageSN tidak ada
        if (!isset($accurateItem['manageSN']) && isset($accurateItem['serialNumberType'])) {
            $hasSnAccurate = ($accurateItem['serialNumberType'] === 'UNIQUE');
        }

        $idBrand = $accurateItem['itemBrand']['id'] ?? null;
        $brandName = $accurateItem['itemBrand']['name'] ?? null;
        $idCategory = $accurateItem['itemCategory']['id'] ?? null;
        $categoryName = $accurateItem['itemCategory']['name'] ?? null;

        $buId = \App\Models\BusinessUnit::where('code', $dbSource)->value('id');

        // 3. Update Master Data (ProductAccurate) - INI YANG UTAMA
        try {
            $productAccurate = \App\Models\ProductAccurate::updateOrCreate(
                [
                    'accurate_id' => $accurateItem['no'],
                    'database_source' => $dbSource,
                ],
                [
                    'item_no' => $itemNo,
                    'business_unit_id' => $buId,
                    'name' => $newName,
                    'base_price' => $newPrice,
                    'base_cost' => $newCost,
                    'stock' => $stock,
                    'has_sn' => $hasSnAccurate,
                    'id_brand_accurate' => $idBrand,
                    'brandName' => $brandName,
                    'id_category_accurate' => $idCategory,
                    'categoryName' => $categoryName,
                    'raw_data' => json_encode($accurateItem),
                ]
            );
            Log::info("Webhook Berhasil: Master ProductAccurate diupdate untuk SKU {$itemNo} | Nama: {$newName}");
        } catch (\Exception $e) {
            Log::error("Webhook Gagal: Gagal update ProductAccurate SKU {$itemNo}. Error: " . $e->getMessage());
        }

        // 4. Update ke Database POS lokal JIKA BARANG SUDAH DI-GENERATE
        $variant = ProductVariant::where('sku', $itemNo)->first()
            ?? SecondProductVariant::where('sku', $itemNo)->first();

        if ($variant) {
            try {
                // Update harga varian
                $variant->update(['price' => $newPrice]);

                // Update nama di tabel Induk agar seragam dengan Accurate
                if ($variant instanceof ProductVariant && $variant->product) {
                    $variant->product->update(['name' => $newName]);
                } elseif ($variant instanceof SecondProductVariant && $variant->secondProduct) {
                    $variant->secondProduct->update(['name' => $newName]);
                }

                Log::info("Webhook Berhasil: Varian POS SKU {$itemNo} ikut diupdate.");
            } catch (\Exception $e) {
                Log::error("Webhook Gagal: Gagal update varian POS SKU {$itemNo}. Error: " . $e->getMessage());
            }

            // 5. Sync Serial Number Jika Perlu
            $needsSn = false;
            if ($variant instanceof SecondProductVariant) {
                $needsSn = true;
            } elseif ($variant instanceof ProductVariant) {
                $needsSn = (bool) ($variant->has_sn ?? false);
            }

            if ($needsSn) {
                try {
                    $syncService = app(SerialNumberSyncService::class);
                    $syncService->syncFromAccurate($itemNo, $dbSource);
                    Log::info("Webhook SN Sync sukses (dari ItemSaveHandler) untuk SKU: {$itemNo}");
                } catch (\Exception $e) {
                    Log::error("Webhook SN Sync failed (dari ItemSaveHandler) for SKU {$itemNo}: " . $e->getMessage());
                }
            }
        }
    }

    private function handleDeletedItem($itemNo)
    {
        // Contoh penanganan jika barang dihapus dari Accurate
        $variant = ProductVariant::where('sku', $itemNo)->first();
        if ($variant) {
            // $variant->update(['is_active' => false]);
            Log::info("Item Dihapus di Accurate: SKU {$itemNo}");
        }
    }
}
