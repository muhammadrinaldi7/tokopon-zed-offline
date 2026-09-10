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

    protected function setUp(): void
    {
        parent::setUp();

        $bu = BusinessUnit::create([
            'name' => 'Tokopon Store',
            'code' => 'TKP',
            'is_active' => true,
        ]);

        $prod1 = Product::create([
            'name' => 'iPhone 13',
            'slug' => 'iphone-13',
            'is_active' => true,
        ]);

        $this->oldPhone = ProductAccurate::create([
            'product_id' => $prod1->id,
            'item_no' => 'IPH13-RESMI-128',
            'name' => 'Apple iPhone 13 128GB (RESMI)',
            'brandName' => 'Apple',
            'categoryName' => 'Handphone',
            'proyek' => 'RESMI',
            'base_price' => 8500000,
            'buy_price' => 6000000,
            'base_cost' => 5000000, // Sensitive cost that must NOT be exposed
            'business_unit_id' => $bu->id,
        ]);

        $prod2 = Product::create([
            'name' => 'iPhone 15',
            'slug' => 'iphone-15',
            'is_active' => true,
        ]);

        $this->newPhone = ProductAccurate::create([
            'product_id' => $prod2->id,
            'item_no' => 'IPH15-RESMI-128',
            'name' => 'Apple iPhone 15 128GB (RESMI)',
            'brandName' => 'Apple',
            'categoryName' => 'Handphone',
            'proyek' => 'RESMI',
            'base_price' => 11399000,
            'buy_price' => 9000000,
            'base_cost' => 10000000, // Sensitive cost that must NOT be exposed
            'business_unit_id' => $bu->id,
        ]);
    }

    public function test_can_fetch_brands()
    {
        $response = $this->getJson('/api/v1/public/trade-in/brands');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => ['Apple']
            ]);
    }

    public function test_can_fetch_old_devices_and_does_not_leak_base_cost()
    {
        $response = $this->getJson('/api/v1/public/trade-in/old-devices?brand=Apple');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'total',
                'data' => [
                    '*' => [
                        'id',
                        'item_no',
                        'name',
                        'brand',
                        'category',
                        'proyek',
                        'buy_price',
                        'formatted_buy_price',
                    ]
                ]
            ])
            ->assertDontSee('base_cost')
            ->assertDontSee('raw_data');
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

    public function test_can_fetch_single_device_price()
    {
        $response = $this->getJson("/api/v1/public/trade-in/device/{$this->oldPhone->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $this->oldPhone->id,
                    'buy_price' => 6000000,
                    'formatted_buy_price' => 'Rp 6.000.000',
                ]
            ]);
    }
}
