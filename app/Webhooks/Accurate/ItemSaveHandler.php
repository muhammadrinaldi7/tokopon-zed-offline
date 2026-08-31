<?php

namespace App\Webhooks\Accurate;

use App\Models\AccurateWebhookLog;
use App\Models\ProductAccurate;
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

        // Mapping charField1 dari Accurate: "Ya" = add-on
        $isAddon = isset($accurateItem['charField1']) && strtolower(trim($accurateItem['charField1'])) === 'ya';

        // Tangkap charField2 untuk field proyek
        $proyek = $accurateItem['charField2'] ?? null;

        // Tangkap data OS dari charField3 (Sesuai konfirmasi)
        $os = $accurateItem['charField3'] ?? null;

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
                    'itemType' => $accurateItem['itemType'] ?? null,
                    'is_addon' => $isAddon,
                    'proyek' => $proyek,
                    'os' => $os,
                    'raw_data' => json_encode($accurateItem),
                ]
            );
            Log::info("Webhook Berhasil: Master ProductAccurate diupdate untuk SKU {$itemNo} | Nama: {$newName}");
        } catch (\Exception $e) {
            Log::error("Webhook Gagal: Gagal update ProductAccurate SKU {$itemNo}. Error: " . $e->getMessage());
        }

        // 4. Sinkronisasi Serial Number JIKA BARANG MEMILIKI SN
        $needsSn = $hasSnAccurate;

        if ($needsSn) {
            try {
                $syncService = app(SerialNumberSyncService::class);
                $syncService->syncFromAccurate($itemNo, $dbSource);
                Log::info("Webhook SN Sync sukses (dari ItemSaveHandler) untuk SKU: {$itemNo}");
            } catch (\Exception $e) {
                Log::error("Webhook SN Sync failed (dari ItemSaveHandler) for SKU {$itemNo}: " . $e->getMessage());
            }
        }

        // 5. Update ke Database POS lokal JIKA BARANG SUDAH DI-GENERATE
        // (Langkah 5 dihapus karena data POS sekarang murni menggunakan ProductAccurate)
    }

    private function handleDeletedItem($itemNo)
    {
        $accurateItem = ProductAccurate::where('item_no', $itemNo)->first();
        if ($accurateItem) {
            $accurateItem->update(['is_active' => false]);
            Log::info("Item Dihapus/Dinonaktifkan di Accurate: SKU {$itemNo}");
        }
    }
}
