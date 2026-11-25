<?php

namespace App\Console\Commands;

use App\Models\BusinessUnit;
use App\Models\CustomerDeposit;
use App\Models\Order;
use App\Models\OrderAccurateDoc;
use App\Services\AccurateService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileAccurateRefundDp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accurate:reconcile-refund-dp 
                            {--bu=syihab : Business Unit code (e.g. syihab, second, distri)}
                            {--invoice= : Specific DP Invoice Number to reconcile}
                            {--dry-run : Only show preview without saving any data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rekonsiliasi Faktur DP Gantung Masa Lalu: Buat dokumen Retur Uang Muka (sales-return) di Accurate agar Faktur DP lama berubah status menjadi LUNAS.';

    /**
     * Execute the console command.
     */
    public function handle(AccurateService $accurateService)
    {
        $buCode = $this->option('bu') ?: 'syihab';
        $specificInvoice = $this->option('invoice');
        $isDryRun = $this->option('dry-run');

        $bu = BusinessUnit::where('code', $buCode)->first();
        if (!$bu) {
            $this->error("Business Unit dengan kode '{$buCode}' tidak ditemukan.");
            return Command::FAILURE;
        }

        $this->info("=== REKONSILIASI FAKTUR DP GANTUNG (RETUR UANG MUKA) ACCURATE ===");
        $this->info("Business Unit : {$bu->name} ({$buCode})");
        if ($specificInvoice) {
            $this->info("Filter Faktur : {$specificInvoice}");
        }
        if ($isDryRun) {
            $this->warn("MODE DRY-RUN: Hanya simulasi/preview, tidak ada perubahan yang dikirim ke Accurate.");
        }

        // Query deposit yang pernah di-refund dan punya accurate_invoice_no
        $query = CustomerDeposit::whereNotNull('accurate_invoice_no')
            ->where(function ($q) {
                $q->where('status', 'REFUNDED')
                  ->orWhere('balance', 0);
            })
            ->where(function ($q) use ($bu) {
                $q->where('business_unit_id', $bu->id)
                  ->orWhereHas('originOrder', function ($oq) use ($bu) {
                      $oq->where('business_unit_id', $bu->id);
                  });
            });

        if ($specificInvoice) {
            $query->where('accurate_invoice_no', $specificInvoice);
        }

        $deposits = $query->with('originOrder.user')->get();

        if ($deposits->isEmpty()) {
            $this->info("Tidak ditemukan data deposit refund dengan faktur Accurate untuk BU ini.");
            return Command::SUCCESS;
        }

        $rows = [];
        $readyCount = 0;
        $alreadyCount = 0;
        $successCount = 0;
        $failCount = 0;

        foreach ($deposits as $deposit) {
            $order = $deposit->originOrder;
            if (!$order) {
                continue;
            }

            $dpInvNo = $deposit->accurate_invoice_no;

            // Cek apakah sudah pernah ada dokumen SALES_RETURN untuk order ini
            $existingReturn = OrderAccurateDoc::where('order_id', $order->id)
                ->where('doc_type', 'SALES_RETURN')
                ->first();

            if ($existingReturn) {
                $alreadyCount++;
                $rows[] = [
                    $order->order_number,
                    $dpInvNo,
                    'Rp ' . number_format($deposit->amount, 0, ',', '.'),
                    $deposit->refunded_at ? $deposit->refunded_at->format('d/m/Y') : '-',
                    '<info>SUDAH ADA RETUR (' . $existingReturn->doc_number . ')</info>',
                ];
                continue;
            }

            $readyCount++;

            if ($isDryRun) {
                $rows[] = [
                    $order->order_number,
                    $dpInvNo,
                    'Rp ' . number_format($deposit->amount, 0, ',', '.'),
                    $deposit->refunded_at ? $deposit->refunded_at->format('d/m/Y') : '-',
                    '<comment>SIAP DIREKONSILIASI</comment>',
                ];
                continue;
            }

            // Eksekusi Pembuatan Retur Uang Muka di Accurate
            try {
                $transDate = $deposit->refunded_at ? $deposit->refunded_at->format('Y-m-d') : now()->format('Y-m-d');
                $result = $accurateService->postSalesReturnDownPayment(
                    $order,
                    $dpInvNo,
                    $buCode,
                    $deposit->amount,
                    $transDate
                );

                $returnNo = $result['r']['number'] ?? null;
                $returnId = $result['r']['id'] ?? null;

                if ($returnNo) {
                    OrderAccurateDoc::create([
                        'order_id' => $order->id,
                        'doc_type' => 'SALES_RETURN',
                        'doc_number' => $returnNo,
                        'accurate_id' => $returnId,
                        'amount' => (float)$deposit->amount,
                        'status' => 'SUCCESS',
                    ]);

                    $successCount++;
                    $rows[] = [
                        $order->order_number,
                        $dpInvNo,
                        'Rp ' . number_format($deposit->amount, 0, ',', '.'),
                        $transDate,
                        '<info>BERHASIL DITUTUP (' . $returnNo . ')</info>',
                    ];
                } else {
                    $failCount++;
                    $rows[] = [
                        $order->order_number,
                        $dpInvNo,
                        'Rp ' . number_format($deposit->amount, 0, ',', '.'),
                        $transDate,
                        '<error>GAGAL: Nomor Retur Kosong</error>',
                    ];
                }
            } catch (\Exception $e) {
                $failCount++;
                $rows[] = [
                    $order->order_number,
                    $dpInvNo,
                    'Rp ' . number_format($deposit->amount, 0, ',', '.'),
                    '-',
                    '<error>GAGAL: ' . substr($e->getMessage(), 0, 40) . '...</error>',
                ];
            }
        }

        $this->table(
            ['No Pesanan', 'No Faktur DP', 'Nominal DP', 'Tgl Refund', 'Status Rekonsiliasi'],
            $rows
        );

        $this->newLine();
        $this->info("Ringkasan:");
        $this->line("- Total Deposit Dipindai : " . $deposits->count());
        $this->line("- Sudah Memiliki Retur   : {$alreadyCount}");
        if ($isDryRun) {
            $this->line("- Siap Direkonsiliasi    : {$readyCount}");
        } else {
            $this->line("- Berhasil Ditutup (SRT) : {$successCount}");
            $this->line("- Gagal                  : {$failCount}");
        }

        return Command::SUCCESS;
    }
}
