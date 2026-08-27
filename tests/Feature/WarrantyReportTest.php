<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BusinessUnit;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductAccurate;
use App\Models\OrderItemSerialNumber;
use App\Models\WarrantyPolicy;
use App\Models\DeviceInspection;
use App\Models\Warranty;
use App\Livewire\Zoffline\Reporting\WarrantyReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WarrantyReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $bu;
    protected Branch $branch;
    protected Order $order;
    protected OrderItemSerialNumber $oisn;
    protected WarrantyPolicy $policy;
    protected DeviceInspection $inspection;
    protected Warranty $warranty;

    protected function setUp(): void
    {
        parent::setUp();

        // Create active business unit
        $this->bu = BusinessUnit::create([
            'name' => 'Test Business Unit',
            'code' => 'TBU',
            'is_active' => true,
        ]);

        // Create Branch
        $this->branch = Branch::create([
            'name' => 'Cabang Test',
            'code' => 'CBT',
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
        $product = ProductAccurate::create([
            'item_no' => 'PROD-001',
            'name' => 'Product Test Warranty',
            'accurate_id' => 'ACC-TEST-004',
            'brandName' => 'Samsung',
            'categoryName' => 'Handphone',
        ]);

        // Create SecondProduct
        $sp = \App\Models\SecondProduct::create([
            'name' => 'Product Test Warranty',
            'slug' => 'product-test-warranty',
        ]);

        // Create SecondProductVariant
        $spv = \App\Models\SecondProductVariant::create([
            'second_product_id' => $sp->id,
            'product_accurate_id' => $product->id,
            'price' => 5000000,
        ]);

        // Create Order
        $this->order = Order::create([
            'order_number' => 'ORD-80001',
            'accurate_invoice_no' => 'INV-80001',
            'business_unit_id' => $this->bu->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'sales_by' => $this->user->id,
            'total_amount' => 5000000,
            'grand_total' => 5000000,
            'order_status' => 'COMPLETED',
            'shipping_address_snapshot' => ['store' => 'Cabang Test'],
        ]);

        // Create OrderItem
        $orderItem = OrderItem::create([
            'order_id' => $this->order->id,
            'product_name' => 'Product Test Warranty',
            'qty' => 1,
            'price_at_checkout' => 5000000,
            'subtotal' => 5000000,
            'product_variant_type' => ProductAccurate::class,
            'product_variant_id' => $product->id,
        ]);

        // Create OrderItemSerialNumber
        $this->oisn = OrderItemSerialNumber::create([
            'order_item_id' => $orderItem->id,
            'serial_number' => 'SN-WARRANTY-001',
        ]);

        // Create WarrantyPolicy
        $this->policy = WarrantyPolicy::create([
            'name' => 'Policy Test',
            'type' => 'standard',
            'duration_days' => 365,
            'business_unit_id' => $this->bu->id,
            'is_active' => true,
        ]);

        // Create DeviceInspection
        $this->inspection = DeviceInspection::create([
            'second_product_variant_id' => $spv->id,
            'imei' => 'SN-WARRANTY-001',
            'inspected_by' => $this->user->id,
            'checklist_results' => [],
            'status' => 'PASSED',
        ]);

        // Create Warranty
        $this->warranty = Warranty::create([
            'serial_number' => 'SN-WARRANTY-001',
            'warranty_policy_id' => $this->policy->id,
            'device_inspection_id' => $this->inspection->id,
            'customer_user_id' => $this->user->id,
            'order_item_id' => $orderItem->id,
            'status' => 'ACTIVE',
            'activated_at' => now(),
            'expires_at' => now()->addDays(365),
        ]);
    }

    public function test_warranty_report_page_accessible_by_authenticated_user()
    {
        $response = $this->actingAs($this->user)->get(route('reporting.warranty'));
        $response->assertStatus(200);
    }

    public function test_warranty_report_filters_by_branch()
    {
        // Empty filter should list the warranty
        Livewire::actingAs($this->user)
            ->test(WarrantyReport::class)
            ->assertSet('branchId', '')
            ->assertViewHas('warranties', function ($warranties) {
                return $warranties->count() === 1;
            });

        // Filter by existing branch
        Livewire::actingAs($this->user)
            ->test(WarrantyReport::class)
            ->set('branchId', $this->branch->id)
            ->assertViewHas('warranties', function ($warranties) {
                return $warranties->count() === 1;
            });

        // Filter by non-existing branch
        $newBranch = Branch::create([
            'name' => 'Cabang Baru',
            'code' => 'CBB',
            'business_unit_id' => $this->bu->id,
        ]);
        Livewire::actingAs($this->user)
            ->test(WarrantyReport::class)
            ->set('branchId', $newBranch->id)
            ->assertViewHas('warranties', function ($warranties) {
                return $warranties->count() === 0;
            });
    }

    public function test_warranty_report_excel_export()
    {
        $response = Livewire::actingAs($this->user)
            ->test(WarrantyReport::class)
            ->call('exportExcel');

        $response->assertFileDownloaded();
    }

    public function test_warranty_report_csv_export()
    {
        $response = Livewire::actingAs($this->user)
            ->test(WarrantyReport::class)
            ->call('exportCsv');

        $response->assertFileDownloaded();
    }
}
