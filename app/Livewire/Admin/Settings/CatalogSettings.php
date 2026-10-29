<?php

namespace App\Livewire\Admin\Settings;

use App\Services\SettingService;
use Livewire\Attributes\Layout;
use Livewire\Component;

class CatalogSettings extends Component
{
    public $minimumStockThreshold = 5;

    // Pengaturan Dinamis API Trade-In & Harga Unit Second
    public bool $tradeinGroupOldDevices = true;
    public bool $tradeinGroupTargetDevices = true;
    public string $tradeinSecondPriceSource = 'custom_fallback';
    public string $tradeinOldPriceStrategy = 'min';
    public string $tradeinTargetPriceStrategy = 'min';
    public bool $tradeinExcludeAccessories = true;

    public function mount(SettingService $settingService)
    {
        $this->minimumStockThreshold = $settingService->get('minimum_stock_threshold', 5);

        $this->tradeinGroupOldDevices = (bool) $settingService->get('tradein_group_old_devices', true);
        $this->tradeinGroupTargetDevices = (bool) $settingService->get('tradein_group_target_devices', true);
        $this->tradeinSecondPriceSource = (string) $settingService->get('tradein_second_price_source', 'custom_fallback');
        $this->tradeinOldPriceStrategy = (string) $settingService->get('tradein_old_price_strategy', 'min');
        $this->tradeinTargetPriceStrategy = (string) $settingService->get('tradein_target_price_strategy', 'min');
        $this->tradeinExcludeAccessories = (bool) $settingService->get('tradein_exclude_accessories', true);
    }

    public function save(SettingService $settingService)
    {
        $this->validate([
            'minimumStockThreshold' => 'required|integer|min:0',
            'tradeinGroupOldDevices' => 'boolean',
            'tradeinGroupTargetDevices' => 'boolean',
            'tradeinSecondPriceSource' => 'required|in:custom_fallback,custom_only,accurate_only',
            'tradeinOldPriceStrategy' => 'required|in:min,max',
            'tradeinTargetPriceStrategy' => 'required|in:min,max',
            'tradeinExcludeAccessories' => 'boolean',
        ]);

        $settingService->set('minimum_stock_threshold', $this->minimumStockThreshold, 'integer');
        $settingService->set('tradein_group_old_devices', $this->tradeinGroupOldDevices, 'boolean');
        $settingService->set('tradein_group_target_devices', $this->tradeinGroupTargetDevices, 'boolean');
        $settingService->set('tradein_second_price_source', $this->tradeinSecondPriceSource, 'string');
        $settingService->set('tradein_old_price_strategy', $this->tradeinOldPriceStrategy, 'string');
        $settingService->set('tradein_target_price_strategy', $this->tradeinTargetPriceStrategy, 'string');
        $settingService->set('tradein_exclude_accessories', $this->tradeinExcludeAccessories, 'boolean');

        $this->dispatch('toast', title: 'Berhasil', message: 'Pengaturan Katalog & API Trade-In berhasil disimpan.', type: 'success');
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        return view('livewire.admin.settings.catalog-settings');
    }
}
