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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number')->unique();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('warehouse_name')->nullable();
            
            // Tipe penyesuaian: OUT (Pengurangan) atau IN (Penambahan)
            $table->string('adjustment_type')->default('OUT');
            
            // SKU & Barang Utama yang Disesuaikan (dikirim ke Accurate)
            $table->string('item_no');
            $table->string('product_name');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_cost', 15, 2)->nullable();
            
            // Proyek (berdasarkan master barang)
            $table->string('proyek')->nullable();
            $table->string('project_no')->nullable();
            
            // SKU & Barang Tujuan Alokasi (Opsional, khusus pencatatan internal & deskripsi Accurate)
            $table->string('target_item_no')->nullable();
            $table->string('target_product_name')->nullable();
            $table->string('target_serial_number')->nullable();
            
            // Kategori Alasan & Detail
            $table->string('reason_category')->default('PEMELIHARAAN_INVENTARIS');
            $table->text('notes')->nullable();
            $table->json('serial_numbers')->nullable();
            
            // Integrasi Accurate Online
            $table->string('accurate_account_no')->nullable();
            $table->string('accurate_adjustment_no')->nullable();
            
            // Status Approval & Sinkronisasi
            // PENDING, APPROVED, REJECTED, SYNCED, FAILED_SYNC
            $table->string('status')->default('PENDING');
            $table->text('sync_error')->nullable();
            
            // Actor & Timestamps
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
