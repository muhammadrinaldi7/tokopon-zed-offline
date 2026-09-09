<?php

namespace Tests\Feature;

use App\Livewire\Zoffline\Reporting\ProjectSalesReport;
use App\Models\BusinessUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductAccurate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProjectSalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $bu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bu = BusinessUnit::create([
            'name' => 'Tokopon Utama',
            'code' => 'TKP',
            'is_active' => true,
        ]);

        $role = Role::create(['name' => 'admin']);
        $permission = Permission::create(['name' => 'view-reporting']);
        $role->givePermissionTo($permission);

        $this->user = User::factory()->create([
            'email' => 'manager@tokopon.com',
            'business_unit_id' => $this->bu->id,
        ]);
        $this->user->assignRole($role);
    }

    public function test_can_render_project_sales_report_page()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('reporting.sales-project'));
        $response->assertStatus(200);
        $response->assertSee('Laporan Penjualan Per Proyek');
        $response->assertSee('Matriks Penjualan Harian');
    }

    public function test_project_sales_matrix_data_aggregation()
    {
        $this->actingAs($this->user);

        $prod = Product::create([
            'name' => 'iPhone 15 Pro',
            'slug' => 'iphone-15-pro',
            'is_active' => true,
        ]);

        $accurate1 = ProductAccurate::create([
            'product_id' => $prod->id,
            'item_no' => 'IPH15-RESMI',
            'name' => 'iPhone 15 Pro Resmi',
            'proyek' => 'RESMI',
            'base_price' => 15000000,
            'base_cost' => 13000000,
            'business_unit_id' => $this->bu->id,
        ]);

        $accurate2 = ProductAccurate::create([
            'product_id' => $prod->id,
            'item_no' => 'IPH15-INTER',
            'name' => 'iPhone 15 Pro Inter',
            'proyek' => 'INTER',
            'base_price' => 12000000,
            'base_cost' => 10000000,
            'business_unit_id' => $this->bu->id,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-001',
            'accurate_invoice_no' => 'INV-TEST-001',
            'order_date' => now()->startOfMonth(),
            'business_unit_id' => $this->bu->id,
            'user_id' => $this->user->id,
            'total_amount' => 27000000,
            'grand_total' => 27000000,
            'order_status' => 'COMPLETED',
            'shipping_address_snapshot' => ['store' => 'Cabang Pusat'],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_type' => ProductAccurate::class,
            'product_variant_id' => $accurate1->id,
            'product_name' => 'iPhone 15 Pro Resmi',
            'qty' => 1,
            'price_at_checkout' => 15000000,
            'discount_amount' => 0,
            'subtotal' => 15000000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_type' => ProductAccurate::class,
            'product_variant_id' => $accurate2->id,
            'product_name' => 'iPhone 15 Pro Inter',
            'qty' => 1,
            'price_at_checkout' => 12000000,
            'discount_amount' => 0,
            'subtotal' => 12000000,
        ]);

        Livewire::test(ProjectSalesReport::class)
            ->set('dateRange', 'this_month')
            ->assertSee('RESMI')
            ->assertSee('INTER')
            ->assertSee('15.000.000')
            ->assertSee('12.000.000')
            ->assertSee('27.000.000')
            ->set('valueMode', 'qty')
            ->assertSee('Unit (Qty)');
    }
}
