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
        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->string('brand_filter', 100)->nullable()->after('category_filter');
            $table->string('project_filter', 100)->nullable()->after('brand_filter');
        });

        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->foreignId('last_counted_by')->nullable()->after('difference_value')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->dropForeign(['last_counted_by']);
            $table->dropColumn('last_counted_by');
        });

        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropColumn(['brand_filter', 'project_filter']);
        });
    }
};
