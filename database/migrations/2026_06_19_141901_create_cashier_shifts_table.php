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
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->date('shift_date');
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->decimal('starting_cash', 15, 2);
            $table->decimal('expected_cash', 15, 2)->nullable();
            $table->decimal('actual_cash', 15, 2)->nullable();
            $table->decimal('cash_difference', 15, 2)->nullable();
            $table->decimal('total_cash_sales', 15, 2)->default(0);
            $table->decimal('total_non_cash_sales', 15, 2)->default(0);
            $table->unsignedInteger('total_transactions')->default(0);
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->enum('reconciliation_status', ['balanced', 'over', 'short'])->nullable();
            $table->timestamps();

            // Constraint: 1 kasir hanya boleh punya 1 shift open di satu waktu. (Sesuai Opsi A - 1 shift/hari, tidak perlu diubah, karena jika kita pakai Opsi A: 1 shift/hari, berarti kasir tidak boleh buka shift lagi kalau sudah tutup. Wait, Opsi A: 1 kasir = 1 shift per hari (jika sudah tutup, tidak bisa buka lagi).
            // Kalau begitu constraintnya adalah unik pada user_id dan shift_date terlepas dari statusnya.
            $table->unique(['user_id', 'shift_date'], 'unique_shift_per_day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_shifts');
    }
};
