<?php

namespace App\Console\Commands;

use App\Services\MessageDispatchService;
use Illuminate\Console\Command;

class BackfillMessageLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'message-logs:backfill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi riwayat Order dan SellPhone yang sudah terkirim (is_wa_sent/is_email_sent) ke tabel message_logs';

    /**
     * Execute the console command.
     */
    public function handle(MessageDispatchService $service)
    {
        $this->info('Memulai sinkronisasi data transaksi lama ke tabel message_logs...');

        $result = $service->backfillHistoricalLogs();

        $this->newLine();
        $this->info("✅ Berhasil membuat {$result['created']} data log baru.");
        $this->line("⏩ Dilewati (sudah tercatat sebelumnya): {$result['skipped']}");
        $this->line("📊 Total diperiksa: {$result['total']}");
        $this->newLine();

        return Command::SUCCESS;
    }
}
