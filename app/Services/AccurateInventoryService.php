<?php

namespace App\Services;

use App\Models\BusinessUnit;
use App\Models\ProductAccurate;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AccurateInventoryService
{
    /**
     * Get API credentials and headers for a given BusinessUnit.
     */
    public function getHeadersForBu(BusinessUnit $bu): array
    {
        $host = rtrim($bu->accurate_host ?: 'https://odin.accurate.id/accurate/api', '/');
        $token = $bu->accurate_token;
        $secretKey = $bu->accurate_secret_key;

        $dt = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
        $timestamp = $dt->format('d/m/Y H:i:s');
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        return [
            'host' => $host,
            'business_unit' => $bu,
            'headers' => [
                'Authorization'   => 'Bearer ' . $token,
                'X-Api-Timestamp' => $timestamp,
                'X-Api-Signature' => $signature,
                'Content-Type'    => 'application/json',
            ],
        ];
    }

    /**
     * Backward compatible getHeaders by code.
     */
    public function getHeaders(string $databaseSource = 'second'): array
    {
        $code = trim(strtolower($databaseSource)) ?: 'second';
        if ($code === 'all') $code = 'second';

        $bu = BusinessUnit::where('code', $code)->first();
        if (!$bu || empty($bu->accurate_token)) {
            $bu = BusinessUnit::whereNotNull('accurate_token')->where('code', 'second')->first()
                ?? BusinessUnit::whereNotNull('accurate_token')->first();
        }

        if (!$bu || empty($bu->accurate_token)) {
            throw new \Exception("Kredensial Accurate Online tidak ditemukan untuk unit '{$databaseSource}'.");
        }

        return $this->getHeadersForBu($bu);
    }

    /**
     * Fetch Live GL Balance for Persediaan (Akun 10.06.001) from Accurate API.
     */
    public function fetchLiveGlBalance(BusinessUnit $bu, ?string $asOfDate = null, bool $forceRefresh = false): array
    {
        $dt = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
        $asOfFormat = $asOfDate ? Carbon::parse($asOfDate)->format('d/m/Y') : $dt->format('d/m/Y');
        $cacheKey = "accurate_gl_bal_{$bu->id}_{$asOfFormat}";

        if (!$forceRefresh && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (!empty($cached['balance']) && $cached['balance'] > 0) {
                return $cached;
            }
        }

        $config = $this->getHeadersForBu($bu);
        $glBalance = 0;
        $glAccountInfo = null;

        try {
            $glRes = Http::timeout(15)->withHeaders($config['headers'])->get($config['host'] . '/glaccount/list.do', [
                'fields' => 'id,no,name,accountType',
                'filter.accountType' => 'INVENTORY',
                'filter.leafOnly' => true,
            ]);

            if ($glRes->successful()) {
                $glList = $glRes->json()['d'] ?? [];
                foreach ($glList as $acc) {
                    // Match leaf Persediaan account (e.g. 10.06.001)
                    if (($acc['accountType'] ?? '') === 'INVENTORY' && stripos($acc['name'], 'Persediaan') !== false && !stripos($acc['name'], 'Intransit') && !stripos($acc['name'], 'Dalam Proses')) {
                        $nowTs = (new \DateTime('now', new \DateTimeZone('Asia/Jakarta')))->format('d/m/Y H:i:s');
                        $nowSig = hash_hmac('sha256', $nowTs, $bu->accurate_secret_key);
                        $headers = $config['headers'];
                        $headers['X-Api-Timestamp'] = $nowTs;
                        $headers['X-Api-Signature'] = $nowSig;

                        $balRes = Http::timeout(10)->withHeaders($headers)->get($config['host'] . '/glaccount/get-balance.do', [
                            'id' => $acc['id'],
                            'asOfDate' => $asOfFormat,
                        ]);

                        if ($balRes->successful()) {
                            $balData = $balRes->json();
                            $accBal = (float)($balData['d']['balance'] ?? ($balData['balance'] ?? 0));
                            if ($accBal > 0) {
                                $glBalance += $accBal;
                                if (!$glAccountInfo) {
                                    $glAccountInfo = [
                                        'account_no' => $acc['no'],
                                        'account_name' => $acc['name'],
                                        'balance' => $accBal,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("Live GL fetch error for {$bu->name}: " . $e->getMessage());
        }

        $result = [
            'balance' => $glBalance,
            'account' => $glAccountInfo ?: [
                'account_no' => '10.06.001',
                'account_name' => 'Persediaan',
                'balance' => $glBalance,
            ],
        ];

        if ($glBalance > 0) {
            Cache::put($cacheKey, $result, now()->addMinutes(30));
        }

        return $result;
    }

    /**
     * Fetch Live Warehouses directly from Accurate API.
     */
    public function fetchLiveWarehouses(BusinessUnit $bu): array
    {
        $config = $this->getHeadersForBu($bu);
        $warehouses = [];
        try {
            $res = Http::timeout(15)->withHeaders($config['headers'])->get($config['host'] . '/warehouse/list.do');
            if ($res->successful()) {
                $rawList = $res->json()['d'] ?? [];
                foreach ($rawList as $w) {
                    $warehouses[] = [
                        'id' => $w['id'] ?? null,
                        'name' => trim($w['name'] ?? 'Gudang'),
                        'suspended' => (bool)($w['suspended'] ?? false),
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning("Live warehouse fetch error for {$bu->name}: " . $e->getMessage());
        }
        return $warehouses;
    }

    /**
     * Sync active items directly from Accurate Online into product_accurates.
     * Menggunakan format dan pemetaan standar yang sama persis dengan ProductAccurateManagement.
     */
    public function syncProductsFromAccurate(string $databaseSource = 'second', int $maxPages = 5): int
    {
        $code = ($databaseSource === 'all' || empty($databaseSource)) ? 'second' : $databaseSource;
        $bu = BusinessUnit::where('code', $code)->first();
        if (!$bu) return 0;

        $buId = $bu->id;
        $service = app(AccurateService::class);
        $imported = 0;

        for ($p = 1; $p <= $maxPages; $p++) {
            try {
                $items = $service->getItemList($p, 100, $code);
            } catch (\Exception $e) {
                Log::warning("Accurate getItemList failed on page {$p} for {$code}: " . $e->getMessage());
                break;
            }

            if (empty($items)) break;

            $batch = [];
            foreach ($items as $item) {
                if (!isset($item['no'])) continue;

                $stock = (float)($item['availableToSell'] ?? 0);
                $cost = (float)($item['balanceUnitCost'] ?? 0);
                $price = (float)($item['unitPrice'] ?? 0);

                $batch[] = [
                    'accurate_id'          => $item['no'],
                    'database_source'      => $code,
                    'business_unit_id'     => $buId,
                    'item_no'              => $item['no'],
                    'name'                 => $item['name'] ?? 'Unknown Item',
                    'base_price'           => (int) round($price),
                    'stock'                => (int) round($stock),
                    'base_cost'            => (int) round($cost),
                    'has_sn'               => (isset($item['serialNumberType']) && $item['serialNumberType'] === 'UNIQUE') || (!empty($item['manageSN'])),
                    'id_brand_accurate'    => $item['itemBrand']['id'] ?? null,
                    'brandName'            => $item['itemBrand']['name'] ?? null,
                    'id_category_accurate' => $item['itemCategory']['id'] ?? null,
                    'categoryName'         => $item['itemCategory']['name'] ?? null,
                    'itemType'             => $item['itemType'] ?? null,
                    'is_addon'             => isset($item['charField1']) && strtolower(trim($item['charField1'])) === 'ya',
                    'proyek'               => $item['charField2'] ?? null,
                    'os'                   => $item['charField3'] ?? null,
                    'raw_data'             => json_encode($item),
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ];
            }

            if (!empty($batch)) {
                ProductAccurate::upsert(
                    $batch,
                    ['accurate_id', 'database_source'],
                    [
                        'item_no',
                        'name',
                        'base_price',
                        'stock',
                        'base_cost',
                        'has_sn',
                        'business_unit_id',
                        'id_brand_accurate',
                        'brandName',
                        'id_category_accurate',
                        'categoryName',
                        'itemType',
                        'is_addon',
                        'proyek',
                        'os',
                        'raw_data',
                        'updated_at'
                    ]
                );
                $imported += count($batch);
            }

            if (count($items) < 100) break;
            usleep(100000);
        }

        return $imported;
    }

    /**
     * Sync active warehouse stocks directly from Accurate Online into warehouse_stocks.
     */
    public function syncWarehouseStocksFromAccurate(string $databaseSource = 'all', int $maxPages = 5): int
    {
        $bus = [];
        if ($databaseSource === 'syihab') {
            $bu = BusinessUnit::where('code', 'syihab')->first();
            if ($bu) $bus[] = $bu;
        } elseif ($databaseSource === 'second') {
            $bu = BusinessUnit::where('code', 'second')->first();
            if ($bu) $bus[] = $bu;
        } else {
            $bus = BusinessUnit::whereNotNull('accurate_token')->get()->all();
        }

        $totalUpserted = 0;

        foreach ($bus as $bu) {
            $config = $this->getHeadersForBu($bu);
            $warehouses = Warehouse::where('business_unit_id', $bu->id)
                ->whereNotNull('warehouse_id')
                ->get();
            $products = ProductAccurate::where('business_unit_id', $bu->id)->pluck('id', 'item_no')->toArray();

            foreach ($warehouses as $wh) {
                for ($p = 1; $p <= $maxPages; $p++) {
                    $dt = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
                    $timestamp = $dt->format('d/m/Y H:i:s');
                    $signature = hash_hmac('sha256', $timestamp, $bu->accurate_secret_key);
                    $headers = $config['headers'];
                    $headers['X-Api-Timestamp'] = $timestamp;
                    $headers['X-Api-Signature'] = $signature;

                    $res = Http::timeout(15)->withHeaders($headers)->get($config['host'] . '/item/list-stock.do', [
                        'sp.pageSize' => 100,
                        'sp.page' => $p,
                        'warehouseName' => $wh->name,
                    ]);

                    if (!$res->successful()) break;
                    $items = $res->json()['d'] ?? [];
                    if (empty($items)) break;

                    $batch = [];
                    foreach ($items as $it) {
                        $qty = (int)round((float)($it['quantity'] ?? 0));
                        if ($qty <= 0) continue;

                        $itemNo = $it['no'] ?? null;
                        if (!$itemNo || !isset($products[$itemNo])) continue;

                        $batch[] = [
                            'warehouse_id' => $wh->id,
                            'variant_id'   => $products[$itemNo],
                            'variant_type' => ProductAccurate::class,
                            'stock'        => $qty,
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ];
                    }

                    if (!empty($batch)) {
                        WarehouseStock::upsert(
                            $batch,
                            ['warehouse_id', 'variant_id', 'variant_type'],
                            ['stock', 'updated_at']
                        );
                        $totalUpserted += count($batch);
                    }

                    if (count($items) < 100) break;
                }
            }
        }

        return $totalUpserted;
    }

    /**
     * Get Complete Inventory Stock & Valuation 100% Live from Accurate Online & Database.
     */
    public function getInventoryValuation(
        string $databaseSource = 'all',
        ?string $asOfDate = null,
        $branch = null,
        $category = null,
        bool $forceRefresh = false,
        ?string $resolvedBuName = null
    ): array {
        $filterHash = md5(json_encode([$databaseSource, $branch, $category]));
        $dateKey = $asOfDate ?: now()->format('Y-m-d');
        $cacheKey = "accurate_inv_live_{$databaseSource}_{$dateKey}_{$filterHash}";

        // 1. Tangani Cache & Refresh On-demand
        if ($forceRefresh) {
            Cache::forget($cacheKey);
            $this->refreshAccurateSync($databaseSource);
        } elseif (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // 2. Ambil Business Unit & Data Live (GL + Gudang)
        $businessUnits = $this->resolveBusinessUnits($databaseSource);
        [$totalValuation, $primaryAccount, $allWarehousesRaw] = $this->fetchGlAndLiveWarehouses($businessUnits, $asOfDate, $forceRefresh, $databaseSource);

        // 3. Resolusi Parameter Filter (Cabang & Kategori)
        $matchedWhIds = $this->resolveMatchedWarehouseIds($branch);
        $catTargets   = $this->resolveCategoryTargets($category);

        // 4. Hitung Total Nilai Fisik Mentah di Database untuk Menghitung Proporsi
        $buTotalRawVal = (float) DB::table('product_accurates')
            ->when($databaseSource !== 'all', fn($q) => $q->where('database_source', $databaseSource))
            ->where('stock', '>', 0)
            ->sum(DB::raw('stock * base_cost')) ?: 1.0;

        // 5. Kalkulasi Breakdown Kategori (dan hitung nilai efektif jika kategori difilter)
        $categoryBreakdown = $this->buildCategoryBreakdown($matchedWhIds, $catTargets, $databaseSource, $totalValuation, $buTotalRawVal);

        // Jika user memfilter kategori, effectiveValuation adalah total dari kategori yang dipilih
        if (!empty($catTargets)) {
            $effectiveValuation = (float) array_sum(array_column($categoryBreakdown, 'total_valuation'));
        } else {
            $effectiveValuation = $totalValuation;
        }

        // 6. Kalkulasi Breakdown Cabang / Gudang (disaring berdasarkan kategori jika dipilih)
        $branchBreakdown = $this->buildBranchBreakdown(
            $allWarehousesRaw,
            $databaseSource,
            $effectiveValuation,
            $buTotalRawVal,
            $branch,
            $catTargets
        );

        // Jika user memfilter cabang, sesuaikan effectiveValuation ke total cabang terpilih
        if (!empty($branch)) {
            $branchFilteredVal = (float) array_sum(array_column($branchBreakdown, 'total_valuation'));
            if ($branchFilteredVal > 0 || !empty($branchBreakdown)) {
                $effectiveValuation = $branchFilteredVal;
            }
        }

        // 7. Kalkulasi Breakdown Brand (diskalakan terhadap effectiveValuation)
        $brandBreakdown = $this->buildBrandBreakdown($matchedWhIds, $catTargets, $databaseSource, $effectiveValuation);

        // 8. Kalkulasi Top Valuasi Barang (disaring berdasarkan cabang & kategori)
        $topValuationItems = $this->buildTopValuationItems($matchedWhIds, $catTargets, $databaseSource);

        // 9. Kalkulasi Slow Moving & Total Unit Riil
        $slowMoving = $this->calculateSlowMoving($databaseSource, $effectiveValuation, 60, $matchedWhIds, $catTargets);

        $totalWhUnits = !empty($branchBreakdown) ? array_sum(array_column($branchBreakdown, 'total_qty')) : 0;
        if ($totalWhUnits <= 0 && empty($matchedWhIds)) {
            $totalWhUnits = (int) ProductAccurate::query()
                ->when($databaseSource !== 'all', fn($q) => $q->where('database_source', $databaseSource))
                ->when(!empty($catTargets), function ($q) use ($catTargets) {
                    $q->where(function ($sub) use ($catTargets) {
                        foreach ($catTargets as $cat) {
                            $sub->orWhere('categoryName', 'LIKE', '%' . $cat . '%');
                        }
                    });
                })
                ->where('stock', '>', 0)
                ->sum('stock');
        }

        $result = [
            'business_unit' => $resolvedBuName ?: ($databaseSource === 'syihab' ? 'Syihab Store' : ($databaseSource === 'second' ? 'GSK Second' : 'Semua Unit Bisnis')),
            'database_source' => $databaseSource,
            'as_of_date' => $asOfDate ?: now()->format('Y-m-d'),
            'gl_account' => $primaryAccount ?: [
                'account_no' => '10.06.001',
                'account_name' => 'Persediaan',
                'balance' => $totalValuation,
            ],
            'total_valuation' => round($effectiveValuation, 2),
            'total_units' => (int)$totalWhUnits,
            'average_unit_cost' => $totalWhUnits > 0 ? round($effectiveValuation / $totalWhUnits, 2) : 0,
            'slow_moving' => $slowMoving,
            'branch_breakdown' => $branchBreakdown,
            'category_breakdown' => $categoryBreakdown,
            'brand_breakdown' => $brandBreakdown,
            'top_valuation_items' => $topValuationItems,
            'last_synced_at' => now()->toIso8601String(),
        ];

        Cache::put($cacheKey, $result, now()->addMinutes(15));
        return $result;
    }

    /**
     * Jalankan sinkronisasi background saat user menekan force refresh.
     */
    protected function refreshAccurateSync(string $databaseSource): void
    {
        try {
            if ($databaseSource === 'all') {
                $this->syncProductsFromAccurate('second', 2);
                $this->syncProductsFromAccurate('syihab', 2);
                $this->syncWarehouseStocksFromAccurate('all', 3);
            } else {
                $this->syncProductsFromAccurate($databaseSource, 3);
                $this->syncWarehouseStocksFromAccurate($databaseSource, 3);
            }
        } catch (\Exception $e) {
            Log::warning("Background product sync on refresh failed: " . $e->getMessage());
        }
    }

    /**
     * Tentukan BusinessUnit target berdasarkan parameter source.
     */
    protected function resolveBusinessUnits(string $databaseSource): array
    {
        if ($databaseSource === 'syihab') {
            $bu = BusinessUnit::where('code', 'syihab')->first();
            return $bu ? [$bu] : [];
        } elseif ($databaseSource === 'second') {
            $bu = BusinessUnit::where('code', 'second')->first();
            return $bu ? [$bu] : [];
        }

        return BusinessUnit::whereNotNull('accurate_token')->get()->all();
    }

    /**
     * Ambil saldo GL live dan daftar gudang langsung dari Accurate Online API.
     */
    protected function fetchGlAndLiveWarehouses(array $businessUnits, ?string $asOfDate, bool $forceRefresh = false, string $databaseSource = 'all'): array
    {
        $totalValuation = 0;
        $primaryAccount = null;
        $allWarehousesRaw = [];

        foreach ($businessUnits as $bu) {
            $gl = $this->fetchLiveGlBalance($bu, $asOfDate, $forceRefresh);
            $totalValuation += $gl['balance'];
            if (!$primaryAccount && $gl['account']) {
                $primaryAccount = $gl['account'];
            }

            $whs = $this->fetchLiveWarehouses($bu);
            if (empty($whs)) {
                $localWhs = Warehouse::where('business_unit_id', $bu->id)->get();
                foreach ($localWhs as $lw) {
                    $whs[] = [
                        'id' => $lw->warehouse_id ?: $lw->id,
                        'local_id' => $lw->id,
                        'name' => $lw->name,
                        'suspended' => false,
                    ];
                }
            }

            foreach ($whs as $w) {
                $localId = $w['local_id'] ?? null;
                if (!$localId) {
                    $localWh = Warehouse::updateOrCreate(
                        ['name' => $w['name'], 'business_unit_id' => $bu->id],
                        ['warehouse_id' => $w['id']]
                    );
                    $localId = $localWh->id;
                }

                $allWarehousesRaw[] = [
                    'id' => $w['id'],
                    'local_id' => $localId,
                    'name' => $w['name'],
                    'suspended' => $w['suspended'] ?? false,
                    'bu_id' => $bu->id,
                    'bu_name' => $bu->name,
                    'bu_code' => $bu->code,
                    'bu_gl_balance' => $gl['balance'],
                    'bu_wh_count' => count($whs),
                ];
            }
        }

        if ($totalValuation <= 0) {
            $totalValuation = (float) DB::table('product_accurates')
                ->when($databaseSource !== 'all', fn($q) => $q->where('database_source', $databaseSource))
                ->where('stock', '>', 0)
                ->sum(DB::raw('stock * base_cost'));

            // Also assign bu_gl_balance if 0
            foreach ($allWarehousesRaw as &$aw) {
                if ($aw['bu_gl_balance'] <= 0) {
                    $aw['bu_gl_balance'] = $totalValuation;
                }
            }
            unset($aw);
        }

        return [$totalValuation, $primaryAccount, $allWarehousesRaw];
    }

    /**
     * Bangun rincian per cabang/gudang dan lakukan rekonsiliasi proporsional terhadap GL.
     */
    protected function buildBranchBreakdown(
        array $allWarehousesRaw,
        string $databaseSource,
        float $effectiveValuation,
        float $buTotalRawVal,
        $branch = null,
        array $catTargets = []
    ): array {
        $whStocksQuery = DB::table('warehouse_stocks')
            ->join('product_accurates', function ($join) {
                $join->on('warehouse_stocks.variant_id', '=', 'product_accurates.id')
                     ->where('warehouse_stocks.variant_type', '=', ProductAccurate::class);
            })
            ->join('warehouses', 'warehouse_stocks.warehouse_id', '=', 'warehouses.id')
            ->where('warehouse_stocks.stock', '>', 0);

        if ($databaseSource !== 'all') {
            $whStocksQuery->where('product_accurates.database_source', $databaseSource);
        }

        if (!empty($catTargets)) {
            $whStocksQuery->where(function ($q) use ($catTargets) {
                foreach ($catTargets as $cat) {
                    $q->orWhere('product_accurates.categoryName', 'LIKE', '%' . $cat . '%');
                }
            });
        }

        $whStockStats = $whStocksQuery->select(
            'warehouses.id as local_wh_id',
            'warehouses.name as warehouse_name',
            'warehouses.business_unit_id',
            DB::raw('SUM(warehouse_stocks.stock) as total_qty'),
            DB::raw('SUM(warehouse_stocks.stock * product_accurates.base_cost) as total_val')
        )->groupBy('warehouses.id', 'warehouses.name', 'warehouses.business_unit_id')->get();

        $whStatsByLocalId = $whStockStats->keyBy('local_wh_id');

        $hasRealStockData = $whStockStats->isNotEmpty() && $whStockStats->sum('total_qty') > 0;
        $totalUnitsInDb = ProductAccurate::query()
            ->when($databaseSource !== 'all', fn($q) => $q->where('database_source', $databaseSource))
            ->when(!empty($catTargets), function ($q) use ($catTargets) {
                $q->where(function ($sub) use ($catTargets) {
                    foreach ($catTargets as $cat) {
                        $sub->orWhere('categoryName', 'LIKE', '%' . $cat . '%');
                    }
                });
            })
            ->where('stock', '>', 0)
            ->sum('stock');

        $activeWarehousesCount = count($allWarehousesRaw);
        $branchBreakdown = [];

        // Hitung total raw valuation untuk subset filter ini agar penskalaan proporsional tepat
        $subsetTotalRawVal = (float) $whStockStats->sum('total_val') ?: 1.0;

        foreach ($allWarehousesRaw as $w) {
            $whCount = max(1, $w['bu_wh_count']);
            $localId = $w['local_id'] ?? null;
            $nameLower = strtolower(trim($w['name']));

            $stat = $localId ? $whStatsByLocalId->get($localId) : null;

            if ($hasRealStockData) {
                $whUnits = $stat ? (int)$stat->total_qty : 0;
                $whRawVal = $stat ? (float)$stat->total_val : 0;

                // Skala proporsional terhadap effectiveValuation untuk subset ini
                if ($effectiveValuation > 0 && $subsetTotalRawVal > 0) {
                    $whVal = round(($whRawVal / $subsetTotalRawVal) * $effectiveValuation, 2);
                } else {
                    $whVal = round($whRawVal, 2);
                }
            } else {
                $whVal = round($effectiveValuation / $whCount, 2);
                $whUnits = $totalUnitsInDb > 0 ? (int)round(($totalUnitsInDb / max(1, $activeWarehousesCount))) : 0;
            }

            $status = $this->determineWarehouseStatus((bool)$w['suspended'], $nameLower);
            $type = $this->determineWarehouseType($nameLower);
            $cleanBranchName = trim(str_ireplace(['gsk - ', 'gsk second - ', 'syihab - '], '', $w['name']));

            $pct = $effectiveValuation > 0 ? round(($whVal / $effectiveValuation) * 100, 1) : 0;

            $branchBreakdown[] = [
                'warehouse_name' => $w['name'],
                'branch_name' => $cleanBranchName,
                'business_unit' => $w['bu_name'],
                'type' => $type,
                'total_valuation' => $whVal,
                'total_qty' => $whUnits,
                'average_unit_cost' => $whUnits > 0 ? round($whVal / $whUnits, 2) : 0,
                'percentage' => $pct,
                'status' => $status,
            ];
        }

        // Terapkan filter cabang jika dipilih
        if (!empty($branch)) {
            $targets = is_array($branch) ? array_map('strtolower', $branch) : [strtolower(trim($branch))];
            $branchBreakdown = array_values(array_filter($branchBreakdown, function ($b) use ($targets) {
                $wh = strtolower($b['warehouse_name']);
                $br = strtolower($b['branch_name']);
                foreach ($targets as $t) {
                    if (str_contains($wh, $t) || str_contains($br, $t) || str_contains($t, $br)) return true;
                }
                return false;
            }));
        }

        return $branchBreakdown;
    }

    /**
     * Tentukan status kesehatan operasional gudang.
     */
    protected function determineWarehouseStatus(bool $suspended, string $nameLower): string
    {
        if ($suspended || str_contains($nameLower, 'rusak') || str_contains($nameLower, 'return') || str_contains($nameLower, 'retur')) {
            return 'ALERT';
        }
        if (str_contains($nameLower, 'head office') || str_contains($nameLower, 'kayutangi')) {
            return 'OPTIMAL';
        }
        return 'NORMAL';
    }

    /**
     * Tentukan tipe fungsi operasional gudang.
     */
    protected function determineWarehouseType(string $nameLower): string
    {
        if (str_contains($nameLower, 'head office') || str_contains($nameLower, 'pusat')) {
            return 'Gudang Pusat';
        }
        if (str_contains($nameLower, 'service')) {
            return 'Pusat Service & Sparepart';
        }
        if (str_contains($nameLower, 'rusak') || str_contains($nameLower, 'afkir')) {
            return 'Barang Rusak / Afkir';
        }
        if (str_contains($nameLower, 'return') || str_contains($nameLower, 'retur')) {
            return 'Gudang Retur Supplier';
        }
        return 'Outlet Store';
    }

    /**
     * Resolusi ID gudang lokal berdasarkan nama filter cabang yang dipilih.
     */
    protected function resolveMatchedWarehouseIds($branch): array
    {
        if (empty($branch)) {
            return [];
        }

        $targets = is_array($branch) ? array_map('strtolower', $branch) : [strtolower(trim($branch))];

        return Warehouse::all()->filter(function ($w) use ($targets) {
            $wName = strtolower($w->name);
            $wClean = strtolower(trim(str_ireplace(['gsk - ', 'gsk second - ', 'syihab - '], '', $w->name)));
            foreach ($targets as $t) {
                if (str_contains($wName, $t) || str_contains($wClean, $t) || str_contains($t, $wClean)) {
                    return true;
                }
            }
            return false;
        })->pluck('id')->toArray();
    }

    /**
     * Normalisasi string/array kategori menjadi array target pencarian.
     */
    protected function resolveCategoryTargets($category): array
    {
        if (empty($category)) {
            return [];
        }

        $catTargets = is_array($category) ? array_map('trim', $category) : [trim($category)];
        return array_values(array_filter($catTargets, fn($c) => $c !== ''));
    }

    /**
     * Bangun rincian valuasi per kategori produk.
     */
    protected function buildCategoryBreakdown(
        array $matchedWhIds,
        array $catTargets,
        string $databaseSource,
        float $totalValuation,
        float $buTotalRawVal = 1.0
    ): array {
        if (!empty($matchedWhIds)) {
            $catQuery = DB::table('warehouse_stocks')
                ->join('product_accurates', function ($join) {
                    $join->on('warehouse_stocks.variant_id', '=', 'product_accurates.id')
                         ->where('warehouse_stocks.variant_type', '=', ProductAccurate::class);
                })
                ->whereIn('warehouse_stocks.warehouse_id', $matchedWhIds)
                ->where('warehouse_stocks.stock', '>', 0)
                ->when($databaseSource !== 'all', fn($q) => $q->where('product_accurates.database_source', $databaseSource));

            if (!empty($catTargets)) {
                $catQuery->where(function ($q) use ($catTargets) {
                    foreach ($catTargets as $cat) {
                        $q->orWhere('product_accurates.categoryName', 'LIKE', '%' . $cat . '%');
                    }
                });
            }

            $dbCats = $catQuery->select(
                'product_accurates.categoryName',
                DB::raw('count(*) as count'),
                DB::raw('sum(warehouse_stocks.stock) as total_qty'),
                DB::raw('sum(warehouse_stocks.stock * product_accurates.base_cost) as total_val')
            )->groupBy('product_accurates.categoryName')->orderByDesc('total_val')->get();
        } else {
            $catQuery = ProductAccurate::where('stock', '>', 0)
                ->when($databaseSource !== 'all', fn($q) => $q->where('database_source', $databaseSource));

            if (!empty($catTargets)) {
                $catQuery->where(function ($q) use ($catTargets) {
                    foreach ($catTargets as $cat) {
                        $q->orWhere('categoryName', 'LIKE', '%' . $cat . '%');
                    }
                });
            }

            $dbCats = $catQuery->select(
                'categoryName',
                DB::raw('count(*) as count'),
                DB::raw('sum(stock) as total_qty'),
                DB::raw('sum(stock * base_cost) as total_val')
            )->groupBy('categoryName')->orderByDesc('total_val')->get();
        }

        $categoryColors = ['#6366f1', '#8b5cf6', '#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#14b8a6', '#64748b'];
        $categoryBreakdown = [];
        $totalRawCatVal = (float) $dbCats->sum('total_val') ?: 1.0;
        $buLabel = $databaseSource === 'syihab' ? 'Syihab' : ($databaseSource === 'second' ? 'GSK Second' : 'Semua BU');

        foreach ($dbCats as $idx => $c) {
            $catName = $c->categoryName ?: 'Umum';

            // Skala proporsional terhadap totalValuation (saldo GL) berdasarkan porsinya di database
            $scaledVal = round(($c->total_val / $buTotalRawVal) * $totalValuation, 2);
            $pct = $totalValuation > 0 ? round(($scaledVal / $totalValuation) * 100, 1) : 0;

            // Jika sedang difilter spesifik satu kategori, persentase dalam subset adalah proporsi terhadap totalRawCatVal
            if (!empty($catTargets)) {
                $pct = round(($c->total_val / $totalRawCatVal) * 100, 1);
            }

            $categoryBreakdown[] = [
                'category_name' => $catName,
                'business_unit' => $buLabel,
                'total_valuation' => $scaledVal,
                'total_qty' => (int)$c->total_qty,
                'average_unit_cost' => $c->total_qty > 0 ? round($scaledVal / $c->total_qty, 2) : 0,
                'percentage' => $pct,
                'color' => $categoryColors[$idx % count($categoryColors)],
            ];
        }

        return $categoryBreakdown;
    }

    /**
     * Bangun rincian valuasi per merek (Brand).
     */
    protected function buildBrandBreakdown(
        array $matchedWhIds,
        array $catTargets,
        string $databaseSource,
        float $effectiveValuation
    ): array {
        if (!empty($matchedWhIds)) {
            $brandQuery = DB::table('warehouse_stocks')
                ->join('product_accurates', function ($join) {
                    $join->on('warehouse_stocks.variant_id', '=', 'product_accurates.id')
                         ->where('warehouse_stocks.variant_type', '=', ProductAccurate::class);
                })
                ->whereIn('warehouse_stocks.warehouse_id', $matchedWhIds)
                ->where('warehouse_stocks.stock', '>', 0)
                ->when($databaseSource !== 'all', fn($q) => $q->where('product_accurates.database_source', $databaseSource));

            if (!empty($catTargets)) {
                $brandQuery->where(function ($q) use ($catTargets) {
                    foreach ($catTargets as $cat) {
                        $q->orWhere('product_accurates.categoryName', 'LIKE', '%' . $cat . '%');
                    }
                });
            }

            $dbBrands = $brandQuery->select(
                'product_accurates.brandName',
                DB::raw('count(*) as count'),
                DB::raw('sum(warehouse_stocks.stock) as total_qty'),
                DB::raw('sum(warehouse_stocks.stock * product_accurates.base_cost) as total_val')
            )->groupBy('product_accurates.brandName')->orderByDesc('total_val')->get();
        } else {
            $brandQuery = ProductAccurate::where('stock', '>', 0)
                ->when($databaseSource !== 'all', fn($q) => $q->where('database_source', $databaseSource));

            if (!empty($catTargets)) {
                $brandQuery->where(function ($q) use ($catTargets) {
                    foreach ($catTargets as $cat) {
                        $q->orWhere('categoryName', 'LIKE', '%' . $cat . '%');
                    }
                });
            }

            $dbBrands = $brandQuery->select(
                'brandName',
                DB::raw('count(*) as count'),
                DB::raw('sum(stock) as total_qty'),
                DB::raw('sum(stock * base_cost) as total_val')
            )->groupBy('brandName')->orderByDesc('total_val')->get();
        }

        $totalBrandVal = (float) $dbBrands->sum('total_val') ?: 1.0;
        $brandBreakdown = [];

        foreach ($dbBrands as $b) {
            $bName = $b->brandName ?: 'Lainnya';
            $pct = round(($b->total_val / $totalBrandVal) * 100, 1);
            $scaledVal = round(($b->total_val / $totalBrandVal) * $effectiveValuation, 2);

            $brandBreakdown[] = [
                'brand_name' => $bName,
                'total_valuation' => $scaledVal,
                'total_qty' => (int)$b->total_qty,
                'percentage' => $pct,
            ];
        }

        return $brandBreakdown;
    }

    /**
     * Ambil 10 item dengan valuasi tertinggi beserta perhitungan aging dan risiko RIIL.
     */
    protected function buildTopValuationItems(array $matchedWhIds, array $catTargets, string $databaseSource): array
    {
        $hasWhStocks = DB::table('warehouse_stocks')->where('stock', '>', 0)->exists();

        if ($hasWhStocks) {
            $topItemsQuery = DB::table('warehouse_stocks')
                ->join('product_accurates', function ($join) {
                    $join->on('warehouse_stocks.variant_id', '=', 'product_accurates.id')
                         ->where('warehouse_stocks.variant_type', '=', ProductAccurate::class);
                })
                ->join('warehouses', 'warehouse_stocks.warehouse_id', '=', 'warehouses.id')
                ->where('warehouse_stocks.stock', '>', 0)
                ->where('product_accurates.base_cost', '>', 0)
                ->when(!empty($matchedWhIds), fn($q) => $q->whereIn('warehouse_stocks.warehouse_id', $matchedWhIds))
                ->when($databaseSource !== 'all', fn($q) => $q->where('product_accurates.database_source', $databaseSource));

            if (!empty($catTargets)) {
                $topItemsQuery->where(function ($q) use ($catTargets) {
                    foreach ($catTargets as $cat) {
                        $q->orWhere('product_accurates.categoryName', 'LIKE', '%' . $cat . '%');
                    }
                });
            }

            $dbTopItems = $topItemsQuery->select([
                'product_accurates.id as product_id',
                'product_accurates.item_no',
                'product_accurates.name',
                'product_accurates.categoryName',
                'product_accurates.brandName',
                'warehouse_stocks.stock',
                'product_accurates.base_cost',
                DB::raw('(warehouse_stocks.stock * product_accurates.base_cost) as total_val'),
                'warehouse_stocks.updated_at',
                'warehouses.id as warehouse_id',
                'warehouses.name as warehouse_name',
            ])->orderByDesc('total_val')->take(10)->get();
        } else {
            // Fallback jika data warehouse_stocks belum disinkronkan dari Accurate
            $topItemsQuery = ProductAccurate::where('stock', '>', 0)
                ->where('base_cost', '>', 0)
                ->when($databaseSource !== 'all', fn($q) => $q->where('database_source', $databaseSource));

            if (!empty($catTargets)) {
                $topItemsQuery->where(function ($q) use ($catTargets) {
                    foreach ($catTargets as $cat) {
                        $q->orWhere('categoryName', 'LIKE', '%' . $cat . '%');
                    }
                });
            }

            $dbTopItems = $topItemsQuery->select([
                'id as product_id',
                'item_no',
                'name',
                'categoryName',
                'brandName',
                'stock',
                'base_cost',
                DB::raw('(stock * base_cost) as total_val'),
                'updated_at',
            ])->orderByDesc('total_val')->take(10)->get();
        }

        $topValuationItems = [];
        foreach ($dbTopItems as $it) {
            // Hitung umur fisik riil dari nomor seri / IMEI yang masih Available
            $oldestSnQuery = DB::table('product_serial_numbers')
                ->where('status', 'Available')
                ->where(function ($q) use ($it) {
                    $q->where('item_no', $it->item_no);
                    if (!empty($it->product_id)) {
                        $q->orWhere('product_accurate_id', $it->product_id);
                    }
                });

            if (!empty($it->warehouse_id)) {
                $oldestSnQuery->where('warehouse_id', $it->warehouse_id);
            } elseif (!empty($matchedWhIds)) {
                $oldestSnQuery->whereIn('warehouse_id', $matchedWhIds);
            }

            $oldestSn = $oldestSnQuery->selectRaw('MIN(COALESCE(receipt_date, created_at)) as oldest_in_date')->first();

            if ($oldestSn && $oldestSn->oldest_in_date) {
                $days = (int) round(Carbon::parse($oldestSn->oldest_in_date)->diffInDays(now()));
            } else {
                // Fallback riil dari tanggal barang masuk / update di database
                $fallbackDate = $it->updated_at ?? now();
                $days = (int) round(Carbon::parse($fallbackDate)->diffInDays(now()));
            }

            $risk = 'LOW';
            if ($days > 90) {
                $risk = 'CRITICAL';
            } elseif ($days > 60) {
                $risk = 'HIGH';
            } elseif ($days > 35) {
                $risk = 'MEDIUM';
            }

            $topValuationItems[] = [
                'sku' => $it->item_no,
                'name' => $it->name,
                'category' => $it->categoryName ?: 'Gadget',
                'brand' => $it->brandName ?: 'Apple',
                'stock_qty' => (int)$it->stock,
                'unit_cost' => (float)$it->base_cost,
                'total_valuation' => (float)$it->total_val,
                'days_in_inventory' => $days,
                'risk_status' => $risk,
                'warehouse_name' => $it->warehouse_name ?? null,
            ];
        }

        return $topValuationItems;
    }

    /**
     * Hitung metrik RIIL barang lambat bergerak (slow moving > 60 hari) dari unit fisik dan stok.
     */
    protected function calculateSlowMoving(
        string $databaseSource,
        float $effectiveValuation,
        int $thresholdDays = 60,
        array $matchedWhIds = [],
        array $catTargets = []
    ): array {
        $cutoffDate = now()->subDays($thresholdDays);

        // 1. Hitung unit ber-SN (IMEI/Serial) yang mengendap > threshold (60 hari) dengan status 'Available'
        $slowSnQuery = DB::table('product_serial_numbers')
            ->leftJoin('product_accurates', 'product_serial_numbers.product_accurate_id', '=', 'product_accurates.id')
            ->where('product_serial_numbers.status', 'Available')
            ->where(function ($q) use ($cutoffDate) {
                $q->where('product_serial_numbers.receipt_date', '<=', $cutoffDate)
                  ->orWhere(function ($q2) use ($cutoffDate) {
                      $q2->whereNull('product_serial_numbers.receipt_date')
                         ->where('product_serial_numbers.created_at', '<=', $cutoffDate);
                  });
            });

        if ($databaseSource !== 'all') {
            $slowSnQuery->where('product_accurates.database_source', $databaseSource);
        }

        if (!empty($matchedWhIds)) {
            $slowSnQuery->whereIn('product_serial_numbers.warehouse_id', $matchedWhIds);
        }

        if (!empty($catTargets)) {
            $slowSnQuery->where(function ($q) use ($catTargets) {
                foreach ($catTargets as $cat) {
                    $q->orWhere('product_accurates.categoryName', 'LIKE', '%' . $cat . '%');
                }
            });
        }

        $slowSns = $slowSnQuery->select([
            'product_serial_numbers.serial_number',
            'product_serial_numbers.hpp',
            'product_accurates.base_cost',
            'product_accurates.base_price',
        ])->get();

        $slowMovingSnQty = $slowSns->count();
        $slowMovingSnVal = $slowSns->sum(function ($sn) {
            if ($sn->hpp > 0) return (float) $sn->hpp;
            if ($sn->base_cost > 0) return (float) $sn->base_cost;
            return (float) round(($sn->base_price ?: 0) * 0.82);
        });

        // 2. Hitung item non-SN (aksesoris / stok umum) yang sudah > 60 hari tidak mengalami pergerakan
        $snSkus = DB::table('product_serial_numbers')->distinct()->pluck('item_no')->toArray();

        $slowNonSnQuery = ProductAccurate::where('stock', '>', 0)
            ->where('created_at', '<=', $cutoffDate)
            ->when(!empty($snSkus), fn($q) => $q->whereNotIn('item_no', $snSkus))
            ->when($databaseSource !== 'all', fn($q) => $q->where('database_source', $databaseSource))
            ->when(!empty($catTargets), function ($q) use ($catTargets) {
                $q->where(function ($sub) use ($catTargets) {
                    foreach ($catTargets as $cat) {
                        $sub->orWhere('categoryName', 'LIKE', '%' . $cat . '%');
                    }
                });
            });

        $slowNonSnItems = $slowNonSnQuery->get();
        $slowNonSnQty = $slowNonSnItems->sum('stock');
        $slowNonSnVal = $slowNonSnItems->sum(function ($p) {
            $cost = $p->base_cost > 0 ? $p->base_cost : round($p->base_price * 0.82);
            return (float) ($p->stock * $cost);
        });

        $totalSlowQty = (int) ($slowMovingSnQty + $slowNonSnQty);
        $totalSlowVal = (float) ($slowMovingSnVal + $slowNonSnVal);

        $percentage = $effectiveValuation > 0
            ? round(($totalSlowVal / $effectiveValuation) * 100, 1)
            : 0;

        return [
            'threshold_days' => $thresholdDays,
            'total_valuation' => round($totalSlowVal, 2),
            'total_qty' => $totalSlowQty,
            'percentage_of_inventory' => $percentage,
        ];
    }
}
