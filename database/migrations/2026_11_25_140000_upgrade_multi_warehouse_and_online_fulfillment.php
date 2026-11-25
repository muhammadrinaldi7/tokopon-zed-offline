<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Multi-Warehouse flags & branch metadata on `warehouses`
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'allow_online_delivery')) {
                $table->boolean('allow_online_delivery')->default(false)->after('is_online_store');
            }
            if (!Schema::hasColumn('warehouses', 'allow_store_pickup')) {
                $table->boolean('allow_store_pickup')->default(false)->after('allow_online_delivery');
            }
            if (!Schema::hasColumn('warehouses', 'fulfillment_priority')) {
                $table->unsignedSmallInteger('fulfillment_priority')->default(100)->after('allow_store_pickup');
            }
            if (!Schema::hasColumn('warehouses', 'address')) {
                $table->text('address')->nullable()->after('fulfillment_priority');
            }
            if (!Schema::hasColumn('warehouses', 'city')) {
                $table->string('city', 100)->nullable()->after('address');
            }
            if (!Schema::hasColumn('warehouses', 'phone')) {
                $table->string('phone', 30)->nullable()->after('city');
            }
            if (!Schema::hasColumn('warehouses', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('warehouses', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('warehouses', 'operating_hours')) {
                $table->string('operating_hours', 100)->nullable()->after('longitude');
            }
        });

        // Backfill aman: gudang yang sebelumnya is_online_store = true langsung aktif untuk delivery & pickup
        if (Schema::hasColumn('warehouses', 'is_online_store')) {
            DB::table('warehouses')
                ->where('is_online_store', true)
                ->update([
                    'allow_online_delivery' => true,
                    'allow_store_pickup' => true,
                ]);
        }

        // 2. Online fulfillment metadata on `orders` (semua NULLABLE agar POS & SO toko tidak terpengaruh)
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'fulfillment_type')) {
                $table->string('fulfillment_type', 20)->nullable()->default(null)->after('order_channel');
            }
            if (!Schema::hasColumn('orders', 'pickup_warehouse_id')) {
                $table->foreignId('pickup_warehouse_id')->nullable()->after('warehouse_id')->constrained('warehouses')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'fulfillment_status')) {
                $table->string('fulfillment_status', 30)->nullable()->default(null)->after('order_status');
            }
            if (!Schema::hasColumn('orders', 'fulfilled_at')) {
                $table->timestamp('fulfilled_at')->nullable()->after('payment_expired_at');
            }
            if (!Schema::hasColumn('orders', 'fulfilled_by')) {
                $table->foreignId('fulfilled_by')->nullable()->after('fulfilled_at')->constrained('users')->nullOnDelete();
            }
        });

        // 3. Tabel terisolasi `online_stock_reservations` (mencegah konflik dengan Webhook Accurate & POS offline)
        if (!Schema::hasTable('online_stock_reservations')) {
            Schema::create('online_stock_reservations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
                $table->foreignId('product_accurate_id')->constrained('product_accurates')->cascadeOnDelete();
                $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                $table->foreignId('product_serial_number_id')->nullable()->constrained('product_serial_numbers')->nullOnDelete();
                $table->unsignedInteger('qty')->default(1);
                $table->string('status', 20)->default('ACTIVE'); // ACTIVE, FULFILLED, RELEASED, EXPIRED
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->index(['product_accurate_id', 'status', 'expires_at'], 'idx_osr_product_status_exp');
                $table->index(['warehouse_id', 'product_accurate_id', 'status'], 'idx_osr_wh_product_status');
                $table->index(['product_serial_number_id', 'status'], 'idx_osr_sn_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('online_stock_reservations');

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'pickup_warehouse_id')) {
                $table->dropConstrainedForeignId('pickup_warehouse_id');
            }
            if (Schema::hasColumn('orders', 'fulfilled_by')) {
                $table->dropConstrainedForeignId('fulfilled_by');
            }
            $dropOrderCols = array_values(array_filter(
                ['fulfillment_type', 'fulfillment_status', 'fulfilled_at'],
                fn($col) => Schema::hasColumn('orders', $col)
            ));
            if (!empty($dropOrderCols)) {
                $table->dropColumn($dropOrderCols);
            }
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $dropWhCols = array_values(array_filter(
                [
                    'allow_online_delivery',
                    'allow_store_pickup',
                    'fulfillment_priority',
                    'address',
                    'city',
                    'phone',
                    'latitude',
                    'longitude',
                    'operating_hours',
                ],
                fn($col) => Schema::hasColumn('warehouses', $col)
            ));
            if (!empty($dropWhCols)) {
                $table->dropColumn($dropWhCols);
            }
        });
    }
};
