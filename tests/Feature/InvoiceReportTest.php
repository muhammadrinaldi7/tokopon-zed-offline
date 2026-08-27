<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\BusinessUnit;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentMethodRate;
use App\Models\OrderPayment;
use App\Livewire\Zoffline\Reporting\InvoiceReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $bu;
    protected Order $order;
    protected PaymentMethod $pm;
    protected PaymentMethodRate $pmr;

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

        // Create Order
        $this->order = Order::create([
            'order_number' => 'ORD-90001',
            'accurate_invoice_no' => 'INV-90001',
            'business_unit_id' => $this->bu->id,
            'user_id' => $this->user->id,
            'total_amount' => 500000,
            'grand_total' => 500000,
            'order_status' => 'COMPLETED',
            'shipping_address_snapshot' => ['store' => 'Cabang Sudirman'],
        ]);

        // Create PaymentMethod
        $this->pm = PaymentMethod::create([
            'name' => 'Transfer Mandiri',
            'category' => 'BANK',
            'bank_name' => 'Mandiri',
            'business_unit_id' => $this->bu->id,
            'is_active' => true,
        ]);

        // Create PaymentMethodRate
        $this->pmr = PaymentMethodRate::create([
            'payment_method_id' => $this->pm->id,
            'name' => 'Mandiri Transfer Rate',
            'mdr_percentage' => 1.5,
            'is_active' => true,
        ]);

        // Create OrderPayment
        OrderPayment::create([
            'order_id' => $this->order->id,
            'payment_method_id' => $this->pm->id,
            'payment_method_rate_id' => $this->pmr->id,
            'amount' => 500000,
            'xendit_external_id' => 'xendit-test-12345',
            'paid_at' => now(),
        ]);
    }

    public function test_payment_report_page_accessible_by_authenticated_user()
    {
        $response = $this->actingAs($this->user)->get(route('reporting.pembayaran'));
        $response->assertStatus(200);
    }

    public function test_payment_report_filters_by_branch()
    {
        // Empty filter should list the order
        Livewire::actingAs($this->user)
            ->test(InvoiceReport::class)
            ->assertSet('branchFilter', '')
            ->assertViewHas('orders', function ($orders) {
                return $orders->count() === 1;
            });

        // Filter by existing branch
        Livewire::actingAs($this->user)
            ->test(InvoiceReport::class)
            ->set('branchFilter', 'Cabang Sudirman')
            ->assertViewHas('orders', function ($orders) {
                return $orders->count() === 1;
            });

        // Filter by non-existing branch
        Livewire::actingAs($this->user)
            ->test(InvoiceReport::class)
            ->set('branchFilter', 'Cabang Thamrin')
            ->assertViewHas('orders', function ($orders) {
                return $orders->count() === 0;
            });
    }

    public function test_payment_report_excel_export()
    {
        $response = Livewire::actingAs($this->user)
            ->test(InvoiceReport::class)
            ->call('exportExcelOrderPayments');

        $response->assertFileDownloaded();
    }

    public function test_payment_report_csv_export()
    {
        $response = Livewire::actingAs($this->user)
            ->test(InvoiceReport::class)
            ->call('exportCsvOrderPayments');

        $response->assertFileDownloaded();
    }
}
