<?php

namespace Tests\Feature;

use App\Livewire\Zoffline\Ecommerce\VoucherManager;
use App\Models\EcommerceCoupon;
use App\Models\EcommerceCouponRedemption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VoucherManagerLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_can_render_voucher_manager_page(): void
    {
        EcommerceCoupon::create([
            'code' => 'TESTCODE',
            'title' => 'Voucher Uji Coba',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 15000,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(VoucherManager::class)
            ->assertStatus(200)
            ->assertSee('TESTCODE')
            ->assertSee('Voucher Uji Coba')
            ->assertSee('Manajemen Kupon & Voucher E-Commerce');
    }

    public function test_can_create_a_new_voucher(): void
    {
        Livewire::actingAs($this->user)
            ->test(VoucherManager::class)
            ->call('openCreateModal')
            ->set('code', 'DISKONNEW')
            ->set('title', 'Promo Diskon Baru')
            ->set('description', 'Diskon belanja baru 20k')
            ->set('type', EcommerceCoupon::TYPE_PRODUCT_FIXED)
            ->set('discount_value', 20000)
            ->set('min_purchase_amount', 100000)
            ->set('usage_limit_total', 50)
            ->set('usage_limit_per_user', 1)
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertDatabaseHas('ecommerce_coupons', [
            'code' => 'DISKONNEW',
            'title' => 'Promo Diskon Baru',
            'discount_value' => 20000,
            'usage_limit_total' => 50,
        ]);
    }

    public function test_validates_unique_coupon_code(): void
    {
        EcommerceCoupon::create([
            'code' => 'EXISTINGCODE',
            'title' => 'Sudah Ada',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 10000,
        ]);

        Livewire::actingAs($this->user)
            ->test(VoucherManager::class)
            ->call('openCreateModal')
            ->set('code', 'EXISTINGCODE')
            ->set('title', 'Duplikat')
            ->set('type', EcommerceCoupon::TYPE_PRODUCT_FIXED)
            ->set('discount_value', 10000)
            ->call('save')
            ->assertHasErrors(['code' => 'unique']);
    }

    public function test_can_toggle_coupon_active_status(): void
    {
        $coupon = EcommerceCoupon::create([
            'code' => 'TOGGLEME',
            'title' => 'Toggle Status',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 10000,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(VoucherManager::class)
            ->call('toggleActive', $coupon->id)
            ->assertDispatched('toast');

        $coupon->refresh();
        $this->assertFalse($coupon->is_active);
    }

    public function test_cannot_delete_coupon_with_usage_history(): void
    {
        $coupon = EcommerceCoupon::create([
            'code' => 'HISTORY',
            'title' => 'Kupon Bersejarah',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 10000,
            'used_count' => 1,
            'is_active' => true,
        ]);

        EcommerceCouponRedemption::create([
            'coupon_id' => $coupon->id,
            'user_id' => $this->user->id,
            'discount_applied' => 10000,
            'discount_type' => 'PRODUCT',
        ]);

        Livewire::actingAs($this->user)
            ->test(VoucherManager::class)
            ->call('delete', $coupon->id)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('ecommerce_coupons', ['id' => $coupon->id]);
    }
}
