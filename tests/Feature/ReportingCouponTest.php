<?php

namespace Tests\Feature;

use App\Exports\ManagementSalesReportExport;
use App\Exports\SalesReportExport;
use App\Livewire\Zoffline\Reporting\ManagementSalesReport;
use App\Livewire\Zoffline\Reporting\SalesReport;
use App\Models\BusinessUnit;
use App\Models\EcommerceCoupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductAccurate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportingCouponTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BusinessUnit $businessUnit;
    protected ProductAccurate $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessUnit = BusinessUnit::firstOrCreate(
            ['code' => 'syihab'],
            ['name' => 'Syihab Gadget', 'is_active' => true]
        );

        $this->user = User::factory()->create([
            'business_unit_id' => $this->businessUnit->id,
        ]);

        $this->product = ProductAccurate::create([
            'accurate_id' => 123456,
            'item_no' => 'IPH15-128',
            'name' => 'iPhone 15 128GB',
            'base_price' => 10000000,
            'base_cost' => 8000000,
            'business_unit_id' => $this->businessUnit->id,
            'has_sn' => false,
        ]);
    }

    public function test_sales_report_export_headings_and_data_columns_match(): void
    {
        $this->actingAs($this->user);

        $coupon = EcommerceCoupon::create([
            'code' => 'HEMAT500',
            'title' => 'Diskon 500rb',
            'type' => EcommerceCoupon::TYPE_PRODUCT_FIXED,
            'discount_value' => 500000,
            'is_active' => true,
        ]);

        $order = Order::create([
            'business_unit_id' => $this->businessUnit->id,
            'user_id' => $this->user->id,
            'order_number' => 'MOB-TEST-001',
            'order_date' => now()->format('Y-m-d'),
            'total_amount' => 10000000,
            'discount_amount' => 0,
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'coupon_discount_amount' => 500000,
            'loyalty_discount_amount' => 50000,
            'grand_total' => 9450000,
            'order_status' => 'COMPLETED',
            'order_channel' => 'MOBILE_APP',
            'shipping_address_snapshot' => ['store' => 'Cabang Utama'],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $this->product->id,
            'product_variant_type' => ProductAccurate::class,
            'product_name' => $this->product->name,
            'qty' => 1,
            'price_at_checkout' => 10000000,
            'subtotal' => 10000000,
            'discount_amount' => 0,
        ]);

        $component = new SalesReport();
        $component->startDate = now()->subDay()->format('Y-m-d');
        $component->endDate = now()->addDay()->format('Y-m-d');
        $component->businessUnitFilter = (string)$this->businessUnit->id;

        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('generateTransactionsData');
        $method->setAccessible(true);
        $rows = $method->invoke($component);

        $this->assertNotEmpty($rows);
        $firstRow = $rows[0];

        $export = new SalesReportExport($rows);
        $headings = $export->headings();

        // Verifikasi jumlah kolom data sama dengan jumlah headings
        $this->assertCount(count($headings), $firstRow, 'Jumlah kolom data di SalesReportExport harus tepat sama dengan jumlah headings.');

        // Verifikasi nama heading dan nilai kolom terkait
        $this->assertContains('KODE VOUCHER', $headings);
        $this->assertContains('DISKON VOUCHER (Rp)', $headings);
        $this->assertContains('DISKON POIN (Rp)', $headings);

        $couponCodeIdx = array_search('KODE VOUCHER', $headings);
        $couponDiscIdx = array_search('DISKON VOUCHER (Rp)', $headings);
        $loyaltyDiscIdx = array_search('DISKON POIN (Rp)', $headings);
        $netSalesIdx = array_search('PENJUALAN BERSIH', $headings);

        $this->assertEquals('HEMAT500', $firstRow[$couponCodeIdx]);
        $this->assertEquals(500000, $firstRow[$couponDiscIdx]);
        $this->assertEquals(50000, $firstRow[$loyaltyDiscIdx]);
        $this->assertEquals(9450000, $firstRow[$netSalesIdx]);
    }

    public function test_management_sales_report_export_headings_and_data_columns_match(): void
    {
        $this->actingAs($this->user);

        $coupon = EcommerceCoupon::create([
            'code' => 'DISKON10',
            'title' => 'Diskon 10%',
            'type' => EcommerceCoupon::TYPE_PRODUCT_PERCENT,
            'discount_value' => 10,
            'is_active' => true,
        ]);

        $order = Order::create([
            'business_unit_id' => $this->businessUnit->id,
            'user_id' => $this->user->id,
            'order_number' => 'MOB-TEST-002',
            'order_date' => now()->format('Y-m-d'),
            'total_amount' => 10000000,
            'discount_amount' => 0,
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'coupon_discount_amount' => 1000000,
            'loyalty_discount_amount' => 0,
            'grand_total' => 9000000,
            'order_status' => 'COMPLETED',
            'order_channel' => 'MOBILE_APP',
            'shipping_address_snapshot' => ['store' => 'Cabang Utama'],
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_variant_id' => $this->product->id,
            'product_variant_type' => ProductAccurate::class,
            'product_name' => $this->product->name,
            'qty' => 1,
            'price_at_checkout' => 10000000,
            'subtotal' => 10000000,
            'discount_amount' => 0,
        ]);

        $component = new ManagementSalesReport();
        $component->startDate = now()->subDay()->format('Y-m-d');
        $component->endDate = now()->addDay()->format('Y-m-d');
        $component->businessUnitFilter = (string)$this->businessUnit->id;

        $rows = $component->generateManagementData();

        $this->assertNotEmpty($rows);
        $firstRow = $rows[0];

        $export = new ManagementSalesReportExport($rows);
        $headings = $export->headings();

        // Verifikasi jumlah kolom data sama dengan jumlah headings
        $this->assertCount(count($headings), $firstRow, 'Jumlah kolom data di ManagementSalesReportExport harus tepat sama dengan jumlah headings.');

        $this->assertContains('KODE VOUCHER', $headings);
        $this->assertContains('DISKON VOUCHER (Rp)', $headings);
        $this->assertContains('DISKON POIN (Rp)', $headings);

        $couponCodeIdx = array_search('KODE VOUCHER', $headings);
        $couponDiscIdx = array_search('DISKON VOUCHER (Rp)', $headings);
        $netSalesIdx = array_search('PENJUALAN BERSIH (Rp)', $headings);

        $this->assertEquals('DISKON10', $firstRow[$couponCodeIdx]);
        $this->assertEquals(1000000, $firstRow[$couponDiscIdx]);
        $this->assertEquals(9000000, $firstRow[$netSalesIdx]);
    }
}
