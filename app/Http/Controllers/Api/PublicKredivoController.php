<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PublicKredivoController extends Controller
{
    /**
     * Endpoint Simulasi Cicilan Kredivo Publik (Web & Mobile).
     *
     * URL: GET /api/v1/public/kredivo/simulate?amount=10000000
     *
     * Alur Kerja:
     * 1. Mengecek Cache (24 jam) berdasarkan nominal harga.
     * 2. Menembak endpoint Kredivo resmi: https://sandbox.kredivo.com/kredivo/v2/payments
     *    dengan server_key yang aman tersimpan di backend (.env).
     * 3. Jika API Kredivo berhasil, memformat response ke skema seragam (Web & Mobile ready).
     * 4. Jika API Kredivo timeout / offline, otomatis fallback ke formula bunga resmi Kredivo
     *    (bunga 0% 30 hari, bunga flat 2.6% untuk 3, 6, 12, 24 bulan).
     */
    public function simulate(Request $request): JsonResponse
    {
        $amount = (float) $request->query('amount', 0);
        $itemName = (string) $request->query('item_name', 'Smartphone Syihab Store');

        if ($amount <= 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Parameter amount wajib disertakan dan harus lebih besar dari 0.',
            ], 422);
        }

        $cacheKey = 'kredivo_sim_' . round($amount);

        $result = Cache::remember($cacheKey, 86400, function () use ($amount, $itemName) {
            return $this->fetchFromKredivoOrFallback($amount, $itemName);
        });

        return response()->json([
            'status'           => 'success',
            'amount'           => $amount,
            'formatted_amount' => $this->formatRupiah($amount),
            'source'           => $result['source'] ?? 'api',
            'payments'         => $result['payments'] ?? [],
        ]);
    }

    /**
     * Panggil API Kredivo dengan fallback cerdas.
     */
    protected function fetchFromKredivoOrFallback(float $amount, string $itemName): array
    {
        $serverKey = config('services.kredivo.server_key', env('INSTALLMENT_SERVER_KEY', '8tLHIx8V0N6KtnSpS9Nbd6zROFFJH7'));
        $baseUrl = config('services.kredivo.base_url', env('KREDIVO_BASE_URL', 'https://sandbox.kredivo.com/kredivo/v2/payments'));

        if (!empty($serverKey)) {
            try {
                $payload = [
                    'server_key' => $serverKey,
                    'amount'     => $amount,
                    'items'      => [
                        [
                            'id'       => 'syihab-prod-' . round($amount),
                            'name'     => $itemName,
                            'price'    => $amount,
                            'url'      => 'https://mobile-api.zedgroup.tech',
                            'type'     => 'Smartphone',
                            'quantity' => 1,
                        ]
                    ]
                ];

                $response = Http::timeout(2.5)
                    ->withHeaders([
                        'Accept'       => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->post($baseUrl, $payload);

                if ($response->successful()) {
                    $body = $response->json();
                    if (!empty($body['payments']) && is_array($body['payments'])) {
                        return [
                            'source'   => 'kredivo_api',
                            'payments' => $this->normalizeKredivoPayments($body['payments'], $amount),
                        ];
                    }
                } else {
                    Log::warning('Kredivo API Non-200: ' . $response->status() . ' - ' . $response->body());
                }
            } catch (\Throwable $e) {
                Log::notice('Kredivo API Error / Timeout: ' . $e->getMessage() . '. Menggunakan fallback kalkulasi Kredivo.');
            }
        }

        // Fallback resmi formula Kredivo
        return [
            'source'   => 'fallback_formula',
            'payments' => $this->calculateFallbackPayments($amount),
        ];
    }

    /**
     * Normalisasi format pembayaran dari Kredivo agar siap dipakai Web & Mobile.
     */
    protected function normalizeKredivoPayments(array $rawPayments, float $amount): array
    {
        $normalized = [];

        foreach ($rawPayments as $item) {
            $tenure = (int) ($item['tenure'] ?? 1);
            $monthlyInstallment = (int) ($item['monthly_installment'] ?? $item['amount'] ?? round($amount / max(1, $tenure)));
            $name = (string) ($item['name'] ?? ($tenure === 1 ? '30_days' : "{$tenure}_months"));

            $isZeroPercent = ($tenure === 1 || str_contains($name, '30_days'));
            $interestRate = $isZeroPercent ? 0 : 2.6;
            $adminFee = (int) ($item['admin_fee'] ?? round($amount * ($isZeroPercent ? 0.01 : 0.02)));
            $totalPayment = $monthlyInstallment * $tenure;

            $normalized[] = [
                'name'                => $name,
                'label'               => $this->getTenureLabel($tenure, $name),
                'tenure'              => $tenure,
                'amount'              => $amount,
                'monthly_installment' => $monthlyInstallment,
                'formatted_monthly'   => $this->formatRupiah($monthlyInstallment),
                'interest_rate'       => $interestRate,
                'admin_fee'           => $adminFee,
                'total_payment'       => $totalPayment,
                'formatted_total'     => $this->formatRupiah($totalPayment),
                'is_zero_percent'     => $isZeroPercent,
            ];
        }

        return $normalized;
    }

    /**
     * Hitung fallback jika Kredivo sandbox tidak merespon / timeout.
     */
    protected function calculateFallbackPayments(float $amount): array
    {
        $schemes = [
            [
                'name'            => '30_days',
                'label'           => 'Bayar dalam 30 Hari (Bunga 0%)',
                'tenure'          => 1,
                'interest_rate'   => 0,
                'admin_percent'   => 0.01,
                'is_zero_percent' => true,
            ],
            [
                'name'            => '3_months',
                'label'           => 'Cicilan 3 Bulan',
                'tenure'          => 3,
                'interest_rate'   => 2.6,
                'admin_percent'   => 0.015,
                'is_zero_percent' => false,
            ],
            [
                'name'            => '6_months',
                'label'           => 'Cicilan 6 Bulan',
                'tenure'          => 6,
                'interest_rate'   => 2.6,
                'admin_percent'   => 0.02,
                'is_zero_percent' => false,
            ],
            [
                'name'            => '12_months',
                'label'           => 'Cicilan 12 Bulan (Paling Populer)',
                'tenure'          => 12,
                'interest_rate'   => 2.6,
                'admin_percent'   => 0.025,
                'is_zero_percent' => false,
            ],
            [
                'name'            => '24_months',
                'label'           => 'Cicilan 24 Bulan (Angsuran Termurah)',
                'tenure'          => 24,
                'interest_rate'   => 2.6,
                'admin_percent'   => 0.03,
                'is_zero_percent' => false,
            ],
        ];

        $payments = [];
        foreach ($schemes as $s) {
            $adminFee = (int) round($amount * $s['admin_percent']);
            $totalInterest = (int) round($amount * ($s['interest_rate'] / 100) * $s['tenure']);
            $totalLoan = (int) ($amount + $totalInterest + $adminFee);
            $monthly = (int) round($totalLoan / $s['tenure']);

            $payments[] = [
                'name'                => $s['name'],
                'label'               => $s['label'],
                'tenure'              => $s['tenure'],
                'amount'              => $amount,
                'monthly_installment' => $monthly,
                'formatted_monthly'   => $this->formatRupiah($monthly),
                'interest_rate'       => $s['interest_rate'],
                'admin_fee'           => $adminFee,
                'total_payment'       => $totalLoan,
                'formatted_total'     => $this->formatRupiah($totalLoan),
                'is_zero_percent'     => $s['is_zero_percent'],
            ];
        }

        return $payments;
    }

    protected function getTenureLabel(int $tenure, string $name): string
    {
        if ($tenure === 1 || str_contains($name, '30_days')) {
            return 'Bayar dalam 30 Hari (Bunga 0%)';
        }
        if ($tenure === 12) {
            return 'Cicilan 12 Bulan (Paling Populer)';
        }
        if ($tenure === 24) {
            return 'Cicilan 24 Bulan (Angsuran Termurah)';
        }
        return "Cicilan {$tenure} Bulan";
    }

    protected function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
