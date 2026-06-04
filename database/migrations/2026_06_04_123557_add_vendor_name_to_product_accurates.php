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
        Schema::table('product_accurates', function (Blueprint $table) {
            $table->string('vendor_name')->nullable()->after('base_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_accurates', function (Blueprint $table) {
            $table->dropColumn('vendor_name');
        });
    }
};
