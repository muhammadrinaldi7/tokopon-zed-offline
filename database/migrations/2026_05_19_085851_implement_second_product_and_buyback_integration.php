<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Drop is_second from products
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'is_second')) {
                $table->dropColumn('is_second');
            }
        });

        // 2. Add has_active_accurate to second_products
        Schema::table('second_products', function (Blueprint $table) {
            if (!Schema::hasColumn('second_products', 'has_active_accurate')) {
                $table->boolean('has_active_accurate')->default(false)->after('total_stock');
            }
        });

        // 3. Add second_product_variant_id to buyback_devices
        Schema::table('buyback_devices', function (Blueprint $table) {
            if (!Schema::hasColumn('buyback_devices', 'second_product_variant_id')) {
                // Relasi ke tabel second_product_variants
                $table->foreignId('second_product_variant_id')->nullable()->after('brand_id')->constrained('second_product_variants')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'is_second')) {
                $table->boolean('is_second')->default(false);
            }
        });

        Schema::table('second_products', function (Blueprint $table) {
            if (Schema::hasColumn('second_products', 'has_active_accurate')) {
                $table->dropColumn('has_active_accurate');
            }
        });

        Schema::table('buyback_devices', function (Blueprint $table) {
            if (Schema::hasColumn('buyback_devices', 'second_product_variant_id')) {
                if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
                    $table->dropForeign(['second_product_variant_id']);
                }
                $table->dropColumn('second_product_variant_id');
            }
        });
    }
};
