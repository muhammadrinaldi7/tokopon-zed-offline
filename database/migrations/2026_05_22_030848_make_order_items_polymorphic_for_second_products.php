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
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->string('product_variant_type')->nullable()->after('product_variant_id');
        });
        
        // Update existing rows
        \Illuminate\Support\Facades\DB::table('order_items')->update([
            'product_variant_type' => \App\Models\ProductVariant::class
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('product_variant_type');
            $table->foreign('product_variant_id')->references('id')->on('product_variants');
        });
    }
};
