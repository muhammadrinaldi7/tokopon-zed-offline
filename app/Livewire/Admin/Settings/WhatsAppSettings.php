<?php

namespace App\Livewire\Admin\Settings;

use App\Services\CrmWhatsAppService;
use App\Services\SettingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin', ['title' => 'Pengaturan Integrasi WhatsApp - TokoPun'])]
class WhatsAppSettings extends Component
{
    public string $activeGateway = 'crm';
    public bool $autoSendOrder = true;
    public bool $autoSendSellphone = true;
    public bool $enablePaymentProof = true;

    // Ping / Health Check state
    public bool $isPinging = false;
    public ?array $pingResult = null;

    public function mount()
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['admin', 'superadmin'])) {
            return redirect('/admin/dashboard');
        }

        $this->activeGateway = CrmWhatsAppService::getActiveGateway();
        $this->autoSendOrder = CrmWhatsAppService::isAutoSendOrderEnabled();
        $this->autoSendSellphone = CrmWhatsAppService::isAutoSendSellPhoneEnabled();
        $this->enablePaymentProof = CrmWhatsAppService::isPaymentProofWaEnabled();
    }

    public function updatedActiveGateway($value)
    {
        CrmWhatsAppService::setActiveGateway($value);

        if ($value === CrmWhatsAppService::GATEWAY_NONE) {
            $this->dispatch('admin-alert', type: 'info', message: "Semua gateway WhatsApp dinonaktifkan (Mode Hanya Email & Cetak aktif).");
        } else {
            $gatewayName = $value === CrmWhatsAppService::GATEWAY_CRM ? 'CRM WhatsApp Zed Group' : 'Mekari Qontak';
            $this->dispatch('admin-alert', type: 'success', message: "Gateway aktif berhasil dialihkan ke {$gatewayName}!");
        }
    }

    public function updatedAutoSendOrder($value)
    {
        app(SettingService::class)->set('whatsapp_auto_send_order', (bool) $value, 'boolean');
        $statusText = $value ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('admin-alert', type: 'success', message: "Pengiriman otomatis struk Penjualan POS {$statusText}.");
    }

    public function updatedAutoSendSellphone($value)
    {
        app(SettingService::class)->set('whatsapp_auto_send_sellphone', (bool) $value, 'boolean');
        $statusText = $value ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('admin-alert', type: 'success', message: "Pengiriman otomatis struk Buyback HP {$statusText}.");
    }

    public function updatedEnablePaymentProof($value)
    {
        app(SettingService::class)->set('whatsapp_enable_payment_proof', (bool) $value, 'boolean');
        $statusText = $value ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('admin-alert', type: 'success', message: "Fitur WhatsApp Bukti Pembayaran Buyback {$statusText}.");
    }

    public function testPing()
    {
        $this->isPinging = true;
        $this->pingResult = null;

        try {
            $this->pingResult = app(CrmWhatsAppService::class)->ping();
        } catch (\Throwable $e) {
            $this->pingResult = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ];
        } finally {
            $this->isPinging = false;
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.whatsapp-settings', [
            'crmConfig' => [
                'api_url'      => config('services.crm_wa.api_url'),
                'token'        => config('services.crm_wa.token') ? substr(config('services.crm_wa.token'), 0, 8) . '...' : '(Belum diatur)',
                'channel_id'   => config('services.crm_wa.channel_integration_id'),
                'template_id'  => config('services.crm_wa.template_id'),
            ],
            'qontakConfig' => [
                'api_url'      => config('services.qontak.api_url'),
                'client_id'    => config('services.qontak.client_id'),
                'channel_id'   => config('services.qontak.integration_id'),
                'template_id'  => config('services.qontak.template_id'),
            ],
        ]);
    }
}
