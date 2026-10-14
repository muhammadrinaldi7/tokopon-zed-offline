<?php

namespace Tests\Unit;

use App\Models\BusinessUnit;
use App\Models\EcommerceCoupon;
use App\Models\EcommerceCouponRedemption;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcommerceCouponModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_coupon_and_verify_casts(): void
    {
        $coupon = EcommerceCoupon::create([
            'code' => 'DISC10',
            'title' => 'Diskon 10%',
            'type' => EcommerceCoupon::TYPE_PRODUCT_PERCENT,
            'discount_value' => 10,
            'max_discount_amount' => 50000,
            'min_purchase_amount' => 100000,
            'usage_limit_total' => 100,
            'usage_limit_per_user' => 1,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('ecommerce_coupons', ['code' => 'DISC10']);
        $this->assertEquals('10.00', $coupon->discount_value);
        $this->assertTrue($coupon->is_active);
        $this->assertTrue($coupon->isProductDiscount());
        $this->assertFalse($coupon->isShippingDiscount());
    }

    public function test_calculate_discount_product_percent_with_cap(): void
    {
        $coupon = EcommerceCoupon::create([
            'code' => 'HEBAT20',
            'title' => 'Diskon 20% Max 30rb',
            'type' => EcommerceCoupon::TYPE_PRODUCT_PERCENT,
            'discount_value' => 20,
            'max_discount_amount' => 30000,
            'min_purchase_amount' => 50000,
        ]);

        // Belanja 100.000 -> 20% = 20.000 (< 30.000)
        $this->assertEquals(20000, $coupon->calculateDiscount(100000));

        // Belanja 200.000 -> 20% = 40.000 -> Terpotong Max Cap 30.000
        $this->assertEquals(30000, $coupon->calculateDiscount(200000));
    }

    public function test_calculate_discount_product_fixed(): void
    {
        $coupon = EcommerceCoupon::create([
            'code' => 'POTONG25',
            'title' => 'Potongan 25rb',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 25000,
        ]);

        $this->assertEquals(25000, $coupon->calculateDiscount(100000));
        // Subtotal lebih kecil dari kupon -> tidak boleh melebihi subtotal
        $this->assertEquals(20000, $coupon->calculateDiscount(20000));
    }

    public function test_calculate_discount_shipping(): void
    {
        $couponFixed = EcommerceCoupon::create([
            'code' => 'GRATISONGKIR',
            'title' => 'Gratis Ongkir 15rb',
            'type' => EcommerceCoupon::TYPE_SHIPPING_FIXED,
            'discount_value' => 15000,
        ]);

        // Ongkir 20.000 -> diskon 15.000
        $this->assertEquals(15000, $couponFixed->calculateDiscount(100000, 20000));
        // Ongkir hanya 10.000 -> diskon maksimal sebesar ongkir aktual (10.000)
        $this->assertEquals(10000, $couponFixed->calculateDiscount(100000, 10000));

        $couponPercent = EcommerceCoupon::create([
            'code' => 'ONGKIR50',
            'title' => 'Diskon Ongkir 50%',
            'type' => EcommerceCoupon::TYPE_SHIPPING_PERCENT,
            'discount_value' => 50,
            'max_discount_amount' => 10000,
        ]);

        // Ongkir 30.000 -> 50% = 15.000 -> capped at 10.000
        $this->assertEquals(10000, $couponPercent->calculateDiscount(100000, 30000));
    }

    public function test_can_be_used_by_user_with_limits(): void
    {
        $user = User::factory()->create();

        $coupon = EcommerceCoupon::create([
            'code' => 'LIMIT1',
            'title' => 'Kupon 1x Pakai',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 10000,
            'usage_limit_total' => 2,
            'usage_limit_per_user' => 1,
            'used_count' => 0,
            'is_active' => true,
        ]);

        $this->assertTrue($coupon->canBeUsedByUser($user->id));

        // Simulasikan sudah pernah dipakai oleh user
        EcommerceCouponRedemption::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'discount_applied' => 10000,
            'discount_type' => 'PRODUCT',
        ]);

        $this->assertFalse($coupon->canBeUsedByUser($user->id), 'User tidak boleh pakai lagi jika sudah capai limit');

        // User lain masih bisa pakai
        $otherUser = User::factory()->create();
        $this->assertTrue($coupon->canBeUsedByUser($otherUser->id));

        // Jika kuota global habis
        $coupon->update(['used_count' => 2]);
        $this->assertFalse($coupon->canBeUsedByUser($otherUser->id), 'Tidak boleh dipakai jika kuota global habis');
    }

    public function test_model_relationships(): void
    {
        $user = User::factory()->create();
        $coupon = EcommerceCoupon::create([
            'code' => 'RELTEST',
            'title' => 'Test Relasi',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 10000,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-COUPON-' . rand(1000, 9999),
            'order_date' => now()->format('Y-m-d'),
            'total_amount' => 100000,
            'grand_total' => 90000,
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'coupon_discount_amount' => 10000,
            'order_status' => 'WAITING_PAYMENT',
            'shipping_address_snapshot' => ['address' => 'Jl. Test No. 123', 'city' => 'Jakarta'],
        ]);

        $redemption = EcommerceCouponRedemption::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'discount_applied' => 10000,
            'discount_type' => 'PRODUCT',
        ]);

        // Test relations
        $this->assertEquals($coupon->id, $order->coupon->id);
        $this->assertEquals($redemption->id, $order->couponRedemption->id);
        $this->assertTrue($user->couponRedemptions()->where('ecommerce_coupon_redemptions.id', $redemption->id)->exists());
        $this->assertTrue($coupon->redemptions()->where('id', $redemption->id)->exists());
        $this->assertTrue($coupon->orders()->where('id', $order->id)->exists());
    }
}
