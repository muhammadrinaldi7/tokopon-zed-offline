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
        Schema::create('migration_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique(); // No Faktur
            $table->date('invoice_date');           // Tgl Faktur
            $table->string('vendor_id');            // No Pemasok (ID di Accurate)
            $table->string('branch_name')->nullable(); // Nama Cabang (opsional)
            $table->text('description')->nullable();   // Keterangan
            $table->boolean('is_exported')->default(false); // Status ekspor
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('migration_invoices');
    }
};
