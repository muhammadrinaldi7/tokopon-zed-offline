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
        Schema::table('product_accurates', function (Blueprint $table) {
            $table->boolean('has_sn')->default(false)->after('stock');
            $table->foreignId('business_unit_id')->nullable()->after('has_sn')->constrained('business_units')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_accurates', function (Blueprint $table) {
            $table->dropForeign(['business_unit_id']);
            $table->dropColumn(['has_sn', 'business_unit_id']);
        });
    }
};
