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
        Schema::table('warranty_claims', function (Blueprint $table) {
            $table->foreignId('receiving_inspection_id')->nullable()->after('warranty_id')->constrained('device_inspections')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warranty_claims', function (Blueprint $table) {
            //
        });
    }
};
