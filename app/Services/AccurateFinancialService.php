<?php

namespace App\Services;

use App\Models\BusinessUnit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AccurateFinancialService
{
    /**
     * Get API credentials and headers for a given business unit code.
     */
    public function getHeaders(string $databaseSource = 'second'): array
    {
        $code = trim(strtolower($databaseSource)) ?: 'second';

        $businessUnit = BusinessUnit::where('code', $code)->first();

        // If not found or credentials empty, try second or syihab
        if (!$businessUnit || empty($businessUnit->accurate_token)) {
            $businessUnit = BusinessUnit::whereNotNull('accurate_token')->where('code', 'second')->first()
                ?? BusinessUnit::whereNotNull('accurate_token')->first();
        }

        if (!$businessUnit || empty($businessUnit->accurate_token)) {
            throw new \Exception("Kredensial Accurate Online tidak ditemukan untuk unit '{$databaseSource}'.");
        }

        $host = rtrim($businessUnit->accurate_host ?: env('ACCURATE_HOST', 'https://odin.accurate.id/accurate/api'), '/');
        $token = $businessUnit->accurate_token;
        $secretKey = $businessUnit->accurate_secret_key;

        $dt = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
        $timestamp = $dt->format('d/m/Y H:i:s');
        $signature = hash_hmac('sha256', $timestamp, $secretKey);

        return [
            'host' => $host,
            'business_unit' => $businessUnit,
            'headers' => [
                'Authorization'   => 'Bearer ' . $token,
                'X-Api-Timestamp' => $timestamp,
                'X-Api-Signature' => $signature,
                'Content-Type'    => 'application/json',
            ],
        ];
    }

    /**
     * Get All Outstanding Receivables (Piutang AR) from Accurate Online.
     * Uses primeOwing|desc for ultra-fast early stopping.
     */
    public function getOutstandingReceivables(string $databaseSource = 'second', ?string $asOfDate = null, bool $forceRefresh = false): array
    {
        $cacheKey = "accurate_ar_{$databaseSource}_" . ($asOfDate ?: now()->format('Y-m-d'));

        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $config = $this->getHeaders($databaseSource);
        $host = $config['host'];
        $secretKey = $config['business_unit']->accurate_secret_key;

        $page = 1;
        $pageSize = 100;
        $allOutstanding = [];
        $asOfCarbon = $asOfDate ? Carbon::parse($asOfDate)->endOfDay() : now()->endOfDay();

        while (true) {
            $dt = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
            $ts = $dt->format('d/m/Y H:i:s');
            $sig = hash_hmac('sha256', $ts, $secretKey);

            $headers = $config['headers'];
            $headers['X-Api-Timestamp'] = $ts;
            $headers['X-Api-Signature'] = $sig;

            $response = Http::timeout(20)->retry(2, 300)->withHeaders($headers)
                ->get($host . '/sales-invoice/list.do', [
                    'fields' => 'id,number,transDate,dueDate,customer,totalAmount,primeOwing,status',
                    'sp.pageSize' => $pageSize,
                    'sp.page' => $page,
                    'sp.sort' => 'primeOwing|desc',
                ]);

            if (!$response->successful()) {
                Log::error("Accurate AR API Error: " . $response->body());
                break;
            }

            $json = $response->json();
            $items = $json['d'] ?? [];
            if (empty($items) || !is_array($items)) {
                break;
            }

            $stop = false;
            foreach ($items as $item) {
                $owing = (float)($item['primeOwing'] ?? 0);
                if ($owing > 0) {
                    $transDateStr = $item['transDate'] ?? null;
                    if ($transDateStr) {
                        try {
                            $tDate = Carbon::createFromFormat('d/m/Y', $transDateStr);
                            if ($tDate->gt($asOfCarbon)) {
                                continue;
                            }
                        } catch (\Exception $e) {
                            // ignore parse error
                        }
                    }
                    $allOutstanding[] = $item;
                } else {
                    $stop = true;
                    break;
                }
            }

            if ($stop || count($items) < $pageSize) {
                break;
            }

            $page++;
            if ($page > 10) break;
            usleep(150000);
        }

        // Aging classification & debtor grouping
        $aging = [
            'days_0_7'   => ['label' => '0 - 7 Hari (Lancar)', 'count' => 0, 'amount' => 0],
            'days_8_14'  => ['label' => '8 - 14 Hari (Perhatian)', 'count' => 0, 'amount' => 0],
            'days_15_30' => ['label' => '15 - 30 Hari (Mendesak)', 'count' => 0, 'amount' => 0],
            'days_over_30' => ['label' => '> 30 Hari (Kritis / Overdue)', 'count' => 0, 'amount' => 0],
        ];

        $debtorsMap = [];
        $formattedInvoices = [];
        $totalAmountAll = 0;
        $totalOwingAll = 0;

        foreach ($allOutstanding as $inv) {
            $owing = round((float)($inv['primeOwing'] ?? 0), 2);
            $total = round((float)($inv['totalAmount'] ?? 0), 2);
            $totalOwingAll += $owing;
            $totalAmountAll += $total;

            $transDateStr = $inv['transDate'] ?? '';
            $agingDays = 0;
            if ($transDateStr) {
                try {
                    $tDate = Carbon::createFromFormat('d/m/Y', $transDateStr)->startOfDay();
                    $agingDays = max(0, (int) $tDate->diffInDays(now()->startOfDay()));
                } catch (\Exception $e) {
                    $agingDays = 0;
                }
            }

            if ($agingDays <= 7) {
                $aging['days_0_7']['count']++;
                $aging['days_0_7']['amount'] += $owing;
            } elseif ($agingDays <= 14) {
                $aging['days_8_14']['count']++;
                $aging['days_8_14']['amount'] += $owing;
            } elseif ($agingDays <= 30) {
                $aging['days_15_30']['count']++;
                $aging['days_15_30']['amount'] += $owing;
            } else {
                $aging['days_over_30']['count']++;
                $aging['days_over_30']['amount'] += $owing;
            }

            $customerName = trim($inv['customer']['name'] ?? 'Pelanggan Umum');
            if (!isset($debtorsMap[$customerName])) {
                $debtorsMap[$customerName] = [
                    'customer_name' => $customerName,
                    'customer_no' => $inv['customer']['customerNo'] ?? '-',
                    'invoice_count' => 0,
                    'total_amount' => 0,
                    'total_owing' => 0,
                ];
            }
            $debtorsMap[$customerName]['invoice_count']++;
            $debtorsMap[$customerName]['total_amount'] += $total;
            $debtorsMap[$customerName]['total_owing'] += $owing;

            $formattedInvoices[] = [
                'id' => $inv['id'],
                'invoice_no' => $inv['number'],
                'trans_date' => $inv['transDate'],
                'due_date' => $inv['dueDate'] ?? $inv['transDate'],
                'customer_name' => $customerName,
                'total_amount' => $total,
                'prime_owing' => $owing,
                'aging_days' => $agingDays,
                'status' => $inv['status'] ?? 'UNPAID',
            ];
        }

        uasort($debtorsMap, fn($a, $b) => $b['total_owing'] <=> $a['total_owing']);

        $result = [
            'business_unit' => $config['business_unit']->name,
            'database_source' => $databaseSource,
            'as_of_date' => $asOfDate ?: now()->format('Y-m-d'),
            'total_receivables' => round($totalOwingAll, 2),
            'total_invoice_amount' => round($totalAmountAll, 2),
            'invoice_count' => count($allOutstanding),
            'aging_summary' => $aging,
            'top_debtors' => array_values(array_slice($debtorsMap, 0, 10)),
            'invoices' => array_slice($formattedInvoices, 0, 100),
            'last_synced_at' => now()->toIso8601String(),
        ];

        Cache::put($cacheKey, $result, now()->addMinutes(10));

        return $result;
    }

    /**
     * Get All Outstanding Payables (Hutang AP Supplier) from Accurate Online.
     */
    public function getOutstandingPayables(string $databaseSource = 'second', ?string $asOfDate = null, bool $forceRefresh = false): array
    {
        $cacheKey = "accurate_ap_{$databaseSource}_" . ($asOfDate ?: now()->format('Y-m-d'));

        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $config = $this->getHeaders($databaseSource);
        $host = $config['host'];
        $secretKey = $config['business_unit']->accurate_secret_key;

        $page = 1;
        $pageSize = 100;
        $allOutstanding = [];
        $asOfCarbon = $asOfDate ? Carbon::parse($asOfDate)->endOfDay() : now()->endOfDay();

        while (true) {
            $dt = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
            $ts = $dt->format('d/m/Y H:i:s');
            $sig = hash_hmac('sha256', $ts, $secretKey);

            $headers = $config['headers'];
            $headers['X-Api-Timestamp'] = $ts;
            $headers['X-Api-Signature'] = $sig;

            $response = Http::timeout(20)->retry(2, 300)->withHeaders($headers)
                ->get($host . '/purchase-invoice/list.do', [
                    'fields' => 'id,number,transDate,dueDate,vendor,totalAmount,primeOwing,status',
                    'sp.pageSize' => $pageSize,
                    'sp.page' => $page,
                    'sp.sort' => 'primeOwing|desc',
                ]);

            if (!$response->successful()) {
                Log::error("Accurate AP API Error: " . $response->body());
                break;
            }

            $json = $response->json();
            $items = $json['d'] ?? [];
            if (empty($items) || !is_array($items)) {
                break;
            }

            $stop = false;
            foreach ($items as $item) {
                $owing = (float)($item['primeOwing'] ?? 0);
                if ($owing > 0) {
                    $transDateStr = $item['transDate'] ?? null;
                    if ($transDateStr) {
                        try {
                            $tDate = Carbon::createFromFormat('d/m/Y', $transDateStr);
                            if ($tDate->gt($asOfCarbon)) {
                                continue;
                            }
                        } catch (\Exception $e) {
                            // ignore parse error
                        }
                    }
                    $allOutstanding[] = $item;
                } else {
                    $stop = true;
                    break;
                }
            }

            if ($stop || count($items) < $pageSize) {
                break;
            }

            $page++;
            if ($page > 10) break;
            usleep(150000);
        }

        $aging = [
            'days_0_7'   => ['label' => '< 7 Hari (Segera)', 'count' => 0, 'amount' => 0],
            'days_8_14'  => ['label' => '8 - 14 Hari', 'count' => 0, 'amount' => 0],
            'days_15_30' => ['label' => '15 - 30 Hari', 'count' => 0, 'amount' => 0],
            'days_over_30' => ['label' => '> 30 Hari (Jatuh Tempo Lampau)', 'count' => 0, 'amount' => 0],
        ];

        $vendorsMap = [];
        $formattedInvoices = [];
        $totalAmountAll = 0;
        $totalOwingAll = 0;

        foreach ($allOutstanding as $inv) {
            $owing = round((float)($inv['primeOwing'] ?? 0), 2);
            $total = round((float)($inv['totalAmount'] ?? 0), 2);
            $totalOwingAll += $owing;
            $totalAmountAll += $total;

            $transDateStr = $inv['transDate'] ?? '';
            $agingDays = 0;
            if ($transDateStr) {
                try {
                    $tDate = Carbon::createFromFormat('d/m/Y', $transDateStr)->startOfDay();
                    $agingDays = max(0, (int) $tDate->diffInDays(now()->startOfDay()));
                } catch (\Exception $e) {
                    $agingDays = 0;
                }
            }

            if ($agingDays <= 7) {
                $aging['days_0_7']['count']++;
                $aging['days_0_7']['amount'] += $owing;
            } elseif ($agingDays <= 14) {
                $aging['days_8_14']['count']++;
                $aging['days_8_14']['amount'] += $owing;
            } elseif ($agingDays <= 30) {
                $aging['days_15_30']['count']++;
                $aging['days_15_30']['amount'] += $owing;
            } else {
                $aging['days_over_30']['count']++;
                $aging['days_over_30']['amount'] += $owing;
            }

            $vendorName = trim($inv['vendor']['name'] ?? 'Vendor Umum');
            if (!isset($vendorsMap[$vendorName])) {
                $vendorsMap[$vendorName] = [
                    'vendor_name' => $vendorName,
                    'vendor_no' => $inv['vendor']['vendorNo'] ?? '-',
                    'invoice_count' => 0,
                    'total_amount' => 0,
                    'total_owing' => 0,
                ];
            }
            $vendorsMap[$vendorName]['invoice_count']++;
            $vendorsMap[$vendorName]['total_amount'] += $total;
            $vendorsMap[$vendorName]['total_owing'] += $owing;

            $formattedInvoices[] = [
                'id' => $inv['id'],
                'invoice_no' => $inv['number'],
                'trans_date' => $inv['transDate'],
                'due_date' => $inv['dueDate'] ?? $inv['transDate'],
                'vendor_name' => $vendorName,
                'total_amount' => $total,
                'prime_owing' => $owing,
                'aging_days' => $agingDays,
                'status' => $inv['status'] ?? 'UNPAID',
            ];
        }

        uasort($vendorsMap, fn($a, $b) => $b['total_owing'] <=> $a['total_owing']);

        $result = [
            'business_unit' => $config['business_unit']->name,
            'database_source' => $databaseSource,
            'as_of_date' => $asOfDate ?: now()->format('Y-m-d'),
            'total_payables' => round($totalOwingAll, 2),
            'total_invoice_amount' => round($totalAmountAll, 2),
            'invoice_count' => count($allOutstanding),
            'aging_summary' => $aging,
            'top_vendors' => array_values(array_slice($vendorsMap, 0, 10)),
            'invoices' => array_slice($formattedInvoices, 0, 100),
            'last_synced_at' => now()->toIso8601String(),
        ];

        Cache::put($cacheKey, $result, now()->addMinutes(10));

        return $result;
    }

    /**
     * Get Cash and Bank Accounts Breakdown with Real Balances from Accurate.
     */
    public function getCashAndBank(string $databaseSource = 'second', ?string $asOfDate = null, bool $forceRefresh = false): array
    {
        $cacheKey = "accurate_cash_bank_{$databaseSource}_" . ($asOfDate ?: now()->format('Y-m-d'));

        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $config = $this->getHeaders($databaseSource);
        $host = $config['host'];
        $secretKey = $config['business_unit']->accurate_secret_key;

        $dt = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
        $ts = $dt->format('d/m/Y H:i:s');
        $sig = hash_hmac('sha256', $ts, $secretKey);

        $headers = $config['headers'];
        $headers['X-Api-Timestamp'] = $ts;
        $headers['X-Api-Signature'] = $sig;

        $response = Http::timeout(20)->withHeaders($headers)
            ->get($host . '/glaccount/list.do', [
                'fields' => 'id,no,name,accountType',
                'filter.leafOnly' => true,
                'sp.pageSize' => 100,
            ]);

        $bankAccounts = [];
        $cashRegisters = [];
        $totalLiquid = 0;

        if ($response->successful()) {
            $json = $response->json();
            $allGl = $json['d'] ?? [];

            $cashBankAccounts = array_filter($allGl, function($acc) {
                return ($acc['accountType'] ?? '') === 'CASH_BANK' && !empty($acc['id']);
            });

            $asOfFormat = $asOfDate ? Carbon::parse($asOfDate)->format('d/m/Y') : $dt->format('d/m/Y');

            foreach ($cashBankAccounts as $acc) {
                $nowDt = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
                $nowTs = $nowDt->format('d/m/Y H:i:s');
                $nowSig = hash_hmac('sha256', $nowTs, $secretKey);
                $headers['X-Api-Timestamp'] = $nowTs;
                $headers['X-Api-Signature'] = $nowSig;

                $balRes = Http::timeout(10)->withHeaders($headers)
                    ->get($host . '/glaccount/get-balance.do', [
                        'id' => $acc['id'],
                        'asOfDate' => $asOfFormat,
                    ]);

                $balance = 0;
                if ($balRes->successful()) {
                    $bJson = $balRes->json();
                    $balance = (float)($bJson['d']['balance'] ?? 0);
                }

                $accItem = [
                    'id' => $acc['id'],
                    'account_no' => $acc['no'],
                    'name' => $acc['name'],
                    'balance' => round($balance, 2),
                ];

                $lowerName = strtolower($acc['name']);
                $isBank = str_contains($lowerName, 'bca') || str_contains($lowerName, 'bni') || str_contains($lowerName, 'mandiri') || str_contains($lowerName, 'bank');

                if ($isBank) {
                    $bankAccounts[] = $accItem;
                } else {
                    $cashRegisters[] = $accItem;
                }

                $totalLiquid += $balance;
                usleep(100000); // 100ms throttle
            }
        }

        $result = [
            'business_unit' => $config['business_unit']->name,
            'database_source' => $databaseSource,
            'as_of_date' => $asOfDate ?: now()->format('Y-m-d'),
            'total_liquid_cash' => round($totalLiquid, 2),
            'bank_accounts' => $bankAccounts,
            'cash_registers' => $cashRegisters,
            'last_synced_at' => now()->toIso8601String(),
        ];

        Cache::put($cacheKey, $result, now()->addMinutes(10));

        return $result;
    }

    /**
     * Get Combined AR & AP Executive Summary.
     */
    public function getArApSummary(string $databaseSource = 'second', ?string $asOfDate = null, bool $forceRefresh = false): array
    {
        $ar = $this->getOutstandingReceivables($databaseSource, $asOfDate, $forceRefresh);
        $ap = $this->getOutstandingPayables($databaseSource, $asOfDate, $forceRefresh);

        $netWorkingCapital = round($ar['total_receivables'] - $ap['total_payables'], 2);

        return [
            'database_source' => $databaseSource,
            'business_unit' => $ar['business_unit'] ?? 'GSK',
            'as_of_date' => $asOfDate ?: now()->format('Y-m-d'),
            'receivables' => [
                'total_amount' => $ar['total_receivables'],
                'invoice_count' => $ar['invoice_count'],
                'aging' => $ar['aging_summary'],
                'top_debtors' => $ar['top_debtors'],
            ],
            'payables' => [
                'total_amount' => $ap['total_payables'],
                'invoice_count' => $ap['invoice_count'],
                'aging' => $ap['aging_summary'],
                'top_vendors' => $ap['top_vendors'],
            ],
            'net_working_capital' => $netWorkingCapital,
            'last_synced_at' => now()->toIso8601String(),
        ];
    }
}
