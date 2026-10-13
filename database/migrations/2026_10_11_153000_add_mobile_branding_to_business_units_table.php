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
        Schema::table('business_units', function (Blueprint $table) {
            if (!Schema::hasColumn('business_units', 'brand_color')) {
                $table->string('brand_color', 20)->nullable()->after('mobile_description');
            }
            if (!Schema::hasColumn('business_units', 'warranty_highlight')) {
                $table->string('warranty_highlight', 255)->nullable()->after('brand_color');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_units', function (Blueprint $table) {
            if (Schema::hasColumn('business_units', 'brand_color')) {
                $table->dropColumn('brand_color');
            }
            if (Schema::hasColumn('business_units', 'warranty_highlight')) {
                $table->dropColumn('warranty_highlight');
            }
        });
    }
};
