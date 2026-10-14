<?php

namespace Tests\Feature;

use App\Models\BusinessUnit;
use App\Models\EcommerceCoupon;
use App\Models\EcommerceCouponRedemption;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\ProductAccurate;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileCouponCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Warehouse $warehouse;
    protected ProductAccurate $product;
    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $phone = '0812' . rand(10000000, 99999999);
        $this->user = User::create([
            'name' => 'Mobile Customer Test',
            'email' => $phone . '@tokopon.internal',
            'password' => bcrypt('password123'),
        ]);

        UserProfile::create([
            'user_id' => $this->user->id,
            'full_name' => 'Mobile Customer Test',
            'phone_number' => $phone,
            'address' => 'Jl. Test No. 99',
        ]);

        $bu = BusinessUnit::create([
            'name' => 'Tokopon Online Store',
            'code' => 'TOS',
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Gudang Pusat Online',
            'code' => 'GPO-01',
            'business_unit_id' => $bu->id,
            'is_online_store' => true,
        ]);

        $this->product = ProductAccurate::create([
            'accurate_id' => 'ACC-PROD-99',
            'item_no' => 'ITEM-PROD-99',
            'name' => 'Headphone Bluetooth Bass Boost',
            'business_unit_id' => $bu->id,
            'base_price' => 150000,
            'stock' => 20,
        ]);

        WarehouseStock::create([
            'warehouse_id' => $this->warehouse->id,
            'variant_id' => $this->product->id,
            'variant_type' => ProductAccurate::class,
            'stock' => 20,
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'name' => 'BCA Virtual Account',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_owner' => 'Tokopon Online',
            'is_active' => true,
        ]);
    }

    public function test_api_available_coupons_endpoint(): void
    {
        EcommerceCoupon::create([
            'code' => 'DISKON20',
            'title' => 'Diskon Rp 20.000',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 20000,
            'min_purchase_amount' => 50000,
            'is_active' => true,
        ]);

        EcommerceCoupon::create([
            'code' => 'BIGBUY',
            'title' => 'Diskon Rp 50.000',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 50000,
            'min_purchase_amount' => 300000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('api.mobile.coupons.available', [
                'subtotal' => 150000,
                'shipping_cost' => 15000,
            ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar voucher berhasil dimuat.',
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        // Kupon 1 eligible
        $this->assertEquals('DISKON20', $data[0]['code']);
        $this->assertTrue($data[0]['is_eligible']);
        $this->assertEquals(20000, $data[0]['potential_discount']);

        // Kupon 2 ineligible (min belanja 300k)
        $this->assertEquals('BIGBUY', $data[1]['code']);
        $this->assertFalse($data[1]['is_eligible']);
    }

    public function test_api_validate_coupon_endpoint_success_and_fail(): void
    {
        EcommerceCoupon::create([
            'code' => 'HEMAT10',
            'title' => 'Potongan 10rb',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 10000,
            'min_purchase_amount' => 50000,
            'is_active' => true,
        ]);

        // 1. Success validate
        $validRes = $this->actingAs($this->user)
            ->postJson(route('api.mobile.coupons.validate'), [
                'code' => 'HEMAT10',
                'subtotal' => 100000,
                'shipping_cost' => 10000,
            ]);

        $validRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'code' => 'HEMAT10',
                    'discount_amount' => 10000,
                    'discount_type' => 'PRODUCT',
                ]
            ]);

        // 2. Fail validate (min belanja tidak cukup)
        $failRes = $this->actingAs($this->user)
            ->postJson(route('api.mobile.coupons.validate'), [
                'code' => 'HEMAT10',
                'subtotal' => 30000,
            ]);

        $failRes->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_checkout_with_coupon_applies_discount_and_updates_order(): void
    {
        $coupon = EcommerceCoupon::create([
            'code' => 'POTONG25',
            'title' => 'Diskon 25k',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 25000,
            'min_purchase_amount' => 100000,
            'usage_limit_total' => 5,
            'usage_limit_per_user' => 1,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $payload = [
            'items' => [
                [
                    'product_accurate_id' => $this->product->id,
                    'qty' => 1,
                ]
            ],
            'warehouse_id' => $this->warehouse->id,
            'payment_method_id' => $this->paymentMethod->id,
            'shipping_cost' => 15000,
            'coupon_code' => 'POTONG25',
            'shipping_address' => [
                'name' => $this->user->name,
                'phone' => '08123456789',
                'address' => 'Jl. Pengujian No. 12',
                'city' => 'Jakarta Barat',
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('api.mobile.orders.checkout'), $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'order_status' => 'WAITING_PAYMENT',
                    'coupon_code' => 'POTONG25',
                    'coupon_discount_amount' => 25000,
                    'grand_total' => 140000, // 150.000 + 15.000 (ongkir) - 25.000 (kupon) = 140.000
                ]
            ]);

        $orderNumber = $response->json('data.order_number');
        $order = Order::where('order_number', $orderNumber)->first();

        $this->assertNotNull($order);
        $this->assertEquals($coupon->id, $order->coupon_id);
        $this->assertEquals('POTONG25', $order->coupon_code);
        $this->assertEquals(25000, (float) $order->coupon_discount_amount);
        $this->assertEquals(140000, (float) $order->grand_total);

        // Pastikan kupon used_count bertambah 1
        $coupon->refresh();
        $this->assertEquals(1, $coupon->used_count);

        // Pastikan catatan redemption tercipta
        $this->assertDatabaseHas('ecommerce_coupon_redemptions', [
            'coupon_id' => $coupon->id,
            'user_id' => $this->user->id,
            'order_id' => $order->id,
            'discount_applied' => 25000,
        ]);
    }

    public function test_cancel_order_rolls_back_coupon_and_redemption(): void
    {
        $coupon = EcommerceCoupon::create([
            'code' => 'CANCELTEST',
            'title' => 'Diskon 30k',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 30000,
            'usage_limit_total' => 2,
            'used_count' => 0,
            'is_active' => true,
        ]);

        // Checkout order
        $payload = [
            'items' => [
                [
                    'product_accurate_id' => $this->product->id,
                    'qty' => 1,
                ]
            ],
            'warehouse_id' => $this->warehouse->id,
            'payment_method_id' => $this->paymentMethod->id,
            'shipping_cost' => 10000,
            'coupon_code' => 'CANCELTEST',
            'shipping_address' => [
                'name' => $this->user->name,
                'phone' => '08123456789',
                'address' => 'Jl. Pengujian No. 12',
                'city' => 'Jakarta Barat',
            ],
        ];

        $checkoutRes = $this->actingAs($this->user)
            ->postJson(route('api.mobile.orders.checkout'), $payload);

        $checkoutRes->assertStatus(201);
        $orderNumber = $checkoutRes->json('data.order_number');

        $coupon->refresh();
        $this->assertEquals(1, $coupon->used_count);

        // Batalkan pesanan via API Cancel Order
        $cancelRes = $this->actingAs($this->user)
            ->postJson(route('api.mobile.orders.cancel', $orderNumber));

        $cancelRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'order_number' => $orderNumber,
                    'order_status' => 'CANCELLED',
                ]
            ]);

        // Kuota kupon harus kembali ke 0
        $coupon->refresh();
        $this->assertEquals(0, $coupon->used_count, 'Used count kupon harus kembali ke 0 setelah order dibatalkan.');

        // Redemption record harus dihapus
        $order = Order::where('order_number', $orderNumber)->first();
        $this->assertDatabaseMissing('ecommerce_coupon_redemptions', [
            'order_id' => $order->id,
        ]);
    }
}
