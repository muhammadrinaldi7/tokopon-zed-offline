<?php

namespace Tests\Feature;

use App\Models\BusinessUnit;
use App\Models\Product;
use App\Models\ProductAccurate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTradeInApiTest extends TestCase
{
    use RefreshDatabase;

    protected ProductAccurate $oldPhone;
    protected ProductAccurate $newPhone;
    protected ProductAccurate $secondPhone;

    protected function setUp(): void
    {
        parent::setUp();

        $bu1 = BusinessUnit::create([
            'name' => 'Syihab Store',
            'code' => 'SYH',
            'is_active' => true,
        ]);

        $bu2 = BusinessUnit::create([
            'name' => 'GSK Second',
            'code' => 'GSK',
            'is_active' => true,
        ]);

        $category = \App\Models\Category::create([
            'name' => 'Smartphones',
            'slug' => 'smartphones',
        ]);

        $prod1 = Product::create([
            'name' => 'iPhone 13',
            'slug' => 'iphone-13',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        // HP Lama Customer (Buyback)
        $this->oldPhone = ProductAccurate::create([
            'product_id' => $prod1->id,
            'accurate_id' => 101,
            'item_no' => 'IPH13-RESMI-128',
            'name' => 'Apple iPhone 13 128GB (RESMI)',
            'brandName' => 'Apple',
            'categoryName' => 'HP SECOND',
            'proyek' => 'RESMI',
            'base_price' => 8500000,
            'buy_price' => 6000000,
            'base_cost' => 5000000,
            'business_unit_id' => 2,
        ]);

        $prod2 = Product::create([
            'name' => 'iPhone 15',
            'slug' => 'iphone-15',
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        // HP Baru (BU 1 Syihab, Category: Handphone)
        $this->newPhone = ProductAccurate::create([
            'product_id' => $prod2->id,
            'accurate_id' => 102,
            'item_no' => 'IPH15-RESMI-128',
            'name' => 'Apple iPhone 15 128GB (RESMI)',
            'brandName' => 'Apple',
            'categoryName' => 'Handphone',
            'proyek' => 'RESMI',
            'base_price' => 11399000,
            'buy_price' => 9000000,
            'base_cost' => 10000000,
            'business_unit_id' => 1, // BU 1
        ]);

        // HP Second (BU 2 GSK, Category: HP SECOND)
        $this->secondPhone = ProductAccurate::create([
            'product_id' => $prod2->id,
            'accurate_id' => 103,
            'item_no' => 'IPH15-SECOND-128',
            'name' => 'Apple iPhone 15 128GB Second',
            'brandName' => 'Apple',
            'categoryName' => 'HP SECOND',
            'proyek' => 'RESMI',
            'base_price' => 9500000,
            'buy_price' => 7500000,
            'base_cost' => 8000000,
            'business_unit_id' => 2, // BU 2
        ]);
    }

    public function test_can_fetch_brands_filtered_by_type()
    {
        $response = $this->getJson('/api/v1/public/trade-in/brands?type=new');
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => ['Apple']
            ]);
    }

    public function test_can_fetch_target_devices_new_vs_second()
    {
        // Target New: BU 1 & Handphone
        $resNew = $this->getJson('/api/v1/public/trade-in/target-devices?target_type=new');
        $resNew->assertStatus(200);
        $this->assertEquals(1, $resNew->json('total'));
        $this->assertEquals('Apple iPhone 15 128GB (RESMI)', $resNew->json('data.0.name'));

        // Target Second: BU 2 & HP SECOND
        $resSecond = $this->getJson('/api/v1/public/trade-in/target-devices?target_type=second');
        $resSecond->assertStatus(200);
        $this->assertEquals(2, $resSecond->json('total')); // secondPhone + oldPhone (if base_price > 0)
    }

    public function test_can_calculate_trade_in_difference()
    {
        $response = $this->postJson('/api/v1/public/trade-in/calculate', [
            'old_device_id' => $this->oldPhone->id,
            'target_device_id' => $this->newPhone->id,
            'customer_name' => 'Budi',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'calculation' => [
                        'old_price' => 6000000,
                        'target_price' => 11399000,
                        'difference' => 5399000,
                        'formatted_difference' => 'Rp 5.399.000',
                    ]
                ]
            ]);

        $this->assertStringContainsString('https://wa.me/', $response->json('data.whatsapp_url'));
        $this->assertStringContainsString('iPhone%2013', $response->json('data.whatsapp_url'));
    }

    public function test_can_search_with_smart_tokens_and_aliases()
    {
        // Search "ip 13" matches "Apple iPhone 13 128GB (RESMI)"
        $res1 = $this->getJson('/api/v1/public/trade-in/old-devices?search=ip+13');
        $res1->assertStatus(200);
        $this->assertEquals(1, $res1->json('total'));
        $this->assertEquals('Apple iPhone 13 128GB (RESMI)', $res1->json('data.0.name'));

        // Search "128 15" matches iPhone 15 regardless of word order
        $res2 = $this->getJson('/api/v1/public/trade-in/target-devices?target_type=new&search=128+15');
        $res2->assertStatus(200);
        $this->assertEquals(1, $res2->json('total'));
        $this->assertEquals('Apple iPhone 15 128GB (RESMI)', $res2->json('data.0.name'));
    }

    public function test_groups_color_variants_dynamically_via_settings()
    {
        $prod11 = Product::first();

        ProductAccurate::create([
            'product_id' => $prod11->id,
            'accurate_id' => 201,
            'item_no' => 'IP11-128-BLK-INT',
            'name' => 'HP 2ND IPHONE 11 - 128GB - BLACK - INTER',
            'brandName' => 'APPLE',
            'categoryName' => 'HP SECOND',
            'proyek' => 'INTER',
            'base_price' => 1, // Placeholder Rp 1 di Accurate
            'buy_price' => 2600000,
            'custom_sell_price' => 3550000,
            'business_unit_id' => 2,
        ]);

        ProductAccurate::create([
            'product_id' => $prod11->id,
            'accurate_id' => 202,
            'item_no' => 'IP11-128-WHT-INT',
            'name' => 'HP 2ND IPHONE 11 - 128GB - WHITE - INTER',
            'brandName' => 'APPLE',
            'categoryName' => 'HP SECOND',
            'proyek' => 'INTER',
            'base_price' => 1,
            'buy_price' => 2650000,
            'custom_sell_price' => 3600000,
            'business_unit_id' => 2,
        ]);

        // Aksesoris nyasar di kategori HP SECOND harus otomatis dikecualikan
        ProductAccurate::create([
            'product_id' => $prod11->id,
            'accurate_id' => 203,
            'item_no' => 'CASE-IP11',
            'name' => 'CASE - IPHONE 11 (MID) LOW',
            'brandName' => 'APPLE',
            'categoryName' => 'HP SECOND',
            'proyek' => 'ACCESSORIES',
            'base_price' => 50000,
            'buy_price' => 20000,
            'custom_sell_price' => 50000,
            'business_unit_id' => 2,
        ]);

        // Default: grouped = true & strategy = min
        $resGrouped = $this->getJson('/api/v1/public/trade-in/target-devices?target_type=second&proyek=INTER');
        $resGrouped->assertStatus(200);
        $this->assertEquals(1, $resGrouped->json('total'));
        $this->assertEquals('HP 2ND IPHONE 11 - 128GB', $resGrouped->json('data.0.name'));
        $this->assertEquals('IPHONE 11 - 128GB', $resGrouped->json('data.0.clean_name'));
        $this->assertEquals('INTER', $resGrouped->json('data.0.proyek'));
        $this->assertEquals(2, $resGrouped->json('data.0.variant_count'));
        $this->assertEquals(3550000, $resGrouped->json('data.0.base_price'));
        $this->assertEquals('custom', $resGrouped->json('data.0.price_source'));

        // Matikan grouping secara dinamis via SettingService
        $settingService = app(\App\Services\SettingService::class);
        $settingService->set('tradein_group_target_devices', false, 'boolean');

        $resUngrouped = $this->getJson('/api/v1/public/trade-in/target-devices?target_type=second&proyek=INTER');
        $resUngrouped->assertStatus(200);
        $this->assertEquals(2, $resUngrouped->json('total'));
    }

    public function test_custom_sell_price_modes_and_calculate_integration()
    {
        $prod = Product::first();

        // Produk BU 2 dengan base_price placeholder Rp 1 (belum diisi custom_sell_price)
        $placeholderPhone = ProductAccurate::create([
            'product_id' => $prod->id,
            'accurate_id' => 301,
            'item_no' => 'IP12-placeholder',
            'name' => 'HP 2ND IPHONE 12 - 128GB - BLUE - INTER',
            'brandName' => 'APPLE',
            'categoryName' => 'HP SECOND',
            'proyek' => 'INTER',
            'base_price' => 1,
            'buy_price' => 3200000,
            'custom_sell_price' => null,
            'business_unit_id' => 2,
        ]);

        // Pada default custom_fallback, sebelum custom_sell_price diisi akan fallback ke base_price Accurate
        $resFallback = $this->getJson('/api/v1/public/trade-in/target-devices?target_type=second&proyek=INTER');
        $this->assertEquals(1, $resFallback->json('total'));
        $this->assertEquals(1, $resFallback->json('data.0.base_price'));
        $this->assertEquals('accurate', $resFallback->json('data.0.price_source'));

        // Update custom_sell_price (simulasi hasil import Excel) -> langsung menggantikan base_price Accurate
        $placeholderPhone->update(['custom_sell_price' => 4750000]);

        $resAfterImport = $this->getJson('/api/v1/public/trade-in/target-devices?target_type=second&proyek=INTER');
        $this->assertEquals(1, $resAfterImport->json('total'));
        $this->assertEquals(4750000, $resAfterImport->json('data.0.base_price'));
        $this->assertEquals('custom', $resAfterImport->json('data.0.price_source'));

        // Endpoint /calculate otomatis menggunakan custom_sell_price (4.750.000) & nama tanpa warna
        $calcRes = $this->postJson('/api/v1/public/trade-in/calculate', [
            'old_device_id' => $placeholderPhone->id, // buy_price = 3.200.000
            'target_device_id' => $placeholderPhone->id, // custom_sell_price = 4.750.000
        ]);

        $calcRes->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'target_device' => [
                        'name' => 'HP 2ND IPHONE 12 - 128GB',
                        'base_price' => 4750000,
                    ],
                    'calculation' => [
                        'old_price' => 3200000,
                        'target_price' => 4750000,
                        'difference' => 1550000,
                    ],
                ],
            ]);

        // Mode custom_only menyembunyikan produk yang hanya punya base_price Accurate (tanpa custom_sell_price)
        $settingService = app(\App\Services\SettingService::class);
        $settingService->set('tradein_second_price_source', 'custom_only', 'string');

        $resCustomOnly = $this->getJson('/api/v1/public/trade-in/target-devices?target_type=second');
        $this->assertEquals(1, $resCustomOnly->json('total')); // Hanya IP12 yang punya custom_sell_price
    }

    public function test_csv_import_updates_custom_sell_price_and_propagates_to_color_group()
    {
        $user = \App\Models\User::factory()->create([
            'business_unit_id' => 2,
        ]);

        $prod = Product::first();

        $black = ProductAccurate::create([
            'product_id' => $prod->id,
            'accurate_id' => 401,
            'database_source' => 'gsk',
            'item_no' => 'IP14-128-BLK',
            'name' => 'HP 2ND IPHONE 14 - 128GB - MIDNIGHT - RESMI',
            'brandName' => 'APPLE',
            'categoryName' => 'HP SECOND',
            'proyek' => 'RESMI',
            'base_price' => 1,
            'buy_price' => 6000000,
            'custom_sell_price' => null,
            'business_unit_id' => 2,
        ]);

        $starlight = ProductAccurate::create([
            'product_id' => $prod->id,
            'accurate_id' => 402,
            'database_source' => 'gsk',
            'item_no' => 'IP14-128-WHT',
            'name' => 'HP 2ND IPHONE 14 - 128GB - STARLIGHT - RESMI',
            'brandName' => 'APPLE',
            'categoryName' => 'HP SECOND',
            'proyek' => 'RESMI',
            'base_price' => 1,
            'buy_price' => 6000000,
            'custom_sell_price' => null,
            'business_unit_id' => 2,
        ]);

        $csvContent = "ID;SKU;Nama Barang;Buy Price;Custom Sell Price\n"
            . "{$black->id};IP14-128-BLK;HP 2ND IPHONE 14 - 128GB;6500000;8250000\n";

        $uploadedFile = \Illuminate\Http\UploadedFile::fake()->createWithContent('harga_second.csv', $csvContent);

        \Livewire\Livewire::actingAs($user)
            ->test(\App\Livewire\Admin\Buyback\DeviceIndex::class)
            ->set('csvFile', $uploadedFile)
            ->set('syncColorGroupOnImport', true)
            ->call('importCsv')
            ->assertHasNoErrors();

        $this->assertEquals(6500000, $black->fresh()->buy_price);
        $this->assertEquals(8250000, $black->fresh()->custom_sell_price);

        // Varian warna Starlight di grup yang sama ikut terupdate otomatis
        $this->assertEquals(6500000, $starlight->fresh()->buy_price);
        $this->assertEquals(8250000, $starlight->fresh()->custom_sell_price);
    }

    public function test_old_devices_respects_dynamic_business_unit_and_buy_price_settings()
    {
        // Default: hanya BU 2 (oldPhone + secondPhone = 2 item; newPhone di BU 1 tidak ikut masuk)
        $resDefault = $this->getJson('/api/v1/public/trade-in/old-devices');
        $resDefault->assertStatus(200);
        $this->assertEquals([2], $resDefault->json('business_unit_ids'));
        $this->assertEquals(2, $resDefault->json('total'));

        // Ubah setting dinamis agar BU 1 dan BU 2 keduanya diizinkan
        $settingService = app(\App\Services\SettingService::class);
        $settingService->set('tradein_old_business_unit_ids', [1, 2], 'json');

        $resBoth = $this->getJson('/api/v1/public/trade-in/old-devices');
        $resBoth->assertStatus(200);
        $this->assertEquals([1, 2], $resBoth->json('business_unit_ids'));
        $this->assertEquals(3, $resBoth->json('total'));
    }
}

