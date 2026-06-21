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
        Schema::create('migration_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('migration_invoice_id')->constrained('migration_invoices')->cascadeOnDelete();
            $table->string('item_code'); // Kode Barang di Accurate
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->default('UNIT'); // Satuan
            $table->decimal('unit_price', 15, 2);    // Harga Satuan
            $table->string('warehouse_name')->nullable(); // Nama Gudang
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('migration_invoice_items');
    }
};
