<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AccurateWebhookLog;
use App\Jobs\ProcessAccurateWebhookJob;

class RetryStuckAccurateWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accurate:retry-webhooks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch ulang semua webhook accurate yang berstatus failed atau stuck processing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Mencari webhook logs yang berstatus failed atau processing...');
        
        $logs = AccurateWebhookLog::whereIn('status', ['failed', 'processing'])->get();
        
        if ($logs->isEmpty()) {
            $this->info('Tidak ada data webhook yang nyangkut. Semua aman!');
            return;
        }

        $bar = $this->output->createProgressBar(count($logs));
        $bar->start();

        foreach ($logs as $log) {
            ProcessAccurateWebhookJob::dispatch($log->id);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info(count($logs) . ' Webhook logs berhasil dimasukkan kembali ke antrean (Jobs)!');
    }
}
