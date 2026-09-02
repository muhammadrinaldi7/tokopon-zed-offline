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
        Schema::table('buyback_tiers', function (Blueprint $table) {
            $table->dropColumn(['min_price', 'max_price']);
        });

        Schema::table('buyback_devices', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['second_product_variant_id']);
            $table->dropColumn([
                'brand_id',
                'model_name',
                'ram',
                'storage',
                'base_price',
                'second_product_variant_id',
                'color'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buyback_tiers', function (Blueprint $table) {
            $table->decimal('min_price', 15, 2)->nullable();
            $table->decimal('max_price', 15, 2)->nullable();
        });

        Schema::table('buyback_devices', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->constrained('brands')->cascadeOnDelete();
            $table->string('model_name')->nullable();
            $table->string('ram')->nullable();
            $table->string('storage')->nullable();
            $table->decimal('base_price', 15, 2)->nullable();
            $table->foreignId('second_product_variant_id')->nullable()->constrained('second_product_variants')->nullOnDelete();
            $table->string('color')->nullable();
        });
    }
};
