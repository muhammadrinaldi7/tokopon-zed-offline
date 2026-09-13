<?php

namespace Tests\Unit;

use App\Models\AccurateWebhookLog;
use App\Models\BusinessUnit;
use App\Models\OrderItem;
use App\Models\ProductAccurate;
use App\Models\WarehouseStock;
use App\Webhooks\Accurate\ItemDeleteHandler;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ItemDeleteHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('business_units', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('product_accurates', function (Blueprint $table) {
            $table->id();
            $table->string('item_no');
            $table->string('name');
            $table->integer('stock')->default(0);
            $table->string('database_source')->nullable();
            $table->unsignedBigInteger('business_unit_id')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('variant_type');
            $table->unsignedBigInteger('variant_id');
            $table->integer('stock')->default(0);
            $table->timestamps();
        });

        Schema::create('product_serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_accurate_id')->nullable();
            $table->string('serial_number')->nullable();
            $table->timestamps();
        });

        Schema::create('second_product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_accurate_id')->nullable();
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_accurate_id')->nullable();
            $table->timestamps();
        });

        Schema::create('buyback_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_accurate_id')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->string('product_variant_type')->nullable();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_it_hard_deletes_product_accurate_and_warehouse_stocks()
    {
        $bu = BusinessUnit::create(['code' => 'gsk', 'name' => 'GSK Unit']);

        $pa = ProductAccurate::create([
            'item_no' => 'IPHONE-15-PRO',
            'name' => 'iPhone 15 Pro 128GB',
            'database_source' => 'gsk',
            'business_unit_id' => $bu->id,
        ]);

        WarehouseStock::create([
            'variant_type' => ProductAccurate::class,
            'variant_id' => $pa->id,
            'stock' => 5,
        ]);

        $handler = new ItemDeleteHandler();
        $result = $handler->deleteItem('IPHONE-15-PRO', 'gsk');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('product_accurates', ['id' => $pa->id]);
        $this->assertDatabaseMissing('warehouse_stocks', ['variant_id' => $pa->id, 'variant_type' => ProductAccurate::class]);
    }

    public function test_it_scopes_deletion_to_correct_business_unit()
    {
        $bu1 = BusinessUnit::create(['code' => 'gsk', 'name' => 'GSK Unit']);
        $bu2 = BusinessUnit::create(['code' => 'syihab', 'name' => 'Syihab Unit']);

        $pa1 = ProductAccurate::create([
            'item_no' => 'SHARED-SKU-01',
            'name' => 'Barang GSK',
            'database_source' => 'gsk',
            'business_unit_id' => $bu1->id,
        ]);

        $pa2 = ProductAccurate::create([
            'item_no' => 'SHARED-SKU-01',
            'name' => 'Barang Syihab',
            'database_source' => 'syihab',
            'business_unit_id' => $bu2->id,
        ]);

        $handler = new ItemDeleteHandler();
        $handler->deleteItem('SHARED-SKU-01', 'gsk');

        // pa1 terhapus, pa2 harus tetap ada
        $this->assertDatabaseMissing('product_accurates', ['id' => $pa1->id]);
        $this->assertDatabaseHas('product_accurates', ['id' => $pa2->id]);
    }

    public function test_it_aborts_delete_if_product_has_local_order_transactions()
    {
        $bu = BusinessUnit::create(['code' => 'gsk', 'name' => 'GSK Unit']);

        $pa = ProductAccurate::create([
            'item_no' => 'SOLD-ITEM-01',
            'name' => 'Barang Terjual',
            'database_source' => 'gsk',
            'business_unit_id' => $bu->id,
        ]);

        OrderItem::create([
            'product_variant_type' => ProductAccurate::class,
            'product_variant_id' => $pa->id,
        ]);

        $handler = new ItemDeleteHandler();
        $result = $handler->deleteItem('SOLD-ITEM-01', 'gsk');

        $this->assertFalse($result);
        $this->assertDatabaseHas('product_accurates', ['id' => $pa->id]);
    }

    public function test_it_handles_webhook_log_payload_properly()
    {
        $bu = BusinessUnit::create(['code' => 'gsk', 'name' => 'GSK Unit']);

        $pa = ProductAccurate::create([
            'item_no' => 'DELETE-ME-BATCH',
            'name' => 'Barang Batch Delete',
            'database_source' => 'gsk',
            'business_unit_id' => $bu->id,
        ]);

        $log = new AccurateWebhookLog([
            'event_type' => 'ITEM_DELETE',
            'database_source' => 'gsk',
            'payload' => [
                'data' => [
                    ['itemNo' => 'DELETE-ME-BATCH']
                ]
            ]
        ]);

        $handler = new ItemDeleteHandler();
        $handler->handle($log);

        $this->assertDatabaseMissing('product_accurates', ['id' => $pa->id]);
    }
}
