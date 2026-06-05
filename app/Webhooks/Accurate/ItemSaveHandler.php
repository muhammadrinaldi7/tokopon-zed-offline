<?php

namespace App\Webhooks\Accurate;

use App\Models\AccurateWebhookLog;
use App\Models\ProductVariant;
use App\Models\SecondProductVariant;
use App\Services\AccurateService;
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
        // Cari varian di database lokal
        $variant = ProductVariant::where('sku', $itemNo)->first()
            ?? SecondProductVariant::where('sku', $itemNo)->first();

        // Jika barang tidak ada di POS lokal, abaikan (mungkin itu aset internal, bukan barang jualan)
        if (!$variant) return;

        // Tarik harga dan nama terbaru dari Accurate
        $service = app(AccurateService::class);
        $accurateItem = $service->itemDetailDo($itemNo);

        if ($accurateItem) {
            // Mapping field dari response Accurate
            $newName = $accurateItem['name'];
            $newPrice = (int) $accurateItem['unitPrice']; // unitPrice adalah harga jual standar di Accurate

            try {
                // Update ke database lokal Laravel (Tabel Varian)
                $variant->update([
                    'price' => $newPrice,
                ]);

                // Update base_price di tabel Induk (Product / SecondProduct)
                if ($variant->product) {
                    $variant->accurateData->update([
                        'base_price' => $newPrice
                    ]);
                }

                Log::info("Webhook Berhasil: Item Updated via Webhook: SKU {$itemNo} | Harga Jual: {$newPrice} | Harga Modal (base_price): {$newPrice}");
            } catch (\Exception $e) {
                Log::error("Webhook Gagal: Gagal update harga SKU {$itemNo}. Error: " . $e->getMessage());
            }

            // --- TAMBAHAN UNTUK STOK AWAL & SN ---
            // Jika user mengisi Stok Awal dan SN saat membuat/mengedit barang di Accurate,
            // Accurate HANYA mengirimkan webhook ITEM (bukan INVENTORY_ADJUSTMENT).
            // Jadi kita harus memaksa pengecekan SN di sini juga!
            $hasSn = false;
            if ($variant instanceof SecondProductVariant) {
                $hasSn = true;
            } elseif ($variant instanceof ProductVariant) {
                $hasSn = (bool) ($variant->has_sn ?? false);
            }

            if ($hasSn) {
                try {
                    $syncService = app(\App\Services\SerialNumberSyncService::class);
                    $syncService->syncFromAccurate($itemNo);
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
