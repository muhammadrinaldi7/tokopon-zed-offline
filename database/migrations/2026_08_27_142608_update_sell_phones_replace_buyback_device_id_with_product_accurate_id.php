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
        Schema::table('sell_phones', function (Blueprint $table) {
            $table->dropColumn('buyback_device_id');
            
            // Add new column
            $table->foreignId('product_accurate_id')->nullable()->constrained('product_accurates')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sell_phones', function (Blueprint $table) {
            $table->dropForeign(['product_accurate_id']);
            $table->dropColumn('product_accurate_id');
            
            $table->foreignId('buyback_device_id')->nullable()->constrained('buyback_devices')->nullOnDelete();
        });
    }
};
