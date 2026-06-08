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
            $table->bigInteger('id_brand_accurate')->nullable();
            $table->string('brandName')->nullable();
            $table->bigInteger('id_category_accurate')->nullable();
            $table->string('categoryName')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_accurates', function (Blueprint $table) {
            $table->dropColumn('id_brand_accurate');
            $table->dropColumn('brandName');
            $table->dropColumn('id_category_accurate');
            $table->dropColumn('categoryName');
        });
    }
};
