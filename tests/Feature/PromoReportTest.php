<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BusinessUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Promo;
use App\Models\ProductAccurate;
use App\Livewire\Zoffline\Reporting\PromoReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PromoReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $bu;
    protected Order $order;
    protected Promo $promo;

    protected function setUp(): void
    {
        parent::setUp();

        // Create active business unit
        $this->bu = BusinessUnit::create([
            'name' => 'Test Business Unit',
            'code' => 'TBU',
            'is_active' => true,
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
            'name' => 'Product Test Promo',
            'accurate_id' => 'ACC-TEST-002',
            'brandName' => 'Apple',
        ]);

        // Create Order
        $this->order = Order::create([
            'order_number' => 'ORD-10001',
            'accurate_invoice_no' => 'INV-10001',
            'business_unit_id' => $this->bu->id,
            'user_id' => $this->user->id,
            'total_amount' => 1000000,
            'grand_total' => 1000000,
            'shipping_address_snapshot' => ['store' => 'Cabang Test'],
        ]);

        // Create OrderItem
        $orderItem = OrderItem::create([
            'order_id' => $this->order->id,
            'product_name' => 'Product Test Promo',
            'qty' => 1,
            'price_at_checkout' => 1000000,
            'subtotal' => 1000000,
            'product_variant_type' => ProductAccurate::class,
            'product_variant_id' => $product->id,
        ]);

        // Create Promo
        $this->promo = Promo::create([
            'name' => 'Promo Diskon Test',
            'code' => 'TESTPROMO',
            'discount_type' => 'fixed',
            'discount_value' => 50000,
        ]);

        // Associate Promo to Order & Item
        $this->order->promos()->attach($this->promo->id, ['discount_applied' => 50000]);
        $orderItem->promos()->attach($this->promo->id, [
            'discount_amount' => 50000,
            'serial_number' => 'SN-PROMO-001',
            'vendor_name' => 'Apple Inc',
        ]);
    }

    public function test_promo_report_page_accessible_by_authenticated_user()
    {
        $response = $this->actingAs($this->user)->get(route('reporting.promo'));
        $response->assertStatus(200);
    }

    public function test_promo_report_filters_by_brand()
    {
        // When brandFilter is empty, it should show Apple
        Livewire::actingAs($this->user)
            ->test(PromoReport::class)
            ->assertSet('brandFilter', '')
            ->assertViewHas('orders', function ($orders) {
                return $orders->count() === 1;
            });

        // Filter by Apple
        Livewire::actingAs($this->user)
            ->test(PromoReport::class)
            ->set('brandFilter', 'Apple')
            ->assertViewHas('orders', function ($orders) {
                return $orders->count() === 1 && $orders->first()->order_number === 'ORD-10001';
            });

        // Filter by Samsung (non-existent)
        Livewire::actingAs($this->user)
            ->test(PromoReport::class)
            ->set('brandFilter', 'Samsung')
            ->assertViewHas('orders', function ($orders) {
                return $orders->count() === 0;
            });
    }

    public function test_promo_report_excel_export()
    {
        $response = Livewire::actingAs($this->user)
            ->test(PromoReport::class)
            ->call('exportExcelClaim');

        $response->assertFileDownloaded();
    }

    public function test_promo_report_csv_export()
    {
        $response = Livewire::actingAs($this->user)
            ->test(PromoReport::class)
            ->call('exportCsvClaim');

        $response->assertFileDownloaded();
    }
}
