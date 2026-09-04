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
        Schema::table('manual_discount_presets', function (Blueprint $table) {
            $table->unsignedBigInteger('business_unit_id')->nullable()->after('id');
            $table->foreign('business_unit_id')->references('id')->on('business_units')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manual_discount_presets', function (Blueprint $table) {
            $table->dropForeign(['business_unit_id']);
            $table->dropColumn('business_unit_id');
        });
    }
};
