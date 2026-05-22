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
            $table->dropForeign(['target_product_id']);
            $table->dropForeign(['product_variant_id']);

            $table->string('target_product_type')->nullable()->after('target_product_id');
            $table->string('product_variant_type')->nullable()->after('product_variant_id');
        });

        // Set default type to SecondProduct for existing data
        \Illuminate\Support\Facades\DB::table('trade_ins')->update([
            'target_product_type' => 'App\Models\SecondProduct',
            'product_variant_type' => 'App\Models\SecondProductVariant',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trade_ins', function (Blueprint $table) {
            $table->dropColumn('target_product_type');
            $table->dropColumn('product_variant_type');
            
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
};
