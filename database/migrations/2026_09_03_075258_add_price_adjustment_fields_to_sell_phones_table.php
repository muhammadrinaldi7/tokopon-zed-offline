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
            $table->unsignedBigInteger('price_adjusted_by')->nullable()->after('is_price_adjusted');
            $table->text('price_adjustment_reason')->nullable()->after('price_adjusted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sell_phones', function (Blueprint $table) {
            $table->dropColumn(['price_adjusted_by', 'price_adjustment_reason']);
        });
    }
};
