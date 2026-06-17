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
        Schema::table('buyback_devices', function (Blueprint $table) {
            $table->unsignedBigInteger('product_accurate_id')->nullable()->after('brand_id');
            // make second_product_variant_id nullable if it's not already
            $table->unsignedBigInteger('second_product_variant_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buyback_devices', function (Blueprint $table) {
            $table->dropColumn('product_accurate_id');
        });
    }
};
