<?php

namespace App\Console\Commands;

use App\Models\BusinessUnit;
use App\Services\AccurateService;
use App\Services\SettingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RenewAccurateWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accurate:renew-webhooks {bu? : Kode Business Unit tertentu (opsional, misal: syihab, second)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perpanjang masa aktif webhook Accurate Online (/webhook-renew.do)';

    /**
     * Execute the console command.
     */
    public function handle(AccurateService $accurateService, SettingService $settingService)
    {
        $specificBu = $this->argument('bu');

        if ($specificBu) {
            $units = BusinessUnit::where('code', trim($specificBu))->get();
            if ($units->isEmpty()) {
                $this->error("❌ Unit usaha dengan kode '{$specificBu}' tidak ditemukan di database.");
                return 1;
            }
        } else {
            $units = BusinessUnit::where('is_active', true)
                ->whereNotNull('accurate_token')
                ->where('accurate_token', '!=', '')
                ->get();

            if ($units->isEmpty()) {
                $this->warn("⚠️ Tidak ada unit usaha aktif yang memiliki kredensial Accurate Token.");
                return 0;
            }
        }

        $this->info("Memulai proses perpanjangan webhook Accurate Online untuk " . $units->count() . " unit usaha...");
        $this->newLine();

        $tableData = [];
        $hasErrors = false;
        $resultsLog = [];

        foreach ($units as $unit) {
            $buLabel = "{$unit->name} ({$unit->code})";
            $this->line("Mengirim request perpanjang webhook untuk: {$buLabel}...");

            try {
                $response = $accurateService->renewWebhookDo($unit->code);
                $message = $response['d'] ?? 'Webhook berhasil diperpanjang.';
                if (is_array($message)) {
                    $message = json_encode($message);
                }

                $tableData[] = [
                    'Unit' => $unit->name,
                    'Kode' => $unit->code,
                    'Status' => '✅ Sukses',
                    'Keterangan' => $message,
                ];

                $resultsLog[$unit->code] = [
                    'status' => 'success',
                    'message' => $message,
                    'renewed_at' => now()->toDateTimeString(),
                ];

                Log::info("Accurate Webhook Renew Berhasil untuk BU [{$unit->code}]: {$message}");
            } catch (\Throwable $e) {
                $hasErrors = true;
                $errorMsg = $e->getMessage();

                $tableData[] = [
                    'Unit' => $unit->name,
                    'Kode' => $unit->code,
                    'Status' => '❌ Gagal',
                    'Keterangan' => $errorMsg,
                ];

                $resultsLog[$unit->code] = [
                    'status' => 'failed',
                    'message' => $errorMsg,
                    'failed_at' => now()->toDateTimeString(),
                ];

                Log::error("Accurate Webhook Renew Gagal untuk BU [{$unit->code}]: {$errorMsg}");
            }
        }

        $this->newLine();
        $this->table(['Unit Usaha', 'Kode', 'Status', 'Keterangan'], $tableData);

        // Simpan riwayat eksekusi ke setting sistem
        $settingService->set('accurate_webhook_last_renewed_at', now()->toDateTimeString());
        $settingService->set('accurate_webhook_last_renew_status', $resultsLog, 'json');

        if ($hasErrors) {
            $this->warn("⚠️ Beberapa unit usaha gagal diperpanjang. Silakan periksa log sistem.");
            return 1;
        }

        $this->info("🎉 Semua webhook Accurate berhasil diperpanjang!");
        return 0;
    }
}
