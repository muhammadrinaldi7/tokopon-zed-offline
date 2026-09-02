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
            $table->unsignedBigInteger('product_accurate_id')->nullable()->change();
            $table->string('os_name')->nullable()->after('product_accurate_id');
            $table->string('category_name')->nullable()->after('os_name');
            $table->string('brand_name')->nullable()->after('category_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('buyback_devices', function (Blueprint $table) {
            $table->dropColumn(['os_name', 'category_name', 'brand_name']);
            // Revert product_accurate_id nullability is tricky as there may be nulls now. We skip reversing the nullability for safety.
        });
    }
};
