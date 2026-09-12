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
}

