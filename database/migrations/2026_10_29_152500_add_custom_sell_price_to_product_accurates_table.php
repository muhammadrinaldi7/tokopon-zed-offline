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
        if (!Schema::hasColumn('product_accurates', 'custom_sell_price')) {
            Schema::table('product_accurates', function (Blueprint $table) {
                $table->decimal('custom_sell_price', 15, 2)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('product_accurates', 'custom_sell_price')) {
            Schema::table('product_accurates', function (Blueprint $table) {
                $table->dropColumn('custom_sell_price');
            });
        }
    }
};
