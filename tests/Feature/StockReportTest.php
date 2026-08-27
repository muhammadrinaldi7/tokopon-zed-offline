<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BusinessUnit;
use App\Models\Warehouse;
use App\Models\ProductAccurate;
use App\Models\ProductSerialNumber;
use App\Models\WarehouseStock;
use App\Livewire\Zoffline\Reporting\StockReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $bu;
    protected Warehouse $warehouse;
    protected ProductAccurate $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create active business unit
        $this->bu = BusinessUnit::create([
            'name' => 'Test Business Unit',
            'code' => 'TBU',
            'is_active' => true,
        ]);

        // Create warehouse
        $this->warehouse = Warehouse::create([
            'name' => 'Test Warehouse',
            'code' => 'WH01',
            'business_unit_id' => $this->bu->id,
        ]);

        // Create Role and Permission
        $role = \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        $permission = \Spatie\Permission\Models\Permission::create(['name' => 'view-reporting']);
        $role->givePermissionTo($permission);

        // Create User
        $this->user = User::factory()->create([
            'email' => 'admin@test.com',
            'business_unit_id' => $this->bu->id,
        ]);
        $this->user->assignRole($role);

        // Create ProductAccurate
        $this->product = ProductAccurate::create([
            'item_no' => 'PROD-001',
            'name' => 'Product Test Stock',
            'accurate_id' => 'ACC-TEST-003',
            'brandName' => 'Apple',
            'categoryName' => 'Handphone',
            'base_cost' => 5000000,
            'base_price' => 6000000,
            'stock' => 10,
            'business_unit_id' => $this->bu->id,
        ]);

        // Create WarehouseStock
        WarehouseStock::create([
            'variant_type' => ProductAccurate::class,
            'variant_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'stock' => 10,
        ]);

        // Create Product Serial Numbers
        ProductSerialNumber::create([
            'serial_number' => 'SN-STOCK-001',
            'item_no' => 'PROD-001',
            'product_accurate_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'hpp' => 5000000,
            'status' => 'Available',
            'receipt_date' => now()->format('Y-m-d'),
        ]);
    }

    public function test_stock_report_page_accessible_by_authenticated_user()
    {
        $response = $this->actingAs($this->user)->get(route('reporting.stock'));
        $response->assertStatus(200);
    }

    public function test_stock_report_filters_by_brand()
    {
        // When filterBrand is empty, it should show Apple
        Livewire::actingAs($this->user)
            ->test(StockReport::class)
            ->assertSet('filterBrand', '')
            ->assertViewHas('stocks', function ($stocks) {
                return $stocks->count() === 1;
            });

        // Filter by Apple
        Livewire::actingAs($this->user)
            ->test(StockReport::class)
            ->set('filterBrand', 'Apple')
            ->assertViewHas('stocks', function ($stocks) {
                return $stocks->count() === 1;
            });

        // Filter by Samsung (non-existent)
        Livewire::actingAs($this->user)
            ->test(StockReport::class)
            ->set('filterBrand', 'Samsung')
            ->assertViewHas('stocks', function ($stocks) {
                return $stocks->count() === 0;
            });
    }

    public function test_stock_report_excel_export()
    {
        $response = Livewire::actingAs($this->user)
            ->test(StockReport::class)
            ->call('exportExcel');

        $response->assertFileDownloaded();
    }

    public function test_stock_report_csv_export()
    {
        $response = Livewire::actingAs($this->user)
            ->test(StockReport::class)
            ->call('exportCsv');

        $response->assertFileDownloaded();
    }
}
