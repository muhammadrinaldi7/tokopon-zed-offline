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
            $table->string('customer_prefix', 10)->nullable()->after('code');
        });

        // Set default prefixes for existing legacy data
        \Illuminate\Support\Facades\DB::table('business_units')
            ->where('code', 'syihab')
            ->update(['customer_prefix' => 'SYB_']);

        \Illuminate\Support\Facades\DB::table('business_units')
            ->where('code', 'second')
            ->update(['customer_prefix' => 'GSK_']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_units', function (Blueprint $table) {
            $table->dropColumn('customer_prefix');
        });
    }
};
