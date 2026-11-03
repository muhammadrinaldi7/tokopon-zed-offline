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
     * Common conversational filler words stripped before querying product names.
     */
    protected array $stopWords = [
        'stok', 'stock', 'ada', 'di', 'cabang', 'mana', 'harga', 'berapa', 'berapakah',
        'apakah', 'tersedia', 'ketersediaan', 'sebaran', 'unit', 'unitnya', 'cek', 'info',
        'informasi', 'hp', 'handphone', 'smartphone', 'gadget', 'toko', 'gudang', 'lokasi',
        'seluruh', 'semua', 'saat', 'ini', 'sekarang', 'dan', 'atau', 'yang', 'untuk',
        'pada', 'dari', 'ke', 'tolong', 'coba', 'tanya', 'mohon', 'tentang', 'bagaimana',
        'gimana', 'kondisi', 'posisi', 'rincian', 'rekap', 'rekapitulasi', 'total', 'nilai',
        'modal', 'modalnya', 'jual', 'jualnya', 'harganya', 'stoknya', 'barang', 'produk',
        'fisik', 'beserta', 'sku', 'teratas', 'teratasnya', 'terbesar', 'terbanyak',
        'berikan', 'tampilkan', 'sajikan', 'lihat', 'daftar', 'list', 'saja', 'sih', 'kah',
        'nya', 'kan', 'nih', 'deh', 'aja', 'ya', 'dong', 'min', 'zed', 'ai', 'pak', 'bu',
        'second', 'bekas', '2nd', 'gsk', 'baru', 'new', 'resmi', 'syihab', 'distri',
    ];

    /**
     * Extract clean product model keywords from a natural-language query.
     */
    public function extractCleanProductWords(string $keyword, ?string $branchName = null): array
    {
        $clean = preg_replace('/[^\p{L}\p{N}\+\s]/u', ' ', strtolower(trim($keyword)));

        if ($branchName) {
            $clean = str_ireplace(strtolower($branchName), ' ', $clean);
        }

        $knownBranches = [
            'banjarbaru', 'martapura', 'sultan adam', 'veteran',
            'premium', 'kayutangi', 'sampit', 'head office', 'banjarmasin',
        ];
        foreach ($knownBranches as $b) {
            $clean = preg_replace('/\b' . preg_quote($b, '/') . '\b/i', ' ', $clean);
        }

        $tokens = preg_split('/\s+/', trim($clean)) ?: [];
        return array_values(array_filter($tokens, function ($w) {
            return strlen($w) >= 2 && !in_array($w, $this->stopWords, true);
        }));
    }

    /**
     * Search products, prices, and physical available stock across branches with full aggregate summary.
     */
    public function searchInventoryWithSummary(
        string $keyword = '',
        ?int $businessUnitId = null,
        ?string $branchName = null,
        int $limit = 12
    ): array {
        $rawKeyword = trim($keyword);

        // Auto-detect business unit from keyword only when caller has not locked it
        if (!$businessUnitId && $rawKeyword !== '') {
            if (preg_match('/\b(second|bekas|2nd|gsk)\b/i', $rawKeyword)) {
                $businessUnitId = 2; // GSK Second
            } elseif (preg_match('/\b(baru|new|resmi|syihab)\b/i', $rawKeyword)) {
                $businessUnitId = 1; // Syihab Baru
            } elseif (preg_match('/\b(distri|distribusi|grosir)\b/i', $rawKeyword)) {
                $businessUnitId = 3; // GSK Distri
            }
        }

        $words = $this->extractCleanProductWords($rawKeyword, $branchName);

        try {
            $baseQuery = DB::table('product_accurates')
                ->when($businessUnitId, fn($q) => $q->where('product_accurates.business_unit_id', $businessUnitId))
                ->where('product_accurates.name', 'not like', 'ADD ON%')
                ->where('product_accurates.name', 'not like', 'TES BARANG%')
                ->where('product_accurates.name', 'not like', 'ITEM TES%');

            if (!empty($words)) {
                $baseQuery->where(function ($q) use ($words) {
                    foreach ($words as $w) {
                        $q->where('product_accurates.name', 'like', "%{$w}%");
                    }
                });
            }

            // If strict AND yielded 0 rows and there are multiple model words, relax only across real model words
            if (!empty($words) && (clone $baseQuery)->count() === 0 && count($words) > 1) {
                $baseQuery = DB::table('product_accurates')
                    ->when($businessUnitId, fn($q) => $q->where('product_accurates.business_unit_id', $businessUnitId))
                    ->where('product_accurates.name', 'not like', 'ADD ON%')
                    ->where('product_accurates.name', 'not like', 'TES BARANG%')
                    ->where('product_accurates.name', 'not like', 'ITEM TES%')
                    ->where(function ($q) use ($words) {
                        foreach ($words as $w) {
                            if (strlen($w) >= 3) {
                                $q->orWhere('product_accurates.name', 'like', "%{$w}%");
                            }
                        }
                    });
            }

            // 1. Compute real aggregate totals across ALL matching SKUs (not truncated by limit)
            $aggRow = (clone $baseQuery)
                ->where('product_accurates.stock', '>', 0)
                ->selectRaw('COUNT(*) as active_sku_count, COALESCE(SUM(stock), 0) as total_stock_accurate, COALESCE(SUM(stock * base_cost), 0) as total_cost_valuation')
                ->first();

            // Physical SN breakdown per warehouse across all matching SKUs
            $snBranchQuery = DB::table('product_serial_numbers')
                ->join('warehouses', 'product_serial_numbers.warehouse_id', '=', 'warehouses.id')
                ->join('product_accurates', function ($join) {
                    $join->on('product_serial_numbers.item_no', '=', 'product_accurates.item_no')
                        ->on('product_serial_numbers.business_unit_id', '=', 'product_accurates.business_unit_id');
                })
                ->where('product_serial_numbers.status', 'Available')
                ->when($businessUnitId, fn($q) => $q->where('product_serial_numbers.business_unit_id', $businessUnitId))
                ->when($branchName, fn($q) => $q->where('warehouses.name', 'like', "%{$branchName}%"))
                ->where('product_accurates.name', 'not like', 'ADD ON%')
                ->where('product_accurates.name', 'not like', 'TES BARANG%')
                ->where('product_accurates.name', 'not like', 'ITEM TES%');

            if (!empty($words)) {
                $snBranchQuery->where(function ($q) use ($words) {
                    foreach ($words as $w) {
                        $q->where('product_accurates.name', 'like', "%{$w}%");
                    }
                });
            }

            $overallBranchSn = $snBranchQuery
                ->select('warehouses.name as branch_name', DB::raw('COUNT(*) as qty'), DB::raw('COALESCE(SUM(COALESCE(NULLIF(product_serial_numbers.hpp, 0), product_accurates.base_cost)), 0) as valuation'))
                ->groupBy('warehouses.name')
                ->orderByDesc('qty')
                ->get();

            // 2. Fetch top products deterministically ordered by available stock & valuation
            $products = (clone $baseQuery)
                ->orderByRaw('CASE WHEN product_accurates.stock > 0 THEN 0 ELSE 1 END ASC')
                ->orderByRaw('(COALESCE(product_accurates.stock, 0) * COALESCE(product_accurates.base_cost, 0)) DESC')
                ->orderByDesc('product_accurates.stock')
                ->orderByDesc('product_accurates.base_price')
                ->take($limit)
                ->get();

            $items = [];
            foreach ($products as $prod) {
                $buId = (int) $prod->business_unit_id;
                $buName = $this->resolveBuLabel($buId);

                $snQuery = DB::table('product_serial_numbers')
                    ->join('warehouses', 'product_serial_numbers.warehouse_id', '=', 'warehouses.id')
                    ->where('product_serial_numbers.item_no', $prod->item_no)
                    ->where('product_serial_numbers.business_unit_id', $buId)
                    ->where('product_serial_numbers.status', 'Available');

                if ($branchName) {
                    $snQuery->where('warehouses.name', 'like', "%{$branchName}%");
                }

                $branchStock = $snQuery->select('warehouses.name as branch_name', DB::raw('count(*) as qty'))
                    ->groupBy('warehouses.name')
                    ->orderByDesc('qty')
                    ->get();

                $totalAvailable = (int) $branchStock->sum('qty');

                $items[] = [
                    'sku' => $prod->item_no,
                    'name' => $prod->name,
                    'category' => $prod->categoryName ?? '-',
                    'project' => $prod->proyek ?? '-',
                    'business_unit_id' => $buId,
                    'business_unit_name' => $buName,
                    'selling_price' => (float) $prod->base_price,
                    'cost_price' => (float) $prod->base_cost,
                    'stock_accurate' => (float) $prod->stock,
                    'total_available_physical' => $totalAvailable,
                    'branches' => $branchStock->pluck('qty', 'branch_name')->toArray(),
                ];
            }

            return [
                'applied_keywords' => $words,
                'business_unit_id' => $businessUnitId,
                'business_unit_name' => $businessUnitId ? $this->resolveBuLabel($businessUnitId) : 'Semua Unit Bisnis',
                'branch_filter' => $branchName,
                'summary' => [
                    'total_active_skus' => (int) ($aggRow->active_sku_count ?? 0),
                    'total_stock_accurate_units' => (float) ($aggRow->total_stock_accurate ?? 0),
                    'total_cost_valuation' => round((float) ($aggRow->total_cost_valuation ?? 0), 2),
                    'total_physical_sn_available' => (int) $overallBranchSn->sum('qty'),
                    'physical_sn_by_branch' => $overallBranchSn->map(fn($r) => [
                        'branch_name' => $r->branch_name,
                        'qty' => (int) $r->qty,
                        'valuation' => round((float) $r->valuation, 2),
                    ])->values()->toArray(),
                ],
                'top_items' => $items,
            ];
        } catch (\Throwable $e) {
            Log::error('ExecutiveInventoryService searchInventoryWithSummary error: ' . $e->getMessage());
            return [
                'applied_keywords' => $words,
                'summary' => [
                    'total_active_skus' => 0,
                    'total_stock_accurate_units' => 0,
                    'total_cost_valuation' => 0,
                    'total_physical_sn_available' => 0,
                    'physical_sn_by_branch' => [],
                ],
                'top_items' => [],
            ];
        }
    }

    /**
     * Backward-compatible product search returning top item list.
     */
    public function searchInventory(string $keyword, ?int $businessUnitId = null, ?string $branchName = null, int $limit = 10): array
    {
        $report = $this->searchInventoryWithSummary($keyword, $businessUnitId, $branchName, $limit);
        return $report['top_items'] ?? [];
    }

    /**
     * Track a specific physical unit by IMEI or Serial Number (with strict Business Unit isolation).
     */
    public function trackSerialNumber(string $serialNumber, ?int $allowedBusinessUnitId = null): ?array
    {
        $sn = trim($serialNumber);
        if (strlen($sn) < 4 || in_array(strtolower($sn), $this->stopWords, true)) {
            return null;
        }

        try {
            $buildQuery = function () use ($allowedBusinessUnitId) {
                return DB::table('product_serial_numbers')
                    ->leftJoin('warehouses', 'product_serial_numbers.warehouse_id', '=', 'warehouses.id')
                    ->leftJoin('product_accurates', function ($join) {
                        $join->on('product_serial_numbers.item_no', '=', 'product_accurates.item_no')
                            ->on('product_serial_numbers.business_unit_id', '=', 'product_accurates.business_unit_id');
                    })
                    ->when($allowedBusinessUnitId, function ($q) use ($allowedBusinessUnitId) {
                        $q->where('product_serial_numbers.business_unit_id', $allowedBusinessUnitId);
                    });
            };

            $selectCols = [
                'product_serial_numbers.*',
                'product_accurates.name as product_name',
                'product_accurates.base_price',
                'product_accurates.base_cost',
                'warehouses.name as warehouse_name',
            ];

            $item = $buildQuery()
                ->where('product_serial_numbers.serial_number', $sn)
                ->first($selectCols);

            if (!$item && strlen($sn) >= 6) {
                $item = $buildQuery()
                    ->where('product_serial_numbers.serial_number', 'like', "%{$sn}%")
                    ->first($selectCols);
            }

            if (!$item) {
                return null;
            }

            $buId = (int) $item->business_unit_id;
            $buName = $this->resolveBuLabel($buId);

            $daysInStock = null;
            if ($item->receipt_date) {
                $daysInStock = Carbon::parse($item->receipt_date)->diffInDays(now());
            } elseif ($item->created_at) {
                $daysInStock = Carbon::parse($item->created_at)->diffInDays(now());
            }

            $history = null;
            if (strtolower((string) $item->status) !== 'available') {
                $orderItem = DB::table('order_items')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->leftJoin('users as cashier', 'orders.handled_by', '=', 'cashier.id')
                    ->leftJoin('users as sales', 'orders.sales_by', '=', 'sales.id')
                    ->leftJoin('users as customer', 'orders.user_id', '=', 'customer.id')
                    ->when($allowedBusinessUnitId, fn($q) => $q->where('orders.business_unit_id', $allowedBusinessUnitId))
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
                        'grand_total' => (float) $orderItem->grand_total,
                        'order_status' => $orderItem->order_status,
                        'cashier' => $orderItem->cashier_name ?? '-',
                        'sales' => $orderItem->sales_name ?? '-',
                        'customer' => $orderItem->customer_name ?? 'Walk-in Customer',
                    ];
                }
            }

            $costPrice = (float) ($item->hpp ?? 0);
            if ($costPrice <= 0 && !empty($item->base_cost)) {
                $costPrice = (float) $item->base_cost;
            }

            return [
                'serial_number' => $item->serial_number,
                'sku' => $item->item_no,
                'product_name' => $item->product_name ?? 'Produk Tidak Terdaftar',
                'business_unit_id' => $buId,
                'business_unit_name' => $buName,
                'status' => $item->status,
                'location_branch' => $item->warehouse_name ?? 'Lokasi Tidak Diketahui',
                'selling_price' => (float) ($item->base_price ?? 0),
                'cost_price' => $costPrice,
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
    public function getDeadStockAlerts(?int $businessUnitId = 2, int $daysThreshold = 30, int $limit = 12): array
    {
        try {
            $cutoffDate = now()->subDays($daysThreshold);

            $items = DB::table('product_serial_numbers')
                ->join('warehouses', 'product_serial_numbers.warehouse_id', '=', 'warehouses.id')
                ->join('product_accurates', function ($join) {
                    $join->on('product_serial_numbers.item_no', '=', 'product_accurates.item_no')
                        ->on('product_serial_numbers.business_unit_id', '=', 'product_accurates.business_unit_id');
                })
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
                    'product_accurates.base_cost',
                    'product_serial_numbers.hpp',
                    'warehouses.name as warehouse_name',
                    DB::raw('count(*) as qty_aging'),
                    DB::raw('MIN(COALESCE(product_serial_numbers.receipt_date, product_serial_numbers.created_at)) as oldest_date'),
                ])
                ->groupBy(
                    'product_accurates.name',
                    'product_accurates.base_price',
                    'product_accurates.base_cost',
                    'product_serial_numbers.hpp',
                    'warehouses.name'
                )
                ->orderBy('qty_aging', 'desc')
                ->take($limit)
                ->get();

            return $items->map(function ($it) use ($daysThreshold) {
                $oldestDays = $it->oldest_date ? Carbon::parse($it->oldest_date)->diffInDays(now()) : $daysThreshold;
                $hpp = (float) $it->hpp > 0 ? (float) $it->hpp : (float) ($it->base_cost ?? 0);
                return [
                    'product_name' => $it->product_name,
                    'branch' => $it->warehouse_name,
                    'qty' => (int) $it->qty_aging,
                    'price' => (float) $it->base_price,
                    'hpp' => $hpp,
                    'days_in_stock' => $oldestDays,
                ];
            })->toArray();
        } catch (\Throwable $e) {
            Log::error('ExecutiveInventoryService getDeadStockAlerts error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Format full inventory report (aggregate totals + top items) for AI context.
     */
    public function formatInventoryReportForAiContext(array $report): string
    {
        $summary = $report['summary'] ?? [];
        $items = $report['top_items'] ?? [];
        $buLabel = $report['business_unit_name'] ?? 'Semua Unit Bisnis';
        $keywords = !empty($report['applied_keywords']) ? implode(' ', $report['applied_keywords']) : 'Semua Produk Aktif';

        if (($summary['total_active_skus'] ?? 0) === 0 && empty($items)) {
            return "[DATA INVENTARIS REALTIME ({$buLabel} | Kata Kunci: {$keywords}): Tidak ditemukan produk dengan stok aktif yang cocok.]";
        }

        $totalSkus = number_format($summary['total_active_skus'] ?? 0, 0, ',', '.');
        $totalUnitsAcc = number_format($summary['total_stock_accurate_units'] ?? 0, 0, ',', '.');
        $totalVal = number_format($summary['total_cost_valuation'] ?? 0, 0, ',', '.');
        $totalSn = number_format($summary['total_physical_sn_available'] ?? 0, 0, ',', '.');

        $text = "[REKAPITULASI TOTAL STOK REALTIME ({$buLabel} | Filter Kata Kunci: {$keywords})]\n";
        $text .= "- Total SKU Aktif (Stok > 0): {$totalSkus} SKU\n";
        $text .= "- Total Kuantitas Stok Sistem Accurate: {$totalUnitsAcc} unit\n";
        $text .= "- Total Nilai Modal HPP Persediaan: Rp {$totalVal}\n";
        $text .= "- Total Unit Ber-SN/IMEI Siap Jual (Status Available): {$totalSn} unit\n";

        if (!empty($summary['physical_sn_by_branch'])) {
            $text .= "\nSEBARAN UNIT FISIK BER-IMEI/SN PER CABANG/GUDANG:\n";
            foreach ($summary['physical_sn_by_branch'] as $b) {
                $bVal = number_format($b['valuation'] ?? 0, 0, ',', '.');
                $text .= "- {$b['branch_name']}: {$b['qty']} unit (Nilai Modal: Rp {$bVal})\n";
            }
        }

        if (!empty($items)) {
            $shownCount = count($items);
            $text .= "\nDAFTAR {$shownCount} SKU TERATAS (DIURUTKAN DARI NILAI STOK TERBESAR DARI TOTAL {$totalSkus} SKU):\n";
            foreach ($items as $idx => $p) {
                $num = $idx + 1;
                $price = number_format($p['selling_price'], 0, ',', '.');
                $cost = number_format($p['cost_price'], 0, ',', '.');
                $accQty = number_format($p['stock_accurate'], 0, ',', '.');
                $snQty = $p['total_available_physical'];

                $text .= "- #{$num} {$p['name']} (SKU: {$p['sku']} | BU: {$p['business_unit_name']})\n";
                $text .= "  * Harga Jual: Rp {$price} | HPP: Rp {$cost} | Stok Accurate: {$accQty} unit | Fisik SN Siap Jual: {$snQty} unit\n";
                if (!empty($p['branches'])) {
                    $branchList = [];
                    foreach ($p['branches'] as $bName => $qty) {
                        $branchList[] = "{$bName}: {$qty}";
                    }
                    $text .= "  * Lokasi SN: " . implode(', ', $branchList) . "\n";
                }
            }
        }

        return trim($text);
    }

    /**
     * Backward-compatible formatter for plain item list.
     */
    public function formatInventoryForAiContext(array $searchResults): string
    {
        if (isset($searchResults['summary']) && isset($searchResults['top_items'])) {
            return $this->formatInventoryReportForAiContext($searchResults);
        }

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
            return "[DATA PELACAKAN IMEI/SN: Nomor seri/IMEI tersebut tidak ditemukan dalam database Unit Bisnis yang diizinkan]";
        }

        $price = number_format($item['selling_price'], 0, ',', '.');
        $cost = number_format($item['cost_price'], 0, ',', '.');
        $days = $item['days_in_warehouse'] !== null ? "{$item['days_in_warehouse']} hari" : 'tidak tercatat';

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

    protected function resolveBuLabel(int $buId): string
    {
        return match ($buId) {
            1 => 'Syihab (HP Baru)',
            2 => 'GSK Second (HP Second)',
            3 => 'GSK Distri (Distribusi)',
            default => 'Lainnya',
        };
    }
}
