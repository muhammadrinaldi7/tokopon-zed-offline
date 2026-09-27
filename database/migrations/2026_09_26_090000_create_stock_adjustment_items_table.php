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
        // 1. Modifikasi tabel header stock_adjustments
        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->integer('total_items')->default(1)->after('notes');
            $table->integer('total_quantity')->default(1)->after('total_items');
            $table->string('item_no')->nullable()->change();
            $table->string('product_name')->nullable()->change();
            $table->integer('quantity')->default(1)->nullable()->change();
        });

        // 2. Buat tabel detail stock_adjustment_items
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            
            // Arah mutasi per baris: OUT (Pengurangan) atau IN (Penambahan)
            $table->string('adjustment_type')->default('OUT');
            
            // Barang yang disesuaikan
            $table->string('item_no');
            $table->string('product_name');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_cost', 15, 2)->default(0);
            
            // Proyek master barang
            $table->string('proyek')->nullable();
            $table->string('project_no')->nullable();
            
            // Barang tujuan alokasi (Opsional)
            $table->string('target_item_no')->nullable();
            $table->string('target_product_name')->nullable();
            $table->string('target_serial_number')->nullable();
            
            // Serial Numbers untuk item ini jika ber-SN
            $table->json('serial_numbers')->nullable();
            $table->text('item_notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');

        Schema::table('stock_adjustments', function (Blueprint $table) {
            $table->dropColumn(['total_items', 'total_quantity']);
        });
    }
};
