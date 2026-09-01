<?php

namespace Tests\Feature;

use App\Livewire\Zoffline\Reporting\CancellationReport;
use App\Models\ApprovalHistory;
use App\Models\ApprovalRequest;
use App\Models\BusinessUnit;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CancellationReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $bu;
    protected Order $posOrder;
    protected Order $soOrder;
    protected ApprovalRequest $approvedRequest;
    protected ApprovalRequest $pendingRequest;

    protected function setUp(): void
    {
        parent::setUp();

        // Create business unit
        $this->bu = BusinessUnit::create([
            'name' => 'Test Business Unit',
            'code' => 'TBU',
            'is_active' => true,
        ]);

        // Create Role and Permission
        $role = Role::create(['name' => 'admin']);
        $permission = Permission::create(['name' => 'view-reporting']);
        $role->givePermissionTo($permission);

        // Create User
        $this->user = User::factory()->create([
            'name' => 'Kasir John Doe',
            'email' => 'admin@test.com',
            'business_unit_id' => $this->bu->id,
        ]);
        $this->user->assignRole($role);

        // Create Approver User
        $approver = User::factory()->create([
            'name' => 'Manager Alex',
            'email' => 'manager@test.com',
            'business_unit_id' => $this->bu->id,
        ]);

        // Create Orders
        $this->posOrder = Order::create([
            'order_number' => 'ORD-POS-001',
            'accurate_invoice_no' => 'INV-POS-001',
            'order_channel' => 'POS',
            'business_unit_id' => $this->bu->id,
            'user_id' => $this->user->id,
            'total_amount' => 750000,
            'grand_total' => 750000,
            'order_status' => 'CANCELLED',
            'shipping_address_snapshot' => ['store' => 'Toko Cabang Utama'],
        ]);

        $this->soOrder = Order::create([
            'order_number' => 'ORD-SO-002',
            'accurate_so_number' => 'SO-002',
            'order_channel' => 'SO',
            'business_unit_id' => $this->bu->id,
            'user_id' => $this->user->id,
            'total_amount' => 1500000,
            'grand_total' => 1500000,
            'order_status' => 'DRAFT',
            'shipping_address_snapshot' => ['store' => 'Toko Cabang Cabang 2'],
        ]);

        // Create Approval Requests for cancellations
        $this->approvedRequest = ApprovalRequest::create([
            'approvable_type' => Order::class,
            'approvable_id' => $this->posOrder->id,
            'request_type' => 'ORDER_CANCELLATION',
            'requested_by' => $this->user->id,
            'reason' => 'Customer salah pilih varian warna',
            'status' => 'APPROVED',
            'required_level' => 1,
            'current_level' => 1,
            'created_at' => Carbon::now(),
        ]);

        ApprovalHistory::create([
            'approval_request_id' => $this->approvedRequest->id,
            'acted_by' => $approver->id,
            'action' => 'APPROVED',
            'level' => 1,
            'notes' => 'Disetujui oleh manager',
            'created_at' => Carbon::now(),
        ]);

        $this->pendingRequest = ApprovalRequest::create([
            'approvable_type' => Order::class,
            'approvable_id' => $this->soOrder->id,
            'request_type' => 'ORDER_CANCELLATION',
            'requested_by' => $this->user->id,
            'reason' => 'Pelanggan minta ganti pembayaran ke tempo',
            'status' => 'PENDING',
            'required_level' => 1,
            'current_level' => 0,
            'created_at' => Carbon::now(),
        ]);
    }

    public function test_cancellation_report_page_accessible_by_authenticated_user()
    {
        $response = $this->actingAs($this->user)->get(route('reporting.pembatalan'));
        $response->assertStatus(200);
    }

    public function test_cancellation_report_renders_requests_and_metrics()
    {
        Livewire::actingAs($this->user)
            ->test(CancellationReport::class)
            ->assertViewHas('requests', function ($requests) {
                return $requests->total() === 2;
            })
            ->assertViewHas('totalCancellations', 2)
            ->assertViewHas('totalApproved', 1)
            ->assertViewHas('totalPending', 1)
            ->assertViewHas('totalRejected', 0)
            ->assertViewHas('totalValue', 2250000)
            ->assertSee('ORD-POS-001')
            ->assertSee('ORD-SO-002')
            ->assertSee('Customer salah pilih varian warna');
    }

    public function test_cancellation_report_filters_by_status()
    {
        // Filter by APPROVED
        Livewire::actingAs($this->user)
            ->test(CancellationReport::class)
            ->set('statusFilter', 'APPROVED')
            ->assertViewHas('requests', function ($requests) {
                return $requests->total() === 1;
            })
            ->assertSee('ORD-POS-001')
            ->assertDontSee('ORD-SO-002');

        // Filter by PENDING
        Livewire::actingAs($this->user)
            ->test(CancellationReport::class)
            ->set('statusFilter', 'PENDING')
            ->assertViewHas('requests', function ($requests) {
                return $requests->total() === 1;
            })
            ->assertSee('ORD-SO-002')
            ->assertDontSee('ORD-POS-001');

        // Filter by REJECTED (empty)
        Livewire::actingAs($this->user)
            ->test(CancellationReport::class)
            ->set('statusFilter', 'REJECTED')
            ->assertViewHas('requests', function ($requests) {
                return $requests->total() === 0;
            });
    }

    public function test_cancellation_report_filters_by_channel()
    {
        // Filter by POS
        Livewire::actingAs($this->user)
            ->test(CancellationReport::class)
            ->set('channelFilter', 'POS')
            ->assertViewHas('requests', function ($requests) {
                return $requests->total() === 1;
            })
            ->assertSee('ORD-POS-001');

        // Filter by SO
        Livewire::actingAs($this->user)
            ->test(CancellationReport::class)
            ->set('channelFilter', 'SO')
            ->assertViewHas('requests', function ($requests) {
                return $requests->total() === 1;
            })
            ->assertSee('ORD-SO-002');
    }

    public function test_cancellation_report_filters_by_search()
    {
        Livewire::actingAs($this->user)
            ->test(CancellationReport::class)
            ->set('search', 'varian warna')
            ->assertViewHas('requests', function ($requests) {
                return $requests->total() === 1;
            })
            ->assertSee('ORD-POS-001')
            ->assertDontSee('ORD-SO-002');

        Livewire::actingAs($this->user)
            ->test(CancellationReport::class)
            ->set('search', 'SO-002')
            ->assertViewHas('requests', function ($requests) {
                return $requests->total() === 1;
            })
            ->assertSee('ORD-SO-002')
            ->assertDontSee('ORD-POS-001');
    }

    public function test_cancellation_report_excel_export()
    {
        $response = Livewire::actingAs($this->user)
            ->test(CancellationReport::class)
            ->call('exportExcel');

        $response->assertFileDownloaded();
    }

    public function test_cancellation_report_csv_export()
    {
        $response = Livewire::actingAs($this->user)
            ->test(CancellationReport::class)
            ->call('exportCsv');

        $response->assertFileDownloaded();
    }
}
