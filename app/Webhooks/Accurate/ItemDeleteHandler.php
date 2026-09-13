<?php

namespace App\Webhooks\Accurate;

use App\Models\AccurateWebhookLog;
use App\Models\BusinessUnit;
use App\Models\OrderItem;
use App\Models\ProductAccurate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ItemDeleteHandler implements WebhookHandlerInterface
{
    /**
     * Menangani event webhook penghapusan item dari Accurate
     */
    public function handle(AccurateWebhookLog $log): void
    {
        $payload = $log->payload;
        $dbSource = $log->database_source;

        if (isset($payload['data']) && is_array($payload['data'])) {
            foreach ($payload['data'] as $itemData) {
                $itemNo = $itemData['itemNo'] ?? null;
                if ($itemNo) {
                    $this->deleteItem($itemNo, $dbSource);
                }
            }
        } elseif (isset($payload['itemNo'])) {
            // Skenario payload tunggal langsung tanpa wrapper data
            $this->deleteItem($payload['itemNo'], $dbSource);
        } else {
            Log::warning("ItemDeleteHandler: Payload tidak memiliki itemNo yang valid.", [
                'log_id' => $log->id,
                'payload' => $payload,
            ]);
        }
    }

    /**
     * Menghapus master data ProductAccurate beserta data relasi terkait (Hard Delete)
     */
    public function deleteItem(string $itemNo, ?string $dbSource): bool
    {
        try {
            $buId = null;
            if ($dbSource) {
                $buId = BusinessUnit::where('code', $dbSource)->value('id');
            }

            // 1. Cari ProductAccurate dengan scoping Business Unit / database_source
            $productAccurate = ProductAccurate::where('item_no', $itemNo)
                ->when($buId, function ($q) use ($buId) {
                    $q->where('business_unit_id', $buId);
                }, function ($q) use ($dbSource) {
                    if ($dbSource) {
                        $q->where('database_source', $dbSource);
                    }
                })
                ->first();

            // Fallback: jika belum ketemu, coba cari berdasarkan database_source jika buId tidak match
            if (!$productAccurate && $dbSource) {
                $productAccurate = ProductAccurate::where('item_no', $itemNo)
                    ->where('database_source', $dbSource)
                    ->first();
            }

            if (!$productAccurate) {
                Log::info("ItemDeleteHandler: Item SKU {$itemNo} tidak ditemukan di database lokal untuk BU {$dbSource}. Penghapusan dilewati.");
                return false;
            }

            $itemId = $productAccurate->id;
            $itemName = $productAccurate->name;

            // 2. Safety Guard: Cek apakah item sudah pernah ada di transaksi kasir lokal (order_items)
            $hasLocalTransactions = OrderItem::where('product_variant_type', ProductAccurate::class)
                ->where('product_variant_id', $itemId)
                ->exists();

            if ($hasLocalTransactions) {
                Log::warning("ItemDeleteHandler: Item SKU {$itemNo} (ID: {$itemId}) terdeteksi memiliki riwayat transaksi di tabel order_items lokal. Hard delete dibatalkan demi integritas riwayat penjualan.");
                return false;
            }

            // 3. Eksekusi Hard Delete secara atomik
            DB::transaction(function () use ($productAccurate) {
                // Hapus stok gudang polimorfik terkait
                $productAccurate->warehouseStocks()->delete();

                // Hapus serial number / IMEI terkait yang tersimpan
                $productAccurate->productSerialNumbers()->delete();

                // Hapus relasi varian sekunder / buyback device jika ada
                $productAccurate->secondProductVariants()->delete();
                $productAccurate->productVariants()->delete();
                $productAccurate->buybackDevice()->delete();

                // Hapus data master produk utama
                $productAccurate->delete();
            });

            Log::info("ItemDeleteHandler: Master ProductAccurate SKU {$itemNo} ({$itemName}) [ID: {$itemId}] beserta relasi stoknya berhasil di-hard delete untuk BU: {$dbSource}.");
            return true;
        } catch (\Exception $e) {
            Log::error("ItemDeleteHandler Gagal: Gagal menghapus SKU {$itemNo} untuk BU {$dbSource}. Error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
