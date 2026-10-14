<?php

namespace Tests\Feature;

use App\Models\BusinessUnit;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\LoyaltyService;
use App\Services\MobileOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyOrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected LoyaltyAccount $account;
    protected LoyaltyService $loyaltyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loyaltyService = app(LoyaltyService::class);

        // Pastikan loyalty aktif
        app(\App\Services\SettingService::class)->set('loyalty_is_active', true);
        app(\App\Services\SettingService::class)->set('loyalty_earn_ratio', 10000); // 1 poin per Rp 10.000
        app(\App\Services\SettingService::class)->set('loyalty_point_value', 100);   // 1 poin = Rp 100
        app(\App\Services\SettingService::class)->set('loyalty_min_redeem_points', 10);

        $phone = '0812' . rand(10000000, 99999999);
        $this->user = User::create([
            'name' => 'Test Customer Loyalty',
            'email' => $phone . '@tokopon.internal',
            'password' => bcrypt('secret123'),
        ]);

        UserProfile::create([
            'user_id' => $this->user->id,
            'full_name' => 'Test Customer Loyalty',
            'phone_number' => $phone,
            'address' => 'Jl. Test No. 123',
        ]);

        $this->account = LoyaltyAccount::create([
            'phone_number' => $this->loyaltyService->normalizePhone($phone),
            'user_id' => $this->user->id,
            'customer_name' => 'Test Customer Loyalty',
            'points_balance' => 0,
            'lifetime_points' => 0,
            'total_spend' => 0,
            'tier' => 'BRONZE',
        ]);
    }

    public function test_earn_points_is_skipped_when_order_is_waiting_payment(): void
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-TEST-' . rand(1000, 9999),
            'order_date' => now()->format('Y-m-d'),
            'total_amount' => 100000,
            'grand_total' => 100000,
            'order_status' => 'WAITING_PAYMENT',
            'order_channel' => 'MOBILE_APP',
            'shipping_address_snapshot' => [
                'name' => $this->user->name,
                'phone' => $this->account->phone_number,
                'address' => 'Jl. Test',
            ],
        ]);

        $result = $this->loyaltyService->earnPoints($order);

        $this->assertNull($result, 'Poin tidak boleh diberikan saat pesanan masih WAITING_PAYMENT.');
        $this->account->refresh();
        $this->assertEquals(0, $this->account->points_balance);
        $this->assertDatabaseMissing('loyalty_transactions', [
            'order_id' => $order->id,
            'type' => 'EARN',
        ]);
    }

    public function test_earn_points_succeeds_when_order_is_completed(): void
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-COMP-' . rand(1000, 9999),
            'order_date' => now()->format('Y-m-d'),
            'total_amount' => 100000,
            'grand_total' => 100000,
            'order_status' => 'COMPLETED',
            'order_channel' => 'MOBILE_APP',
            'shipping_address_snapshot' => [
                'name' => $this->user->name,
                'phone' => $this->account->phone_number,
                'address' => 'Jl. Test',
            ],
        ]);

        $tx = $this->loyaltyService->earnPoints($order);

        $this->assertNotNull($tx);
        $this->assertEquals('EARN', $tx->type);
        $this->assertEquals(10, $tx->points); // 100.000 / 10.000 = 10 poin

        $this->account->refresh();
        $this->assertEquals(10, $this->account->points_balance);
        $this->assertEquals(10, $this->account->lifetime_points);

        $order->refresh();
        $this->assertEquals(10, $order->loyalty_points_earned);
    }

    public function test_cancel_order_refunds_redeemed_points(): void
    {
        // Beri saldo awal 100 poin
        $this->account->update(['points_balance' => 100]);

        $order = Order::create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-RED-' . rand(1000, 9999),
            'order_date' => now()->format('Y-m-d'),
            'total_amount' => 50000,
            'grand_total' => 45000,
            'order_status' => 'WAITING_PAYMENT',
            'order_channel' => 'MOBILE_APP',
            'shipping_address_snapshot' => [
                'name' => $this->user->name,
                'phone' => $this->account->phone_number,
                'address' => 'Jl. Test',
            ],
        ]);

        // Redeem 50 poin
        $this->loyaltyService->redeemPoints($order, 50, $this->user);

        $this->account->refresh();
        $this->assertEquals(50, $this->account->points_balance); // 100 - 50 = 50

        // Batalkan order
        $refundResults = $this->loyaltyService->refundPoints($order, $this->user);

        $this->assertEquals(50, $refundResults['refunded_redeem_points']);
        $this->account->refresh();
        $this->assertEquals(100, $this->account->points_balance, 'Saldo poin harus kembali 100.');

        $this->assertDatabaseHas('loyalty_transactions', [
            'order_id' => $order->id,
            'type' => 'REFUND',
            'points' => 50,
        ]);

        // Cek idempoten: panggil refundPoints lagi tidak boleh refund double
        $secondRefund = $this->loyaltyService->refundPoints($order, $this->user);
        $this->assertEquals(0, $secondRefund['refunded_redeem_points']);
        $this->account->refresh();
        $this->assertEquals(100, $this->account->points_balance);
    }

    public function test_cancel_order_revokes_earned_points(): void
    {
        $order = Order::create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-REV-' . rand(1000, 9999),
            'order_date' => now()->format('Y-m-d'),
            'total_amount' => 100000,
            'grand_total' => 100000,
            'order_status' => 'COMPLETED',
            'order_channel' => 'MOBILE_APP',
            'shipping_address_snapshot' => [
                'name' => $this->user->name,
                'phone' => $this->account->phone_number,
                'address' => 'Jl. Test',
            ],
        ]);

        // Earn 10 poin
        $this->loyaltyService->earnPoints($order);
        $this->account->refresh();
        $this->assertEquals(10, $this->account->points_balance);

        // Batalkan order
        $order->update(['order_status' => 'CANCELLED']);
        $refundResults = $this->loyaltyService->refundPoints($order, $this->user);

        $this->assertEquals(10, $refundResults['revoked_earn_points']);
        $this->account->refresh();
        $this->assertEquals(0, $this->account->points_balance, 'Saldo poin harus ditarik kembali menjadi 0.');

        $this->assertDatabaseHas('loyalty_transactions', [
            'order_id' => $order->id,
            'type' => 'REFUND_CANCEL',
            'points' => -10,
        ]);

        // Cek idempoten: panggil refundPoints lagi tidak boleh tarik minus lagi
        $secondRefund = $this->loyaltyService->refundPoints($order, $this->user);
        $this->assertEquals(0, $secondRefund['revoked_earn_points']);
        $this->account->refresh();
        $this->assertEquals(0, $this->account->points_balance);
    }

    public function test_mobile_cancel_endpoint_returns_proper_json_and_refunds_points(): void
    {
        $this->account->update(['points_balance' => 80]);

        $order = Order::create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-API-CANCEL-' . rand(1000, 9999),
            'order_date' => now()->format('Y-m-d'),
            'total_amount' => 50000,
            'grand_total' => 47000,
            'order_status' => 'WAITING_PAYMENT',
            'order_channel' => 'MOBILE_APP',
            'shipping_address_snapshot' => [
                'name' => $this->user->name,
                'phone' => $this->account->phone_number,
                'address' => 'Jl. Test',
            ],
        ]);

        // Redeem 30 poin
        $this->loyaltyService->redeemPoints($order, 30, $this->user);
        $this->account->refresh();
        $this->assertEquals(50, $this->account->points_balance);

        // Panggil endpoint cancel mobile
        $response = $this->actingAs($this->user)
            ->postJson(route('api.mobile.orders.cancel', $order->order_number));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'order_number' => $order->order_number,
                    'order_status' => 'CANCELLED',
                    'loyalty_points_refunded' => 30,
                    'loyalty_points_revoked' => 0,
                ]
            ]);

        $this->account->refresh();
        $this->assertEquals(80, $this->account->points_balance, 'Saldo poin harus kembali ke 80 setelah cancel via API.');
    }

    public function test_model_relationships_for_order_user_and_loyalty_transactions(): void
    {
        $this->account->update(['points_balance' => 50]);

        // Test helper attribute User -> loyalty_points
        $this->assertEquals(50, $this->user->loyalty_points);
        $this->assertInstanceOf(LoyaltyAccount::class, $this->user->loyaltyAccount);

        $order = Order::create([
            'user_id' => $this->user->id,
            'order_number' => 'ORD-REL-' . rand(1000, 9999),
            'order_date' => now()->format('Y-m-d'),
            'total_amount' => 50000,
            'grand_total' => 45000,
            'order_status' => 'COMPLETED',
            'order_channel' => 'MOBILE_APP',
            'shipping_address_snapshot' => [
                'name' => $this->user->name,
                'phone' => $this->account->phone_number,
                'address' => 'Jl. Test',
            ],
        ]);

        $tx = $this->loyaltyService->earnPoints($order);

        // Test Order -> loyaltyTransactions
        $this->assertTrue($order->loyaltyTransactions()->where('id', $tx->id)->exists());

        // Test LoyaltyTransaction -> order & account
        $this->assertEquals($order->id, $tx->order->id);
        $this->assertEquals($this->account->id, $tx->account->id);

        // Test User -> loyaltyTransactions (via hasManyThrough)
        $this->assertTrue($this->user->loyaltyTransactions()->where('loyalty_transactions.id', $tx->id)->exists());
    }
}
