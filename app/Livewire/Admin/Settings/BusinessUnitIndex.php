<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use App\Models\BusinessUnit;
use App\Services\AccurateService;
use App\Services\SettingService;
use Illuminate\Support\Facades\Log;

class BusinessUnitIndex extends Component
{
    public $units = [];
    
    // Webhook Renew & Schedule State
    public $renewFrequency = 'monthly';
    public $lastRenewedAt = null;
    public $lastRenewStatus = [];
    public $isRenewing = false;

    // Form fields
    public $unitId;
    public $name;
    public $code;
    public $customer_prefix;
    public $order_prefix;
    public $draft_prefix;
    public $prefix;
    public $store_title;
    public $receipt_show_discount = false;
    public $accurate_host;
    public $accurate_token;
    public $accurate_secret_key;
    public $accurate_webhook_token;
    public $telegram_approval_webhook;
    public $telegram_log_webhook;
    public $accurate_database_id;
    public $accurate_return_warehouse_id;
    public $accurate_return_warehouse_name;
    public $is_taxable = false;
    public $is_active = true;

    public $showModal = false;

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->units = BusinessUnit::all();
        $settingService = app(SettingService::class);
        $this->renewFrequency = $settingService->get('accurate_webhook_renew_frequency', 'monthly');
        $this->lastRenewedAt = $settingService->get('accurate_webhook_last_renewed_at');
        $this->lastRenewStatus = $settingService->get('accurate_webhook_last_renew_status', []);
    }

    public function resetFields()
    {
        $this->unitId = null;
        $this->name = '';
        $this->code = '';
        $this->customer_prefix = '';
        $this->order_prefix = '';
        $this->draft_prefix = '';
        $this->prefix = '';
        $this->store_title = '';
        $this->receipt_show_discount = false;
        $this->accurate_host = '';
        $this->accurate_token = '';
        $this->accurate_secret_key = '';
        $this->accurate_webhook_token = '';
        $this->telegram_approval_webhook = '';
        $this->telegram_log_webhook = '';
        $this->accurate_database_id = '';
        $this->accurate_return_warehouse_id = '';
        $this->accurate_return_warehouse_name = '';
        $this->is_taxable = false;
        $this->is_active = true;
    }

    public function openModal()
    {
        $this->resetFields();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetFields();
    }

    public function edit($id)
    {
        $unit = BusinessUnit::findOrFail($id);
        $this->unitId = $unit->id;
        $this->name = $unit->name;
        $this->code = $unit->code;
        $this->customer_prefix = $unit->customer_prefix;
        $this->order_prefix = $unit->order_prefix;
        $this->draft_prefix = $unit->draft_prefix;
        $this->prefix = $unit->prefix;
        $this->store_title = $unit->store_title;
        $this->receipt_show_discount = (bool)$unit->receipt_show_discount;
        $this->accurate_host = $unit->accurate_host;
        $this->accurate_token = $unit->accurate_token;
        $this->accurate_secret_key = $unit->accurate_secret_key;
        $this->accurate_webhook_token = $unit->accurate_webhook_token;
        $this->telegram_approval_webhook = $unit->telegram_approval_webhook;
        $this->telegram_log_webhook = $unit->telegram_log_webhook;
        $this->accurate_database_id = $unit->accurate_database_id;
        $this->accurate_return_warehouse_id = $unit->accurate_return_warehouse_id;
        $this->accurate_return_warehouse_name = $unit->accurate_return_warehouse_name;
        $this->is_taxable = (bool)$unit->is_taxable;
        $this->is_active = $unit->is_active;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:business_units,code,' . $this->unitId,
            'customer_prefix' => 'nullable|string|max:10',
            'order_prefix' => 'nullable|string|max:20',
            'draft_prefix' => 'nullable|string|max:20',
            'prefix' => 'nullable|string|max:20',
            'store_title' => 'nullable|string|max:100',
            'receipt_show_discount' => 'boolean',
            'telegram_approval_webhook' => 'nullable|url',
            'telegram_log_webhook' => 'nullable|url',
        ]);

        BusinessUnit::updateOrCreate(
            ['id' => $this->unitId],
            [
                'name' => $this->name,
                'code' => $this->code,
                'customer_prefix' => $this->customer_prefix ? strtoupper($this->customer_prefix) : null,
                'order_prefix' => $this->order_prefix ? strtoupper($this->order_prefix) : null,
                'draft_prefix' => $this->draft_prefix ? strtoupper($this->draft_prefix) : null,
                'prefix' => $this->prefix ? strtoupper($this->prefix) : null,
                'store_title' => $this->store_title ? strtoupper($this->store_title) : null,
                'receipt_show_discount' => $this->receipt_show_discount,
                'accurate_host' => $this->accurate_host,
                'accurate_token' => $this->accurate_token,
                'accurate_secret_key' => $this->accurate_secret_key,
                'accurate_webhook_token' => $this->accurate_webhook_token,
                'telegram_approval_webhook' => $this->telegram_approval_webhook,
                'telegram_log_webhook' => $this->telegram_log_webhook,
                'accurate_database_id' => $this->accurate_database_id,
                'accurate_return_warehouse_id' => $this->accurate_return_warehouse_id,
                'accurate_return_warehouse_name' => $this->accurate_return_warehouse_name,
                'is_taxable' => $this->is_taxable,
                'is_active' => $this->is_active,
            ]
        );

        $this->closeModal();
        $this->loadData();
        session()->flash('message', $this->unitId ? 'Unit Usaha berhasil diupdate.' : 'Unit Usaha berhasil ditambahkan.');
    }

    public function toggleActive($id)
    {
        $unit = BusinessUnit::findOrFail($id);
        $unit->update(['is_active' => !$unit->is_active]);
        $this->loadData();
    }

    public function saveScheduleSettings()
    {
        $settingService = app(SettingService::class);
        $settingService->set('accurate_webhook_renew_frequency', $this->renewFrequency);
        $this->loadData();
        $this->dispatch('toast', title: 'Berhasil', message: 'Pengaturan jadwal perpanjangan otomatis berhasil disimpan.', type: 'success');
    }

    public function renewWebhook($unitId = null)
    {
        $accurateService = app(AccurateService::class);
        $settingService = app(SettingService::class);
        $this->isRenewing = true;

        try {
            if ($unitId) {
                $unit = BusinessUnit::find($unitId);
                if (!$unit) {
                    $this->dispatch('toast', title: 'Gagal', message: 'Unit usaha tidak ditemukan.', type: 'error');
                    return;
                }

                if (empty($unit->accurate_token) || empty($unit->accurate_secret_key)) {
                    $this->dispatch('toast', title: 'Kredensial Tidak Lengkap', message: "Unit {$unit->name} belum memiliki Accurate Token atau Secret Key.", type: 'warning');
                    return;
                }

                $response = $accurateService->renewWebhookDo($unit->code);
                $message = $response['d'] ?? 'Webhook berhasil diperpanjang.';
                if (is_array($message)) {
                    $message = json_encode($message);
                }

                $currentStatus = $this->lastRenewStatus ?: [];
                $currentStatus[$unit->code] = [
                    'status' => 'success',
                    'message' => $message,
                    'renewed_at' => now()->toDateTimeString(),
                ];

                $settingService->set('accurate_webhook_last_renewed_at', now()->toDateTimeString());
                $settingService->set('accurate_webhook_last_renew_status', $currentStatus, 'json');

                $this->loadData();
                $this->dispatch('toast', title: 'Perpanjangan Berhasil', message: "Webhook untuk unit {$unit->name} berhasil diperpanjang: {$message}", type: 'success');
            } else {
                $units = BusinessUnit::where('is_active', true)
                    ->whereNotNull('accurate_token')
                    ->where('accurate_token', '!=', '')
                    ->get();

                if ($units->isEmpty()) {
                    $this->dispatch('toast', title: 'Peringatan', message: 'Tidak ada unit usaha aktif dengan kredensial Accurate Token.', type: 'warning');
                    return;
                }

                $successCount = 0;
                $failCount = 0;
                $currentStatus = [];

                foreach ($units as $unit) {
                    try {
                        $response = $accurateService->renewWebhookDo($unit->code);
                        $message = $response['d'] ?? 'Webhook berhasil diperpanjang.';
                        if (is_array($message)) {
                            $message = json_encode($message);
                        }

                        $currentStatus[$unit->code] = [
                            'status' => 'success',
                            'message' => $message,
                            'renewed_at' => now()->toDateTimeString(),
                        ];
                        $successCount++;
                    } catch (\Throwable $e) {
                        $failCount++;
                        $currentStatus[$unit->code] = [
                            'status' => 'failed',
                            'message' => $e->getMessage(),
                            'failed_at' => now()->toDateTimeString(),
                        ];
                    }
                }

                $settingService->set('accurate_webhook_last_renewed_at', now()->toDateTimeString());
                $settingService->set('accurate_webhook_last_renew_status', $currentStatus, 'json');

                $this->loadData();

                if ($failCount === 0) {
                    $this->dispatch('toast', title: 'Perpanjangan Berhasil', message: "Semua webhook untuk {$successCount} unit usaha berhasil diperpanjang!", type: 'success');
                } else {
                    $this->dispatch('toast', title: 'Perpanjangan Sebagian Selesai', message: "{$successCount} unit berhasil, {$failCount} unit gagal diperpanjang. Periksa detail log.", type: 'warning');
                }
            }
        } catch (\Throwable $e) {
            Log::error("Manual Webhook Renew Error: " . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal Perpanjang Webhook', message: $e->getMessage(), type: 'error');
        } finally {
            $this->isRenewing = false;
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.business-unit-index')->layout('layouts.admin');
    }
}
