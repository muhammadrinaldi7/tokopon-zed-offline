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
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
        });

        Schema::table('second_products', function (Blueprint $table) {
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_tables', function (Blueprint $table) {
            //
        });
    }
};
