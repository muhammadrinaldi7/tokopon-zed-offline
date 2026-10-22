<?php

namespace Tests\Feature;

use App\Models\BusinessUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductAccurate;
use App\Models\SellPhone;
use App\Models\User;
use App\Services\ExecutiveMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExecutiveMetricsHarmonizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $bu;
    protected ExecutiveMetricsService $metricsService;

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

        $this->metricsService = app(ExecutiveMetricsService::class);
    }

    public function test_prioritizes_order_item_total_hpp_snapshot()
    {
        $itemAccurate = ProductAccurate::create([
            'accurate_id' => 2001,
            'item_no' => 'ACC-01',
            'name' => 'Charger 20W',
            'base_price' => 300000,
            'base_cost' => 150000,
            'buy_price' => 120000,
            'business_unit_id' => $this->bu->id,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-HPP-001',
            'order_date' => now(),
            'business_unit_id' => $this->bu->id,
            'user_id' => $this->user->id,
            'total_amount' => 300000,
            'grand_total' => 300000,
            'order_status' => 'COMPLETED',
            'shipping_address_snapshot' => ['store' => 'Cabang Pusat'],
        ]);

        // Item has explicit snapshot total_hpp = 185000 (different from base_cost 150000)
        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_type' => ProductAccurate::class,
            'product_variant_id' => $itemAccurate->id,
            'product_name' => $itemAccurate->name,
            'qty' => 1,
            'price_at_checkout' => 300000,
            'discount_amount' => 0,
            'subtotal' => 300000,
            'total_hpp' => 185000,
        ]);

        $orders = collect([$order->load(['items', 'payments'])]);
        $aggregates = $this->metricsService->computeAggregates($orders);

        // HPP should strictly be 185000 (from snapshot total_hpp), NOT 150000
        $this->assertEquals(185000, $aggregates['total_hpp']);
        $this->assertEquals(300000, $aggregates['net_sales']);
        $this->assertEquals(115000, $aggregates['gross_profit']);
    }

    public function test_resolves_second_handphone_hpp_from_sellphone_appraised_value()
    {
        $imei = '358999112233445';

        // Customer sold this phone to store via SellPhone buyback
        SellPhone::create([
            'user_id' => $this->user->id,
            'imei' => $imei,
            'business_unit_id' => $this->bu->id,
            'status' => 'COMPLETED',
            'appraised_value' => 8500000,
            'phone_brand' => 'Apple',
            'phone_model' => 'iPhone 13 128GB',
        ]);

        $itemSecond = ProductAccurate::create([
            'accurate_id' => 2002,
            'item_no' => 'IPH13-SEC',
            'name' => 'iPhone 13 128GB Second',
            'base_price' => 9500000,
            'base_cost' => 0, // master cost is 0
            'buy_price' => 0,
            'business_unit_id' => $this->bu->id,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-SEC-001',
            'order_date' => now(),
            'business_unit_id' => $this->bu->id,
            'user_id' => $this->user->id,
            'total_amount' => 9500000,
            'grand_total' => 9500000,
            'order_status' => 'COMPLETED',
            'shipping_address_snapshot' => ['store' => 'Cabang Pusat'],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_type' => ProductAccurate::class,
            'product_variant_id' => $itemSecond->id,
            'product_name' => $itemSecond->name,
            'serial_number' => $imei,
            'qty' => 1,
            'price_at_checkout' => 9500000,
            'discount_amount' => 0,
            'subtotal' => 9500000,
        ]);

        $orders = collect([$order->load(['items', 'payments'])]);
        $aggregates = $this->metricsService->computeAggregates($orders);

        // HPP must resolve to 8500000 from SellPhone appraised_value
        $this->assertEquals(8500000, $aggregates['total_hpp']);
        $this->assertEquals(9500000, $aggregates['net_sales']);
        $this->assertEquals(1000000, $aggregates['gross_profit']);
    }

    public function test_falls_back_to_buy_price_when_base_cost_is_zero()
    {
        $itemAccurate = ProductAccurate::create([
            'accurate_id' => 2003,
            'item_no' => 'CASE-01',
            'name' => 'Clear Case',
            'base_price' => 100000,
            'base_cost' => 0, // 0 in base_cost
            'buy_price' => 45000, // available in buy_price
            'business_unit_id' => $this->bu->id,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-BUYP-001',
            'order_date' => now(),
            'business_unit_id' => $this->bu->id,
            'user_id' => $this->user->id,
            'total_amount' => 200000,
            'grand_total' => 200000,
            'order_status' => 'COMPLETED',
            'shipping_address_snapshot' => ['store' => 'Cabang Pusat'],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_type' => ProductAccurate::class,
            'product_variant_id' => $itemAccurate->id,
            'product_name' => $itemAccurate->name,
            'qty' => 2,
            'price_at_checkout' => 100000,
            'discount_amount' => 0,
            'subtotal' => 200000,
        ]);

        $orders = collect([$order->load(['items', 'payments'])]);
        $aggregates = $this->metricsService->computeAggregates($orders);

        // HPP must fall back to 45000 * 2 = 90000
        $this->assertEquals(90000, $aggregates['total_hpp']);
        $this->assertEquals(200000, $aggregates['net_sales']);
        $this->assertEquals(110000, $aggregates['gross_profit']);
    }

    public function test_harmonizes_net_sales_with_discounts_and_returns()
    {
        $item = ProductAccurate::create([
            'accurate_id' => 2004,
            'item_no' => 'ACC-RET-01',
            'name' => 'Speaker Bluetooth',
            'base_price' => 500000,
            'base_cost' => 300000,
            'business_unit_id' => $this->bu->id,
        ]);

        // Return order with negative quantity
        $returnOrder = Order::create([
            'order_number' => 'RET-ORD-001',
            'order_date' => now(),
            'business_unit_id' => $this->bu->id,
            'user_id' => $this->user->id,
            'total_amount' => -500000,
            'grand_total' => -500000,
            'order_status' => 'COMPLETED',
            'shipping_address_snapshot' => ['store' => 'Cabang Pusat'],
        ]);

        OrderItem::create([
            'order_id' => $returnOrder->id,
            'product_variant_type' => ProductAccurate::class,
            'product_variant_id' => $item->id,
            'product_name' => $item->name,
            'qty' => -1,
            'price_at_checkout' => 500000,
            'discount_amount' => 0,
            'subtotal' => -500000,
        ]);

        $orders = collect([$returnOrder->load(['items', 'payments'])]);
        $aggregates = $this->metricsService->computeAggregates($orders);

        $this->assertEquals(-500000, $aggregates['net_sales']);
        $this->assertEquals(-300000, $aggregates['total_hpp']);
        $this->assertEquals(-200000, $aggregates['gross_profit']);
    }
}
