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
            $table->boolean('is_taxable')->default(false)->after('accurate_secret_key');
        });

        // Set syihab to true
        \Illuminate\Support\Facades\DB::table('business_units')
            ->where('code', 'syihab')
            ->update(['is_taxable' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_units', function (Blueprint $table) {
            $table->dropColumn('is_taxable');
        });
    }
};
