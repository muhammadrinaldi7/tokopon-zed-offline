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
                // Update ke database lokal Laravel
                $variant->update([
                    'price' => $newPrice,
                    // Jika struktur POS Anda menyimpan nama di level parent (Product), 
                    // Anda mungkin perlu meng-update $variant->product->update(['name' => $newName])
                ]);
                Log::info("Webhook Berhasil: Item Updated via Webhook: SKU {$itemNo} | Harga Baru: {$newPrice}");
            } catch (\Exception $e) {
                Log::error("Webhook Gagal: Gagal update harga SKU {$itemNo}. Error: " . $e->getMessage());
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
