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
        Schema::table('trade_ins', function (Blueprint $table) {
            // Drop old foreign keys
            $table->dropForeign(['target_product_id']);
            $table->dropForeign(['product_variant_id']);

            // Re-add foreign keys pointing to new tables
            $table->foreign('target_product_id')
                  ->references('id')
                  ->on('second_products')
                  ->onDelete('cascade');

            $table->foreign('product_variant_id')
                  ->references('id')
                  ->on('second_product_variants')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trade_ins', function (Blueprint $table) {
            $table->dropForeign(['target_product_id']);
            $table->dropForeign(['product_variant_id']);

            $table->foreign('target_product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');

            $table->foreign('product_variant_id')
                  ->references('id')
                  ->on('product_variants')
                  ->onDelete('set null');
        });
    }
};
