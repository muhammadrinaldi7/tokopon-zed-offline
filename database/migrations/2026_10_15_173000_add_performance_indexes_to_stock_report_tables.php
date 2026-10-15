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
        // 1. Index penting pada warehouse_stocks untuk mempercepat JOIN variant_id
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            // Index komposit variant_id & variant_type mengubah scan O(N^2) menjadi index seek O(1)
            $table->index(['variant_id', 'variant_type'], 'ws_variant_id_type_idx');
        });

        // 2. Index pada product_accurates untuk mempercepat filter BU, Merek, Kategori, dan SKU
        Schema::table('product_accurates', function (Blueprint $table) {
            $table->index(['business_unit_id', 'brandName'], 'pa_bu_brand_idx');
            $table->index(['business_unit_id', 'categoryName'], 'pa_bu_cat_idx');
            $table->index(['business_unit_id', 'item_no'], 'pa_bu_item_no_idx');
            $table->index('item_no', 'pa_item_no_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->dropIndex('ws_variant_id_type_idx');
        });

        Schema::table('product_accurates', function (Blueprint $table) {
            $table->dropIndex('pa_bu_brand_idx');
            $table->dropIndex('pa_bu_cat_idx');
            $table->dropIndex('pa_bu_item_no_idx');
            $table->dropIndex('pa_item_no_idx');
        });
    }
};
