<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BusinessUnit;
use App\Models\Warehouse;
use App\Models\ProductAccurate;
use App\Models\WarehouseStock;
use App\Livewire\Zoffline\Warehouse\CekStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CekStockPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected BusinessUnit $bu1;
    protected BusinessUnit $bu2;
    protected Warehouse $warehouseBu1;
    protected Warehouse $warehouseBu2;
    protected ProductAccurate $productBu1;
    protected ProductAccurate $productBu2;

    protected function setUp(): void
    {
        parent::setUp();

        // Fetch or create Business Units (already seeded in migration)
        $this->bu1 = BusinessUnit::firstOrCreate(
            ['code' => 'syihab'],
            ['name' => 'Syihab', 'is_active' => true]
        );

        $this->bu2 = BusinessUnit::firstOrCreate(
            ['code' => 'second'],
            ['name' => 'GSK Second', 'is_active' => true]
        );

        // Create Warehouses
        $this->warehouseBu1 = Warehouse::create([
            'name' => 'Gudang Syihab 1',
            'code' => 'GS1',
            'business_unit_id' => $this->bu1->id,
        ]);

        $this->warehouseBu2 = Warehouse::create([
            'name' => 'Gudang GSK 1',
            'code' => 'GG1',
            'business_unit_id' => $this->bu2->id,
        ]);

        // Create Permissions
        Permission::firstOrCreate(['name' => 'view-stock', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'cek-stock-all-bu', 'guard_name' => 'web']);

        // Create Products in both BUs with similar name
        $this->productBu1 = ProductAccurate::create([
            'accurate_id' => 'ACC-001',
            'item_no' => 'IPHONE-13-SYI',
            'name' => 'iPhone 13 128GB Midnight',
            'business_unit_id' => $this->bu1->id,
            'base_price' => 10000000,
            'stock' => 5,
        ]);

        $this->productBu2 = ProductAccurate::create([
            'accurate_id' => 'ACC-002',
            'item_no' => 'IPHONE-13-GSK',
            'name' => 'iPhone 13 128GB Blue Second',
            'business_unit_id' => $this->bu2->id,
            'base_price' => 8500000,
            'stock' => 3,
        ]);

        // Create Warehouse Stocks
        WarehouseStock::create([
            'warehouse_id' => $this->warehouseBu1->id,
            'variant_id' => $this->productBu1->id,
            'variant_type' => ProductAccurate::class,
            'stock' => 5,
        ]);

        WarehouseStock::create([
            'warehouse_id' => $this->warehouseBu2->id,
            'variant_id' => $this->productBu2->id,
            'variant_type' => ProductAccurate::class,
            'stock' => 3,
        ]);
    }

    public function test_user_without_permission_cannot_check_other_bu(): void
    {
        $user = User::factory()->create([
            'business_unit_id' => $this->bu1->id,
            'warehouse_id' => $this->warehouseBu1->id,
        ]);
        $user->givePermissionTo('view-stock');

        $this->actingAs($user);

        Livewire::test(CekStock::class)
            ->assertSet('canCheckAllBu', false)
            ->assertDontSee('Semua Unit Bisnis')
            ->set('searchQuery', 'iPhone 13')
            ->assertSee('iPhone 13 128GB Midnight')
            ->assertDontSee('iPhone 13 128GB Blue Second');
    }

    public function test_user_with_permission_can_check_all_bu_and_filter(): void
    {
        $user = User::factory()->create([
            'business_unit_id' => $this->bu1->id,
            'warehouse_id' => $this->warehouseBu1->id,
        ]);
        $user->givePermissionTo(['view-stock', 'cek-stock-all-bu']);

        $this->actingAs($user);

        // 1. Default (Semua BU): should see products from both BU1 and BU2
        Livewire::test(CekStock::class)
            ->assertSet('canCheckAllBu', true)
            ->assertSee('Semua Unit Bisnis')
            ->assertSee($this->bu1->name)
            ->assertSee($this->bu2->name)
            ->set('searchQuery', 'iPhone 13')
            ->assertSee('iPhone 13 128GB Midnight')
            ->assertSee('iPhone 13 128GB Blue Second')
            // 2. Filter to BU 1: should only see BU 1 product
            ->set('filterBusinessUnit', (string)$this->bu1->id)
            ->assertSee('iPhone 13 128GB Midnight')
            ->assertDontSee('iPhone 13 128GB Blue Second')
            // 3. Filter to BU 2: should only see BU 2 product
            ->set('filterBusinessUnit', (string)$this->bu2->id)
            ->assertDontSee('iPhone 13 128GB Midnight')
            ->assertSee('iPhone 13 128GB Blue Second')
            // 4. Select product from BU 2 and check detail
            ->call('selectProduct', $this->productBu2->id, 'accurate')
            ->assertSet('selectedProductBu', $this->bu2->name)
            ->assertSee('Gudang GSK 1');
    }

    public function test_unauthorized_user_cannot_bypass_bu_scoping_by_setting_filter(): void
    {
        $user = User::factory()->create([
            'business_unit_id' => $this->bu1->id,
            'warehouse_id' => $this->warehouseBu1->id,
        ]);
        $user->givePermissionTo('view-stock');

        $this->actingAs($user);

        // Even if unauthorized user sets filterBusinessUnit to BU 2, it still restricts to BU 1
        Livewire::test(CekStock::class)
            ->set('filterBusinessUnit', (string)$this->bu2->id)
            ->set('searchQuery', 'iPhone 13')
            ->assertSee('iPhone 13 128GB Midnight')
            ->assertDontSee('iPhone 13 128GB Blue Second');
    }

    public function test_project_filter_updates_with_business_unit_selection(): void
    {
        $this->productBu1->update(['proyek' => 'Proyek Syihab Retail']);
        $this->productBu2->update(['proyek' => 'Proyek Second Tradein']);

        $user = User::factory()->create([
            'business_unit_id' => $this->bu1->id,
            'warehouse_id' => $this->warehouseBu1->id,
        ]);
        $user->givePermissionTo(['view-stock', 'cek-stock-all-bu']);

        $this->actingAs($user);

        // When "Semua BU" (empty), both projects appear
        Livewire::test(CekStock::class)
            ->assertSee('Proyek Syihab Retail')
            ->assertSee('Proyek Second Tradein')
            // When filtered to BU 1
            ->set('filterBusinessUnit', (string)$this->bu1->id)
            ->assertSee('Proyek Syihab Retail')
            ->assertDontSee('Proyek Second Tradein')
            // When filtered to BU 2
            ->set('filterBusinessUnit', (string)$this->bu2->id)
            ->assertDontSee('Proyek Syihab Retail')
            ->assertSee('Proyek Second Tradein');
    }
}
