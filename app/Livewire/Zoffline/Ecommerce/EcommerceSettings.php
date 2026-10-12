<?php

namespace App\Livewire\Zoffline\Ecommerce;

use App\Models\BusinessUnit;
use App\Models\Employe;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SettingService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.z')]
class EcommerceSettings extends Component
{
    // Global Feature Toggles
    public bool $dynamicShipping = false;
    public bool $instantPayment = false;
    public string $csPhone = '081298765432';

    // Business Unit Scoped Settings
    public ?int $selectedBusinessUnitId = null;
    public ?int $selectedWarehouseId = null;
    public string $defaultBranchName = 'Banjarbaru';
    public ?int $defaultSalesId = null;
    public ?int $defaultHandledById = null;

    public function mount(SettingService $settingService): void
    {
        $this->dynamicShipping = filter_var($settingService->get('ecommerce_dynamic_shipping', false), FILTER_VALIDATE_BOOLEAN);
        $this->instantPayment = filter_var($settingService->get('ecommerce_instant_payment', false), FILTER_VALIDATE_BOOLEAN);
        $this->csPhone = (string) $settingService->get('ecommerce_cs_phone', '081298765432');

        // Pilih Unit Usaha awal (dari user/session atau yang pertama aktif)
        $firstBu = BusinessUnit::where('is_active', true)->first();
        $this->selectedBusinessUnitId = auth()->user()?->getActiveBusinessUnitId() ?? $firstBu?->id;

        $this->loadBusinessUnitSettings();
    }

    public function selectBusinessUnit(int $buId): void
    {
        $this->selectedBusinessUnitId = $buId;
        $this->loadBusinessUnitSettings();
    }

    public function updatedSelectedBusinessUnitId(): void
    {
        $this->loadBusinessUnitSettings();
    }

    public function loadBusinessUnitSettings(): void
    {
        $settingService = app(SettingService::class);
        $buId = $this->selectedBusinessUnitId;

        if (!$buId) {
            $this->selectedWarehouseId = null;
            $this->defaultBranchName = 'Banjarbaru';
            $this->defaultSalesId = null;
            $this->defaultHandledById = null;
            return;
        }

        // 1. Gudang Online Store milik BU ini
        $this->selectedWarehouseId = Warehouse::where('business_unit_id', $buId)
            ->where('is_online_store', true)
            ->value('id');

        // 2. Cabang Default Accurate BU ini
        $this->defaultBranchName = (string) $settingService->get(
            "ecommerce_branch_{$buId}",
            $settingService->get('ecommerce_default_branch_name', 'Banjarbaru')
        );

        // 3. Sales Default BU ini
        $rawSalesId = $settingService->get(
            "ecommerce_sales_{$buId}",
            $settingService->get('ecommerce_default_sales_id')
        );
        $this->defaultSalesId = !empty($rawSalesId) ? (int) $rawSalesId : null;

        // 4. Kasir / Handled By Default BU ini
        $rawHandledById = $settingService->get(
            "ecommerce_handled_by_{$buId}",
            $settingService->get('ecommerce_default_handled_by_id')
        );
        $this->defaultHandledById = !empty($rawHandledById) ? (int) $rawHandledById : null;
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

    public function saveWarehouse(): void
    {
        if (!$this->selectedBusinessUnitId) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Pilih unit usaha terlebih dahulu.', type: 'warning');
            return;
        }

        // Reset flag is_online_store HANYA untuk gudang milik Unit Usaha terpilih
        Warehouse::where('business_unit_id', $this->selectedBusinessUnitId)->update(['is_online_store' => false]);

        if ($this->selectedWarehouseId) {
            Warehouse::where('id', $this->selectedWarehouseId)
                ->where('business_unit_id', $this->selectedBusinessUnitId)
                ->update(['is_online_store' => true]);

            $whName = Warehouse::find($this->selectedWarehouseId)?->name;
            $buName = BusinessUnit::find($this->selectedBusinessUnitId)?->name;
            $this->dispatch('toast', title: 'Berhasil', message: "Gudang '{$whName}' telah ditetapkan sebagai Gudang Online untuk {$buName}.", type: 'success');
        } else {
            $this->dispatch('toast', title: 'Berhasil', message: 'Gudang online dinonaktifkan untuk unit usaha ini.', type: 'info');
        }
    }

    public function saveDefaultBranch(SettingService $settingService): void
    {
        $this->validate(['defaultBranchName' => 'required|string|max:100']);
        $buId = $this->selectedBusinessUnitId;

        if ($buId) {
            $settingService->set("ecommerce_branch_{$buId}", trim($this->defaultBranchName), 'string');
        }
        $settingService->set('ecommerce_default_branch_name', trim($this->defaultBranchName), 'string');

        $buName = BusinessUnit::find($buId)?->name ?? 'Semua Unit';
        $this->dispatch('toast', title: 'Berhasil', message: "Cabang Default Accurate untuk {$buName} diatur ke: {$this->defaultBranchName}", type: 'success');
    }

