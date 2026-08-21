<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_accurates', function (Blueprint $table) {
            $table->boolean('is_addon')->default(false)->after('itemType');
        });

        // Backfill: Produk existing dengan categoryName "ADD ON" -> is_addon = true
        DB::table('product_accurates')
            ->where('categoryName', 'like', '%ADD ON%')
            ->update(['is_addon' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_accurates', function (Blueprint $table) {
            $table->dropColumn('is_addon');
        });
    }
};
