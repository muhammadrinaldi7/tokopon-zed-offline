<?php

namespace App\Console\Commands;

use App\Services\CrmWhatsAppService;
use Illuminate\Console\Command;

class CrmWaPingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm-wa:ping';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Health check ke endpoint CRM WhatsApp Zed Group';

    /**
     * Execute the console command.
     */
    public function handle(CrmWhatsAppService $service)
    {
        $this->info('Menguji koneksi ke CRM WhatsApp Zed Group...');
        $apiUrl = config('services.crm_wa.api_url');
        $this->line("Target URL: {$apiUrl}");

        $result = $service->ping();

        if ($result['success']) {
            $this->info('Status: ONLINE & SIAP!');
            $this->table(['Key', 'Value'], [
                ['Status Code', $result['status_code'] ?? 200],
                ['Message', $result['message'] ?? 'OK'],
                ['Response', json_encode($result['response'] ?? [], JSON_PRETTY_PRINT)],
            ]);
        } else {
            $this->error('Status: OFFLINE / GAGAL!');
            $this->table(['Key', 'Value'], [
                ['Status Code', $result['status_code'] ?? 'N/A'],
                ['Message', $result['message'] ?? 'Gagal menghubungi CRM'],
                ['Error Detail', $result['error'] ?? 'N/A'],
            ]);
            $this->warn('Catatan: Sesuai dokumentasi Mas Zaini, ngrok tunnel aktif saat server Mas Zaini dijalankan.');
        }

        return $result['success'] ? 0 : 1;
    }
}
