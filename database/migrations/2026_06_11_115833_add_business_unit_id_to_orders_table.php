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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('business_unit_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        // Set default business_unit_id for existing orders
        $syihabUnit = \App\Models\BusinessUnit::where('code', 'syihab')->first();
        if ($syihabUnit) {
            \App\Models\Order::whereNull('business_unit_id')->update(['business_unit_id' => $syihabUnit->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['business_unit_id']);
            $table->dropColumn('business_unit_id');
        });
    }
};
