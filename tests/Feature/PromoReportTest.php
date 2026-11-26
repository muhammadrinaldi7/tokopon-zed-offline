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

    public function test_promo_report_uses_order_date_for_filtering_and_claim_rows()
    {
        $targetDate = now()->subDays(5)->format('Y-m-d');

        // Update order to have a specific order_date 5 days ago, while created_at is today
        $this->order->update([
            'order_date' => $targetDate,
        ]);

        // When date range matches targetDate, it should find the order
        $component = Livewire::actingAs($this->user)
            ->test(PromoReport::class)
            ->set('dateRange', 'custom')
            ->set('startDate', $targetDate)
            ->set('endDate', $targetDate);

        $component->assertViewHas('orders', function ($orders) {
            return $orders->count() === 1 && $orders->first()->order_number === 'ORD-10001';
        });

        // Verify that getClaimRows maps tanggal using order_date and includes updated_at
        $rows = invade($component->instance())->getClaimRows();
        $this->assertNotEmpty($rows);
        $this->assertEquals($targetDate, $rows[0]['tanggal']);
        $this->assertArrayHasKey('updated_at', $rows[0]);
        $this->assertNotEquals('-', $rows[0]['updated_at']);
    }

    public function test_promo_report_includes_bundle_item_and_sn_with_cross_brand_filter()
    {
        $mainProduct = ProductAccurate::create([
            'item_no' => 'HP-MAIN-01',
            'name' => 'Samsung Galaxy S25',
            'accurate_id' => 'ACC-MAIN-01',
            'brandName' => 'Samsung',
            'has_sn' => true,
        ]);

        $bundleWithSn = ProductAccurate::create([
            'item_no' => 'TWS-BND-01',
            'name' => 'Anker Soundcore TWS',
            'accurate_id' => 'ACC-BND-01',
            'brandName' => 'Anker',
            'has_sn' => true,
        ]);

        $bundleNoSn = ProductAccurate::create([
            'item_no' => 'CASE-BND-02',
            'name' => 'Clear Case',
            'accurate_id' => 'ACC-BND-02',
            'brandName' => 'Zed',
            'has_sn' => false,
        ]);

        $bundlePromo = Promo::create([
            'name' => 'Promo Bundling S25',
            'code' => 'BNDS25',
            'discount_type' => 'fixed',
            'discount_value' => 500000,
            'is_bundle' => true,
            'bundle_discount_type' => 'fixed',
            'bundle_discount_value' => 100000,
        ]);
        $bundlePromo->skus()->create(['sku' => 'HP-MAIN-01']);
        $bundlePromo->bundleSkus()->create(['sku' => 'TWS-BND-01']);
        $bundlePromo->bundleSkus()->create(['sku' => 'CASE-BND-02']);

        $bundleOrder = Order::create([
            'order_number' => 'ORD-BND-001',
            'accurate_invoice_no' => 'INV-BND-001',
            'business_unit_id' => $this->bu->id,
            'user_id' => $this->user->id,
            'order_status' => 'COMPLETED',
            'total_amount' => 15000000,
            'grand_total' => 14400000,
            'shipping_address_snapshot' => ['store' => 'Cabang Test'],
        ]);
        $bundleOrder->promos()->attach($bundlePromo->id, ['discount_applied' => 600000]);

        $mainOrderItem = OrderItem::create([
            'order_id' => $bundleOrder->id,
            'product_name' => 'Samsung Galaxy S25',
            'qty' => 1,
            'price_at_checkout' => 14000000,
            'subtotal' => 14000000,
            'serial_number' => '356789012345678',
            'product_variant_type' => ProductAccurate::class,
            'product_variant_id' => $mainProduct->id,
        ]);
        $mainOrderItem->promos()->attach($bundlePromo->id, [
            'discount_amount' => 500000,
            'serial_number' => '356789012345678',
            'vendor_name' => 'Samsung Indonesia',
        ]);

        // Bundle item 1: punya SN, tapi discount_amount = 0 (tidak tercatat di order_item_promos)
        OrderItem::create([
            'order_id' => $bundleOrder->id,
            'product_name' => 'Anker Soundcore TWS',
            'qty' => 1,
            'price_at_checkout' => 800000,
            'subtotal' => 800000,
            'serial_number' => 'SN-ANKER-998877',
            'product_variant_type' => ProductAccurate::class,
            'product_variant_id' => $bundleWithSn->id,
        ]);

        // Bundle item 2: tidak punya SN, punya discount_amount di order_item_promos
        $bundleItemNoSn = OrderItem::create([
            'order_id' => $bundleOrder->id,
            'product_name' => 'Clear Case',
            'qty' => 1,
            'price_at_checkout' => 200000,
            'subtotal' => 200000,
            'serial_number' => '',
            'product_variant_type' => ProductAccurate::class,
            'product_variant_id' => $bundleNoSn->id,
        ]);
        $bundleItemNoSn->promos()->attach($bundlePromo->id, [
            'discount_amount' => 100000,
            'serial_number' => '',
            'vendor_name' => 'Zed',
        ]);

        $getClaimRows = new \ReflectionMethod(PromoReport::class, 'getClaimRows');
        $getClaimRows->setAccessible(true);

        // 1. Tanpa brandFilter: muncul baris Produk Utama + baris Item Bundling (Clear Case)
        $componentAll = Livewire::actingAs($this->user)->test(PromoReport::class);
        $rowsAll = $getClaimRows->invoke($componentAll->instance());
        $bundleOrderRows = collect($rowsAll)->where('order_no', 'ORD-BND-001')->values();

        $this->assertCount(2, $bundleOrderRows);
        $this->assertEquals('Produk Utama', $bundleOrderRows[0]['tipe_item']);
        $this->assertEquals('356789012345678', $bundleOrderRows[0]['sn']);
        $this->assertEquals('Anker Soundcore TWS (x1); Clear Case (x1)', $bundleOrderRows[0]['item_bundling']);
        $this->assertEquals('SN-ANKER-998877', $bundleOrderRows[0]['sn_bundling']);

        $this->assertEquals('Item Bundling', $bundleOrderRows[1]['tipe_item']);
        $this->assertEquals('-', $bundleOrderRows[1]['sn']);
        $this->assertEquals('-', $bundleOrderRows[1]['item_bundling']);
        $this->assertEquals('-', $bundleOrderRows[1]['sn_bundling']);

        // 2. Dengan brandFilter = Samsung: baris Produk Utama tetap membawa info & SN bundling lintas brand
        $componentSamsung = Livewire::actingAs($this->user)
            ->test(PromoReport::class)
            ->set('brandFilter', 'Samsung');
        $rowsSamsung = $getClaimRows->invoke($componentSamsung->instance());

        $this->assertCount(1, $rowsSamsung);
        $this->assertEquals('Produk Utama', $rowsSamsung[0]['tipe_item']);
        $this->assertEquals('SN-ANKER-998877', $rowsSamsung[0]['sn_bundling']);

        // 3. Export Excel berhasil terdownload & membaca isi sheet via PhpSpreadsheet
        $componentSamsung->call('exportExcelClaim')->assertFileDownloaded();

        $binary = \Maatwebsite\Excel\Facades\Excel::raw(
            new \App\Exports\PromoReportExport($rowsSamsung),
            \Maatwebsite\Excel\Excel::XLSX
        );
        $tmpFile = tempnam(sys_get_temp_dir(), 'promo_export_') . '.xlsx';
        file_put_contents($f = $tmpFile, $binary);
        try {
            \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(new \PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder());
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($f);
            $sheet = $spreadsheet->getActiveSheet();
            $this->assertEquals('TIPE ITEM', $sheet->getCell('I1')->getValue());
            $this->assertEquals('ITEM BUNDLING', $sheet->getCell('L1')->getValue());
            $this->assertEquals('SN BUNDLING', $sheet->getCell('M1')->getValue());
            $this->assertEquals('Produk Utama', $sheet->getCell('I2')->getValue());
            $this->assertSame('356789012345678', $sheet->getCell('K2')->getValue());
            $this->assertEquals(\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING, $sheet->getCell('K2')->getDataType());
            $this->assertEquals('Anker Soundcore TWS (x1); Clear Case (x1)', $sheet->getCell('L2')->getValue());
            $this->assertSame('SN-ANKER-998877', $sheet->getCell('M2')->getValue());
            $this->assertEquals(\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING, $sheet->getCell('M2')->getDataType());
            $this->assertEquals(\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC, $sheet->getCell('O2')->getDataType());
            $this->assertEquals(500000, $sheet->getCell('O2')->getValue());
        } finally {
            @unlink($tmpFile);
        }
    }
}
