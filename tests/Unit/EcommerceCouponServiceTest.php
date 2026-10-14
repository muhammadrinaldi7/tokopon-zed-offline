<?php

namespace Tests\Unit;

use App\Models\EcommerceCoupon;
use App\Models\EcommerceCouponRedemption;
use App\Models\Order;
use App\Models\User;
use App\Services\EcommerceCouponService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcommerceCouponServiceTest extends TestCase
{
    use RefreshDatabase;

    protected EcommerceCouponService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EcommerceCouponService();
    }

    public function test_get_available_coupons_calculates_eligibility_and_sorts(): void
    {
        $user = User::factory()->create();

        // Kupon 1: Memenuhi syarat (Diskon Rp 20.000)
        $coupon1 = EcommerceCoupon::create([
            'code' => 'HEMAT20',
            'title' => 'Diskon 20k',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 20000,
            'min_purchase_amount' => 50000,
            'is_active' => true,
        ]);

        // Kupon 2: Min belanja belum terpenuhi (Min 200k)
        $coupon2 = EcommerceCoupon::create([
            'code' => 'BIGBUY',
            'title' => 'Diskon 50k',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 50000,
            'min_purchase_amount' => 200000,
            'is_active' => true,
        ]);

        // Kupon 3: Inaktif (tidak boleh muncul di list)
        EcommerceCoupon::create([
            'code' => 'INACTIVE',
            'title' => 'Diskon Inaktif',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 10000,
            'is_active' => false,
        ]);

        $subtotal = 100000;
        $shippingCost = 15000;

        $available = $this->service->getAvailableCoupons($user, $subtotal, $shippingCost);

        $this->assertCount(2, $available);

        $first = $available->first();
        $this->assertEquals('HEMAT20', $first->code);
        $this->assertTrue($first->is_eligible);
        $this->assertEquals(20000, $first->potential_discount);

        $second = $available->last();
        $this->assertEquals('BIGBUY', $second->code);
        $this->assertFalse($second->is_eligible);
        $this->assertStringContainsString('Minimum belanja', $second->ineligibility_reason);
    }

    public function test_validate_coupon_various_cases(): void
    {
        $user = User::factory()->create();

        // 1. Kosong
        $res = $this->service->validateCoupon('', $user, 100000);
        $this->assertFalse($res['valid']);

        // 2. Tidak ditemukan
        $res = $this->service->validateCoupon('NONEXISTENT', $user, 100000);
        $this->assertFalse($res['valid']);
        $this->assertStringContainsString('tidak ditemukan', $res['message']);

        // 3. Inaktif
        $inactive = EcommerceCoupon::create([
            'code' => 'OFF10',
            'title' => 'Off',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 10000,
            'is_active' => false,
        ]);
        $res = $this->service->validateCoupon('OFF10', $user, 100000);
        $this->assertFalse($res['valid']);
        $this->assertStringContainsString('tidak aktif', $res['message']);

        // 4. Kuota habis
        $maxed = EcommerceCoupon::create([
            'code' => 'MAXED',
            'title' => 'Maxed out',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 10000,
            'usage_limit_total' => 1,
            'used_count' => 1,
            'is_active' => true,
        ]);
        $res = $this->service->validateCoupon('MAXED', $user, 100000);
        $this->assertFalse($res['valid']);
        $this->assertStringContainsString('Kuota', $res['message']);

        // 5. Min belanja belum cukup
        $minCoupon = EcommerceCoupon::create([
            'code' => 'MIN150',
            'title' => 'Min 150k',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 10000,
            'min_purchase_amount' => 150000,
            'is_active' => true,
        ]);
        $res = $this->service->validateCoupon('MIN150', $user, 100000);
        $this->assertFalse($res['valid']);
        $this->assertStringContainsStringIgnoringCase('minimum', $res['message']);

        // 6. Valid diskon produk persen dengan cap
        $percentCoupon = EcommerceCoupon::create([
            'code' => 'DISKON10',
            'title' => 'Diskon 10% max 15k',
            'type' => EcommerceCoupon::TYPE_PRODUCT_PERCENT,
            'discount_value' => 10,
            'max_discount_amount' => 15000,
            'is_active' => true,
        ]);
        $res = $this->service->validateCoupon('DISKON10', $user, 200000);
        $this->assertTrue($res['valid']);
        $this->assertEquals(15000, $res['discount']);
        $this->assertEquals('PRODUCT', $res['discount_type']);

        // 7. Diskon ongkir
        $shippingCoupon = EcommerceCoupon::create([
            'code' => 'FREEONGKIR',
            'title' => 'Potongan Ongkir 10k',
            'type' => EcommerceCoupon::TYPE_SHIPPING_FIXED,
            'discount_value' => 10000,
            'is_active' => true,
        ]);
        // Ongkir 0 -> gagal
        $res0 = $this->service->validateCoupon('FREEONGKIR', $user, 100000, 0);
        $this->assertFalse($res0['valid']);

        // Ongkir 20.000 -> diskon 10.000
        $resValid = $this->service->validateCoupon('FREEONGKIR', $user, 100000, 20000);
        $this->assertTrue($resValid['valid']);
        $this->assertEquals(10000, $resValid['discount']);
        $this->assertEquals('SHIPPING', $resValid['discount_type']);
    }

    public function test_apply_coupon_to_order_and_rollback(): void
    {
        $user = User::factory()->create();

        $coupon = EcommerceCoupon::create([
            'code' => 'APPLYTEST',
            'title' => 'Test Apply',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 25000,
            'usage_limit_total' => 2,
            'usage_limit_per_user' => 1,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-TEST-APPLY-1',
            'order_date' => now()->format('Y-m-d'),
            'total_amount' => 100000,
            'grand_total' => 75000,
            'order_status' => 'WAITING_PAYMENT',
            'shipping_address_snapshot' => ['address' => 'Jl. Merdeka No. 45'],
        ]);

        // 1. Terapkan kupon ke Order
        $redemption = $this->service->applyCouponToOrder($coupon, $order, $user, 25000);

        $this->assertNotNull($redemption);
        $this->assertEquals(25000, $redemption->discount_applied);
        $this->assertEquals($coupon->id, $redemption->coupon_id);

        // Cek coupon used_count bertambah
        $coupon->refresh();
        $this->assertEquals(1, $coupon->used_count);

        // Cek order ter-update
        $order->refresh();
        $this->assertEquals($coupon->id, $order->coupon_id);
        $this->assertEquals('APPLYTEST', $order->coupon_code);
        $this->assertEquals(25000, (float) $order->coupon_discount_amount);

        // User tidak boleh apply lagi karena limit per user = 1
        $this->expectException(Exception::class);
        $order2 = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-TEST-APPLY-2',
            'order_date' => now()->format('Y-m-d'),
            'total_amount' => 100000,
            'grand_total' => 75000,
            'order_status' => 'WAITING_PAYMENT',
            'shipping_address_snapshot' => ['address' => 'Jl. Merdeka No. 45'],
        ]);
        $this->service->applyCouponToOrder($coupon, $order2, $user, 25000);
    }

    public function test_rollback_coupon_success(): void
    {
        $user = User::factory()->create();

        $coupon = EcommerceCoupon::create([
            'code' => 'ROLLBACK1',
            'title' => 'Test Rollback',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 20000,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-ROLLBACK-1',
            'order_date' => now()->format('Y-m-d'),
            'total_amount' => 100000,
            'grand_total' => 80000,
            'order_status' => 'WAITING_PAYMENT',
            'shipping_address_snapshot' => ['address' => 'Jl. Pahlawan No. 10'],
        ]);

        // Apply
        $this->service->applyCouponToOrder($coupon, $order, $user, 20000);
        $coupon->refresh();
        $this->assertEquals(1, $coupon->used_count);
        $this->assertDatabaseHas('ecommerce_coupon_redemptions', ['order_id' => $order->id]);

        // Rollback
        $result = $this->service->rollbackCoupon($order);
        $this->assertTrue($result);

        $coupon->refresh();
        $this->assertEquals(0, $coupon->used_count, 'Used count kupon harus berkurang kembali ke 0');
        $this->assertDatabaseMissing('ecommerce_coupon_redemptions', ['order_id' => $order->id]);

        // Rollback kedua kali harus return false secara aman tanpa error
        $order->refresh();
        $resultSecond = $this->service->rollbackCoupon($order);
        $this->assertFalse($resultSecond);
    }
}
