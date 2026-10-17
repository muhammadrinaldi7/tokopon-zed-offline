<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Models\Employe;
use App\Models\ProductAccurate;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class EcommerceSettingsController extends Controller
{
    /**
     * Tampilan utama Pengaturan Toko & E-Commerce Mobile (Inertia React)
     */
    public function index(Request $request, SettingService $settingService): Response
    {
        $selectedBuId = $request->input('bu');
        if (!$selectedBuId) {
            $userBuId = Auth::user()?->getActiveBusinessUnitId() ?? Auth::user()?->business_unit_id;
            $firstBu = BusinessUnit::where('is_active', true)->first() ?: BusinessUnit::first();
            $selectedBuId = $userBuId ?: $firstBu?->id;
        }

        // 1. Pengaturan Global
        $globalSettings = [
            'dynamic_shipping' => filter_var($settingService->get('ecommerce_dynamic_shipping', false), FILTER_VALIDATE_BOOLEAN),
            'instant_payment' => filter_var($settingService->get('ecommerce_instant_payment', false), FILTER_VALIDATE_BOOLEAN),
            'cs_phone' => (string) $settingService->get('ecommerce_cs_phone', '081298765432'),
        ];

        // 2. Data Gudang & Master Cabang BU
        $hasOnlineStoreCol = Schema::hasColumn('warehouses', 'is_online_store');
        $whCols = ['id', 'name'];
        if ($hasOnlineStoreCol) {
            $whCols[] = 'is_online_store';
        }

        $warehouses = $selectedBuId
            ? Warehouse::where('business_unit_id', $selectedBuId)->get($whCols)
            : collect();

        $onlineWarehouseId = $hasOnlineStoreCol
            ? $warehouses->firstWhere('is_online_store', true)?->id
            : null;

        $defaultBranchName = (string) $settingService->get(
            "ecommerce_branch_{$selectedBuId}",
            $settingService->get('ecommerce_default_branch_name', 'Banjarbaru')
        );

        $defaultSalesId = $selectedBuId ? $settingService->get("ecommerce_sales_{$selectedBuId}", $settingService->get('ecommerce_default_sales_id')) : null;
        $defaultHandledById = $selectedBuId ? $settingService->get("ecommerce_handled_by_{$selectedBuId}", $settingService->get('ecommerce_default_handled_by_id')) : null;

        // 3. Kategori Produk BU
        $allCategories = collect();
        $activeCategories = [];
        $allBrands = collect();
        $activeBrands = [];

        if ($selectedBuId) {
            $allCategories = ProductAccurate::where('business_unit_id', $selectedBuId)
                ->whereNotNull('categoryName')
                ->where('categoryName', '!=', '')
                ->selectRaw('categoryName, count(*) as count')
                ->groupBy('categoryName')
                ->orderBy('categoryName')
                ->get()
                ->map(function ($row) {
                    return [
                        'name' => (string) $row->categoryName,
                        'count' => (int) $row->count,
                    ];
                });

            $activeCatsRaw = $settingService->get("ecommerce_categories_{$selectedBuId}", $allCategories->pluck('name')->toArray());
            if (is_string($activeCatsRaw)) {
                $decoded = json_decode($activeCatsRaw, true);
                $activeCategories = is_array($decoded) ? $decoded : [];
            } elseif (is_array($activeCatsRaw)) {
                $activeCategories = $activeCatsRaw;
            } else {
                $activeCategories = $allCategories->pluck('name')->toArray();
            }

            // 4. Merek Produk BU
            $allBrands = ProductAccurate::where('business_unit_id', $selectedBuId)
                ->whereNotNull('brandName')
                ->where('brandName', '!=', '')
                ->selectRaw('brandName, count(*) as count')
                ->groupBy('brandName')
                ->orderBy('brandName')
                ->get()
                ->map(function ($row) {
                    return [
                        'name' => (string) $row->brandName,
                        'count' => (int) $row->count,
                    ];
                });

            $activeBrandsRaw = $settingService->get("ecommerce_brands_{$selectedBuId}", $allBrands->pluck('name')->toArray());
            if (is_string($activeBrandsRaw)) {
                $decoded = json_decode($activeBrandsRaw, true);
                $activeBrands = is_array($decoded) ? $decoded : [];
            } elseif (is_array($activeBrandsRaw)) {
                $activeBrands = $activeBrandsRaw;
            } else {
                $activeBrands = $allBrands->pluck('name')->toArray();
            }
        }

        // Pastikan array selalu indexed dan bersih
        $activeCategories = array_values(array_filter((array) $activeCategories, fn($v) => is_string($v) && $v !== ''));
        $activeBrands = array_values(array_filter((array) $activeBrands, fn($v) => is_string($v) && $v !== ''));

        // Karyawan & User untuk sales / kasir default
        $employees = collect();
        if (Schema::hasTable('employes')) {
            $employees = Employe::orderBy('name')->get(['id', 'name']);
        }
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return Inertia::render('Settings/Index', [
            'globalSettings' => $globalSettings,
            'selectedBuId' => $selectedBuId ? (int) $selectedBuId : null,
            'buSettings' => [
                'online_warehouse_id' => $onlineWarehouseId,
                'default_branch_name' => $defaultBranchName,
                'default_sales_id' => $defaultSalesId ? (int) $defaultSalesId : null,
                'default_handled_by_id' => $defaultHandledById ? (int) $defaultHandledById : null,
                'all_categories' => $allCategories,
                'active_categories' => $activeCategories,
                'all_brands' => $allBrands,
                'active_brands' => $activeBrands,
            ],
            'warehouses' => $warehouses,
            'employees' => $employees,
            'users' => $users,
        ]);
    }

    /**
     * Simpan pengaturan fitur global
     */
    public function saveGlobalSettings(Request $request, SettingService $settingService): RedirectResponse
    {
        $validated = $request->validate([
            'cs_phone' => 'required|string|max:20',
            'dynamic_shipping' => 'boolean',
            'instant_payment' => 'boolean',
        ]);

        $settingService->set('ecommerce_cs_phone', $validated['cs_phone']);
        $settingService->set('ecommerce_dynamic_shipping', $validated['dynamic_shipping'] ? '1' : '0');
        $settingService->set('ecommerce_instant_payment', $validated['instant_payment'] ? '1' : '0');

        return redirect()->back()->with('success', 'Pengaturan global E-Commerce berhasil disimpan.');
    }

    /**
     * Simpan pengaturan per Unit Bisnis
     */
    public function saveBusinessUnitSettings(Request $request, SettingService $settingService): RedirectResponse
    {
        $validated = $request->validate([
            'business_unit_id' => 'required|exists:business_units,id',
            'online_warehouse_id' => 'nullable|exists:warehouses,id',
            'default_branch_name' => 'nullable|string|max:100',
            'default_sales_id' => 'nullable|exists:employes,id',
            'default_handled_by_id' => 'nullable|exists:users,id',
            'active_categories' => 'array',
            'active_brands' => 'array',
        ]);

        $buId = $validated['business_unit_id'];

        // Atur gudang online store
        if (Schema::hasColumn('warehouses', 'is_online_store') && !empty($validated['online_warehouse_id'])) {
            Warehouse::where('business_unit_id', $buId)->update(['is_online_store' => false]);
            Warehouse::where('id', $validated['online_warehouse_id'])->update(['is_online_store' => true]);
        }

        $settingService->set("ecommerce_branch_{$buId}", $validated['default_branch_name'] ?? 'Banjarbaru');
        $settingService->set("ecommerce_sales_{$buId}", $validated['default_sales_id'] ?? null);
        $settingService->set("ecommerce_handled_by_{$buId}", $validated['default_handled_by_id'] ?? null);
        $settingService->set("ecommerce_categories_{$buId}", json_encode($validated['active_categories'] ?? []));
        $settingService->set("ecommerce_brands_{$buId}", json_encode($validated['active_brands'] ?? []));

        return redirect()->back()->with('success', 'Pengaturan unit bisnis toko berhasil disimpan.');
    }
}
