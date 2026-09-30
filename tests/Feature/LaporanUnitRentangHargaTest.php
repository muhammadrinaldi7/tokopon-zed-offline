<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BusinessUnit;
use App\Models\Warehouse;
use App\Models\Vendor;
use App\Models\ProductSerialNumber;
use App\Models\ProductAccurate;
use App\Livewire\Zoffline\Reporting\LaporanUnitRentangHarga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanUnitRentangHargaTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $bu;
    protected BusinessUnit $bu2;
    protected Warehouse $warehouse;
    protected Warehouse $warehouse2;
    protected Vendor $vendor1;
    protected Vendor $vendor2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create active business unit 1
        $this->bu = BusinessUnit::create([
            'name' => 'Test Business Unit',
            'code' => 'TBU',
            'is_active' => true,
        ]);

        // Create active business unit 2
        $this->bu2 = BusinessUnit::create([
            'name' => 'Second Business Unit',
            'code' => 'SBU',
            'is_active' => true,
        ]);

        // Create warehouses
        $this->warehouse = Warehouse::create([
            'name' => 'Test Warehouse BU1',
            'code' => 'WH01',
            'business_unit_id' => $this->bu->id,
        ]);

        $this->warehouse2 = Warehouse::create([
            'name' => 'Test Warehouse BU2',
            'code' => 'WH02',
            'business_unit_id' => $this->bu2->id,
        ]);

        // Create Role and Permission
        $role = \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $permissionReporting = \Spatie\Permission\Models\Permission::create(['name' => 'view-reporting']);
        $permissionCekHarga = \Spatie\Permission\Models\Permission::create(['name' => 'cek-unit-rentang-harga']);
        $role->givePermissionTo([$permissionReporting, $permissionCekHarga]);
        
        // Create User
        $this->user = User::factory()->create([
            'email' => 'admin@test.com',
            'business_unit_id' => $this->bu->id,
        ]);
        
        $this->user->assignRole($role);

        // Create Vendors
        $this->vendor1 = Vendor::create([
            'vendor_no' => 'VND-001',
            'vendor_name' => 'Vendor Pertama',
        ]);

        $this->vendor2 = Vendor::create([
            'vendor_no' => 'VND-002',
            'vendor_name' => 'Vendor Kedua',
        ]);

        // Create Products in different price tiers and brands:
        // Product 1: Rp 800.000, Brand: Apple, BU1, Kategori: Smartphone
        $product1 = ProductAccurate::create([
            'item_no' => 'PROD-TIER1',
            'name' => 'iPhone SE 800rb',
            'brandName' => 'Apple',
            'categoryName' => 'Smartphone',
            'accurate_id' => 'ACC-TEST-001',
            'proyek' => 'RESMI',
            'base_price' => 800000,
            'business_unit_id' => $this->bu->id,
        ]);

        // Product 2: Rp 2.500.000, Brand: Samsung, BU1, Kategori: Tablet
        $product2 = ProductAccurate::create([
            'item_no' => 'PROD-TIER2',
            'name' => 'Samsung Galaxy Mid 2.5jt',
            'brandName' => 'Samsung',
            'categoryName' => 'Tablet',
            'accurate_id' => 'ACC-TEST-002',
            'proyek' => 'INTER',
            'base_price' => 2500000,
            'business_unit_id' => $this->bu->id,
        ]);

        // Product 3: Rp 7.000.000, Brand: Apple, BU1, Kategori: Smartphone
        $product3 = ProductAccurate::create([
            'item_no' => 'PROD-TIER3',
            'name' => 'iPhone 13 7jt',
            'brandName' => 'Apple',
            'categoryName' => 'Smartphone',
            'accurate_id' => 'ACC-TEST-003',
            'proyek' => 'BEACUKAI',
            'base_price' => 7000000,
            'business_unit_id' => $this->bu->id,
        ]);

        // Product 4: Rp 3.000.000, Brand: Xiaomi, BU2, Kategori: Smartphone
        $product4 = ProductAccurate::create([
            'item_no' => 'PROD-TIER4',
            'name' => 'Xiaomi Note BU2 3jt',
            'brandName' => 'Xiaomi',
            'categoryName' => 'Smartphone',
            'accurate_id' => 'ACC-TEST-004',
            'proyek' => 'RESMI',
            'base_price' => 3000000,
            'business_unit_id' => $this->bu2->id,
        ]);

        // Create Serial Numbers
        ProductSerialNumber::create([
            'serial_number' => 'SN-800K-APPLE',
            'item_no' => 'PROD-TIER1',
            'product_accurate_id' => $product1->id,
            'warehouse_id' => $this->warehouse->id,
            'hpp' => 500000,
            'vendor_id' => $this->vendor1->id,
            'status' => 'Available',
            'receipt_date' => now()->subDays(3)->format('Y-m-d'),
        ]);

        ProductSerialNumber::create([
            'serial_number' => 'SN-2.5M-SAMSUNG',
            'item_no' => 'PROD-TIER2',
            'product_accurate_id' => $product2->id,
            'warehouse_id' => $this->warehouse->id,
            'hpp' => 2000000,
            'vendor_id' => $this->vendor2->id,
            'status' => 'Available',
            'receipt_date' => now()->subDays(6)->format('Y-m-d'),
        ]);

        ProductSerialNumber::create([
            'serial_number' => 'SN-7M-APPLE',
            'item_no' => 'PROD-TIER3',
            'product_accurate_id' => $product3->id,
            'warehouse_id' => $this->warehouse->id,
            'hpp' => 5500000,
            'vendor_id' => $this->vendor1->id,
            'status' => 'Available',
            'receipt_date' => now()->subDays(10)->format('Y-m-d'),
        ]);

        ProductSerialNumber::create([
            'serial_number' => 'SN-3M-XIAOMI-BU2',
            'item_no' => 'PROD-TIER4',
            'product_accurate_id' => $product4->id,
            'warehouse_id' => $this->warehouse2->id,
            'hpp' => 2200000,
            'vendor_id' => $this->vendor2->id,
            'status' => 'Available',
            'receipt_date' => now()->subDays(2)->format('Y-m-d'),
        ]);
    }

    public function test_laporan_unit_harga_page_accessible()
    {
        $response = $this->actingAs($this->user)->get(route('reporting.laporan-unit-harga'));
        $response->assertStatus(200);
        $response->assertSee('Cek Unit Rentang Harga');
        $response->assertSee('Preset Rentang Harga');
        $response->assertSee('Rekap SKU (Produk)');
        $response->assertSee('PROD-TIER1');
        $response->assertSee('PROD-TIER2');
        $response->assertSee('PROD-TIER3');
    }

    public function test_filter_by_min_and_max_price_in_sku_mode()
    {
        // In SKU mode (default), filter 1jt - 3jt should return PROD-TIER2 (base_price 2.500.000)
        // and its serial number SN-2.5M-SAMSUNG (HPP 2.000.000)
        Livewire::actingAs($this->user)
            ->test(LaporanUnitRentangHarga::class)
            ->set('businessUnitId', $this->bu->id)
            ->set('minPrice', '1000000')
            ->set('maxPrice', '3000000')
            ->assertViewHas('items', function ($items) {
                return $items->count() === 1 && $items->first()->item_no === 'PROD-TIER2';
            })
            ->assertViewHas('summary', function ($summary) {
                return $summary['total_skus'] === 1 && $summary['total_units'] === 1 && $summary['total_nilai_jual'] == 2500000;
            });
    }

    public function test_filter_in_sn_mode()
    {
        // Switch to SN mode
        Livewire::actingAs($this->user)
            ->test(LaporanUnitRentangHarga::class)
            ->set('viewMode', 'sn')
            ->set('businessUnitId', $this->bu->id)
            ->set('minPrice', '1000000')
            ->set('maxPrice', '3000000')
            ->assertViewHas('items', function ($items) {
                return $items->count() === 1 && $items->first()->serial_number === 'SN-2.5M-SAMSUNG';
            });
    }

    public function test_filter_by_brand()
    {
        // Filter by brand = 'Apple' in SKU mode
        Livewire::actingAs($this->user)
            ->test(LaporanUnitRentangHarga::class)
            ->set('businessUnitId', $this->bu->id)
            ->set('brand', 'Apple')
            ->assertViewHas('items', function ($items) {
                return $items->count() === 2 &&
                    $items->contains('item_no', 'PROD-TIER1') &&
                    $items->contains('item_no', 'PROD-TIER3');
            });
    }

    public function test_filter_by_kategori()
    {
        // Filter by kategori = 'Tablet' in SKU mode -> matches PROD-TIER2
        Livewire::actingAs($this->user)
            ->test(LaporanUnitRentangHarga::class)
            ->set('businessUnitId', $this->bu->id)
            ->set('kategori', 'Tablet')
            ->assertViewHas('items', function ($items) {
                return $items->count() === 1 && $items->first()->item_no === 'PROD-TIER2';
            });

        // Filter by kategori = 'Smartphone' in SKU mode -> matches PROD-TIER1 and PROD-TIER3 in BU1
        Livewire::actingAs($this->user)
            ->test(LaporanUnitRentangHarga::class)
            ->set('businessUnitId', $this->bu->id)
            ->set('kategori', 'Smartphone')
            ->assertViewHas('items', function ($items) {
                return $items->count() === 2 &&
                    $items->contains('item_no', 'PROD-TIER1') &&
                    $items->contains('item_no', 'PROD-TIER3');
            });
    }

    public function test_filter_by_business_unit()
    {
        // When businessUnitId is BU2 -> returns PROD-TIER4
        Livewire::actingAs($this->user)
            ->test(LaporanUnitRentangHarga::class)
            ->set('businessUnitId', $this->bu2->id)
            ->assertViewHas('items', function ($items) {
                return $items->count() === 1 && $items->first()->item_no === 'PROD-TIER4';
            });
    }

    public function test_filter_by_presets()
    {
        $component = Livewire::actingAs($this->user)
            ->test(LaporanUnitRentangHarga::class)
            ->set('businessUnitId', $this->bu->id);

        // Under 1M preset -> matches PROD-TIER1
        $component->call('setPreset', 'under_1m')
            ->assertSet('minPrice', '0')
            ->assertSet('maxPrice', '1000000')
            ->assertViewHas('items', function ($items) {
                return $items->count() === 1 && $items->first()->item_no === 'PROD-TIER1';
            });

        // 5M - 10M preset -> matches PROD-TIER3
        $component->call('setPreset', '5m_10m')
            ->assertSet('minPrice', '5000000')
            ->assertSet('maxPrice', '10000000')
            ->assertViewHas('items', function ($items) {
                return $items->count() === 1 && $items->first()->item_no === 'PROD-TIER3';
            });
    }

    public function test_filter_by_hpp_price_type()
    {
        // Filter HPP between 1jt and 3jt -> matches PROD-TIER2 (HPP SN: 2.000.000)
        Livewire::actingAs($this->user)
            ->test(LaporanUnitRentangHarga::class)
            ->set('businessUnitId', $this->bu->id)
            ->set('priceType', 'hpp')
            ->set('minPrice', '1000000')
            ->set('maxPrice', '3000000')
            ->assertViewHas('items', function ($items) {
                return $items->count() === 1 && $items->first()->item_no === 'PROD-TIER2';
            });
    }

    public function test_export_excel_and_csv_in_both_modes()
    {
        // SKU mode export
        Livewire::actingAs($this->user)
            ->test(LaporanUnitRentangHarga::class)
            ->set('viewMode', 'sku')
            ->set('minPrice', '1000000')
            ->call('exportExcel')
            ->assertFileDownloaded();

        Livewire::actingAs($this->user)
            ->test(LaporanUnitRentangHarga::class)
            ->set('viewMode', 'sku')
            ->set('minPrice', '1000000')
            ->call('exportCsv')
            ->assertFileDownloaded();

        // SN mode export
        Livewire::actingAs($this->user)
            ->test(LaporanUnitRentangHarga::class)
            ->set('viewMode', 'sn')
            ->set('minPrice', '1000000')
            ->call('exportExcel')
            ->assertFileDownloaded();
    }

    public function test_laporan_unit_harga_forbidden_without_permission()
    {
        $unauthorizedUser = User::factory()->create([
            'email' => 'unauthorized@test.com',
            'business_unit_id' => $this->bu->id,
        ]);
        $roleNoPerm = \Spatie\Permission\Models\Role::create(['name' => 'staff_no_perm']);
        $roleNoPerm->givePermissionTo('view-reporting');
        $unauthorizedUser->assignRole($roleNoPerm);

        $response = $this->actingAs($unauthorizedUser)->get(route('reporting.laporan-unit-harga'));
        $response->assertStatus(403);
    }
}