    public function saveDefaultSales(SettingService $settingService): void
    {
        $buId = $this->selectedBusinessUnitId;

        if ($buId) {
            $settingService->set("ecommerce_sales_{$buId}", $this->defaultSalesId ? (string) $this->defaultSalesId : null, 'string');
        }
        $settingService->set('ecommerce_default_sales_id', $this->defaultSalesId ? (string) $this->defaultSalesId : null, 'string');

        $salesName = $this->defaultSalesId ? Employe::find($this->defaultSalesId)?->name : 'Tanpa Sales Default';
        $buName = BusinessUnit::find($buId)?->name ?? 'Semua Unit';
        $this->dispatch('toast', title: 'Berhasil', message: "Sales Default ({$buName}) diatur ke: {$salesName}", type: 'success');
    }

    public function saveDefaultHandledBy(SettingService $settingService): void
    {
        $buId = $this->selectedBusinessUnitId;

        if ($buId) {
            $settingService->set("ecommerce_handled_by_{$buId}", $this->defaultHandledById ? (string) $this->defaultHandledById : null, 'string');
        }
        $settingService->set('ecommerce_default_handled_by_id', $this->defaultHandledById ? (string) $this->defaultHandledById : null, 'string');

        $userName = $this->defaultHandledById ? User::find($this->defaultHandledById)?->name : 'Tanpa Kasir Default';
        $buName = BusinessUnit::find($buId)?->name ?? 'Semua Unit';
        $this->dispatch('toast', title: 'Berhasil', message: "Kasir/Handled By Default ({$buName}) diatur ke: {$userName}", type: 'success');
    }

    public function render(SettingService $settingService)
    {
        $businessUnits = BusinessUnit::where('is_active', true)->orderBy('name')->get();
        $currentBU = BusinessUnit::find($this->selectedBusinessUnitId);

        // Bank terfilter berdasarkan BU terpilih
        $mobileBanks = PaymentMethod::where('is_visible_mobile', true)
            ->when($this->selectedBusinessUnitId, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('business_unit_id', $this->selectedBusinessUnitId)
                        ->orWhereNull('business_unit_id');
                });
            })
            ->get();

        // Gudang milik BU terpilih
        $buWarehouses = Warehouse::when($this->selectedBusinessUnitId, fn($q) => $q->where('business_unit_id', $this->selectedBusinessUnitId))
            ->orderBy('name')
            ->get();

        $onlineWarehouse = Warehouse::when($this->selectedBusinessUnitId, fn($q) => $q->where('business_unit_id', $this->selectedBusinessUnitId))
            ->where('is_online_store', true)
            ->first();

        // Cabang milik BU terpilih (fallback ke semua jika belum ada)
        $buBranches = \App\Models\Branch::when($this->selectedBusinessUnitId, fn($q) => $q->where('business_unit_id', $this->selectedBusinessUnitId))
            ->orderBy('name')
            ->get();
        if ($buBranches->isEmpty()) {
            $buBranches = \App\Models\Branch::orderBy('name')->get();
        }

        // Sales milik BU terpilih (fallback ke semua aktif jika kosong)
        $buSales = Employe::active()
            ->when($this->selectedBusinessUnitId, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('business_unit_id', $this->selectedBusinessUnitId)
                        ->orWhereNull('business_unit_id');
                });
            })
            ->orderBy('name')
            ->get();
        if ($buSales->isEmpty()) {
            $buSales = Employe::active()->orderBy('name')->get();
        }

        // Kasir / Handled By users (User admin/kasir milik BU terpilih)
        $buCashiers = User::when($this->selectedBusinessUnitId, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('business_unit_id', $this->selectedBusinessUnitId)
                        ->orWhereNull('business_unit_id');
                });
            })
            ->orderBy('name')
            ->get();

        $hasBiteshipKey = !empty($settingService->get('biteship_api_key'));
        $hasXenditKey = !empty($settingService->get('xendit_secret_key'));

        return view('livewire.zoffline.ecommerce.ecommerce-settings', [
            'businessUnits' => $businessUnits,
            'currentBU' => $currentBU,
            'mobileBanks' => $mobileBanks,
            'onlineWarehouse' => $onlineWarehouse,
            'buWarehouses' => $buWarehouses,
            'buBranches' => $buBranches,
            'buSales' => $buSales,
            'buCashiers' => $buCashiers,
            'hasBiteshipKey' => $hasBiteshipKey,
            'hasXenditKey' => $hasXenditKey,
        ]);
    }
}
