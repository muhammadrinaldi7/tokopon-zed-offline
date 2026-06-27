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
        // 1. Update warranties table
        Schema::table('warranties', function (Blueprint $table) {
            $table->string('type')->nullable()->after('serial_number'); // full_cover, ganti_unit
            $table->integer('duration_days')->nullable()->after('type');
        });

        // 2. Update warranty_claims table
        Schema::table('warranty_claims', function (Blueprint $table) {
            $table->string('resolution_type')->nullable()->after('status'); // ganti_unit, service_center
            $table->text('technician_notes')->nullable()->after('resolution_type');
        });

        // 3. Create warranty_replacements table
        Schema::create('warranty_replacements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warranty_claim_id')->constrained()->onDelete('cascade');
            $table->string('old_imei');
            $table->string('new_imei');
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('system_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranty_replacements');

        Schema::table('warranty_claims', function (Blueprint $table) {
            $table->dropColumn(['resolution_type', 'technician_notes']);
        });

        Schema::table('warranties', function (Blueprint $table) {
            $table->dropColumn(['type', 'duration_days']);
        });
    }
};
