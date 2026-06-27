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
        Schema::table('warranty_policies', function (Blueprint $table) {
            // Drop old unused columns
            if (Schema::hasColumn('warranty_policies', 'brand_id')) {
                $table->dropForeign(['brand_id']);
                $table->dropColumn('brand_id');
            }
            if (Schema::hasColumn('warranty_policies', 'item_category')) {
                $table->dropColumn('item_category');
            }
            if (Schema::hasColumn('warranty_policies', 'max_claims')) {
                $table->dropColumn('max_claims');
            }

            // Add new columns
            $table->string('type')->change(); // make sure it can accept store_default, payment_override, insurance
            $table->string('coverage_type')->default('ganti_unit')->after('type'); // full_cover, ganti_unit
            $table->string('brand_rule')->default('all_brands')->after('duration_days'); // all_brands, include, exclude
            $table->json('brand_list')->nullable()->after('brand_rule');
            $table->string('payment_keywords')->nullable()->after('brand_list');
            $table->integer('priority')->default(0)->after('payment_keywords');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warranty_policies', function (Blueprint $table) {
            $table->dropColumn(['coverage_type', 'brand_rule', 'brand_list', 'payment_keywords', 'priority']);
            
            $table->foreignId('brand_id')->nullable()->constrained('brands');
            $table->string('item_category')->nullable();
            $table->integer('max_claims')->default(1);
        });
    }
};
