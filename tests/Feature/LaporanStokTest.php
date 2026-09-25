<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BusinessUnit;
use App\Models\Warehouse;
use App\Models\Vendor;
use App\Models\ProductSerialNumber;
use App\Models\ProductAccurate;
use App\Livewire\Zoffline\Reporting\LaporanStok;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanStokTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $bu;
    protected Warehouse $warehouse;
    protected Vendor $vendor1;
    protected Vendor $vendor2;

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

        // Create Vendors
        $this->vendor1 = Vendor::create([
            'vendor_no' => 'VND-001',
            'vendor_name' => 'Vendor Pertama',
        ]);

        $this->vendor2 = Vendor::create([
            'vendor_no' => 'VND-002',
            'vendor_name' => 'Vendor Kedua',
        ]);

        // Create ProductAccurate
        $product1 = ProductAccurate::create([
            'item_no' => 'PROD-001',
            'name' => 'Product Test Resmi',
            'accurate_id' => 'ACC-TEST-001',
            'proyek' => 'RESMI',
            'business_unit_id' => $this->bu->id,
        ]);

        $product2 = ProductAccurate::create([
            'item_no' => 'PROD-002',
            'name' => 'Product Test Inter',
            'accurate_id' => 'ACC-TEST-002',
            'proyek' => 'INTER',
            'business_unit_id' => $this->bu->id,
        ]);

        // Create Product Serial Numbers
        ProductSerialNumber::create([
            'serial_number' => 'SN-VND1-001',
            'item_no' => 'PROD-001',
            'product_accurate_id' => $product1->id,
            'warehouse_id' => $this->warehouse->id,
            'hpp' => 100000,
            'vendor_id' => $this->vendor1->id,
            'status' => 'Available',
            'receipt_date' => now()->subDays(5)->format('Y-m-d'),
        ]);

        ProductSerialNumber::create([
            'serial_number' => 'SN-VND2-002',
            'item_no' => 'PROD-002',
            'product_accurate_id' => $product2->id,
            'warehouse_id' => $this->warehouse->id,
            'hpp' => 120000,
            'vendor_id' => $this->vendor2->id,
            'status' => 'Available',
            'receipt_date' => now()->subDays(10)->format('Y-m-d'),
        ]);
    }

    public function test_laporan_stok_page_accessible_by_authenticated_user()
    {
        $response = $this->actingAs($this->user)->get(route('reporting.laporan-stok'));
        $response->assertStatus(200);
        $response->assertSee('Subkategori');
    }

    public function test_laporan_stok_filters_by_vendor_id()
    {
        // When vendor_id is empty, it should show both
        Livewire::actingAs($this->user)
            ->test(LaporanStok::class)
            ->assertSet('vendor_id', '')
            ->assertViewHas('stocks', function ($stocks) {
                return $stocks->count() === 2;
            });

        // Filter by vendor 1
        Livewire::actingAs($this->user)
            ->test(LaporanStok::class)
            ->set('vendor_id', $this->vendor1->id)
            ->assertViewHas('stocks', function ($stocks) {
                return $stocks->count() === 1 && $stocks->first()->serial_number === 'SN-VND1-001';
            });

        // Filter by vendor 2
        Livewire::actingAs($this->user)
            ->test(LaporanStok::class)
            ->set('vendor_id', $this->vendor2->id)
            ->assertViewHas('stocks', function ($stocks) {
                return $stocks->count() === 1 && $stocks->first()->serial_number === 'SN-VND2-002';
            });
    }

    public function test_laporan_stok_filters_by_subkategori()
    {
        // Filter by subkategori RESMI
        Livewire::actingAs($this->user)
            ->test(LaporanStok::class)
            ->set('subkategori', 'RESMI')
            ->assertViewHas('stocks', function ($stocks) {
                return $stocks->count() === 1 && $stocks->first()->serial_number === 'SN-VND1-001';
            });

        // Filter by subkategori INTER
        Livewire::actingAs($this->user)
            ->test(LaporanStok::class)
            ->set('subkategori', 'INTER')
            ->assertViewHas('stocks', function ($stocks) {
                return $stocks->count() === 1 && $stocks->first()->serial_number === 'SN-VND2-002';
            });
    }

    public function test_laporan_stok_filters_by_query_parameter()
    {
        // Access with vendor_id parameter
        Livewire::withQueryParams(['vendor_id' => $this->vendor2->id])
            ->actingAs($this->user)
            ->test(LaporanStok::class)
            ->assertSet('vendor_id', $this->vendor2->id)
            ->assertViewHas('stocks', function ($stocks) {
                return $stocks->count() === 1 && $stocks->first()->serial_number === 'SN-VND2-002';
            });

        // Access with subkategori parameter
        Livewire::withQueryParams(['subkategori' => 'RESMI'])
            ->actingAs($this->user)
            ->test(LaporanStok::class)
            ->assertSet('subkategori', 'RESMI')
            ->assertViewHas('stocks', function ($stocks) {
                return $stocks->count() === 1 && $stocks->first()->serial_number === 'SN-VND1-001';
            });
    }

    public function test_laporan_stok_sort_by_subkategori()
    {
        Livewire::actingAs($this->user)
            ->test(LaporanStok::class)
            ->call('sortBy', 'subkategori')
            ->assertSet('sortField', 'subkategori')
            ->assertSet('sortDirection', 'asc')
            ->assertViewHas('stocks', function ($stocks) {
                // INTER comes before RESMI in ascending order
                return $stocks->first()->serial_number === 'SN-VND2-002';
            });
    }

    public function test_laporan_stok_excel_export()
    {
        $response = Livewire::actingAs($this->user)
            ->test(LaporanStok::class)
            ->set('vendor_id', $this->vendor1->id)
            ->call('exportExcel');

        $response->assertFileDownloaded();
    }

    public function test_laporan_stok_csv_export()
    {
        $response = Livewire::actingAs($this->user)
            ->test(LaporanStok::class)
            ->set('vendor_id', $this->vendor2->id)
            ->call('exportCsv');

        $response->assertFileDownloaded();
    }
}
