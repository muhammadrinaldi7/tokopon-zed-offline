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
        Schema::table('qc_templates', function (Blueprint $table) {
            $table->foreignId('business_unit_id')->nullable()->after('id')->constrained('business_units')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qc_templates', function (Blueprint $table) {
            $table->dropForeign(['business_unit_id']);
            $table->dropColumn('business_unit_id');
        });
    }
};
