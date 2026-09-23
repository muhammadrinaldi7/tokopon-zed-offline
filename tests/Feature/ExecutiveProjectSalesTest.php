<?php

namespace Tests\Feature;

use App\Models\BusinessUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductAccurate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExecutiveProjectSalesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $bu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bu = BusinessUnit::create([
            'name' => 'Tokopon Gadget Center',
            'code' => 'TGC',
            'is_active' => true,
        ]);

        $role = Role::create(['name' => 'director']);

        $this->user = User::factory()->create([
            'name' => 'Executive Director',
            'email' => 'director@tokopon.com',
            'business_unit_id' => $this->bu->id,
        ]);
        $this->user->assignRole($role);
    }

    public function test_can_fetch_project_sales_matrix_and_breakdown()
    {
        Sanctum::actingAs($this->user);

        $category = \App\Models\Category::create([
            'name' => 'Smartphone',
            'slug' => 'smartphone',
        ]);

        $prod = Product::create([
            'category_id' => $category->id,
            'name' => 'iPhone 15 Pro Max',
            'slug' => 'iphone-15-pro-max',
            'is_active' => true,
        ]);

        $itemResmi = ProductAccurate::create([
            'accurate_id' => 1001,
            'product_id' => $prod->id,
            'item_no' => 'IPH15-RESMI-01',
            'name' => 'iPhone 15 Pro Max Resmi 256GB',
            'proyek' => 'RESMI',
            'base_price' => 20000000,
            'base_cost' => 18000000,
            'business_unit_id' => $this->bu->id,
        ]);

        $itemInter = ProductAccurate::create([
            'accurate_id' => 1002,
            'product_id' => $prod->id,
            'item_no' => 'IPH15-INTER-01',
            'name' => 'iPhone 15 Pro Max Inter 256GB',
            'proyek' => 'INTER',
            'base_price' => 16000000,
            'base_cost' => 14000000,
            'business_unit_id' => $this->bu->id,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-EXEC-001',
            'accurate_invoice_no' => 'INV-EXEC-001',
            'order_date' => now(),
            'business_unit_id' => $this->bu->id,
            'user_id' => $this->user->id,
            'total_amount' => 36000000,
            'grand_total' => 36000000,
            'order_status' => 'COMPLETED',
            'shipping_address_snapshot' => ['store' => 'Cabang Banjarbaru'],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_type' => ProductAccurate::class,
            'product_variant_id' => $itemResmi->id,
            'product_name' => $itemResmi->name,
            'qty' => 1,
            'price_at_checkout' => 20000000,
            'discount_amount' => 0,
            'subtotal' => 20000000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_type' => ProductAccurate::class,
            'product_variant_id' => $itemInter->id,
            'product_name' => $itemInter->name,
            'qty' => 1,
            'price_at_checkout' => 16000000,
            'discount_amount' => 0,
            'subtotal' => 16000000,
        ]);

        $response = $this->getJson(route('api.executive.project-sales', [
            'date_range' => 'today',
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'period' => ['range', 'start_date', 'end_date', 'total_days'],
                    'summary' => [
                        'total_net_sales',
                        'total_qty',
                        'total_hpp',
                        'gross_profit',
                        'profit_margin',
                        'total_projects_count',
                        'daily_average_sales',
                        'daily_average_qty',
                    ],
                    'project_breakdown',
                    'columns',
                    'dates',
                    'matrix',
                    'row_totals',
                    'column_totals',
                    'grand_total',
                    'available_projects',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(36000000, $data['summary']['total_net_sales']);
        $this->assertEquals(2, $data['summary']['total_qty']);
        $this->assertContains('RESMI', $data['columns']);
        $this->assertContains('INTER', $data['columns']);
    }

    public function test_can_fetch_project_sales_detail_drilldown()
    {
        Sanctum::actingAs($this->user);

        $category = \App\Models\Category::create([
            'name' => 'Smartphone 2',
            'slug' => 'smartphone-2',
        ]);

        $prod = Product::create([
            'category_id' => $category->id,
            'name' => 'iPhone 15 Pro Max',
            'slug' => 'iphone-15-pro-max',
            'is_active' => true,
        ]);

        $itemResmi = ProductAccurate::create([
            'accurate_id' => 1003,
            'product_id' => $prod->id,
            'item_no' => 'IPH15-RESMI-01',
            'name' => 'iPhone 15 Pro Max Resmi 256GB',
            'proyek' => 'RESMI',
            'base_price' => 20000000,
            'base_cost' => 18000000,
            'business_unit_id' => $this->bu->id,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-EXEC-002',
            'accurate_invoice_no' => 'INV-EXEC-002',
            'order_date' => now(),
            'business_unit_id' => $this->bu->id,
            'user_id' => $this->user->id,
            'total_amount' => 20000000,
            'grand_total' => 20000000,
            'order_status' => 'COMPLETED',
            'shipping_address_snapshot' => ['store' => 'Cabang Banjarbaru'],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_type' => ProductAccurate::class,
            'product_variant_id' => $itemResmi->id,
            'product_name' => $itemResmi->name,
            'qty' => 1,
            'price_at_checkout' => 20000000,
            'discount_amount' => 0,
            'subtotal' => 20000000,
        ]);

        $today = now()->format('Y-m-d');
        $response = $this->getJson(route('api.executive.project-sales.detail', [
            'date' => $today,
            'project' => 'RESMI',
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'order_number',
                        'invoice_no',
                        'time',
                        'customer_name',
                        'sales_name',
                        'branch',
                        'project',
                        'product_name',
                        'sku',
                        'qty',
                        'price',
                        'subtotal',
                    ],
                ],
            ]);

        $items = $response->json('data');
        $this->assertCount(1, $items);
        $this->assertEquals(20000000, $items[0]['subtotal']);
        $this->assertEquals('ORD-EXEC-002', $items[0]['order_number']);
        $this->assertEquals('RESMI', $items[0]['project']);
    }
}
