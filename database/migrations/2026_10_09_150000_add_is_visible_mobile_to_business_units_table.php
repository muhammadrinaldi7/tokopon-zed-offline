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
        if (!Schema::hasColumn('business_units', 'is_visible_mobile')) {
            Schema::table('business_units', function (Blueprint $table) {
                $table->boolean('is_visible_mobile')->default(false)->after('is_active');
                $table->string('mobile_display_name')->nullable()->after('is_visible_mobile');
                $table->string('mobile_category')->nullable()->after('mobile_display_name'); // misal: 'BARU', 'SECOND', dll
                $table->text('mobile_description')->nullable()->after('mobile_category');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_units', function (Blueprint $table) {
            $table->dropColumn([
                'is_visible_mobile',
                'mobile_display_name',
                'mobile_category',
                'mobile_description',
            ]);
        });
    }
};
