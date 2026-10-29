<?php

namespace App\Livewire\Admin\Settings;

use App\Models\BusinessUnit;
use App\Models\ProductAccurate;
use App\Services\SettingService;
use Livewire\Attributes\Layout;
use Livewire\Component;

class CatalogSettings extends Component
{
    public $minimumStockThreshold = 5;

    // Pengaturan Dinamis API Trade-In & Harga Unit Second
    public array $tradeinOldBusinessUnitIds = [2];
    public string $tradeinOldPriceSource = 'buy_fallback';
    public array $tradeinOldAllowedProyeks = [];
    public array $tradeinTargetNewAllowedProyeks = [];
    public array $tradeinTargetSecondAllowedProyeks = [];
    public bool $tradeinGroupOldDevices = true;
    public bool $tradeinGroupTargetDevices = true;
    public string $tradeinSecondPriceSource = 'custom_fallback';
    public string $tradeinOldPriceStrategy = 'min';
    public string $tradeinTargetPriceStrategy = 'min';
    public bool $tradeinExcludeAccessories = true;

    public function mount(SettingService $settingService)
    {
        $this->minimumStockThreshold = $settingService->get('minimum_stock_threshold', 5);

        $savedBuIds = $settingService->get('tradein_old_business_unit_ids', [2]);
        if (is_array($savedBuIds) && !empty($savedBuIds)) {
            $this->tradeinOldBusinessUnitIds = array_values(array_map('intval', $savedBuIds));
        } else {
            $this->tradeinOldBusinessUnitIds = [2];
        }

        $this->tradeinOldPriceSource = (string) $settingService->get('tradein_old_price_source', 'buy_fallback');
        $this->tradeinOldAllowedProyeks = $this->normalizeProyekList($settingService->get('tradein_old_allowed_proyeks', []));
        $this->tradeinTargetNewAllowedProyeks = $this->normalizeProyekList($settingService->get('tradein_target_new_allowed_proyeks', []));
        $this->tradeinTargetSecondAllowedProyeks = $this->normalizeProyekList($settingService->get('tradein_target_second_allowed_proyeks', []));

        $this->tradeinGroupOldDevices = (bool) $settingService->get('tradein_group_old_devices', true);
        $this->tradeinGroupTargetDevices = (bool) $settingService->get('tradein_group_target_devices', true);
        $this->tradeinSecondPriceSource = (string) $settingService->get('tradein_second_price_source', 'custom_fallback');
        $this->tradeinOldPriceStrategy = (string) $settingService->get('tradein_old_price_strategy', 'min');
        $this->tradeinTargetPriceStrategy = (string) $settingService->get('tradein_target_price_strategy', 'min');
        $this->tradeinExcludeAccessories = (bool) $settingService->get('tradein_exclude_accessories', true);
    }

    protected function normalizeProyekList(mixed $list): array
    {
        if (!is_array($list)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(fn($item) => strtoupper(trim((string) $item)), $list),
            fn($item) => $item !== ''
        )));
    }

    public function save(SettingService $settingService)
    {
        $this->tradeinOldBusinessUnitIds = array_values(array_unique(array_filter(
            array_map('intval', (array) $this->tradeinOldBusinessUnitIds),
            fn($id) => $id > 0
        )));

        $this->tradeinOldAllowedProyeks = $this->normalizeProyekList($this->tradeinOldAllowedProyeks);
        $this->tradeinTargetNewAllowedProyeks = $this->normalizeProyekList($this->tradeinTargetNewAllowedProyeks);
        $this->tradeinTargetSecondAllowedProyeks = $this->normalizeProyekList($this->tradeinTargetSecondAllowedProyeks);

        $this->validate([
            'minimumStockThreshold' => 'required|integer|min:0',
            'tradeinOldBusinessUnitIds' => 'required|array|min:1',
            'tradeinOldBusinessUnitIds.*' => 'integer|exists:business_units,id',
            'tradeinOldPriceSource' => 'required|in:buy_fallback,buy_only',
            'tradeinOldAllowedProyeks' => 'array',
            'tradeinOldAllowedProyeks.*' => 'string|max:50',
            'tradeinTargetNewAllowedProyeks' => 'array',
            'tradeinTargetNewAllowedProyeks.*' => 'string|max:50',
            'tradeinTargetSecondAllowedProyeks' => 'array',
            'tradeinTargetSecondAllowedProyeks.*' => 'string|max:50',
            'tradeinGroupOldDevices' => 'boolean',
            'tradeinGroupTargetDevices' => 'boolean',
            'tradeinSecondPriceSource' => 'required|in:custom_fallback,custom_only,accurate_only',
            'tradeinOldPriceStrategy' => 'required|in:min,max',
            'tradeinTargetPriceStrategy' => 'required|in:min,max',
            'tradeinExcludeAccessories' => 'boolean',
        ], [
            'tradeinOldBusinessUnitIds.required' => 'Pilih minimal 1 Business Unit sebagai acuan daftar HP Lama (Buyback).',
            'tradeinOldBusinessUnitIds.min' => 'Pilih minimal 1 Business Unit sebagai acuan daftar HP Lama (Buyback).',
        ]);

        $settingService->set('minimum_stock_threshold', $this->minimumStockThreshold, 'integer');
        $settingService->set('tradein_old_business_unit_ids', $this->tradeinOldBusinessUnitIds, 'json');
        $settingService->set('tradein_old_price_source', $this->tradeinOldPriceSource, 'string');
        $settingService->set('tradein_old_allowed_proyeks', $this->tradeinOldAllowedProyeks, 'json');
        $settingService->set('tradein_target_new_allowed_proyeks', $this->tradeinTargetNewAllowedProyeks, 'json');
        $settingService->set('tradein_target_second_allowed_proyeks', $this->tradeinTargetSecondAllowedProyeks, 'json');
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
        $businessUnits = BusinessUnit::orderBy('id')->get(['id', 'name', 'code', 'is_active']);

        // Ambil seluruh variasi proyek unik dari product_accurates (dinormalisasi ke huruf besar)
        $dbProyeks = ProductAccurate::query()
            ->selectRaw("DISTINCT UPPER(TRIM(COALESCE(proyek, ''))) as norm_proyek")
            ->pluck('norm_proyek')
            ->map(fn($p) => ($p === '' || $p === 'NON-PROYEK') ? 'NON-PROYEK' : $p)
            ->reject(fn($p) => $p === 'ACCESSORIES')
            ->unique()
            ->values()
            ->all();

        $availableProyeks = collect(array_merge(['RESMI', 'INTER', 'NON-PROYEK'], $dbProyeks))
            ->unique()
            ->sort()
            ->values()
            ->all();

        return view('livewire.admin.settings.catalog-settings', compact('businessUnits', 'availableProyeks'));
    }
}
