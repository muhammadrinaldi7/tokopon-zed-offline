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
        Schema::create('service_center_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warranty_claim_id')->constrained('warranty_claims')->cascadeOnDelete();
            
            // Standard service form fields
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('device_type'); // e.g. iPhone 13 Pro Max
            $table->string('imei_sn');
            
            $table->text('physical_condition_on_receipt')->nullable(); // Cek fisik saat diterima
            $table->text('accessories_included')->nullable(); // Kelengkapan yang ditinggal
            $table->text('reported_issue'); // Keluhan utama
            
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->string('status')->default('received'); // received, in_progress, waiting_part, finished, picked_up, cancelled
            
            $table->text('technician_notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_center_tickets');
    }
};
