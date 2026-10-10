<?php

namespace App\Livewire\Zoffline\Ecommerce;

use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Models\Warehouse;
use App\Services\SettingService;
use Livewire\Attributes\Layout;
use Livewire\Component;

class EcommerceSettings extends Component
{
    public bool $dynamicShipping = false;
    public bool $instantPayment = false;
    public string $csPhone = '081298765432';

    public function mount(SettingService $settingService): void
    {
        $this->dynamicShipping = filter_var($settingService->get('ecommerce_dynamic_shipping', false), FILTER_VALIDATE_BOOLEAN);
        $this->instantPayment = filter_var($settingService->get('ecommerce_instant_payment', false), FILTER_VALIDATE_BOOLEAN);
        $this->csPhone = (string) $settingService->get('ecommerce_cs_phone', '081298765432');
    }

    public function toggleDynamicShipping(SettingService $settingService): void
    {
        $this->dynamicShipping = !$this->dynamicShipping;
        $settingService->set('ecommerce_dynamic_shipping', $this->dynamicShipping ? 'true' : 'false', 'boolean');
        
        $statusText = $this->dynamicShipping ? 'DIPERLUKAN (Kalkulator Ekspedisi Biteship)' : 'NON-AKTIF (Mode Ambil di Toko / Free Ongkir)';
        $this->dispatch('toast', title: 'Pengaturan Diperbarui', message: "Logistik Dinamis sekarang {$statusText}", type: 'success');
    }

    public function toggleInstantPayment(SettingService $settingService): void
    {
        $this->instantPayment = !$this->instantPayment;
        $settingService->set('ecommerce_instant_payment', $this->instantPayment ? 'true' : 'false', 'boolean');

        $statusText = $this->instantPayment ? 'AKTIF (Xendit VA / QRIS Otomatis)' : 'NON-AKTIF (Transfer Bank Manual & Upload Bukti)';
        $this->dispatch('toast', title: 'Pengaturan Diperbarui', message: "Pembayaran Instan sekarang {$statusText}", type: 'success');
    }

    public function saveCsPhone(SettingService $settingService): void
    {
        $this->validate([
            'csPhone' => 'required|string|min:9|max:20',
        ]);

        $settingService->set('ecommerce_cs_phone', trim($this->csPhone), 'string');
        $this->dispatch('toast', title: 'Berhasil', message: 'Nomor WhatsApp CS Mobile berhasil diperbarui', type: 'success');
    }

    #[Layout('layouts.admin')]
    public function render(SettingService $settingService)
    {
        $mobileBanks = PaymentMethod::where('is_visible_mobile', true)->get();
        $onlineWarehouse = Warehouse::where('is_online_store', true)->first();
        $hasBiteshipKey = !empty($settingService->get('biteship_api_key'));
        $hasXenditKey = !empty($settingService->get('xendit_secret_key'));

        return view('livewire.zoffline.ecommerce.ecommerce-settings', [
            'mobileBanks' => $mobileBanks,
            'onlineWarehouse' => $onlineWarehouse,
            'hasBiteshipKey' => $hasBiteshipKey,
            'hasXenditKey' => $hasXenditKey,
        ]);
    }
}
