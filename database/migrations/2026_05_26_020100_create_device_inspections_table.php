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
        Schema::create('device_inspections', function (Blueprint $table) {
            $table->id();

            // ─── Identitas Unit Fisik ────────────────────────
            $table->string('imei')->index(); // IMEI sebagai cross-cutting ID per unit fisik

            // ─── Relasi ke Produk Second di Katalog ──────────
            $table->foreignId('second_product_variant_id')->nullable()
                  ->constrained('second_product_variants')
                  ->nullOnDelete();

            // ─── Template QC yang digunakan ──────────────────
            $table->foreignId('qc_template_id')->nullable()
                  ->constrained('qc_templates')->nullOnDelete();

            // ─── Konteks: dari proses apa inspeksi ini ───────
            // Bisa dari SellPhone, TradeIn, Order, atau standalone
            $table->nullableMorphs('inspectable');

            // ─── Label/keterangan kapan QC ini dilakukan ─────
            $table->string('label')->nullable();
            // Contoh: "QC Inbound", "QC Etalase", "QC Pre-Sale", "QC Serah Terima"

            // ─── Hasil Checklist ─────────────────────────────
            $table->json('checklist_results');
            // Format: [{name: "LCD", type: "boolean", value: true}, ...]

            // ─── Ringkasan Skor ──────────────────────────────
            $table->integer('passed_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->integer('total_items')->default(0);

            // ─── Verdict ─────────────────────────────────────
            $table->string('verdict')->default('pass'); // pass, fail, conditional

            $table->text('inspector_notes')->nullable();

            // ─── Siapa & Kapan ───────────────────────────────
            $table->foreignId('inspected_by')
                  ->constrained('users')->cascadeOnDelete();
            $table->timestamp('inspected_at')->useCurrent();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_inspections');
    }
};
