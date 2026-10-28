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
        try {
            $selectedBuId = $request->input('bu');
            if (!$selectedBuId) {
                try {
                    $userBuId = Auth::user()?->getActiveBusinessUnitId() ?? Auth::user()?->business_unit_id;
                } catch (\Throwable $e) {
                    $userBuId = null;
                }

                try {
                    $firstBu = Schema::hasTable('business_units')
                        ? (BusinessUnit::where('is_active', true)->first() ?: BusinessUnit::first())
                        : null;
                    $selectedBuId = $userBuId ?: $firstBu?->id;
                } catch (\Throwable $e) {
                    $selectedBuId = $userBuId;
                }
            }

            // 1. Pengaturan Global
            try {
                $globalSettings = [
                    'dynamic_shipping' => filter_var($settingService->get('ecommerce_dynamic_shipping', false), FILTER_VALIDATE_BOOLEAN),
                    'instant_payment' => filter_var($settingService->get('ecommerce_instant_payment', false), FILTER_VALIDATE_BOOLEAN),
                    'cs_phone' => (string) $settingService->get('ecommerce_cs_phone', '081298765432'),
                ];

                $referralSettings = [
                    'referral_is_active' => filter_var($settingService->get('referral_is_active', true), FILTER_VALIDATE_BOOLEAN),
                    'referral_commission_type' => strtoupper((string) $settingService->get('referral_commission_type', 'PERCENTAGE')),
                    'referral_commission_rate' => (float) $settingService->get('referral_commission_rate', 1.0),
                    'referral_reward_scope' => strtoupper((string) $settingService->get('referral_reward_scope', 'FIRST_ORDER_ONLY')),
                    'referral_max_commission_cap' => (float) $settingService->get('referral_max_commission_cap', 100000),
                    'referral_hold_days' => (int) $settingService->get('referral_hold_days', 7),
                    'referral_min_withdrawal' => (float) $settingService->get('referral_min_withdrawal', 50000),
                ];
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Settings: Failed to load global/referral settings: ' . $e->getMessage());
                $globalSettings = [
                    'dynamic_shipping' => false,
                    'instant_payment' => false,
                    'cs_phone' => '081298765432',
                ];
                $referralSettings = [
                    'referral_is_active' => true,
                    'referral_commission_type' => 'PERCENTAGE',
                    'referral_commission_rate' => 1.0,
                    'referral_reward_scope' => 'FIRST_ORDER_ONLY',
                    'referral_max_commission_cap' => 100000,
                    'referral_hold_days' => 7,
                    'referral_min_withdrawal' => 50000,
                ];
            }

            // 2. Data Gudang & Master Cabang BU
            $hasOnlineStoreCol = Schema::hasTable('warehouses') && Schema::hasColumn('warehouses', 'is_online_store');
            $hasBuCol = Schema::hasTable('warehouses') && Schema::hasColumn('warehouses', 'business_unit_id');

            $whCols = ['id', 'name'];
            if ($hasOnlineStoreCol) {
                $whCols[] = 'is_online_store';
            }

            $warehouses = collect();
            try {
                if (Schema::hasTable('warehouses')) {
                    if ($selectedBuId && $hasBuCol) {
                        $warehouses = Warehouse::where('business_unit_id', $selectedBuId)->get($whCols);
                    } else {
                        $warehouses = Warehouse::limit(50)->get($whCols);
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Settings: Failed to load warehouses: ' . $e->getMessage());
            }

            $onlineWarehouseId = $hasOnlineStoreCol
                ? $warehouses->firstWhere('is_online_store', true)?->id
                : null;

            try {
                $defaultBranchName = (string) $settingService->get(
                    "ecommerce_branch_{$selectedBuId}",
                    $settingService->get('ecommerce_default_branch_name', 'Banjarbaru')
                );
                $defaultSalesId = $selectedBuId ? $settingService->get("ecommerce_sales_{$selectedBuId}", $settingService->get('ecommerce_default_sales_id')) : null;
                $defaultHandledById = $selectedBuId ? $settingService->get("ecommerce_handled_by_{$selectedBuId}", $settingService->get('ecommerce_default_handled_by_id')) : null;
            } catch (\Throwable $e) {
                $defaultBranchName = 'Banjarbaru';
                $defaultSalesId = null;
                $defaultHandledById = null;
            }

            // 3. Kategori Produk BU
            $allCategories = collect();
            $activeCategories = [];
            $allBrands = collect();
            $activeBrands = [];

            if ($selectedBuId && Schema::hasTable('product_accurates')) {
                try {
                    $catCol = Schema::hasColumn('product_accurates', 'categoryName') ? 'categoryName' : null;
                    if ($catCol) {
                        $catQuery = ProductAccurate::whereNotNull($catCol)->where($catCol, '!=', '');
                        if (Schema::hasColumn('product_accurates', 'business_unit_id')) {
                            $catQuery->where('business_unit_id', $selectedBuId);
                        }
                        $allCategories = $catQuery->selectRaw("{$catCol}, count(*) as count")
                            ->groupBy($catCol)
                            ->orderBy($catCol)
                            ->get()
                            ->map(function ($row) use ($catCol) {
                                return [
                                    'name' => (string) $row->{$catCol},
                                    'count' => (int) $row->count,
                                ];
                            });
                    }

                    $activeCatsRaw = $settingService->get("ecommerce_categories_{$selectedBuId}", $allCategories->pluck('name')->toArray());
                    if (is_string($activeCatsRaw)) {
                        $decoded = json_decode($activeCatsRaw, true);
                        $activeCategories = is_array($decoded) ? $decoded : [];
                    } elseif (is_array($activeCatsRaw)) {
                        $activeCategories = $activeCatsRaw;
                    } else {
                        $activeCategories = $allCategories->pluck('name')->toArray();
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Settings: Failed to load categories: ' . $e->getMessage());
                }

                try {
                    $brandCol = Schema::hasColumn('product_accurates', 'brandName') ? 'brandName' : null;
                    if ($brandCol) {
                        $brandQuery = ProductAccurate::whereNotNull($brandCol)->where($brandCol, '!=', '');
                        if (Schema::hasColumn('product_accurates', 'business_unit_id')) {
                            $brandQuery->where('business_unit_id', $selectedBuId);
                        }
                        $allBrands = $brandQuery->selectRaw("{$brandCol}, count(*) as count")
                            ->groupBy($brandCol)
                            ->orderBy($brandCol)
                            ->get()
                            ->map(function ($row) use ($brandCol) {
                                return [
                                    'name' => (string) $row->{$brandCol},
                                    'count' => (int) $row->count,
                                ];
                            });
                    }

                    $activeBrandsRaw = $settingService->get("ecommerce_brands_{$selectedBuId}", $allBrands->pluck('name')->toArray());
                    if (is_string($activeBrandsRaw)) {
                        $decoded = json_decode($activeBrandsRaw, true);
                        $activeBrands = is_array($decoded) ? $decoded : [];
                    } elseif (is_array($activeBrandsRaw)) {
                        $activeBrands = $activeBrandsRaw;
                    } else {
                        $activeBrands = $allBrands->pluck('name')->toArray();
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Settings: Failed to load brands: ' . $e->getMessage());
                }
            }

            // Pastikan array selalu indexed dan bersih
            $activeCategories = array_values(array_filter((array) $activeCategories, fn($v) => is_string($v) && $v !== ''));
            $activeBrands = array_values(array_filter((array) $activeBrands, fn($v) => is_string($v) && $v !== ''));

            // Karyawan & User untuk sales / kasir default
            $employees = collect();
            try {
                if (Schema::hasTable('employes')) {
                    $empCols = ['id'];
                    if (Schema::hasColumn('employes', 'name')) $empCols[] = 'name';
                    $employees = Employe::get($empCols);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Settings: Failed to load employes: ' . $e->getMessage());
            }

            $users = collect();
            try {
                if (Schema::hasTable('users')) {
                    $userCols = ['id'];
                    if (Schema::hasColumn('users', 'name')) $userCols[] = 'name';
                    if (Schema::hasColumn('users', 'email')) $userCols[] = 'email';
                    $users = User::get($userCols);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Settings: Failed to load users: ' . $e->getMessage());
            }

            return Inertia::render('Settings/Index', [
                'globalSettings' => $globalSettings,
                'referralSettings' => $referralSettings,
                'selectedBuId' => $selectedBuId ? (int) $selectedBuId : null,
                'buSettings' => [
                    'online_warehouse_id' => $onlineWarehouseId,
                    'default_branch_name' => $defaultBranchName,
                    'default_sales_id' => $defaultSalesId ? (int) $defaultSalesId : null,
                    'default_handled_by_id' => $defaultHandledById ? (int) $defaultHandledById : null,
                    'all_categories' => $allCategories->values()->all(),
                    'active_categories' => $activeCategories,
                    'all_brands' => $allBrands->values()->all(),
                    'active_brands' => $activeBrands,
                ],
                'warehouses' => $warehouses->values()->all(),
                'employees' => $employees->values()->all(),
                'users' => $users->values()->all(),
            ]);
        } catch (\Throwable $fatalError) {
            \Illuminate\Support\Facades\Log::error('Settings: Fatal error in index(): ' . $fatalError->getMessage() . "\n" . $fatalError->getTraceAsString());
            return Inertia::render('Settings/Index', [
                'globalSettings' => [
                    'dynamic_shipping' => false,
                    'instant_payment' => false,
                    'cs_phone' => '081298765432',
                ],
                'referralSettings' => [
                    'referral_is_active' => true,
                    'referral_commission_type' => 'PERCENTAGE',
                    'referral_commission_rate' => 1.0,
                    'referral_reward_scope' => 'FIRST_ORDER_ONLY',
                    'referral_max_commission_cap' => 100000,
                    'referral_hold_days' => 7,
                    'referral_min_withdrawal' => 50000,
                ],
                'selectedBuId' => null,
                'buSettings' => [
                    'online_warehouse_id' => null,
                    'default_branch_name' => 'Banjarbaru',
                    'default_sales_id' => null,
                    'default_handled_by_id' => null,
                    'all_categories' => [],
                    'active_categories' => [],
                    'all_brands' => [],
                    'active_brands' => [],
                ],
                'warehouses' => [],
                'employees' => [],
                'users' => [],
            ]);
        }
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

    /**
     * Simpan pengaturan Program Referral & Komisi dari halaman Pengaturan App
     */
    public function saveReferralSettings(Request $request, SettingService $settingService): RedirectResponse
    {
        $validated = $request->validate([
            'referral_is_active' => 'boolean',
            'referral_commission_type' => 'required|in:PERCENTAGE,FIXED',
            'referral_commission_rate' => 'required|numeric|min:0.01',
            'referral_reward_scope' => 'required|in:FIRST_ORDER_ONLY,ALL_ORDERS',
            'referral_max_commission_cap' => 'required|numeric|min:0',
            'referral_hold_days' => 'required|integer|min:0|max:90',
            'referral_min_withdrawal' => 'required|numeric|min:10000',
        ]);

        $settingService->set('referral_is_active', $validated['referral_is_active'] ? '1' : '0');
        $settingService->set('referral_commission_type', $validated['referral_commission_type']);
        $settingService->set('referral_commission_rate', (string) $validated['referral_commission_rate']);
        $settingService->set('referral_reward_scope', $validated['referral_reward_scope']);
        $settingService->set('referral_max_commission_cap', (string) $validated['referral_max_commission_cap']);
        $settingService->set('referral_hold_days', (string) $validated['referral_hold_days']);
        $settingService->set('referral_min_withdrawal', (string) $validated['referral_min_withdrawal']);

        return redirect()->back()->with('success', 'Pengaturan program referral & komisi berhasil disimpan.');
    }
}
