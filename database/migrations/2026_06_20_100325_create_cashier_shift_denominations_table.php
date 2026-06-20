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
        Schema::create('cashier_shift_denominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_shift_id')->constrained('cashier_shifts')->cascadeOnDelete();
            $table->enum('type', ['opening', 'closing']);
            $table->decimal('denomination', 15, 2);
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->unique(['cashier_shift_id', 'type', 'denomination'], 'unique_denom_per_shift_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_shift_denominations');
    }
};
