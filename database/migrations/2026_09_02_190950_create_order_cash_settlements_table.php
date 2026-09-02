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
        Schema::create('order_cash_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('handled_by')->constrained('users')->cascadeOnDelete();
            $table->decimal('nominal_tunai', 15, 2);
            $table->decimal('nominal_settle', 15, 2);
            $table->decimal('selisih', 15, 2)->default(0);
            $table->foreignId('monitoring_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('settled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_cash_settlements');
    }
};
