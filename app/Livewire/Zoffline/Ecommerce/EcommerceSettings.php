<?php

namespace App\Livewire\Zoffline\Ecommerce;

use App\Models\BusinessUnit;
use App\Models\Employe;
use App\Models\PaymentMethod;
use App\Models\ProductAccurate;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SettingService;
use Illuminate\Support\Facades\Auth;
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

    // Category & Brand Curation per Business Unit
    public array $buCategories = [];
    public array $activeCategories = [];
    public array $categoryCounts = [];

    public array $buBrands = [];
    public array $activeBrands = [];
    public array $brandCounts = [];

    public function mount(SettingService $settingService): void
    {
        $this->dynamicShipping = filter_var($settingService->get('ecommerce_dynamic_shipping', false), FILTER_VALIDATE_BOOLEAN);
        $this->instantPayment = filter_var($settingService->get('ecommerce_instant_payment', false), FILTER_VALIDATE_BOOLEAN);
        $this->csPhone = (string) $settingService->get('ecommerce_cs_phone', '081298765432');

        // Pilih Unit Usaha awal (dari user/session atau yang pertama aktif)
        $firstBu = BusinessUnit::where('is_active', true)->first();
        $this->selectedBusinessUnitId = Auth::user()?->getActiveBusinessUnitId() ?? $firstBu?->id;

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
            $this->buCategories = [];
            $this->activeCategories = [];
            $this->categoryCounts = [];
            $this->buBrands = [];
            $this->activeBrands = [];
            $this->brandCounts = [];
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

        // 5. Kurasi Kategori Produk BU ini
        $this->buCategories = ProductAccurate::where('business_unit_id', $buId)
            ->whereNotNull('categoryName')
            ->where('categoryName', '!=', '')
            ->distinct()
            ->orderBy('categoryName')
            ->pluck('categoryName')
            ->toArray();

        $this->categoryCounts = ProductAccurate::where('business_unit_id', $buId)
            ->whereNotNull('categoryName')
            ->where('categoryName', '!=', '')
            ->selectRaw('categoryName, count(*) as count')
            ->groupBy('categoryName')
            ->pluck('count', 'categoryName')
            ->toArray();

        $savedCats = $settingService->get("ecommerce_active_categories_{$buId}");
        if ($savedCats === null) {
            // Default aktifkan semua KECUALI yang mengandung kata LDU atau DEMO
            $this->activeCategories = array_values(array_filter($this->buCategories, function ($cat) {
                $upper = strtoupper($cat);
                return !str_contains($upper, 'LDU') && !str_contains($upper, 'DEMO');
            }));
        } else {
            $this->activeCategories = is_array($savedCats) ? $savedCats : [];
        }

        // 6. Kurasi Merek/Brand Produk BU ini
        $this->buBrands = ProductAccurate::where('business_unit_id', $buId)
            ->whereNotNull('brandName')
            ->where('brandName', '!=', '')
            ->distinct()
            ->orderBy('brandName')
            ->pluck('brandName')
            ->toArray();

        $this->brandCounts = ProductAccurate::where('business_unit_id', $buId)
            ->whereNotNull('brandName')
            ->where('brandName', '!=', '')
            ->selectRaw('brandName, count(*) as count')
            ->groupBy('brandName')
            ->pluck('count', 'brandName')
            ->toArray();

        $savedBrands = $settingService->get("ecommerce_active_brands_{$buId}");
        if ($savedBrands === null) {
            $this->activeBrands = $this->buBrands;
        } else {
            $this->activeBrands = is_array($savedBrands) ? $savedBrands : [];
        }
    }

    public function toggleCategory(string $category): void
    {
        if (in_array($category, $this->activeCategories)) {
            $this->activeCategories = array_values(array_diff($this->activeCategories, [$category]));
        } else {
            $this->activeCategories[] = $category;
        }
        $this->saveActiveCategories();
    }

    public function toggleAllCategories(bool $enable): void
    {
        $this->activeCategories = $enable ? $this->buCategories : [];
        $this->saveActiveCategories();
    }

    public function saveActiveCategories(): void
    {
        if (!$this->selectedBusinessUnitId) return;
        $settingService = app(SettingService::class);
        $settingService->set("ecommerce_active_categories_{$this->selectedBusinessUnitId}", $this->activeCategories, 'json');
        
        $buName = BusinessUnit::find($this->selectedBusinessUnitId)?->name ?? 'Unit Usaha';
        $this->dispatch('toast', title: 'Tersimpan', message: "Daftar kategori aktif untuk {$buName} berhasil diperbarui (" . count($this->activeCategories) . " aktif).", type: 'success');
    }

    public function toggleBrand(string $brand): void
    {
        if (in_array($brand, $this->activeBrands)) {
            $this->activeBrands = array_values(array_diff($this->activeBrands, [$brand]));
        } else {
            $this->activeBrands[] = $brand;
        }
        $this->saveActiveBrands();
    }

    public function toggleAllBrands(bool $enable): void
    {
        $this->activeBrands = $enable ? $this->buBrands : [];
        $this->saveActiveBrands();
    }

    public function saveActiveBrands(): void
    {
        if (!$this->selectedBusinessUnitId) return;
        $settingService = app(SettingService::class);
        $settingService->set("ecommerce_active_brands_{$this->selectedBusinessUnitId}", $this->activeBrands, 'json');

        $buName = BusinessUnit::find($this->selectedBusinessUnitId)?->name ?? 'Unit Usaha';
        $this->dispatch('toast', title: 'Tersimpan', message: "Daftar merek aktif untuk {$buName} berhasil diperbarui (" . count($this->activeBrands) . " aktif).", type: 'success');
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
        $businessUnits = BusinessUnit::select('id', 'name', 'code', 'is_active')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $currentBU = BusinessUnit::select('id', 'name')->find($this->selectedBusinessUnitId);

        // Bank terfilter berdasarkan BU terpilih
        $mobileBanks = PaymentMethod::select('id', 'name', 'bank_name', 'account_number', 'account_owner', 'business_unit_id', 'is_visible_mobile')
            ->where('is_visible_mobile', true)
            ->when($this->selectedBusinessUnitId, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('business_unit_id', $this->selectedBusinessUnitId)
                        ->orWhereNull('business_unit_id');
                });
            })
            ->get();

        // Gudang milik BU terpilih
        $buWarehouses = Warehouse::select('id', 'name', 'business_unit_id', 'is_online_store')
            ->when($this->selectedBusinessUnitId, fn($q) => $q->where('business_unit_id', $this->selectedBusinessUnitId))
            ->orderBy('name')
            ->get();

        $onlineWarehouse = $buWarehouses->firstWhere('is_online_store', true);

        // Cabang milik BU terpilih (fallback ke semua jika belum ada)
        $buBranches = \App\Models\Branch::select('id', 'name', 'business_unit_id')
            ->when($this->selectedBusinessUnitId, fn($q) => $q->where('business_unit_id', $this->selectedBusinessUnitId))
            ->orderBy('name')
            ->get();
        if ($buBranches->isEmpty()) {
            $buBranches = \App\Models\Branch::select('id', 'name', 'business_unit_id')->orderBy('name')->get();
        }

        // Sales milik BU terpilih (fallback ke semua aktif jika kosong)
        $buSales = Employe::select('id', 'name', 'employee_no', 'business_unit_id')
            ->active()
            ->when($this->selectedBusinessUnitId, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('business_unit_id', $this->selectedBusinessUnitId)
                        ->orWhereNull('business_unit_id');
                });
            })
            ->orderBy('name')
            ->get();
        if ($buSales->isEmpty()) {
            $buSales = Employe::select('id', 'name', 'employee_no', 'business_unit_id')->active()->orderBy('name')->get();
        }

        // Kasir / Handled By users: Khusus staf internal/kasir/admin (JANGAN ambil jutaan user mobile/pelanggan)
        $buCashiers = User::select('id', 'name', 'email')
            ->where('email', 'not like', '%@tokopon.internal')
            ->where(function ($q) {
                $q->whereHas('roles', function ($r) {
                    $r->whereIn('name', ['admin', 'cs', 'fl', 'kasir_sju', 'superadmin', 'director', 'manager']);
                })
                ->orWhereNotNull('branch_id')
                ->orWhereNotNull('warehouse_id');
            })
            ->when($this->selectedBusinessUnitId, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('business_unit_id', $this->selectedBusinessUnitId)
                        ->orWhereNull('business_unit_id');
                });
            })
            ->orderBy('name')
            ->get();

        if ($buCashiers->isEmpty()) {
            // Fallback aman untuk staf jika role belum diset lengkap di database
            $buCashiers = User::select('id', 'name', 'email')
                ->where('email', 'not like', '%@tokopon.internal')
                ->whereDoesntHave('roles', fn($r) => $r->where('name', 'user'))
                ->when($this->selectedBusinessUnitId, function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('business_unit_id', $this->selectedBusinessUnitId)
                            ->orWhereNull('business_unit_id');
                    });
                })
                ->limit(50)
                ->orderBy('name')
                ->get();
        }

        // Format array opsi ringan untuk komponen Searchable Dropdown Alpine.js
        $warehouseOptions = $buWarehouses->map(fn($w) => [
            'id' => (string) $w->id,
            'name' => $w->name . ($w->is_online_store ? ' ★ (Aktif Saat Ini)' : '')
        ])->values()->all();

        $branchOptions = $buBranches->map(fn($b) => [
            'id' => $b->name,
            'name' => $b->name
        ])->values()->all();

        $salesOptions = $buSales->map(fn($s) => [
            'id' => (string) $s->id,
            'name' => $s->name . ($s->employee_no ? " ({$s->employee_no})" : '')
        ])->values()->all();

        $cashierOptions = $buCashiers->map(fn($c) => [
            'id' => (string) $c->id,
            'name' => $c->name . " ({$c->email})"
        ])->values()->all();

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
            'warehouseOptions' => $warehouseOptions,
            'branchOptions' => $branchOptions,
            'salesOptions' => $salesOptions,
            'cashierOptions' => $cashierOptions,
            'hasBiteshipKey' => $hasBiteshipKey,
            'hasXenditKey' => $hasXenditKey,
        ]);
    }
}
