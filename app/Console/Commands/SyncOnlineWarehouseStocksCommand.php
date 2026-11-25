<?php

namespace App\Console\Commands;

use App\Models\ProductAccurate;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\AccurateService;
use App\Services\OnlineStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncOnlineWarehouseStocksCommand extends Command
{
    protected $signature = 'accurate:sync-online-stocks
                            {--bu= : Filter kode Business Unit tertentu (misal: syihab atau second)}
                            {--warehouse= : Filter ID gudang lokal tertentu}';

    protected $description = 'Rekonsiliasi periodik stok gudang online aktif (Delivery/Pick Up) dari Accurate Online ke warehouse_stocks lokal.';

    public function handle(AccurateService $accurateService, OnlineStockService $onlineStockService): int
    {
        // 1. Bersihkan reservasi yang sudah kadaluarsa terlebih dahulu
        $expiredCount = $onlineStockService->releaseExpiredReservations();
        if ($expiredCount > 0) {
            $this->info("Melepaskan {$expiredCount} reservasi online yang kadaluarsa.");
        }

        $query = Warehouse::with('businessUnit')->onlineStore();

        if ($buCode = $this->option('bu')) {
            $query->whereHas('businessUnit', fn($q) => $q->where('code', $buCode));
        }

        if ($whId = $this->option('warehouse')) {
            $query->where('id', (int) $whId);
        }

        $warehouses = $query->get();

        if ($warehouses->isEmpty()) {
            $this->warn('Tidak ada gudang online aktif yang perlu disinkronisasi.');
            return self::SUCCESS;
        }

        $totalUpdated = 0;

        foreach ($warehouses as $warehouse) {
            $bu = $warehouse->businessUnit;
            if (!$bu || empty($bu->accurate_token)) {
                $this->warn("Skip gudang '{$warehouse->name}': kredensial Accurate BU belum dikonfigurasi.");
                continue;
            }

            $dbSource = $bu->code;
            $this->info("Menarik stok gudang '{$warehouse->name}' (BU: {$dbSource}) dari Accurate...");

            try {
                // Bangun map item_no => product_accurate_id untuk BU ini agar cepat (1 query)
                $skuToId = ProductAccurate::where(function ($q) use ($bu, $dbSource) {
                    $q->where('business_unit_id', $bu->id)
                        ->orWhere('database_source', $dbSource);
                })->pluck('id', 'item_no')->toArray();

                $page = 1;
                $warehouseSynced = 0;

                while ($page <= 50) {
                    $rows = $accurateService->getItemStockPerWarehousePage($warehouse->name, $page, 100, $dbSource);
                    if (empty($rows)) {
                        break;
                    }

                    foreach ($rows as $row) {
                        $itemNo = (string) ($row['no'] ?? ($row['itemNo'] ?? ''));
                        if ($itemNo === '' || !isset($skuToId[$itemNo])) {
                            continue;
                        }

                        $qty = (int) round((float) ($row['quantity'] ?? ($row['availableStock'] ?? 0)));

                        WarehouseStock::updateOrCreate(
                            [
                                'warehouse_id' => $warehouse->id,
                                'variant_id' => $skuToId[$itemNo],
                                'variant_type' => ProductAccurate::class,
                            ],
                            [
                                'stock' => max(0, $qty),
                            ]
                        );
                        $warehouseSynced++;
                    }

                    if (count($rows) < 100) {
                        break;
                    }
                    $page++;
                }

                $totalUpdated += $warehouseSynced;
                $this->info("✓ Gudang '{$warehouse->name}': {$warehouseSynced} SKU berhasil disinkronkan.");
            } catch (\Throwable $e) {
                Log::warning("SyncOnlineWarehouseStocksCommand error gudang '{$warehouse->name}' ({$dbSource}): " . $e->getMessage());
                $this->error("✗ Gagal sinkron gudang '{$warehouse->name}': " . $e->getMessage());
            }
        }

        $this->info("Rekonsiliasi selesai. Total SKU diperbarui: {$totalUpdated}.");
        return self::SUCCESS;
    }
}
