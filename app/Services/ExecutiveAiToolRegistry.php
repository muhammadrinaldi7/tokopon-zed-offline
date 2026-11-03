<?php

namespace App\Services;

use App\Models\BusinessUnit;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ExecutiveAiToolRegistry
{
    protected ExecutiveMetricsService $metricsService;
    protected ExecutiveInventoryService $inventoryService;
    protected AccurateInventoryService $accurateInventoryService;
    protected AccurateFinancialService $accurateFinancialService;

    public function __construct(
        ExecutiveMetricsService $metricsService,
        ExecutiveInventoryService $inventoryService,
        AccurateInventoryService $accurateInventoryService,
        AccurateFinancialService $accurateFinancialService
    ) {
        $this->metricsService = $metricsService;
        $this->inventoryService = $inventoryService;
        $this->accurateInventoryService = $accurateInventoryService;
        $this->accurateFinancialService = $accurateFinancialService;
    }

    /**
     * Determine whether user is restricted to a single Business Unit (RBAC lock).
     */
    public function getLockedBusinessUnitId(?User $user): ?int
    {
        if ($user && !$user->hasAnyRole(['superadmin', 'director', 'admin']) && !empty($user->business_unit_id)) {
            return (int) $user->business_unit_id;
        }
        return null;
    }

    /**
     * Resolve effective filters combining active dashboard filters, tool overrides, and RBAC lock.
     */
    public function resolveEffectiveFilters(array $toolArgs = [], array $activeFilters = [], ?User $user = null): array
    {
        $lockedBuId = $this->getLockedBusinessUnitId($user);

        // 1. Business Unit resolution
        $buId = $activeFilters['business_unit_id'] ?? null;
        if ($lockedBuId !== null) {
            $buId = $lockedBuId;
        } elseif (array_key_exists('business_unit', $toolArgs) && $toolArgs['business_unit'] !== null && $toolArgs['business_unit'] !== '') {
            $rawBu = strtolower(trim((string) $toolArgs['business_unit']));
            if (in_array($rawBu, ['all', 'semua', 'gabungan', 'konsolidasi'], true)) {
                $buId = null;
            } elseif (in_array($rawBu, ['1', 'syihab', 'baru'], true)) {
                $buId = 1;
            } elseif (in_array($rawBu, ['2', 'gsk', 'second', 'bekas', 'gsk_second'], true)) {
                $buId = 2;
            } elseif (in_array($rawBu, ['3', 'distri', 'distribusi', 'gsk_distri'], true)) {
                $buId = 3;
            } elseif (is_numeric($rawBu)) {
                $buId = (int) $rawBu;
            }
        }

        // 2. Date range & custom dates
        $dateRange = $toolArgs['date_range'] ?? ($activeFilters['date_range'] ?? 'this_month');
        $startDate = $toolArgs['start_date'] ?? ($activeFilters['start_date'] ?? null);
        $endDate = $toolArgs['end_date'] ?? ($activeFilters['end_date'] ?? null);

        if (!empty($toolArgs['start_date']) || !empty($toolArgs['end_date'])) {
            $dateRange = 'custom';
        } elseif (!empty($toolArgs['date_range']) && $toolArgs['date_range'] !== 'custom') {
            // Clear stale custom dates from activeFilters when LLM explicitly requests a preset period
            $startDate = null;
            $endDate = null;
        }

        // 3. Branch, Category & Project filters (inheriting active filters unless overridden or cleared with 'ALL')
        $branch = $this->mergeMultiFilter($toolArgs['branch'] ?? null, $activeFilters['branch'] ?? null);
        $category = $this->mergeMultiFilter($toolArgs['category'] ?? null, $activeFilters['category'] ?? null);
        $project = $this->mergeMultiFilter($toolArgs['project'] ?? null, $activeFilters['project'] ?? null);

        return array_filter([
            'date_range' => $dateRange,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'business_unit_id' => $buId ?: null,
            'branch' => $branch,
            'category' => $category,
            'project' => $project,
        ], fn($v) => $v !== null && $v !== '' && $v !== []);
    }

    protected function mergeMultiFilter($toolValue, $activeValue)
    {
        if ($toolValue !== null && $toolValue !== '') {
            if (is_string($toolValue) && in_array(strtoupper(trim($toolValue)), ['ALL', 'SEMUA', '*'], true)) {
                return null;
            }
            return $toolValue;
        }
        return $activeValue;
    }

    /**
     * Resolve Accurate database_source ('all', 'syihab', 'second') respecting RBAC.
     */
    protected function resolveAccurateSource(?int $buId): array
    {
        if ($buId) {
            $bu = BusinessUnit::find($buId);
            if ($bu) {
                return [$bu->code ?: 'second', $bu->name];
            }
            if ($buId === 1) {
                return ['syihab', 'Syihab Store'];
            }
            if ($buId === 2) {
                return ['second', 'GSK Second'];
            }
        }
        return ['all', 'Semua Unit Bisnis'];
    }

    /**
     * Human-readable Indonesian status label while executing a specific tool (for SSE stream UX).
     */
    public function getToolStatusLabel(string $toolName): string
    {
        return match ($toolName) {
            'get_sales_and_branch_performance' => 'Mengambil metrik Omset, Laba Kotor & Performa Cabang...',
            'get_payment_and_mdr_breakdown' => 'Menganalisis rincian Metode Pembayaran & Biaya MDR EDC...',
            'get_staff_and_salesperson_kpi' => 'Mengambil peringkat KPI Salesperson & Kasir...',
            'get_cashier_void_and_buyback_audit' => 'Memeriksa log Audit Void Kasir & Buyback HP Second...',
            'get_brand_promo_and_project_analytics' => 'Mengambil analitik Brand, Klaim Subsidi Promo & Proyek...',
            'get_pos_piutang_and_leasing_pending' => 'Memeriksa portofolio Piutang Toko & Leasing Pending...',
            'get_accurate_inventory_valuation' => 'Mengambil Valuasi Persediaan & Sebaran Gudang Accurate Online...',
            'get_accurate_financial_position' => 'Memeriksa posisi Kas/Bank, Piutang (AR) & Hutang (AP) Accurate...',
            'search_physical_stock_imei_and_deadstock' => 'Melacak ketersediaan stok fisik cabang & nomor seri/IMEI...',
            default => 'Mengambil data terverifikasi dari server...',
        };
    }

    /**
     * OpenAI-compatible Function/Tool Definitions.
     */
    public function getToolDefinitions(): array
    {
        $commonFilterProps = [
            'date_range' => [
                'type' => 'string',
                'description' => "Preset periode: 'today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'last_7_days', 'this_year', atau 'custom'. Kosongkan jika mengikuti filter layar aktif.",
            ],
            'start_date' => [
                'type' => 'string',
                'description' => 'Tanggal awal format YYYY-MM-DD (wajib diisi bersama end_date jika menanyakan tanggal/bulan spesifik di luar preset, misal 2026-09-01).',
            ],
            'end_date' => [
                'type' => 'string',
                'description' => 'Tanggal akhir format YYYY-MM-DD (misal 2026-09-30).',
            ],
            'business_unit' => [
                'type' => 'string',
                'description' => "Filter Unit Bisnis: 'syihab' (HP Baru), 'second' (GSK HP Second), 'distri' (GSK Distri), atau 'all' (Semua Unit Bisnis). Kosongkan untuk mengikuti filter layar aktif.",
            ],
            'branch' => [
                'type' => 'string',
                'description' => "Nama cabang toko (contoh: 'Banjarbaru', 'Martapura', 'Veteran', atau 'ALL' untuk seluruh cabang tanpa filter).",
            ],
            'category' => [
                'type' => 'string',
                'description' => "Nama kategori produk (pisahkan koma jika lebih dari satu, atau 'ALL' untuk semua kategori).",
            ],
            'project' => [
                'type' => 'string',
                'description' => "Nama proyek master item (contoh: 'RESMI', 'INTER', atau 'ALL' untuk semua proyek).",
            ],
        ];

        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_sales_and_branch_performance',
                    'description' => 'Ambil ringkasan KPI Penjualan (Omset Bersih/Net Sales, Gross Sales, Laba Kotor, Margin %, Biaya MDR, Total Order, Kas Lunas, Piutang, Pertumbuhan MTD), Peringkat Performa Seluruh Cabang, dan Top 10 Produk Terlaris. Panggil beberapa kali dengan periode berbeda jika Direksi meminta komparasi antar bulan/periode.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $commonFilterProps,
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_payment_and_mdr_breakdown',
                    'description' => 'Ambil rincian metode pembayaran, mesin EDC bank (BCA, Mandiri, BNI, QRIS, Transfer, Leasing, Tunai), porsi pembayaran, dan potongan biaya MDR terbesar.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $commonFilterProps,
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_staff_and_salesperson_kpi',
                    'description' => 'Ambil papan peringkat pencapaian KPI Salesperson (omset, unit terjual, jumlah transaksi) dan keaktifan Kasir.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $commonFilterProps,
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_cashier_void_and_buyback_audit',
                    'description' => 'Ambil laporan Audit Kasir: daftar kasir yang paling sering membatalkan nota (void/cancellation), nominal pembatalan, alasan void, serta deviasi kemahalan beli HP second (SellPhone buyback overpay).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $commonFilterProps,
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_brand_promo_and_project_analytics',
                    'description' => 'Ambil analitik Performa Merk/Brand (market share, margin per brand), Klaim Subsidi Promo Vendor, dan Penjualan per Proyek (Resmi/Inter/Proyek).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => array_merge($commonFilterProps, [
                            'focus' => [
                                'type' => 'string',
                                'enum' => ['all', 'brand', 'promo', 'project'],
                                'description' => "Fokus laporan: 'brand', 'promo', 'project', atau 'all'.",
                            ],
                        ]),
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_pos_piutang_and_leasing_pending',
                    'description' => 'Ambil rincian Piutang POS Tokopon: pemisahan Piutang Toko langsung (belum lunas) vs Piutang Leasing/Multi-Finance status PENDING belum cair (Kredivo, Akulaku, HCI, Samsung Finance) beserta daftar nota menunggak terbesar.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $commonFilterProps,
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_accurate_inventory_valuation',
                    'description' => 'Ambil data RESMI 1:1 Accurate Online untuk Tab Valuasi Persediaan: total nilai aset modal stok (Rp), total unit fisik, saldo akun GL Persediaan, stok mengendap >60 hari (slow moving), rincian modal & unit per Gudang/Cabang, per Kategori, per Brand, dan Top Item bernilai modal terbesar.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'business_unit' => $commonFilterProps['business_unit'],
                            'branch' => [
                                'type' => 'string',
                                'description' => "Nama gudang/cabang (atau 'ALL' untuk seluruh gudang).",
                            ],
                            'category' => $commonFilterProps['category'],
                            'project' => $commonFilterProps['project'],
                            'as_of_date' => [
                                'type' => 'string',
                                'description' => 'Tanggal posisi stok format YYYY-MM-DD (opsional, default hari ini).',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_accurate_financial_position',
                    'description' => 'Ambil posisi Keuangan RESMI Accurate Online: Saldo Kas & Rekening Bank (likuiditas riil), Total Piutang Usaha Accurate (AR + aging + top debitur), Total Hutang Dagang Accurate (AP + aging + top vendor), serta Net Working Capital.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'business_unit' => $commonFilterProps['business_unit'],
                            'as_of_date' => [
                                'type' => 'string',
                                'description' => 'Tanggal posisi neraca/kas format YYYY-MM-DD (opsional, default hari ini).',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_physical_stock_imei_and_deadstock',
                    'description' => 'Cari stok & harga model HP/produk spesifik lintas cabang beserta rekap total seluruh SKU yang cocok, lacak posisi/histori 1 unit berdasarkan nomor IMEI/Serial Number (SN), atau ambil daftar peringatan Dead Stock (>30 hari belum terjual).',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'action' => [
                                'type' => 'string',
                                'enum' => ['stock_search', 'imei_track', 'dead_stock'],
                                'description' => "Pilih 'stock_search' untuk cek stok/harga produk, 'imei_track' untuk lacak nomor seri/IMEI, atau 'dead_stock' untuk stok mengendap >30 hari.",
                            ],
                            'keyword' => [
                                'type' => 'string',
                                'description' => "Kata kunci nama model produk (contoh: 'iPhone 15 Pro', 'Samsung S24', atau kosongkan jika ingin rekap seluruh stok pada Unit Bisnis terkait).",
                            ],
                            'serial_number' => [
                                'type' => 'string',
                                'description' => 'Nomor IMEI atau Serial Number (khusus action=imei_track).',
                            ],
                            'business_unit' => $commonFilterProps['business_unit'],
                            'branch' => $commonFilterProps['branch'],
                            'days_threshold' => [
                                'type' => 'integer',
                                'description' => 'Batas hari mengendap untuk dead_stock (default: 30).',
                            ],
                        ],
                        'required' => ['action'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Execute a named tool and return compact, structured, verified Indonesian text for the LLM.
     */
    public function executeTool(string $toolName, array $args, array $activeFilters = [], ?User $user = null): string
    {
        try {
            $filters = $this->resolveEffectiveFilters($args, $activeFilters, $user);
            $filterDesc = $this->describeFilters($filters);

            return match ($toolName) {
                'get_sales_and_branch_performance' => $this->runSalesAndBranchTool($filters, $filterDesc),
                'get_payment_and_mdr_breakdown' => $this->runPaymentMdrTool($filters, $filterDesc),
                'get_staff_and_salesperson_kpi' => $this->runStaffKpiTool($filters, $filterDesc),
                'get_cashier_void_and_buyback_audit' => $this->runCashierAuditTool($filters, $filterDesc),
                'get_brand_promo_and_project_analytics' => $this->runBrandPromoProjectTool($filters, $filterDesc, $args['focus'] ?? 'all'),
                'get_pos_piutang_and_leasing_pending' => $this->runPosPiutangTool($filters, $filterDesc),
                'get_accurate_inventory_valuation' => $this->runAccurateInventoryTool($filters, $args['as_of_date'] ?? null),
                'get_accurate_financial_position' => $this->runAccurateFinancialTool($filters, $args['as_of_date'] ?? null),
                'search_physical_stock_imei_and_deadstock' => $this->runStockImeiDeadstockTool($filters, $args, $user),
                default => "[TOOL ERROR: Nama tool '{$toolName}' tidak dikenali.]",
            };
        } catch (\Throwable $e) {
            Log::error("ExecutiveAiToolRegistry error [{$toolName}]: " . $e->getMessage());
            return "[PERINGATAN SISTEM: Gagal mengambil data pada modul '{$toolName}'. Laporkan secara transparan ke Direksi bahwa modul data tersebut sedang tidak merespons sementara tanpa mengarang angka.]";
        }
    }

    protected function describeFilters(array $filters): string
    {
        $parts = [];
        $range = $filters['date_range'] ?? 'this_month';
        if ($range === 'custom' && (!empty($filters['start_date']) || !empty($filters['end_date']))) {
            $parts[] = 'Periode: ' . ($filters['start_date'] ?? '?') . ' s/d ' . ($filters['end_date'] ?? '?');
        } else {
            $parts[] = "Periode: {$range}";
        }

        if (!empty($filters['business_unit_id'])) {
            $buId = (int) $filters['business_unit_id'];
            $buLabel = match ($buId) {
                1 => 'Syihab (HP Baru)',
                2 => 'GSK Second',
                3 => 'GSK Distri',
                default => "BU #{$buId}",
            };
            $parts[] = "Unit Bisnis: {$buLabel}";
        } else {
            $parts[] = 'Unit Bisnis: Semua Unit Bisnis';
        }

        foreach (['branch' => 'Cabang', 'category' => 'Kategori', 'project' => 'Proyek'] as $k => $lbl) {
            if (!empty($filters[$k])) {
                $val = is_array($filters[$k]) ? implode(', ', $filters[$k]) : (string) $filters[$k];
                $parts[] = "{$lbl}: {$val}";
            }
        }

        return implode(' | ', $parts);
    }

    protected function runSalesAndBranchTool(array $filters, string $filterDesc): string
    {
        $kpi = $this->metricsService->getKpiSummary($filters);
        $branches = $this->metricsService->getBranchComparison($filters);
        $topProducts = $this->metricsService->getTopProducts($filters, 10);

        $s = $kpi['summary'] ?? [];
        $periodInfo = $kpi['period'] ?? [];
        $dateWindow = ($periodInfo['start_date'] ?? '-') . ' s/d ' . ($periodInfo['end_date'] ?? '-');

        $grossSales = number_format($s['gross_sales'] ?? 0, 0, ',', '.');
        $mdrFee = number_format($s['mdr_fee'] ?? 0, 0, ',', '.');
        $netSales = number_format($s['net_sales'] ?? 0, 0, ',', '.');
        $grossProfit = number_format($s['gross_profit'] ?? 0, 0, ',', '.');
        $margin = number_format($s['profit_margin'] ?? 0, 2, ',', '.');
        $orders = number_format($s['total_orders'] ?? 0, 0, ',', '.');
        $unitsSold = number_format($s['total_items_sold'] ?? 0, 0, ',', '.');
        $completed = number_format($s['completed_amount'] ?? 0, 0, ',', '.');
        $piutang = number_format($s['piutang_amount'] ?? 0, 0, ',', '.');

        $out = "[HASIL TOOL: KPI PENJUALAN & PERFORMA CABANG ({$filterDesc} | Tanggal: {$dateWindow})]\n";
        $out .= "- Gross Sales (Omset Kotor Faktur): Rp {$grossSales}\n";
        $out .= "- Potongan Biaya MDR: Rp {$mdrFee}\n";
        $out .= "- Net Sales (Omset Bersih): Rp {$netSales}\n";
        $out .= "- Gross Profit (Laba Kotor): Rp {$grossProfit} (Margin: {$margin}%)\n";
        $out .= "- Total Transaksi: {$orders} pesanan ({$unitsSold} item terjual)\n";
        $out .= "- Realisasi Kas Lunas: Rp {$completed} | Piutang Berjalan Periode Ini: Rp {$piutang}\n";

        if (!empty($kpi['mtd_comparison']['growth'])) {
            $g = $kpi['mtd_comparison']['growth'];
            $prevNet = number_format($kpi['mtd_comparison']['previous_summary']['net_sales'] ?? 0, 0, ',', '.');
            $out .= "- Pertumbuhan vs Periode Pembanding: Net Sales " . ($g['net_sales_pct'] ?? 0) . "% (Pembanding: Rp {$prevNet}), Laba Kotor " . ($g['gross_profit_pct'] ?? 0) . "%\n";
        }

        if (!empty($branches)) {
            $out .= "\nPERINGKAT CABANG:\n";
            foreach ($branches as $idx => $b) {
                $bNet = number_format($b['net_sales'] ?? 0, 0, ',', '.');
                $bProf = number_format($b['gross_profit'] ?? 0, 0, ',', '.');
                $bMar = number_format($b['margin_percentage'] ?? 0, 1, ',', '.');
                $bShare = $b['contribution_percentage'] ?? 0;
                $bOrd = number_format($b['orders_count'] ?? 0, 0, ',', '.');
                $out .= "- #" . ($idx + 1) . " {$b['branch_name']}: Net Sales Rp {$bNet} (Share {$bShare}%), Laba Kotor Rp {$bProf} (Margin {$bMar}%), {$bOrd} trx\n";
            }
        } else {
            $out .= "\nPERINGKAT CABANG: Tidak ada transaksi cabang pada filter ini (Rp 0).\n";
        }

        if (!empty($topProducts)) {
            $out .= "\nTOP 10 PRODUK TERLARIS:\n";
            foreach (array_slice($topProducts, 0, 10) as $idx => $p) {
                $pRev = number_format($p['revenue'] ?? 0, 0, ',', '.');
                $pProf = number_format($p['profit'] ?? 0, 0, ',', '.');
                $out .= "- #" . ($idx + 1) . " {$p['name']}: {$p['qty_sold']} unit | Omset Rp {$pRev} | Laba Rp {$pProf}\n";
            }
        }

        return trim($out);
    }

    protected function runPaymentMdrTool(array $filters, string $filterDesc): string
    {
        $payments = $this->metricsService->getPaymentMethodBreakdown($filters);
        if (empty($payments)) {
            return "[HASIL TOOL: BREAKDOWN PEMBAYARAN & MDR ({$filterDesc})]: Tidak ada data transaksi pembayaran (Rp 0).";
        }

        $totalGross = array_sum(array_column($payments, 'total_amount'));
        $totalMdr = array_sum(array_column($payments, 'total_mdr'));
        $byMdrDesc = $payments;
        usort($byMdrDesc, fn($a, $b) => ($b['total_mdr'] <=> $a['total_mdr']));

        $out = "[HASIL TOOL: RINCIAN METODE PEMBAYARAN & BIAYA MDR EDC ({$filterDesc})]\n";
        $out .= '- Total Pembayaran Masuk: Rp ' . number_format($totalGross, 0, ',', '.') . "\n";
        $out .= '- Total Beban Biaya MDR Keseluruhan: Rp ' . number_format($totalMdr, 0, ',', '.') . "\n\n";
        $out .= "DAFTAR METODE BAYAR / EDC (DIURUTKAN DARI PEMAKAN BIAYA MDR TERBESAR):\n";

        foreach ($byMdrDesc as $idx => $p) {
            $amt = number_format($p['total_amount'] ?? 0, 0, ',', '.');
            $mdr = number_format($p['total_mdr'] ?? 0, 0, ',', '.');
            $net = number_format($p['net_amount'] ?? 0, 0, ',', '.');
            $effRate = ($p['total_amount'] ?? 0) > 0 ? round((($p['total_mdr'] ?? 0) / $p['total_amount']) * 100, 2) : 0;
            $out .= '- #' . ($idx + 1) . " {$p['payment_method_name']} (Bank/Kategori: {$p['bank_name']} / {$p['category']}): Gross Rp {$amt} ({$p['transactions_count']} trx, Share {$p['share_percentage']}%) | Biaya MDR: Rp {$mdr} (Efektif {$effRate}%) | Net: Rp {$net}\n";
        }

        return trim($out);
    }

    protected function runStaffKpiTool(array $filters, string $filterDesc): string
    {
        $staff = $this->metricsService->getStaffKpi($filters);
        $sales = $staff['sales'] ?? [];
        $cashiers = $staff['cashiers'] ?? [];

        if (empty($sales) && empty($cashiers)) {
            return "[HASIL TOOL: KPI STAF & SALESPERSON ({$filterDesc})]: Belum ada data pencapaian salesperson/kasir pada periode & filter ini.";
        }

        $out = "[HASIL TOOL: PERINGKAT KPI SALESPERSON & KASIR ({$filterDesc})]\n";
        $out .= '- Total Salesperson Aktif: ' . ($staff['summary']['total_sales_count'] ?? count($sales)) . ' orang | Total Kasir Aktif: ' . ($staff['summary']['total_cashiers_count'] ?? count($cashiers)) . " orang\n";

        if (!empty($sales)) {
            $out .= "\nTOP 15 SALESPERSON (BERDASARKAN OMSET BERSIH):\n";
            foreach (array_slice($sales, 0, 15) as $s) {
                $net = number_format($s['net_sales'] ?? 0, 0, ',', '.');
                $prof = number_format($s['gross_profit'] ?? 0, 0, ',', '.');
                $branch = $s['branch_name'] ?? ($s['branch'] ?? '-');
                $out .= "- #{$s['rank']} {$s['sales_name']} (Cabang: {$branch}): Omset Bersih Rp {$net} | Laba Rp {$prof} | {$s['total_qty']} unit | {$s['orders_count']} nota\n";
            }
        }

        if (!empty($cashiers)) {
            $out .= "\nTOP 10 KASIR (BERDASARKAN JUMLAH TRANSAKSI):\n";
            foreach (array_slice($cashiers, 0, 10) as $c) {
                $cName = $c['cashier_name'] ?? '-';
                $cNet = number_format($c['net_sales'] ?? ($c['total_sales'] ?? 0), 0, ',', '.');
                $out .= "- #{$c['rank']} {$cName}: {$c['orders_count']} transaksi (Porsi {$c['share_pct']}%, Nilai Rp {$cNet})\n";
            }
        }

        return trim($out);
    }

    protected function runCashierAuditTool(array $filters, string $filterDesc): string
    {
        $audit = $this->metricsService->getCashierAudit($filters);
        $cancel = $audit['cancellation_audit'] ?? [];
        $sellPhone = $audit['sell_phone_audit'] ?? [];

        $totCancel = (int) ($cancel['total_cancellations'] ?? 0);
        $totCancelAmt = number_format($cancel['total_cancelled_amount'] ?? 0, 0, ',', '.');

        $out = "[HASIL TOOL: AUDIT PEMBATALAN NOTA (VOID KASIR) & BUYBACK HP SECOND ({$filterDesc})]\n";
        $out .= "1. RINGKASAN VOID / PEMBATALAN NOTA:\n";
        $out .= "- Total Pengajuan/Nota Batal (Void): {$totCancel} nota\n";
        $out .= "- Total Nilai Faktur yang Dibatalkan: Rp {$totCancelAmt}\n";

        if (!empty($cancel['cashier_leaderboard'])) {
            $out .= "\nPERINGKAT KASIR DENGAN VOID TERBANYAK:\n";
            foreach (array_slice($cancel['cashier_leaderboard'], 0, 10) as $idx => $c) {
                $amt = number_format($c['total_amount'] ?? 0, 0, ',', '.');
                $reasons = !empty($c['reasons']) ? implode('; ', $c['reasons']) : 'Tanpa keterangan';
                $out .= '- #' . ($idx + 1) . " {$c['cashier_name']}: {$c['cancellation_count']} kali void (Total Nilai Nota: Rp {$amt}) | Alasan: {$reasons}\n";
            }
        } else {
            $out .= "- Tidak ada kasir yang melakukan pembatalan nota (0 void) pada periode ini.\n";
        }

        if (!empty($cancel['recent_logs'])) {
            $out .= "\nLOG NOTA VOID TERBARU (MAKS 8):\n";
            foreach (array_slice($cancel['recent_logs'], 0, 8) as $log) {
                $gTot = number_format($log['grand_total'] ?? 0, 0, ',', '.');
                $out .= "- [{$log['date']}] Nota {$log['order_number']} | Cabang: {$log['branch']} | Kasir: {$log['cashier_name']} | Rp {$gTot} | Status: {$log['status']} | Alasan: {$log['reason']}\n";
            }
        }

        $overpayUnits = (int) ($sellPhone['total_overpay_units'] ?? 0);
        $overpayAmt = number_format($sellPhone['total_overpay_amount'] ?? 0, 0, ',', '.');
        $boughtUnits = (int) ($sellPhone['total_bought_units'] ?? 0);
        $boughtAmt = number_format($sellPhone['total_bought_amount'] ?? 0, 0, ',', '.');

        $out .= "\n2. AUDIT BUYBACK / TUKAR TAMBAH HP SECOND (SELLPHONE):\n";
        $out .= "- Total Unit Dibeli Masuk: {$boughtUnits} unit (Total Rp {$boughtAmt})\n";
        $out .= "- Indikasi Kemahalan di Atas Harga Sistem (Overpay): {$overpayUnits} unit (Selisih Lebih Bayar: Rp {$overpayAmt})\n";

        if (!empty($sellPhone['cashier_overpay_leaderboard'])) {
            foreach (array_slice($sellPhone['cashier_overpay_leaderboard'], 0, 5) as $idx => $op) {
                $opName = $op['cashier_name'] ?? '-';
                $opCnt = $op['overpay_count'] ?? 0;
                $opDiff = number_format($op['total_overpay'] ?? ($op['diff_amount'] ?? 0), 0, ',', '.');
                $out .= '  * #' . ($idx + 1) . " {$opName}: {$opCnt} unit overpay (Total selisih Rp {$opDiff})\n";
            }
        }

        return trim($out);
    }

    protected function runBrandPromoProjectTool(array $filters, string $filterDesc, string $focus = 'all'): string
    {
        $out = "[HASIL TOOL: ANALITIK BRAND, PROMO & PROYEK ({$filterDesc})]\n";

        if (in_array($focus, ['all', 'brand'], true)) {
            $brandData = $this->metricsService->getBrandAnalytics($filters);
            $brands = $brandData['brands'] ?? [];
            $out .= "\nA. PERFORMA MERK / BRAND:\n";
            if (!empty($brands)) {
                foreach (array_slice($brands, 0, 12) as $b) {
                    $bName = $b['brand'] ?? ($b['brand_name'] ?? '-');
                    $bSales = number_format($b['gross_sales'] ?? 0, 0, ',', '.');
                    $bProf = number_format($b['gross_profit'] ?? 0, 0, ',', '.');
                    $out .= "- #{$b['rank']} {$bName}: Omset Rp {$bSales} (Market Share {$b['market_share_pct']}%), Laba Rp {$bProf} (Margin {$b['margin_pct']}%), {$b['qty_sold']} unit\n";
                }
            } else {
                $out .= "- Tidak ada data penjualan brand pada filter ini.\n";
            }
        }

        if (in_array($focus, ['all', 'project'], true)) {
            $projReport = $this->metricsService->getProjectSalesReport($filters);
            $projects = $projReport['project_breakdown'] ?? [];
            $out .= "\nB. PENJUALAN PER PROYEK (MASTER ITEM):\n";
            if (!empty($projects)) {
                foreach ($projects as $p) {
                    $pSales = number_format($p['net_sales'] ?? 0, 0, ',', '.');
                    $pProf = number_format($p['gross_profit'] ?? 0, 0, ',', '.');
                    $pMar = number_format($p['margin_percentage'] ?? 0, 1, ',', '.');
                    $out .= "- Proyek {$p['project']}: Net Sales Rp {$pSales} (Share {$p['contribution_percentage']}%), Laba Rp {$pProf} (Margin {$pMar}%), Qty {$p['total_qty']} unit\n";
                }
            } else {
                $out .= "- Tidak ada data proyek pada filter ini.\n";
            }
        }

        if (in_array($focus, ['all', 'promo'], true)) {
            $promo = $this->metricsService->getPromoClaims($filters);
            $pSum = $promo['summary'] ?? [];
            $totSub = number_format($pSum['total_discount_amount'] ?? 0, 0, ',', '.');
            $totCnt = $pSum['total_promo_claims_count'] ?? 0;
            $out .= "\nC. KLAIM SUBSIDI PROMO & VENDOR:\n";
            $out .= "- Total Klaim Subsidi Promo: Rp {$totSub} ({$totCnt} klaim)\n";
            foreach (array_slice($promo['vendor_breakdown'] ?? [], 0, 8) as $v) {
                $vSub = number_format($v['total_subsidy'] ?? 0, 0, ',', '.');
                $out .= "  * Vendor {$v['vendor_name']}: Rp {$vSub} ({$v['claims_count']} klaim)\n";
            }
        }

        return trim($out);
    }

    protected function runPosPiutangTool(array $filters, string $filterDesc): string
    {
        $report = $this->metricsService->getPiutangReport($filters);
        $summary = $report['summary'] ?? [];

        $totalPiutang = number_format($summary['total_piutang'] ?? 0, 0, ',', '.');
        $totalSisa = number_format($summary['total_sisa'] ?? 0, 0, ',', '.');
        $piutangToko = number_format($summary['total_piutang_toko'] ?? 0, 0, ',', '.');
        $financePending = number_format($summary['total_finance_pending'] ?? 0, 0, ',', '.');
        $outstandingCount = $summary['outstanding_orders_count'] ?? 0;

        $text = "[HASIL TOOL: PIUTANG POS & LEASING PENDING ({$filterDesc})]\n";
        $text .= "- Total Portofolio Piutang Tercatat: Rp {$totalPiutang}\n";
        $text .= "- Sisa Tagihan Outstanding Belum Cair/Lunas: Rp {$totalSisa} ({$outstandingCount} transaksi)\n";
        $text .= "  * Piutang Toko Langsung (Customer Belum Lunas): Rp {$piutangToko}\n";
        $text .= "  * Piutang Leasing / Multi-Finance (Status PENDING Belum Cair): Rp {$financePending}\n";

        if (!empty($report['piutang_transactions'])) {
            $text .= "\nRINCIAN PER METODE PEMBIAYAAN:\n";
            foreach ($report['piutang_transactions'] as $pt) {
                $tot = number_format($pt['total'] ?? 0, 0, ',', '.');
                $sis = number_format($pt['sisa'] ?? 0, 0, ',', '.');
                $type = ($pt['type'] ?? '') === 'toko' ? 'Piutang Toko' : 'Leasing Multi-Finance';
                $text .= "- {$pt['payment_method']} [{$type}]: Total Rp {$tot} ({$pt['count_total']} trx) | Sisa Outstanding: Rp {$sis} ({$pt['count_pending']} pending)\n";
            }
        }

        $outstandingDetails = array_values(array_filter($report['details'] ?? [], fn($d) => !empty($d['is_outstanding'])));
        if (!empty($outstandingDetails)) {
            $text .= "\n10 NOTA OUTSTANDING TERBESAR:\n";
            foreach (array_slice($outstandingDetails, 0, 10) as $idx => $d) {
                $net = number_format($d['net_amount'] ?? 0, 0, ',', '.');
                $text .= '  #' . ($idx + 1) . " Nota {$d['order_number']} ({$d['order_date']}, umur {$d['aging_days']} hr) | {$d['payment_method']} | Cabang: {$d['branch']} | Pelanggan: {$d['customer_name']} | Sisa: Rp {$net}\n";
            }
        }

        return trim($text);
    }

    protected function runAccurateInventoryTool(array $filters, ?string $asOfDate = null): string
    {
        [$source, $buName] = $this->resolveAccurateSource($filters['business_unit_id'] ?? null);
        $branch = $filters['branch'] ?? null;
        $category = $filters['category'] ?? null;
        $project = $filters['project'] ?? null;

        $val = $this->accurateInventoryService->getInventoryValuation(
            $source,
            $asOfDate,
            $branch,
            $category,
            false,
            $buName,
            $project
        );

        $totVal = number_format($val['total_valuation'] ?? 0, 0, ',', '.');
        $totUnits = number_format($val['total_units'] ?? 0, 0, ',', '.');
        $avgCost = number_format($val['average_unit_cost'] ?? 0, 0, ',', '.');
        $glBal = number_format($val['gl_account']['balance'] ?? 0, 0, ',', '.');
        $slow = $val['slow_moving'] ?? [];
        $slowVal = number_format($slow['total_valuation'] ?? 0, 0, ',', '.');
        $slowQty = number_format($slow['total_qty'] ?? 0, 0, ',', '.');
        $slowPct = $slow['percentage_of_inventory'] ?? 0;

        $filterDesc = $this->describeFilters($filters);
        $out = "[HASIL TOOL: VALUASI PERSEDIAAN RESMI ACCURATE ONLINE ({$filterDesc} | Posisi: {$val['as_of_date']})]\n";
        $out .= "- Total Nilai Aset Modal Persediaan (Fisik x HPP Accurate): Rp {$totVal}\n";
        $out .= "- Total Kuantitas Fisik Persediaan: {$totUnits} unit (Rata-rata Modal/Unit: Rp {$avgCost})\n";
        $out .= "- Saldo Akun Buku Besar (GL Persediaan Tanpa Filter): Rp {$glBal}\n";
        $out .= "- Stok Slow Moving (>60 Hari): Rp {$slowVal} ({$slowQty} unit / {$slowPct}% dari persediaan)\n";

        if (!empty($val['branch_breakdown'])) {
            $out .= "\nPERSEBARAN STOK & MODAL PER GUDANG / CABANG ACCURATE:\n";
            foreach ($val['branch_breakdown'] as $idx => $wh) {
                $wName = $wh['warehouse_name'] ?? ($wh['branch_name'] ?? '-');
                $wQty = number_format($wh['total_qty'] ?? 0, 0, ',', '.');
                $wVal = number_format($wh['total_valuation'] ?? 0, 0, ',', '.');
                $wShare = $wh['share_percentage'] ?? ($wh['percentage'] ?? 0);
                $out .= '- #' . ($idx + 1) . " {$wName}: {$wQty} unit | Nilai Modal: Rp {$wVal} (Porsi {$wShare}%)\n";
            }
        }

        if (!empty($val['category_breakdown'])) {
            $out .= "\nALOKASI MODAL PER KATEGORI:\n";
            foreach (array_slice($val['category_breakdown'], 0, 10) as $cat) {
                $cName = $cat['category_name'] ?? ($cat['category'] ?? '-');
                $cQty = number_format($cat['total_qty'] ?? 0, 0, ',', '.');
                $cVal = number_format($cat['total_valuation'] ?? 0, 0, ',', '.');
                $cShare = $cat['share_percentage'] ?? ($cat['percentage'] ?? 0);
                $out .= "- {$cName}: {$cQty} unit | Rp {$cVal} ({$cShare}%)\n";
            }
        }

        if (!empty($val['brand_breakdown'])) {
            $out .= "\nALOKASI MODAL PER MERK / BRAND:\n";
            foreach (array_slice($val['brand_breakdown'], 0, 10) as $br) {
                $brName = $br['brand_name'] ?? ($br['brand'] ?? '-');
                $brQty = number_format($br['total_qty'] ?? 0, 0, ',', '.');
                $brVal = number_format($br['total_valuation'] ?? 0, 0, ',', '.');
                $out .= "- {$brName}: {$brQty} unit | Rp {$brVal}\n";
            }
        }

        if (!empty($val['top_valuation_items'])) {
            $out .= "\nTOP 12 ITEM DENGAN NILAI MODAL TERBESAR:\n";
            foreach (array_slice($val['top_valuation_items'], 0, 12) as $idx => $it) {
                $itName = $it['name'] ?? ($it['product_name'] ?? '-');
                $itQty = number_format($it['qty'] ?? ($it['total_qty'] ?? ($it['stock'] ?? 0)), 0, ',', '.');
                $itCost = number_format($it['unit_cost'] ?? ($it['base_cost'] ?? 0), 0, ',', '.');
                $itVal = number_format($it['total_valuation'] ?? 0, 0, ',', '.');
                $out .= '- #' . ($idx + 1) . " {$itName}: {$itQty} unit x HPP Rp {$itCost} = Total Rp {$itVal}\n";
            }
        }

        return trim($out);
    }

    protected function runAccurateFinancialTool(array $filters, ?string $asOfDate = null): string
    {
        $buId = $filters['business_unit_id'] ?? null;
        $sourcesToQuery = [];

        if ($buId) {
            [$src, $lbl] = $this->resolveAccurateSource((int) $buId);
            $sourcesToQuery[] = ['code' => $src === 'all' ? 'second' : $src, 'label' => $lbl];
        } else {
            // Consolidated view across active Accurate-linked Business Units
            $bus = BusinessUnit::whereNotNull('accurate_access_token')
                ->where('accurate_access_token', '!=', '')
                ->get();
            if ($bus->isNotEmpty()) {
                foreach ($bus as $b) {
                    $sourcesToQuery[] = ['code' => $b->code ?: 'second', 'label' => $b->name];
                }
            } else {
                $sourcesToQuery[] = ['code' => 'second', 'label' => 'GSK Second'];
            }
        }

        $out = "[HASIL TOOL: POSISI KEUANGAN RESMI ACCURATE ONLINE (KAS/BANK, PIUTANG AR & HUTANG AP)]\n";
        $grandCash = 0.0;
        $grandAr = 0.0;
        $grandAp = 0.0;

        foreach ($sourcesToQuery as $sItem) {
            try {
                $cashBank = $this->accurateFinancialService->getCashAndBank($sItem['code'], $asOfDate, false);
                $arAp = $this->accurateFinancialService->getArApSummary($sItem['code'], $asOfDate, false);

                $liq = (float) ($cashBank['total_liquid_cash'] ?? 0);
                $arTot = (float) ($arAp['receivables']['total_amount'] ?? 0);
                $apTot = (float) ($arAp['payables']['total_amount'] ?? 0);
                $nwc = (float) ($arAp['net_working_capital'] ?? ($arTot - $apTot));

                $grandCash += $liq;
                $grandAr += $arTot;
                $grandAp += $apTot;

                $out .= "\n=== UNIT BISNIS: {$sItem['label']} ( per " . ($cashBank['as_of_date'] ?? now()->format('Y-m-d')) . " ) ===\n";
                $out .= '- Total Kas & Bank Likuid: Rp ' . number_format($liq, 0, ',', '.') . "\n";
                foreach (array_slice($cashBank['bank_accounts'] ?? [], 0, 8) as $ba) {
                    $out .= "  * Bank {$ba['name']} ({$ba['account_no']}): Rp " . number_format($ba['balance'] ?? 0, 0, ',', '.') . "\n";
                }
                foreach (array_slice($cashBank['cash_registers'] ?? [], 0, 6) as $cr) {
                    $out .= "  * Kas {$cr['name']} ({$cr['account_no']}): Rp " . number_format($cr['balance'] ?? 0, 0, ',', '.') . "\n";
                }

                $out .= '- Total Piutang Usaha Accurate (AR): Rp ' . number_format($arTot, 0, ',', '.') . ' (' . ($arAp['receivables']['invoice_count'] ?? 0) . " faktur)\n";
                foreach (array_slice($arAp['receivables']['top_debtors'] ?? [], 0, 5) as $deb) {
                    $dName = $deb['customer_name'] ?? ($deb['name'] ?? '-');
                    $dAmt = number_format($deb['total_owing'] ?? ($deb['amount'] ?? 0), 0, ',', '.');
                    $out .= "  * Debitur {$dName}: Rp {$dAmt}\n";
                }

                $out .= '- Total Hutang Usaha Accurate (AP): Rp ' . number_format($apTot, 0, ',', '.') . ' (' . ($arAp['payables']['invoice_count'] ?? 0) . " faktur)\n";
                foreach (array_slice($arAp['payables']['top_vendors'] ?? [], 0, 5) as $ven) {
                    $vName = $ven['vendor_name'] ?? ($ven['name'] ?? '-');
                    $vAmt = number_format($ven['total_owing'] ?? ($ven['amount'] ?? 0), 0, ',', '.');
                    $out .= "  * Vendor {$vName}: Rp {$vAmt}\n";
                }
                $out .= '- Modal Kerja Bersih (AR - AP): Rp ' . number_format($nwc, 0, ',', '.') . "\n";
            } catch (\Throwable $inner) {
                Log::warning("Accurate financial fetch warning [{$sItem['code']}]: " . $inner->getMessage());
                $out .= "\n=== UNIT BISNIS: {$sItem['label']} ===\n- Koneksi Accurate pada unit bisnis ini sedang belum terautentikasi / tidak merespons.\n";
            }
        }

        if (count($sourcesToQuery) > 1) {
            $out .= "\n=== TOTAL KONSOLIDASI GRUP ===\n";
            $out .= '- Total Kas & Bank Likuid Grup: Rp ' . number_format($grandCash, 0, ',', '.') . "\n";
            $out .= '- Total Piutang Usaha (AR) Grup: Rp ' . number_format($grandAr, 0, ',', '.') . "\n";
            $out .= '- Total Hutang Usaha (AP) Grup: Rp ' . number_format($grandAp, 0, ',', '.') . "\n";
            $out .= '- Net Working Capital Grup (AR - AP): Rp ' . number_format($grandAr - $grandAp, 0, ',', '.') . "\n";
        }

        return trim($out);
    }

    protected function runStockImeiDeadstockTool(array $filters, array $args, ?User $user = null): string
    {
        $action = $args['action'] ?? 'stock_search';
        $lockedBuId = $this->getLockedBusinessUnitId($user);
        $targetBuId = $lockedBuId ?? ($filters['business_unit_id'] ?? null);
        $branchStr = isset($filters['branch']) ? (is_array($filters['branch']) ? ($filters['branch'][0] ?? null) : (string) $filters['branch']) : null;

        if ($action === 'imei_track') {
            $sn = trim((string) ($args['serial_number'] ?? ($args['keyword'] ?? '')));
            $tracked = $this->inventoryService->trackSerialNumber($sn, $lockedBuId);
            return $this->inventoryService->formatImeiForAiContext($tracked);
        }

        if ($action === 'dead_stock') {
            $days = max(7, (int) ($args['days_threshold'] ?? 30));
            $buForDeadStock = $targetBuId ?: 2;
            $dead = $this->inventoryService->getDeadStockAlerts($buForDeadStock, $days, 12);
            if (empty($dead)) {
                return "[DATA DEAD STOCK (>{$days} HARI): Tidak ditemukan unit mengendap >{$days} hari pada Unit Bisnis tersebut. Rotasi stok sehat.]";
            }
            $text = "[PERINGATAN OPERASIONAL: STOK MENGENDAP / DEAD STOCK (>{$days} HARI DI TOKO)]\n";
            foreach ($dead as $idx => $ds) {
                $num = $idx + 1;
                $price = number_format($ds['price'], 0, ',', '.');
                $cost = number_format($ds['hpp'], 0, ',', '.');
                $text .= "- #{$num} {$ds['product_name']} | Cabang: {$ds['branch']} | Qty: {$ds['qty']} unit | Mengendap: {$ds['days_in_stock']} hari (Harga Jual: Rp {$price}, HPP: Rp {$cost})\n";
            }
            return trim($text);
        }

        $keyword = trim((string) ($args['keyword'] ?? ''));
        $report = $this->inventoryService->searchInventoryWithSummary($keyword, $targetBuId ? (int) $targetBuId : null, $branchStr, 12);
        return $this->inventoryService->formatInventoryReportForAiContext($report);
    }

    /**
     * Build a fast, server-verified baseline context matching the Director's active tab & filters.
     */
    public function buildActiveScreenBaseline(?string $activeTab, array $activeFilters = [], ?User $user = null): string
    {
        $filters = $this->resolveEffectiveFilters([], $activeFilters, $user);
        $tab = $activeTab ?: 'overview';
        $cacheKey = 'exec_ai_baseline_v2_' . md5(json_encode([$tab, $filters, $user?->id]));

        return Cache::remember($cacheKey, 45, function () use ($tab, $filters) {
            $filterDesc = $this->describeFilters($filters);
            $header = "[SNAPSHOT VERIFIKASI SERVER — TAB AKTIF: {$tab} | FILTER AKTIF: {$filterDesc}]\n";

            if (in_array($tab, ['inventory_valuation', 'inventory-valuation'], true)) {
                return $header . $this->runAccurateInventoryTool($filters);
            }
            if (in_array($tab, ['financial_accurate', 'accurate-finance'], true)) {
                return $header . $this->runAccurateFinancialTool($filters);
            }
            if ($tab === 'audit') {
                return $header . $this->runCashierAuditTool($filters, $filterDesc);
            }
            if ($tab === 'staff') {
                return $header . $this->runStaffKpiTool($filters, $filterDesc);
            }
            if ($tab === 'brands') {
                return $header . $this->runBrandPromoProjectTool($filters, $filterDesc, 'brand');
            }
            if (in_array($tab, ['promos', 'promo'], true)) {
                return $header . $this->runBrandPromoProjectTool($filters, $filterDesc, 'promo');
            }
            if ($tab === 'projects') {
                return $header . $this->runBrandPromoProjectTool($filters, $filterDesc, 'project');
            }

            return $header . $this->runSalesAndBranchTool($filters, $filterDesc);
        });
    }

    /**
     * Safety fallback hydrator used ONLY if an upstream proxy model rejects OpenAI `tools` schema.
     */
    public function hydrateFallbackToolsForQuery(string $message, array $activeFilters = [], ?User $user = null): string
    {
        $msg = strtolower($message);
        $blocks = [];

        // Bare 10-20 digit IMEI or explicit IMEI/SN mention
        if (preg_match('/\b(\d{12,18})\b/', $message, $mNum)) {
            $blocks[] = $this->executeTool('search_physical_stock_imei_and_deadstock', [
                'action' => 'imei_track',
                'serial_number' => $mNum[1],
            ], $activeFilters, $user);
        } elseif (preg_match('/\b(?:imei|sn|serial(?:\s*number)?)\s*(?:\/sn)?\s*[:#]\s*([a-zA-Z0-9\-\/]{5,30})\b/i', $message, $mSn)) {
            $blocks[] = $this->executeTool('search_physical_stock_imei_and_deadstock', [
                'action' => 'imei_track',
                'serial_number' => $mSn[1],
            ], $activeFilters, $user);
        }

        if (preg_match('/\b(void|batal|pembatalan|cancel|buyback|overpay|kemahalan|tukar\s*tambah)\b/i', $msg)) {
            $blocks[] = $this->executeTool('get_cashier_void_and_buyback_audit', [], $activeFilters, $user);
        }
        if (preg_match('/\b(mdr|edc|qris|pembayaran|metode\s*bayar|potongan\s*bank)\b/i', $msg)) {
            $blocks[] = $this->executeTool('get_payment_and_mdr_breakdown', [], $activeFilters, $user);
        }
        if (preg_match('/\b(salesperson|sales|staf|staff|kasir\s*teraktif|pencapaian\s*sales|top\s*sales)\b/i', $msg)) {
            $blocks[] = $this->executeTool('get_staff_and_salesperson_kpi', [], $activeFilters, $user);
        }
        if (preg_match('/\b(kas|bank|likuiditas|hutang|ap\b|ar\b|jatuh\s*tempo|modal\s*kerja|accurate\s*keuangan)\b/i', $msg)) {
            $blocks[] = $this->executeTool('get_accurate_financial_position', [], $activeFilters, $user);
        }
        if (preg_match('/\b(valuasi|aset\s*stok|modal\s*stok|gudang|persediaan|slow\s*moving)\b/i', $msg)) {
            $blocks[] = $this->executeTool('get_accurate_inventory_valuation', [], $activeFilters, $user);
        }
        if (preg_match('/\b(dead\s*stock|mengendap|belum\s*laku|stok\s*mati)\b/i', $msg)) {
            $buOverride = preg_match('/\b(baru|syihab)\b/i', $msg) ? 'syihab' : (preg_match('/\b(second|gsk)\b/i', $msg) ? 'second' : null);
            $blocks[] = $this->executeTool('search_physical_stock_imei_and_deadstock', array_filter([
                'action' => 'dead_stock',
                'business_unit' => $buOverride,
            ]), $activeFilters, $user);
        } elseif (preg_match('/\b(stok|stock|harga|tersedia|ketersediaan|iphone|samsung|oppo|vivo|xiaomi|ipad|macbook)\b/i', $msg)) {
            $buOverride = preg_match('/\b(second|bekas|2nd|gsk)\b/i', $msg) ? 'second' : (preg_match('/\b(baru|resmi|syihab)\b/i', $msg) ? 'syihab' : null);
            $blocks[] = $this->executeTool('search_physical_stock_imei_and_deadstock', array_filter([
                'action' => 'stock_search',
                'keyword' => $message,
                'business_unit' => $buOverride,
            ]), $activeFilters, $user);
        }
        if (preg_match('/\b(piutang|leasing|kredivo|akulaku|home\s*credit|hci|samsung\s*finance|belum\s*cair|menunggak)\b/i', $msg)) {
            $blocks[] = $this->executeTool('get_pos_piutang_and_leasing_pending', [], $activeFilters, $user);
        }
        if (preg_match('/\b(brand|merk|subsidi|promo|klaim|proyek|project|inter)\b/i', $msg)) {
            $blocks[] = $this->executeTool('get_brand_promo_and_project_analytics', ['focus' => 'all'], $activeFilters, $user);
        }

        return implode("\n\n", array_filter($blocks));
    }
}
