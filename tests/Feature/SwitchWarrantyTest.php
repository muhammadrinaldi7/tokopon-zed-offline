<?php

namespace Tests\Feature;

use App\Livewire\Admin\Approvals\Index as ApprovalsIndex;
use App\Livewire\Zoffline\Qc\ActivationList;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRule;
use App\Models\BusinessUnit;
use App\Models\DeviceInspection;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Warranty;
use App\Models\WarrantyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SwitchWarrantyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $bmUser;
    protected User $moUser;
    protected Role $bmRole;
    protected Role $moRole;
    protected BusinessUnit $businessUnit;
    protected \App\Models\ProductAccurate $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessUnit = BusinessUnit::firstOrCreate(
            ['code' => 'TEST-BU'],
            ['name' => 'Unit Bisnis Test', 'is_active' => true]
        );

        $this->product = \App\Models\ProductAccurate::create([
            'item_no'      => 'PROD-001',
            'name'         => 'iPhone 15 Pro Max',
            'accurate_id'  => 'ACC-TEST-001',
            'brandName'    => 'Apple',
            'categoryName' => 'Smartphone',
        ]);

        // Setup Roles and Permissions
        $permission = Permission::firstOrCreate(['name' => 'switch-warranty', 'guard_name' => 'web']);
        $this->bmRole = Role::firstOrCreate(['name' => 'bm', 'guard_name' => 'web']);
        $this->moRole = Role::firstOrCreate(['name' => 'manager_operasional', 'guard_name' => 'web']);
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        $this->bmRole->givePermissionTo($permission);
        $this->moRole->givePermissionTo($permission);

        // Create Users
        $this->user = User::factory()->create([
            'business_unit_id' => $this->businessUnit->id,
        ]);
        $this->user->givePermissionTo($permission);

        $this->bmUser = User::factory()->create([
            'name' => 'Branch Manager',
            'business_unit_id' => $this->businessUnit->id,
        ]);
        $this->bmUser->assignRole($this->bmRole);

        $this->moUser = User::factory()->create([
            'name' => 'Manager Operasional',
            'business_unit_id' => $this->businessUnit->id,
        ]);
        $this->moUser->assignRole($this->moRole);

        // Setup Global Approval Rules for SWITCH_WARRANTY
        // Level 1: BM
        ApprovalRule::updateOrCreate([
            'module'           => 'SWITCH_WARRANTY',
            'level'            => 1,
            'business_unit_id' => null,
        ], [
            'role_id'          => $this->bmRole->id,
            'min_amount'       => 0,
        ]);

        // Level 2: MO
        ApprovalRule::updateOrCreate([
            'module'           => 'SWITCH_WARRANTY',
            'level'            => 2,
            'business_unit_id' => null,
        ], [
            'role_id'          => $this->moRole->id,
            'min_amount'       => 0,
        ]);
    }

    public function test_unauthorized_user_cannot_open_modal_or_submit(): void
    {
        $unauthorized = User::factory()->create();

        Livewire::actingAs($unauthorized)
            ->test(ActivationList::class)
            ->call('openSwitchModal', 'SN-TEST-999', 1, 'ORD-001', 'Test Phone')
            ->assertSet('showSwitchModal', false)
            ->assertDispatched('toast');
    }

    public function test_authorized_user_can_submit_switch_warranty_request(): void
    {
        $customer1 = User::factory()->create(['name' => 'Customer Lama']);
        $customer2 = User::factory()->create(['name' => 'Customer Baru']);

        // Order 1 (Lama - Dibatalkan)
        $oldOrder = Order::create([
            'order_number'     => 'SO-OLD-001',
            'user_id'          => $customer1->id,
            'business_unit_id' => $this->businessUnit->id,
            'order_status'     => 'CANCELLED',
            'total_amount'              => 15000000,
            'grand_total'               => 15000000,
            'shipping_address_snapshot' => ['store' => 'Cabang Test'],
        ]);

        $oldOrderItem = OrderItem::create([
            'order_id'             => $oldOrder->id,
            'product_name'         => 'iPhone 15 Pro Max',
            'serial_number'        => 'SN-IPHONE-15-001',
            'qty'                  => 1,
            'price_at_checkout'    => 15000000,
            'subtotal'             => 15000000,
            'product_variant_type' => \App\Models\ProductAccurate::class,
            'product_variant_id'   => $this->product->id,
        ]);

        // Device Inspection sebelumnya
        $oldInspection = DeviceInspection::create([
            'inspectable_type'  => OrderItem::class,
            'inspectable_id'    => $oldOrderItem->id,
            'imei'              => 'SN-IPHONE-15-001',
            'inspected_by'      => $this->user->id,
            'checklist_results' => ['screen' => 'ok', 'body' => 'ok'],
            'total_items'       => 2,
            'passed_count'      => 2,
            'status'            => 'passed',
        ]);

        // Warranty sebelumnya (voided karena cancel order)
        $policy = WarrantyPolicy::create([
            'name'          => 'Garansi Toko 1 Tahun',
            'coverage_type' => 'Full Cover',
            'duration_days' => 365,
        ]);

        $oldWarranty = Warranty::create([
            'warranty_policy_id'   => $policy->id,
            'order_item_id'        => $oldOrderItem->id,
            'device_inspection_id' => $oldInspection->id,
            'customer_user_id'     => $customer1->id,
            'serial_number'        => 'SN-IPHONE-15-001',
            'status'               => 'voided',
            'duration_days'        => 365,
        ]);

        // Order 2 (Baru - Aktif COMPLETED)
        $newOrder = Order::create([
            'order_number'     => 'SO-NEW-002',
            'user_id'          => $customer2->id,
            'business_unit_id' => $this->businessUnit->id,
            'order_status'     => 'COMPLETED',
            'total_amount'              => 15000000,
            'grand_total'               => 15000000,
            'shipping_address_snapshot' => ['store' => 'Cabang Test'],
        ]);

        $newOrderItem = OrderItem::create([
            'order_id'             => $newOrder->id,
            'product_name'         => 'iPhone 15 Pro Max',
            'serial_number'        => 'SN-IPHONE-15-001',
            'qty'                  => 1,
            'price_at_checkout'    => 15000000,
            'subtotal'             => 15000000,
            'product_variant_type' => \App\Models\ProductAccurate::class,
            'product_variant_id'   => $this->product->id,
        ]);

        // Tes interaksi Livewire ActivationList
        Livewire::actingAs($this->user)
            ->test(ActivationList::class)
            ->call('openSwitchModal', 'SN-IPHONE-15-001', $newOrder->id, $newOrder->order_number, 'iPhone 15 Pro Max')
            ->assertSet('showSwitchModal', true)
            ->assertSet('switchTargetSn', 'SN-IPHONE-15-001')
            ->assertSet('switchTargetOrderItemId', $newOrderItem->id)
            ->set('switchReason', 'Customer salah metode pembayaran di order lama, order baru sudah dibuat dan bayar cash')
            ->call('submitSwitchRequest')
            ->assertSet('showSwitchModal', false)
            ->assertDispatched('toast');

        // Pastikan ApprovalRequest terbentuk dengan 2 level
        $approval = ApprovalRequest::where('request_type', 'SWITCH_WARRANTY')->first();
        $this->assertNotNull($approval);
        $this->assertEquals('PENDING', $approval->status);
        $this->assertEquals(2, $approval->required_level);
        $this->assertEquals(0, $approval->current_level);
        $this->assertEquals('SN-IPHONE-15-001', $approval->payload['serial_number']);
        $this->assertEquals($newOrderItem->id, $approval->payload['new_order_item_id']);
        $this->assertEquals($oldInspection->id, $approval->payload['device_inspection_id']);
    }

    public function test_full_approval_flow_relinks_inspection_and_activates_warranty(): void
    {
        $customer1 = User::factory()->create(['name' => 'Customer 1']);
        $customer2 = User::factory()->create(['name' => 'Customer 2']);

        $oldOrder = Order::create([
            'order_number'     => 'SO-OLD-999',
            'user_id'          => $customer1->id,
            'business_unit_id' => $this->businessUnit->id,
            'order_status'              => 'CANCELLED',
            'total_amount'              => 10000000,
            'grand_total'               => 10000000,
            'shipping_address_snapshot' => ['store' => 'Cabang Test'],
        ]);

        $oldOrderItem = OrderItem::create([
            'order_id'             => $oldOrder->id,
            'product_name'         => 'Samsung Galaxy S24',
            'serial_number'        => 'SN-S24-999',
            'qty'                  => 1,
            'price_at_checkout'    => 10000000,
            'subtotal'             => 10000000,
            'product_variant_type' => \App\Models\ProductAccurate::class,
            'product_variant_id'   => $this->product->id,
        ]);

        $inspection = DeviceInspection::create([
            'inspectable_type'  => OrderItem::class,
            'inspectable_id'    => $oldOrderItem->id,
            'imei'              => 'SN-S24-999',
            'inspected_by'      => $this->user->id,
            'checklist_results' => ['screen' => 'ok'],
            'total_items'       => 1,
            'passed_count'      => 1,
            'status'            => 'passed',
        ]);

        $policy = WarrantyPolicy::create([
            'name'          => 'Garansi Toko',
            'coverage_type' => 'Full',
            'duration_days' => 180,
        ]);

        $warranty = Warranty::create([
            'warranty_policy_id'   => $policy->id,
            'order_item_id'        => $oldOrderItem->id,
            'device_inspection_id' => $inspection->id,
            'customer_user_id'     => $customer1->id,
            'serial_number'        => 'SN-S24-999',
            'status'               => 'voided',
            'duration_days'        => 180,
        ]);

        $newOrder = Order::create([
            'order_number'              => 'SO-NEW-999',
            'user_id'                   => $customer2->id,
            'business_unit_id'          => $this->businessUnit->id,
            'order_status'              => 'COMPLETED',
            'total_amount'              => 10000000,
            'grand_total'               => 10000000,
            'shipping_address_snapshot' => ['store' => 'Cabang Test'],
        ]);

        $newOrderItem = OrderItem::create([
            'order_id'             => $newOrder->id,
            'product_name'         => 'Samsung Galaxy S24',
            'serial_number'        => 'SN-S24-999',
            'qty'                  => 1,
            'price_at_checkout'    => 10000000,
            'subtotal'             => 10000000,
            'product_variant_type' => \App\Models\ProductAccurate::class,
            'product_variant_id'   => $this->product->id,
        ]);

        // Ajukan request alih garansi
        $approvalService = app(\App\Services\ApprovalService::class);
        $approvalRequest = $approvalService->createRequest([
            'approvable_type'  => DeviceInspection::class,
            'approvable_id'    => $inspection->id,
            'request_type'     => 'SWITCH_WARRANTY',
            'requested_by'     => $this->user->id,
            'business_unit_id' => $this->businessUnit->id,
            'reason'           => 'Migrasi order karena salah bayar',
            'required_level'   => 2,
            'payload'          => [
                'serial_number'        => 'SN-S24-999',
                'device_inspection_id' => $inspection->id,
                'old_order_id'         => $oldOrder->id,
                'old_order_number'     => $oldOrder->order_number,
                'old_order_item_id'    => $oldOrderItem->id,
                'new_order_id'         => $newOrder->id,
                'new_order_number'     => $newOrder->order_number,
                'new_order_item_id'    => $newOrderItem->id,
                'product_name'         => 'Samsung Galaxy S24',
                'voided_warranty_ids'  => [$warranty->id],
            ],
        ]);

        $this->assertEquals('PENDING', $approvalRequest->status);
        $this->assertEquals(0, $approvalRequest->current_level);

        // 1. APPROVAL LEVEL 1 (Branch Manager)
        Livewire::actingAs($this->bmUser)
            ->test(ApprovalsIndex::class)
            ->call('approve', $approvalRequest->id);

        $approvalRequest->refresh();
        $this->assertEquals('PENDING', $approvalRequest->status);
        $this->assertEquals(1, $approvalRequest->current_level);

        // Pastikan belum dialihkan karena masih butuh MO (Level 2)
        $inspection->refresh();
        $this->assertEquals($oldOrderItem->id, $inspection->inspectable_id);

        // 2. APPROVAL LEVEL 2 (Manager Operasional) - Final
        Livewire::actingAs($this->moUser)
            ->test(ApprovalsIndex::class)
            ->call('approve', $approvalRequest->id);

        $approvalRequest->refresh();
        $this->assertEquals('COMPLETED', $approvalRequest->status);
        $this->assertEquals(2, $approvalRequest->current_level);

        // VERIFIKASI HASIL:
        // A. DeviceInspection telah dialihkan ke newOrderItem
        $inspection->refresh();
        $this->assertEquals(OrderItem::class, $inspection->inspectable_type);
        $this->assertEquals($newOrderItem->id, $inspection->inspectable_id);

        // B. Warranty telah dialihkan ke newOrderItem & customer baru, dan statusnya aktif!
        $warranty->refresh();
        $this->assertEquals($newOrderItem->id, $warranty->order_item_id);
        $this->assertEquals($customer2->id, $warranty->customer_user_id);
        $this->assertEquals('active', $warranty->status);
    }
}
