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
        Schema::table('product_variants', function (Blueprint $table) {
            $table->boolean('has_sn')->default(true)->after('stock');
        });
        
        if (Schema::hasTable('second_product_variants')) {
            Schema::table('second_product_variants', function (Blueprint $table) {
                $table->boolean('has_sn')->default(true)->after('stock');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('has_sn');
        });
        
        if (Schema::hasTable('second_product_variants')) {
            Schema::table('second_product_variants', function (Blueprint $table) {
                $table->dropColumn('has_sn');
            });
        }
    }
};
