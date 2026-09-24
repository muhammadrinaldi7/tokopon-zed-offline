<?php

namespace App\Services;

use App\Models\AiChatHistory;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExecutiveAiService
{
    protected ExecutiveMetricsService $metricsService;
    protected ExecutiveInventoryService $inventoryService;
    protected string $apiBase;
    protected string $apiKey;
    protected string $model;
    protected int $timeout;

    public function __construct(ExecutiveMetricsService $metricsService, ExecutiveInventoryService $inventoryService)
    {
        $this->metricsService = $metricsService;
        $this->inventoryService = $inventoryService;

        // 9router or any OpenAI-compatible gateway configuration
        $this->apiBase = rtrim(config('services.ninerouter.base_url', 'https://api.9router.com/v1'), '/');
        $this->apiKey = config('services.ninerouter.api_key', '');
        $this->model = config('services.ninerouter.model', 'groq/openai/gpt-oss-120b');
        $this->timeout = (int) config('services.ninerouter.timeout', 90);
    }

    /**
     * Build rich executive system prompt injected with live business metrics context.
     */
    public function buildSystemPrompt(?array $contextData = null, ?User $user = null): string
    {
        $now = now()->translatedFormat('l, d F Y H:i:s');
        $userName = $user ? $user->name : 'Bapak/Ibu Direksi';

        if (empty($contextData)) {
            $contextData = Cache::remember('executive_live_summary_context', 120, function () {
                try {
                    return [
                        'period' => ['range' => 'this_month'],
                        'summary' => $this->metricsService->getKpiSummary(['period' => 'this_month']),
                        'branches' => $this->metricsService->getBranchComparison(['period' => 'this_month']),
                    ];
                } catch (\Throwable $e) {
                    Log::warning('ExecutiveAiService fallback metrics error: ' . $e->getMessage());
                    return null;
                }
            });
        }

        $metricsContext = '';
        if ($contextData) {
            $period = $contextData['period']['range'] ?? 'this_month';
            $summary = $contextData['summary'] ?? ($contextData['kpi']['summary'] ?? null);
            $branches = $contextData['branches'] ?? null;
            $topProducts = $contextData['top_products'] ?? null;
            $payments = $contextData['payments'] ?? null;

            if ($summary) {
                $netSales = number_format($summary['net_sales'] ?? 0, 0, ',', '.');
                $grossProfit = number_format($summary['gross_profit'] ?? 0, 0, ',', '.');
                $margin = number_format($summary['profit_margin'] ?? 0, 1, ',', '.');
                $orders = number_format($summary['total_orders'] ?? 0, 0, ',', '.');
                $piutang = number_format($summary['piutang_amount'] ?? 0, 0, ',', '.');
                $completed = number_format($summary['completed_amount'] ?? 0, 0, ',', '.');

                $metricsContext .= "\n[DATA REALTIME AKTIF DASHBOARD]\n";
                $metricsContext .= "- Periode Laporan: {$period}\n";
                $metricsContext .= "- Omset Bersih (Net Sales): Rp {$netSales}\n";
                $metricsContext .= "- Laba Kotor (Gross Profit): Rp {$grossProfit} (Margin: {$margin}%)\n";
                $metricsContext .= "- Total Transaksi: {$orders} pesanan\n";
                $metricsContext .= "- Realisasi Kas Lunas: Rp {$completed}\n";
                $metricsContext .= "- Total Piutang Berjalan: Rp {$piutang}\n";
            }

            if (!empty($branches) && is_array($branches)) {
                $metricsContext .= "\n[PERFORMA LENGKAP SELURUH CABANG]\n";
                foreach ($branches as $idx => $b) {
                    $bName = $b['branch_name'] ?? '-';
                    $bNet = number_format($b['net_sales'] ?? 0, 0, ',', '.');
                    $bProfit = number_format($b['gross_profit'] ?? 0, 0, ',', '.');
                    $bMargin = number_format($b['margin_percentage'] ?? 0, 1, ',', '.');
                    $bShare = $b['contribution_percentage'] ?? 0;
                    $bOrders = number_format($b['orders_count'] ?? 0, 0, ',', '.');
                    $metricsContext .= "- #" . ($idx + 1) . " {$bName}: Omset Bersih Rp {$bNet} (Laba Kotor: Rp {$bProfit}, Margin: {$bMargin}%, Kontribusi: {$bShare}%, Transaksi: {$bOrders})\n";
                }
            }

            if (!empty($topProducts) && is_array($topProducts)) {
                $metricsContext .= "\n[TOP PRODUK TERLARIS]\n";
                foreach (array_slice($topProducts, 0, 10) as $idx => $p) {
                    $pName = $p['name'] ?? '-';
                    $pQty = $p['qty_sold'] ?? 0;
                    $pRev = number_format($p['revenue'] ?? 0, 0, ',', '.');
                    $rank = $p['rank'] ?? ($idx + 1);
                    $metricsContext .= "- #{$rank} {$pName} ({$pQty} unit, Rp {$pRev})\n";
                }
            }

            if (!empty($payments) && is_array($payments)) {
                $metricsContext .= "\n[BREAKDOWN METODE PEMBAYARAN]\n";
                foreach ($payments as $pay) {
                    $mName = $pay['payment_method_name'] ?? '-';
                    $mTotal = number_format($pay['total_amount'] ?? 0, 0, ',', '.');
                    $mShare = $pay['share_percentage'] ?? 0;
                    $mMdr = number_format($pay['total_mdr'] ?? 0, 0, ',', '.');
                    $metricsContext .= "- {$mName}: Total Rp {$mTotal} (Porsi: {$mShare}%, Biaya MDR: Rp {$mMdr})\n";
                }
            }

            $projects = $contextData['projects'] ?? ($contextData['project_breakdown'] ?? null);
            if (!empty($projects) && is_array($projects)) {
                $metricsContext .= "\n[PENJUALAN PER KATEGORI PROYEK (RESMI, INTER, NON-PROYEK)]\n";
                foreach ($projects as $proj) {
                    $pName = $proj['project'] ?? '-';
                    $pSales = number_format($proj['net_sales'] ?? 0, 0, ',', '.');
                    $pProfit = number_format($proj['gross_profit'] ?? 0, 0, ',', '.');
                    $pMargin = number_format($proj['margin_percentage'] ?? 0, 1, ',', '.');
                    $pShare = $proj['contribution_percentage'] ?? 0;
                    $pQty = number_format($proj['total_qty'] ?? 0, 0, ',', '.');
                    $metricsContext .= "- Proyek {$pName}: Omset Rp {$pSales} (Share: {$pShare}%, Laba: Rp {$pProfit}, Margin: {$pMargin}%, Qty: {$pQty} unit)\n";
                }
            }

            $staff = $contextData['staff'] ?? null;
            if (!empty($staff['sales']) && is_array($staff['sales'])) {
                $metricsContext .= "\n[TOP PERFORMER SALESPERSON]\n";
                foreach (array_slice($staff['sales'], 0, 5) as $s) {
                    $sName = $s['sales_name'] ?? '-';
                    $sNet = number_format($s['net_sales'] ?? 0, 0, ',', '.');
                    $sQty = $s['total_qty'] ?? 0;
                    $sOrders = $s['orders_count'] ?? 0;
                    $metricsContext .= "- #{$s['rank']} {$sName}: Omset Rp {$sNet} ({$sQty} unit, {$sOrders} order)\n";
                }
            }

            $audit = $contextData['audit'] ?? null;
            if (!empty($audit['cashier_cancellations']) && is_array($audit['cashier_cancellations'])) {
                $metricsContext .= "\n[AUDIT PEMBATALAN / VOID KASIR]\n";
                foreach (array_slice($audit['cashier_cancellations'], 0, 5) as $c) {
                    $cName = $c['cashier_name'] ?? '-';
                    $cCount = $c['cancellation_count'] ?? 0;
                    $cAmt = number_format($c['total_amount'] ?? 0, 0, ',', '.');
                    $reasons = implode(', ', $c['reasons'] ?? []);
                    $metricsContext .= "- Kasir {$cName}: {$cCount} void (Total Rp {$cAmt}) | Alasan: {$reasons}\n";
                }
            }

            $promosData = $contextData['promos'] ?? null;
            if (!empty($promosData['brand_breakdown']) && is_array($promosData['brand_breakdown'])) {
                $metricsContext .= "\n[KLAIM SUBSIDI PROMO PER BRAND & VENDOR (PENAGIHAN)]\n";
                foreach (array_slice($promosData['brand_breakdown'], 0, 5) as $b) {
                    $bName = $b['brand'] ?? '-';
                    $bSub = number_format($b['total_subsidy'] ?? 0, 0, ',', '.');
                    $bCnt = $b['claims_count'] ?? 0;
                    $metricsContext .= "- Brand {$bName}: Total Subsidi Rp {$bSub} ({$bCnt} klaim)\n";
                    if (!empty($b['vendors'])) {
                        foreach (array_slice($b['vendors'], 0, 3) as $v) {
                            $vName = $v['vendor_name'] ?? '-';
                            $vSub = number_format($v['total_subsidy'] ?? 0, 0, ',', '.');
                            $metricsContext .= "   * Ditagihkan ke {$vName}: Rp {$vSub}\n";
                        }
                    }
                }
            }

            // Provide comparative baseline between Today and Yesterday if relevant
            $activePeriod = $contextData['period']['range'] ?? 'today';
            if ($activePeriod === 'today') {
                $yesterdayData = $this->getMetricsForPeriod('yesterday', $user);
                $ySummary = $yesterdayData['summary'] ?? null;
                if ($ySummary && ($ySummary['total_orders'] > 0 || $ySummary['net_sales'] > 0)) {
                    $yNet = number_format($ySummary['net_sales'] ?? 0, 0, ',', '.');
                    $yOrders = number_format($ySummary['total_orders'] ?? 0, 0, ',', '.');
                    $yMargin = number_format($ySummary['profit_margin'] ?? 0, 1, ',', '.');
                    $metricsContext .= "\n[KOMPARASI PERFORMA KEMARIN (YESTERDAY)]\n";
                    $metricsContext .= "- Total Omset Kemarin: Rp {$yNet} (Transaksi: {$yOrders}, Margin: {$yMargin}%)\n";
                }
            } elseif ($activePeriod === 'yesterday') {
                $todayData = $this->getMetricsForPeriod('today', $user);
                $tSummary = $todayData['summary'] ?? null;
                if ($tSummary && ($tSummary['total_orders'] > 0 || $tSummary['net_sales'] > 0)) {
                    $tNet = number_format($tSummary['net_sales'] ?? 0, 0, ',', '.');
                    $tOrders = number_format($tSummary['total_orders'] ?? 0, 0, ',', '.');
                    $tMargin = number_format($tSummary['profit_margin'] ?? 0, 1, ',', '.');
                    $metricsContext .= "\n[KOMPARASI PERFORMA HARI INI BERJALAN (TODAY)]\n";
                }
            }

            if (!empty($contextData['inventory_context'])) {
                $metricsContext .= "\n\n" . $contextData['inventory_context'] . "\n";
            }
        }

        return <<<PROMPT
Kamu adalah "Zed Executive Intelligence AI" — Asisten Intelijen Bisnis & Penasihat Strategis khusus untuk jajaran Direksi dan C-Level PT Syihab Store & GSK Group (Tokopon Zed).
Pengguna saat ini: {$userName}.
Waktu saat ini: {$now}.

Gaya Komunikasi & Standar Jawaban:
1. SIKAP & NADA: Sangat profesional, lugas, berbasis data, ringkas, dan berorientasi pada keputusan strategis eksekutif (C-Level). Hindari basa-basi panjang.
2. PANDUAN FORMATTING TABEL (SANGAT KRUSIAL AGAR RAPI DI SEMUA LAYAR & MOBILE):
   - JIKA MENAMPILKAN DATA CABANG ATAU PRODUK:
     * TABEL HARUS KOMPAK MAKSIMAL 4-5 KOLOM (JANGAN buat 6 kolom lebar karena akan terpotong, wrapping berantakan, dan sulit dibaca di chat/mobile).
     * SETIAP BARIS TABEL WAJIB MENGGUNAKAN BARIS BARU (LINE BREAK \n). DILARANG KERAS menggabungkan 2 atau lebih baris tabel dalam satu line teks!
     * DILARANG MENGGUNAKAN DOUBLE/TRIPLE PIPE (|| atau |||). Setiap baris tabel WAJIB diawali tepat satu pipa (|) dan diakhiri tepat satu pipa (|).
     * JANGAN ADA BARIS KOSONG di antara baris-baris data dalam satu tabel. Baris header langsung diikuti baris pemisah (|---|---|), lalu baris data.
     * Padatkan informasi dalam kolom ringkas:
       | No | Cabang | Omset Bersih (Share) | Margin (Laba) | Trx |
       |----|--------|----------------------|---------------|-----|
       | 1  | Banjarbaru | Rp 288.918.693 (25,4%) | 5,8% (Rp 16,7 Jt) | 106 |
     * DILARANG menggunakan tanda bintang ganda (**) di dalam sel data tabel! Tulis teks angka dan nama cabang polos bersih tanpa asterisks agar layout tabel tidak rusak. Bolding (**) hanya diperbolehkan untuk baris TOTAL di terbawah.
     * Tulis persentase rapat tanpa spasi sebelum persen (contoh: 25,4% BUKAN 25,4 %).
     * Baris TOTAL wajib disertakan di bagian bawah tabel.
3. STRUKTUR LAPORAN EKSEKUTIF YANG MUDAH DI-SCAN:
   - Awali dengan **📊 Highlight Singkat** (Total Omset, Total Laba Kotor, Rata-rata Margin, Total Transaksi).
   - Tampilkan Tabel Ringkas Kompak.
   - 🎯 **Top Performer & High Margin** (cabang kontributor omset & margin tertinggi).
   - ⚠️ **Cabang Perlu Perhatian** (cabang dengan margin di bawah target <5% atau anomali transaksi).
   - 💡 **Rekomendasi Tindakan C-Level** (langkah konkret yang dapat langsung dieksekusi).
4. KAMUS STRUKTUR UNIT BISNIS & ATURAN PRODUK (WAJIB DIPAHAMI):
   - UNIT BISNIS 1: "Syihab" (HP Baru / Retail Utama Resmi)
     * Toko Cabang: Banjarbaru, Martapura, Sultan Adam, Veteran, Premium.
     * Produk: Khusus HP/Gadget BARU resmi (Apple Resmi, Android Baru, Aksesoris).
   - UNIT BISNIS 2: "GSK Second" (Spesialis HP Second / Bekas & Tukar Tambah)
     * Toko Cabang: Menggunakan prefix "GSK - " (GSK - Banjarbaru, GSK - Martapura, GSK - Sultan Adam, GSK - Veteran, GSK - Kayutangi, GSK - Sampit).
     * ATURAN MUTLAK: SETIAP PERTANYAAN TENTANG HP SECOND / BEKAS HARUS MERUJUK KE UNIT BISNIS GSK SECOND.
   - UNIT BISNIS 3: "GSK Distri" (Grosir / Distribusi B2B).
5. ATURAN CARA MENJAWAB INFORMASI STOK, HARGA & LOKASI HP:
   - JIKA ADA BLOK [DATA REALTIME STOK & LOKASI UNIT HP DI GUDANG/CABANG] ATAU [DATA PELACAKAN FISIK NOMOR SERI / IMEI], utamakan menjawab berdasarkan data tersebut.
   - Sajikan secara detail dan ramah:
     * Nama model produk lengkap & SKU-nya.
     * Unit Bisnis yang menaungi (Syihab Baru vs GSK Second).
     * Harga Jual resmi & Modal HPP (karena Anda berbicara dengan Direksi).
     * Total unit fisik yang siap jual (Available) dan rincian lokasinya per cabang toko.
     * Jika stok fisik kosong atau tidak tersedia di cabang tertentu, sampaikan secara transparan.
6. KONTEKS DATA REALTIME:
{$metricsContext}
7. Jawab pertanyaan Direksi dengan menganalisis angka-angka di atas secara tajam, berikan 'Key Takeaways' dan 'Rekomendasi Tindakan' praktis jika relevan.
PROMPT;
    }

    /**
     * Detect time-period intent from user query.
     */
    public function detectRequestedPeriod(string $message): ?string
    {
        $msg = strtolower($message);

        if (preg_match('/\b(kemarin|yesterday|hari\s*kemarin)\b/i', $msg)) {
            return 'yesterday';
        }

        if (preg_match('/\b(hari\s*ini|today)\b/i', $msg)) {
            return 'today';
        }

        if (preg_match('/\b(bulan\s*lalu|last\s*month|bulan\s*kemarin)\b/i', $msg)) {
            return 'last_month';
        }

        if (preg_match('/\b(minggu\s*lalu|last\s*week|pekan\s*lalu)\b/i', $msg)) {
            return 'last_week';
        }

        if (preg_match('/\b(minggu\s*ini|this\s*week|pekan\s*ini)\b/i', $msg)) {
            return 'this_week';
        }

        if (preg_match('/\b(bulan\s*ini|this\s*month)\b/i', $msg)) {
            return 'this_month';
        }

        if (preg_match('/\b(7\s*hari|last\s*7\s*days)\b/i', $msg)) {
            return 'last_7_days';
        }

        if (preg_match('/\b(tahun\s*ini|this\s*year)\b/i', $msg)) {
            return 'this_year';
        }

        return null;
    }

    /**
     * Detect real-time operational inventory / stock / IMEI / dead-stock intent from user query.
     */
    public function detectInventoryIntent(string $message): ?array
    {
        $msg = strtolower($message);

        // 1. Detect IMEI / Serial Number tracking
        if (preg_match('/\b(?:imei|sn|serial(?:\s*number)?)\s*[:#]?\s*([a-zA-Z0-9\-\/]{4,30})\b/i', $message, $m)) {
            return [
                'type' => 'imei_track',
                'imei' => trim($m[1]),
            ];
        }

        // 2. Detect Dead Stock / Aging stock intent
        if (preg_match('/\b(dead\s*stock|stok\s*(?:mati|mengendap|lama|tertahan)|lama\s*belum\s*laku)\b/i', $msg)) {
            $buId = 2; // Default to GSK Second
            if (preg_match('/\b(baru|syihab)\b/i', $msg)) {
                $buId = 1;
            }
            return [
                'type' => 'dead_stock',
                'business_unit_id' => $buId,
            ];
        }

        // 3. Detect stock, price, or product location inquiry
        $inventoryKeywords = [
            'stok', 'stock', 'harga', 'price', 'lokasi', 'ada di mana', 'tersedia', 'ketersediaan',
            'iphone', 'ipad', 'macbook', 'apple watch', 'samsung', 'oppo', 'vivo', 'xiaomi', 'redmi',
            'infinix', 'poco', 'iqoo', 'realme', 'second', 'bekas', '2nd', 'unit'
        ];

        $matched = false;
        foreach ($inventoryKeywords as $kw) {
            if (str_contains($msg, $kw)) {
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            return null;
        }

        // Detect Business Unit
        $buId = null;
        if (preg_match('/\b(second|bekas|2nd|gsk)\b/i', $msg)) {
            $buId = 2; // GSK Second
        } elseif (preg_match('/\b(baru|new|resmi|syihab)\b/i', $msg)) {
            $buId = 1; // Syihab Baru
        }

        // Detect branch filter if mentioned
        $branches = ['banjarbaru', 'martapura', 'sultan adam', 'veteran', 'premium', 'kayutangi', 'sampit', 'head office'];
        $branchFilter = null;
        foreach ($branches as $b) {
            if (str_contains($msg, $b)) {
                $branchFilter = $b;
                break;
            }
        }

        // Extract product search term
        $clean = preg_replace('/[?!.,;:]+/', ' ', $message);
        $clean = preg_replace('/\b(cek|tolong|coba|tanya|apakah|ada|stok|stock|harga|berapa|unit|lokasi|di|cabang|toko|mana|ya|dong|min|zed|ai|mohon|info|informasi|tentang)\b/i', ' ', $clean);
        $clean = preg_replace('/\b(saja|sih|kah|nya|kan|nih|deh|aja)\b/i', ' ', $clean);
        $clean = preg_replace('/\b(second|bekas|2nd|baru|new|resmi|banjarbaru|martapura|veteran|sultan adam|premium|kayutangi|sampit|head office)\b/i', ' ', $clean);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));

        if (empty($clean) || strlen($clean) < 2) {
            if (preg_match('/(iphone\s*\d*(?:\s*(?:pro\s*max|pro|plus|mini))?|samsung\s*[a-z0-9\+\s]+|iqoo\s*[a-z0-9\s]+|macbook|ipad)/i', $message, $mModel)) {
                $clean = trim($mModel[1]);
            }
        }

        if (empty($clean)) {
            return null;
        }

        return [
            'type' => 'stock_search',
            'keyword' => $clean,
            'business_unit_id' => $buId,
            'branch' => $branchFilter,
        ];
    }

    /**
     * Format dead stock data into AI context text.
     */
    public function formatDeadStockForAiContext(array $deadStock): string
    {
        if (empty($deadStock)) {
            return "[DATA STOK MENGENDAP (DEAD STOCK): Tidak ada unit yang mengendap >30 hari, rotasi stok fisik sehat!]";
        }

        $text = "[PERINGATAN OPERASIONAL: STOK MENGENDAP / DEAD STOCK (>30 HARI DI TOKO)]\n";
        foreach ($deadStock as $idx => $ds) {
            $num = $idx + 1;
            $price = number_format($ds['price'], 0, ',', '.');
            $cost = number_format($ds['hpp'], 0, ',', '.');
            $text .= "- #{$num} {$ds['product_name']} | Cabang: {$ds['branch']} | Qty: {$ds['qty']} unit | Mengendap: {$ds['days_in_stock']} hari (Harga Jual: Rp {$price}, Modal HPP: Rp {$cost})\n";
        }
        return trim($text);
    }
    public function getMetricsForPeriod(string $period, ?User $user = null): array
    {
        $cacheKey = 'executive_metrics_auto_' . $period . '_' . ($user ? $user->id : 'all');

        return Cache::remember($cacheKey, 60, function () use ($period, $user) {
            $filters = ['date_range' => $period];
            if ($user && !$user->hasAnyRole(['superadmin', 'director', 'admin'])) {
                $filters['business_unit_id'] = $user->business_unit_id;
            }

            try {
                $kpi = $this->metricsService->getKpiSummary($filters);
                $branches = $this->metricsService->getBranchComparison($filters);
                $topProducts = $this->metricsService->getTopProducts($filters, 10);
                $payments = $this->metricsService->getPaymentMethodBreakdown($filters);
                $projectReport = $this->metricsService->getProjectSalesReport($filters);
                $staff = $this->metricsService->getStaffKpi($filters);
                $audit = $this->metricsService->getCashierAudit($filters);
                $promos = $this->metricsService->getPromoClaims($filters);

                return [
                    'period' => ['range' => $period],
                    'summary' => $kpi['summary'] ?? null,
                    'branches' => $branches,
                    'top_products' => $topProducts,
                    'payments' => $payments,
                    'projects' => $projectReport['project_breakdown'] ?? [],
                    'staff' => $staff,
                    'audit' => $audit,
                    'promos' => $promos,
                ];
            } catch (\Throwable $e) {
                Log::warning("Failed to fetch metrics for period {$period}: " . $e->getMessage());
                return ['period' => ['range' => $period]];
            }
        });
    }

    /**
     * Send chat prompt to 9router / OpenAI-compatible endpoint and record history.
     */
    public function chat(string $message, string $sessionId, int $adminId, ?array $contextData = null, ?User $user = null): array
    {
        // 1. Detect if user is asking for a specific period (e.g. 'yesterday' or 'last_month')
        $detectedPeriod = $this->detectRequestedPeriod($message);
        $activeContextPeriod = $contextData['period']['range'] ?? null;

        if ($detectedPeriod && $detectedPeriod !== $activeContextPeriod) {
            $contextData = $this->getMetricsForPeriod($detectedPeriod, $user);
        } elseif (empty($contextData)) {
            $contextData = $this->getMetricsForPeriod($detectedPeriod ?: 'today', $user);
        }

        // 1b. Real-time Inventory & Operational Intent Pre-fetch
        $inventoryIntent = $this->detectInventoryIntent($message);
        if ($inventoryIntent) {
            if ($inventoryIntent['type'] === 'stock_search') {
                $searchResults = $this->inventoryService->searchInventory(
                    $inventoryIntent['keyword'],
                    $inventoryIntent['business_unit_id'],
                    $inventoryIntent['branch'] ?? null
                );
                $contextData['inventory_context'] = $this->inventoryService->formatInventoryForAiContext($searchResults);
            } elseif ($inventoryIntent['type'] === 'imei_track') {
                $imeiResult = $this->inventoryService->trackSerialNumber($inventoryIntent['imei']);
                $contextData['inventory_context'] = $this->inventoryService->formatImeiForAiContext($imeiResult);
            } elseif ($inventoryIntent['type'] === 'dead_stock') {
                $deadStock = $this->inventoryService->getDeadStockAlerts($inventoryIntent['business_unit_id'] ?? 2, 30);
                $contextData['inventory_context'] = $this->formatDeadStockForAiContext($deadStock);
            }
        }

        // 2. Record user message
        AiChatHistory::create([
            'admin_id' => $adminId,
            'session_id' => $sessionId,
            'role' => 'user',
            'message' => $message,
        ]);

        // 2. Load recent conversation history for this session (up to 8 messages for context)
        $pastMessages = AiChatHistory::where('admin_id', $adminId)
            ->where('session_id', $sessionId)
            ->orderBy('id', 'desc')
            ->take(8)
            ->get()
            ->reverse();

        $messages = [];

        // System prompt with live context
        $messages[] = [
            'role' => 'system',
            'content' => $this->buildSystemPrompt($contextData, $user),
        ];

        // Append historical conversation
        foreach ($pastMessages as $hist) {
            $messages[] = [
                'role' => $hist->role === 'assistant' ? 'assistant' : 'user',
                'content' => $hist->message,
            ];
        }

        // 3. Call 9router OpenAI-compatible chat completions
        $endpoint = $this->apiBase . '/chat/completions';

        try {
            $request = Http::timeout($this->timeout);

            if (!empty($this->apiKey)) {
                $request = $request->withToken($this->apiKey);
            }

            $payload = [
                'model' => $this->model,
                'messages' => $messages,
                'stream' => false,
                'temperature' => (float) config('services.ninerouter.temperature', 0.4),
                'max_tokens' => (int) config('services.ninerouter.max_tokens', 2000),
            ];

            $response = $request->post($endpoint, $payload);

            if ($response->failed()) {
                $errMsg = 'Koneksi ke 9router AI gagal (HTTP ' . $response->status() . '): ' . $response->body();
                Log::error($errMsg);
                throw new \Exception($errMsg);
            }

            $reply = $this->extractReplyFromResponse($response);
            $reply = $this->normalizeMarkdownTables($reply);

            // 4. Save assistant response
            $assistantRecord = AiChatHistory::create([
                'admin_id' => $adminId,
                'session_id' => $sessionId,
                'role' => 'assistant',
                'message' => $reply,
            ]);

            return [
                'reply' => $reply,
                'session_id' => $sessionId,
                'created_at' => $assistantRecord->created_at->format('Y-m-d H:i:s'),
                'model' => $this->model,
            ];
        } catch (\Exception $e) {
            Log::error('ExecutiveAiService Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate an on-demand 1-click strategic executive summary for current dashboard metrics.
     */
    public function generateExecutiveSummary(array $contextData, int $adminId, ?User $user = null): array
    {
        $sessionId = 'summary-' . date('Ymd');
        $prompt = "Berikan Analisis Eksekutif & Ringkasan Strategis C-Level atas seluruh data performa bisnis Tokopon Zed pada periode ini. Sajikan dalam 3 bagian terstruktur:\n1. 📊 Key Performance Highlights (Kinerja Omset & Transaksi)\n2. ⚖️ Analisis Margin, Beban MDR, & Risiko Piutang\n3. 🎯 Rekomendasi Langkah Strategis untuk Direksi.";

        return $this->chat($prompt, $sessionId, $adminId, $contextData, $user);
    }

    /**
     * Retrieve chat history for given session and director.
     */
    public function getHistory(int $adminId, ?string $sessionId = null, int $limit = 50): Collection
    {
        return AiChatHistory::where('admin_id', $adminId)
            ->when($sessionId, function ($q) use ($sessionId) {
                $q->where('session_id', $sessionId);
            })
            ->orderBy('id', 'asc')
            ->take($limit)
            ->get(['id', 'role', 'message', 'created_at'])
            ->map(function ($item) {
                if ($item->role === 'assistant') {
                    $item->message = $this->normalizeMarkdownTables($item->message);
                }
                return $item;
            });
    }

    /**
     * Clear chat history for given session.
     */
    public function clearHistory(int $adminId, string $sessionId): bool
    {
        AiChatHistory::where('admin_id', $adminId)
            ->where('session_id', $sessionId)
            ->delete();

        return true;
    }

    /**
     * Robustly extract textual reply from 9router / OpenAI-compatible response,
     * handling standard JSON, reasoning/thinking models, content arrays, and SSE streams.
     */
    protected function extractReplyFromResponse(\Illuminate\Http\Client\Response $response): string
    {
        $body = trim($response->body());
        
        // Strip trailing 'data: [DONE]' or streaming sentinel appended by some router gateways
        $cleanedBody = preg_replace('/\s*data:\s*\[DONE\]\s*$/s', '', $body);
        $json = json_decode($cleanedBody, true) ?? $response->json();

        // 1. Standard OpenAI / Groq JSON format
        if (is_array($json) && isset($json['choices'][0])) {
            $choice = $json['choices'][0];

            // Direct message content
            if (isset($choice['message']['content']) && is_string($choice['message']['content']) && trim($choice['message']['content']) !== '') {
                return trim($choice['message']['content']);
            }

            // Reasoning models (Groq / DeepSeek / OpenAI reasoning)
            $reasoning = $choice['message']['reasoning_content'] ?? ($choice['message']['reasoning'] ?? null);
            if (!empty($reasoning) && is_string($reasoning) && trim($reasoning) !== '') {
                return trim($reasoning);
            }

            // Message content as array of blocks (e.g. Claude format)
            if (isset($choice['message']['content']) && is_array($choice['message']['content'])) {
                $textParts = [];
                foreach ($choice['message']['content'] as $block) {
                    if (is_string($block)) {
                        $textParts[] = $block;
                    } elseif (is_array($block) && isset($block['text'])) {
                        $textParts[] = $block['text'];
                    }
                }
                if (!empty($textParts)) {
                    return trim(implode("\n", $textParts));
                }
            }

            // Legacy completions format (choices[0].text)
            if (!empty($choice['text']) && is_string($choice['text'])) {
                return trim($choice['text']);
            }
        }

        // 2. Fallback: Parse Server-Sent Events (SSE) stream if response came as streaming chunks
        if (str_contains($body, 'data:')) {
            $collectedText = '';
            $lines = preg_split("/\r\n|\n|\r/", $body);

            foreach ($lines as $line) {
                $line = trim($line);
                if (!str_starts_with($line, 'data:')) {
                    continue;
                }

                $dataPayload = trim(substr($line, 5));
                if ($dataPayload === '' || $dataPayload === '[DONE]') {
                    continue;
                }

                $chunk = json_decode($dataPayload, true);
                if (is_array($chunk) && isset($chunk['choices'][0]['delta'])) {
                    $delta = $chunk['choices'][0]['delta'];
                    $content = $delta['content'] ?? ($delta['reasoning_content'] ?? null);
                    if ($content) {
                        $collectedText .= $content;
                    }
                }
            }

            if (trim($collectedText) !== '') {
                return trim($collectedText);
            }
        }

        Log::warning('ExecutiveAiService: Response format unexpected or empty content', [
            'status' => $response->status(),
            'body_sample' => substr($body, 0, 500),
        ]);

        return 'Maaf, AI tidak menghasilkan respons teks.';
    }

    /**
     * Robustly normalize and repair markdown tables from LLM:
     * - Fixes consecutive pipes (||, |||) and collapsed rows into proper line breaks
     * - Ensures valid leading and trailing pipes
     * - Enforces GFM table continuity without accidental blank lines
     */
    public function normalizeMarkdownTables(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        $lines = explode("\n", $text);
        $processedLines = [];
        $inCodeBlock = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (str_starts_with($trimmed, '```')) {
                $inCodeBlock = !$inCodeBlock;
                $processedLines[] = $line;
                continue;
            }

            if ($inCodeBlock) {
                $processedLines[] = $line;
                continue;
            }

            // Clean up multi-pipe artifacts at the start of a line
            $line = preg_replace('/^\|{2,}\s*/', '| ', $line);

            // Convert collapsed row separators into line breaks
            $line = preg_replace('/\|\s*\|\s*\|\s*/', "|\n| ", $line);
            $line = preg_replace('/\|\s*\|\s*([0-9A-Za-z#\-\:\*\—\–\w])/', "|\n| $1", $line);
            $line = preg_replace('/\|{2,}\s*/', "|\n| ", $line);

            $subLines = explode("\n", $line);
            foreach ($subLines as $sub) {
                $subTrimmed = trim($sub);
                if (str_contains($subTrimmed, '|') && !str_starts_with($subTrimmed, '>') && !str_starts_with($subTrimmed, '#')) {
                    if (!str_starts_with($subTrimmed, '|')) {
                        $subTrimmed = '| ' . $subTrimmed;
                    }
                    if (!str_ends_with($subTrimmed, '|')) {
                        $subTrimmed = $subTrimmed . ' |';
                    }
                }
                $processedLines[] = $subTrimmed;
            }
        }

        $finalLines = [];
        $inTable = false;
        $inCodeBlock = false;

        for ($i = 0; $i < count($processedLines); $i++) {
            $line = $processedLines[$i];
            $trimmed = trim($line);

            if (str_starts_with($trimmed, '```')) {
                $inCodeBlock = !$inCodeBlock;
                $finalLines[] = $line;
                continue;
            }

            if ($inCodeBlock) {
                $finalLines[] = $line;
                continue;
            }

            $isTableRow = str_starts_with($trimmed, '|') && str_ends_with($trimmed, '|') && strlen($trimmed) > 2;

            if ($isTableRow) {
                if (!$inTable) {
                    if (!empty($finalLines) && end($finalLines) !== '') {
                        $finalLines[] = '';
                    }
                    $inTable = true;
                }
                $finalLines[] = $trimmed;
            } else {
                if ($inTable && $trimmed === '') {
                    $nextIsTable = false;
                    for ($j = $i + 1; $j < count($processedLines); $j++) {
                        $nextTrim = trim($processedLines[$j]);
                        if ($nextTrim === '') continue;
                        if (str_starts_with($nextTrim, '|') && str_ends_with($nextTrim, '|')) {
                            $nextIsTable = true;
                        }
                        break;
                    }
                    if ($nextIsTable) {
                        continue;
                    }
                    $inTable = false;
                } elseif ($inTable) {
                    $inTable = false;
                }
                $finalLines[] = $line;
            }
        }

        return implode("\n", $finalLines);
    }
}

