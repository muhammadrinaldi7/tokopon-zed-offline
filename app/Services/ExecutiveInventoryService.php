<?php

namespace App\Services;

use App\Models\BusinessUnit;
use App\Models\ProductAccurate;
use App\Models\ProductSerialNumber;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExecutiveInventoryService
{
    /**
     * Search products, prices, and physical available stock across branches.
     */
    public function searchInventory(string $keyword, ?int $businessUnitId = null, ?string $branchName = null, int $limit = 6): array
    {
        $cleanKeyword = trim($keyword);
        if (empty($cleanKeyword)) {
            return [];
        }

        // Clean up common query words
        $stopWords = ['stok', 'stock', 'ada', 'di', 'cabang', 'mana', 'harga', 'berapa', 'apakah', 'tersedia', 'unit', 'cek', 'info', 'hp', 'handphone', 'toko', 'lokasi'];
        $words = array_filter(explode(' ', strtolower($cleanKeyword)), function ($w) use ($stopWords) {
            return !in_array($w, $stopWords) && strlen($w) > 1;
        });

        // Auto-detect business unit from keyword if not explicitly passed
        if (!$businessUnitId) {
            if (preg_match('/\b(second|bekas|2nd|gsk)\b/i', $cleanKeyword)) {
                $businessUnitId = 2; // GSK Second
            } elseif (preg_match('/\b(baru|new|resmi|syihab)\b/i', $cleanKeyword)) {
                $businessUnitId = 1; // Syihab Baru
            }
        }

        try {
            $query = DB::table('product_accurates')
                ->when($businessUnitId, function ($q) use ($businessUnitId) {
                    $q->where('product_accurates.business_unit_id', $businessUnitId);
                });

            if (!empty($words)) {
                $query->where(function ($q) use ($words) {
                    foreach ($words as $w) {
                        $q->where('product_accurates.name', 'like', "%{$w}%");
                    }
                });
            } else {
                $query->where('product_accurates.name', 'like', "%{$cleanKeyword}%");
            }

            // Exclude test / placeholder products
            $query->where('product_accurates.name', 'not like', 'ADD ON%')
                ->where('product_accurates.name', 'not like', 'TES BARANG%')
                ->where('product_accurates.name', 'not like', 'ITEM TES%');

            $products = $query->orderBy('product_accurates.base_price', 'desc')
                ->take($limit)
                ->get();

            if ($products->isEmpty()) {
                // Relax search with OR if AND yielded 0 results
                if (count($words) > 1) {
                    $queryOr = DB::table('product_accurates')
                        ->when($businessUnitId, fn($q) => $q->where('product_accurates.business_unit_id', $businessUnitId))
                        ->where(function ($q) use ($words) {
                            foreach ($words as $w) {
                                if (strlen($w) >= 3) {
                                    $q->orWhere('product_accurates.name', 'like', "%{$w}%");
                                }
                            }
                        })
                        ->where('product_accurates.name', 'not like', 'ADD ON%')
                        ->take($limit)
                        ->get();
                    $products = $queryOr;
                }
            }

            $results = [];

            foreach ($products as $prod) {
                $buId = (int)$prod->business_unit_id;
                $buName = match ($buId) {
                    1 => 'Syihab (HP Baru)',
                    2 => 'GSK Second (HP Second)',
                    3 => 'GSK Distri (Distribusi)',
                    default => 'Lainnya'
                };

                // Query physical serial numbers with status 'Available'
                $snQuery = DB::table('product_serial_numbers')
                    ->join('warehouses', 'product_serial_numbers.warehouse_id', '=', 'warehouses.id')
                    ->where('product_serial_numbers.item_no', $prod->item_no)
                    ->where('product_serial_numbers.status', 'Available');

                if ($branchName) {
                    $snQuery->where('warehouses.name', 'like', "%{$branchName}%");
                }

                $branchStock = $snQuery->select('warehouses.name as branch_name', DB::raw('count(*) as qty'))
                    ->groupBy('warehouses.name')
                    ->get();

                $totalAvailable = $branchStock->sum('qty');

                // Get a few sample serial numbers
                $sampleSns = DB::table('product_serial_numbers')
                    ->where('item_no', $prod->item_no)
                    ->where('status', 'Available')
                    ->take(3)
                    ->pluck('serial_number')
                    ->toArray();

                $results[] = [
                    'sku' => $prod->item_no,
                    'name' => $prod->name,
                    'business_unit_id' => $buId,
                    'business_unit_name' => $buName,
                    'selling_price' => (float)$prod->base_price,
                    'cost_price' => (float)$prod->base_cost,
                    'stock_accurate' => (float)$prod->stock,
                    'total_available_physical' => $totalAvailable,
                    'branches' => $branchStock->pluck('qty', 'branch_name')->toArray(),
                    'sample_serials' => $sampleSns,
                ];
            }

            return $results;
        } catch (\Throwable $e) {
            Log::error('ExecutiveInventoryService searchInventory error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Track a specific physical unit by IMEI or Serial Number.
     */
    public function trackSerialNumber(string $serialNumber): ?array
    {
        $sn = trim($serialNumber);
        if (empty($sn)) {
            return null;
        }

        try {
            $item = DB::table('product_serial_numbers')
                ->leftJoin('warehouses', 'product_serial_numbers.warehouse_id', '=', 'warehouses.id')
                ->leftJoin('product_accurates', 'product_serial_numbers.item_no', '=', 'product_accurates.item_no')
                ->where('product_serial_numbers.serial_number', $sn)
                ->first([
                    'product_serial_numbers.*',
                    'product_accurates.name as product_name',
                    'product_accurates.base_price',
                    'warehouses.name as warehouse_name'
                ]);

            if (!$item) {
                // Try like search
                $item = DB::table('product_serial_numbers')
                    ->leftJoin('warehouses', 'product_serial_numbers.warehouse_id', '=', 'warehouses.id')
                    ->leftJoin('product_accurates', 'product_serial_numbers.item_no', '=', 'product_accurates.item_no')
                    ->where('product_serial_numbers.serial_number', 'like', "%{$sn}%")
                    ->first([
                        'product_serial_numbers.*',
                        'product_accurates.name as product_name',
                        'product_accurates.base_price',
                        'warehouses.name as warehouse_name'
                    ]);
            }

            if (!$item) {
                return null;
            }

            $buId = (int)$item->business_unit_id;
            $buName = match ($buId) {
                1 => 'Syihab (HP Baru)',
                2 => 'GSK Second (HP Second)',
                3 => 'GSK Distri (Distribusi)',
                default => 'Lainnya'
            };

            $daysInStock = null;
            if ($item->receipt_date) {
                $daysInStock = Carbon::parse($item->receipt_date)->diffInDays(now());
            } elseif ($item->created_at) {
                $daysInStock = Carbon::parse($item->created_at)->diffInDays(now());
            }

            $history = null;
            if (strtolower($item->status) !== 'available') {
                $orderItem = DB::table('order_items')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->leftJoin('users as cashier', 'orders.handled_by', '=', 'cashier.id')
                    ->leftJoin('users as sales', 'orders.sales_by', '=', 'sales.id')
                    ->leftJoin('users as customer', 'orders.user_id', '=', 'customer.id')
                    ->where('order_items.serial_number', 'like', "%{$item->serial_number}%")
                    ->first([
                        'orders.order_number',
                        'orders.order_date',
                        'orders.grand_total',
                        'orders.order_status',
                        'cashier.name as cashier_name',
                        'sales.name as sales_name',
                        'customer.name as customer_name',
                    ]);

                if ($orderItem) {
                    $history = [
                        'order_number' => $orderItem->order_number,
                        'order_date' => $orderItem->order_date,
                        'grand_total' => (float)$orderItem->grand_total,
                        'order_status' => $orderItem->order_status,
                        'cashier' => $orderItem->cashier_name ?? '-',
                        'sales' => $orderItem->sales_name ?? '-',
                        'customer' => $orderItem->customer_name ?? 'Walk-in Customer',
                    ];
                }
            }

            return [
                'serial_number' => $item->serial_number,
                'sku' => $item->item_no,
                'product_name' => $item->product_name ?? 'Produk Tidak Terdaftar',
                'business_unit_id' => $buId,
                'business_unit_name' => $buName,
                'status' => $item->status,
                'location_branch' => $item->warehouse_name ?? 'Lokasi Tidak Diketahui',
                'selling_price' => (float)($item->base_price ?? 0),
                'cost_price' => (float)($item->hpp ?? 0),
                'receipt_date' => $item->receipt_date,
                'days_in_warehouse' => $daysInStock,
                'qc_status' => $item->qc_status ?? 'Standard',
                'sales_history' => $history,
            ];
        } catch (\Throwable $e) {
            Log::error('ExecutiveInventoryService trackSerialNumber error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get dead stock / aging stock alerts (units available > X days without being sold).
     */
    public function getDeadStockAlerts(?int $businessUnitId = 2, int $daysThreshold = 30, int $limit = 8): array
    {
        try {
            $cutoffDate = now()->subDays($daysThreshold);

            $items = DB::table('product_serial_numbers')
                ->join('warehouses', 'product_serial_numbers.warehouse_id', '=', 'warehouses.id')
                ->join('product_accurates', 'product_serial_numbers.item_no', '=', 'product_accurates.item_no')
                ->where('product_serial_numbers.status', 'Available')
                ->when($businessUnitId, fn($q) => $q->where('product_serial_numbers.business_unit_id', $businessUnitId))
                ->where(function ($q) use ($cutoffDate) {
                    $q->where('product_serial_numbers.receipt_date', '<=', $cutoffDate)
                        ->orWhere(function ($q2) use ($cutoffDate) {
                            $q2->whereNull('product_serial_numbers.receipt_date')
                                ->where('product_serial_numbers.created_at', '<=', $cutoffDate);
                        });
                })
                ->select([
                    'product_accurates.name as product_name',
                    'product_accurates.base_price',
                    'product_serial_numbers.hpp',
                    'warehouses.name as warehouse_name',
                    DB::raw('count(*) as qty_aging'),
                    DB::raw('MIN(COALESCE(product_serial_numbers.receipt_date, product_serial_numbers.created_at)) as oldest_date')
                ])
                ->groupBy('product_accurates.name', 'product_accurates.base_price', 'product_serial_numbers.hpp', 'warehouses.name')
                ->orderBy('qty_aging', 'desc')
                ->take($limit)
                ->get();

            return $items->map(function ($it) {
                $oldestDays = $it->oldest_date ? Carbon::parse($it->oldest_date)->diffInDays(now()) : 30;
                return [
                    'product_name' => $it->product_name,
                    'branch' => $it->warehouse_name,
                    'qty' => (int)$it->qty_aging,
                    'price' => (float)$it->base_price,
                    'hpp' => (float)$it->hpp,
                    'days_in_stock' => $oldestDays,
                ];
            })->toArray();
        } catch (\Throwable $e) {
            Log::error('ExecutiveInventoryService getDeadStockAlerts error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Format search results into a clean, compact block for AI system prompt context.
     */
    public function formatInventoryForAiContext(array $searchResults): string
    {
        if (empty($searchResults)) {
            return "[DATA INVENTARIS REALTIME: Tidak ada produk yang cocok dengan kata kunci pencarian]";
        }

        $text = "[DATA REALTIME STOK & LOKASI UNIT HP DI GUDANG/CABANG]\n";
        foreach ($searchResults as $idx => $p) {
            $num = $idx + 1;
            $price = number_format($p['selling_price'], 0, ',', '.');
            $cost = number_format($p['cost_price'], 0, ',', '.');
            $totalAvail = $p['total_available_physical'];
            $bu = $p['business_unit_name'];

            $text .= "- #{$num} {$p['name']} (SKU: {$p['sku']})\n";
            $text .= "  * Unit Bisnis: {$bu}\n";
            $text .= "  * Harga Jual Resmi: Rp {$price} | HPP Modal: Rp {$cost}\n";
            $text .= "  * Total Unit Siap Jual (Available): {$totalAvail} unit\n";

            if (!empty($p['branches'])) {
                $branchList = [];
                foreach ($p['branches'] as $bName => $qty) {
                    $branchList[] = "{$bName}: {$qty} unit";
                }
                $text .= "  * Sebaran Cabang Toko: " . implode(', ', $branchList) . "\n";
            } else {
                $text .= "  * Sebaran Cabang Toko: Belum ada fisik nomor seri siap jual (Stok Accurate sistem: {$p['stock_accurate']})\n";
            }
        }

        return trim($text);
    }

    /**
     * Format single serial number tracking into AI prompt context.
     */
    public function formatImeiForAiContext(?array $item): string
    {
        if (!$item) {
            return "[DATA PELACAKAN IMEI/SN: Nomor seri/IMEI tersebut tidak ditemukan dalam database]";
        }

        $price = number_format($item['selling_price'], 0, ',', '.');
        $cost = number_format($item['cost_price'], 0, ',', '.');
        $days = $item['days_in_warehouse'] !== null ? "{$item['days_in_warehouse']} hari" : "tidak tercatat";

        $text = "[DATA PELACAKAN FISIK NOMOR SERI / IMEI REALTIME]\n";
        $text .= "- Nomor Seri / IMEI: {$item['serial_number']}\n";
        $text .= "- Nama Produk: {$item['product_name']} (SKU: {$item['sku']})\n";
        $text .= "- Unit Bisnis: {$item['business_unit_name']}\n";
        $text .= "- Status Unit: {$item['status']}\n";
        $text .= "- Lokasi Fisik Saat Ini: {$item['location_branch']}\n";
        $text .= "- Harga Jual Resmi: Rp {$price} | HPP Modal: Rp {$cost}\n";
        $text .= "- Lama Mengendap di Toko: {$days}\n";

        if (!empty($item['sales_history'])) {
            $h = $item['sales_history'];
            $total = number_format($h['grand_total'], 0, ',', '.');
            $text .= "- Riwayat Transaksi: Terjual di Faktur #{$h['order_number']} ({$h['order_date']}) seharga Rp {$total}\n";
            $text .= "  * Kasir: {$h['cashier']} | Sales: {$h['sales']} | Pelanggan: {$h['customer']}\n";
        }

        return trim($text);
    }
}
