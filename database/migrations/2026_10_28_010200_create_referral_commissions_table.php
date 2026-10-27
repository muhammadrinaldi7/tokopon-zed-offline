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
        Schema::create('referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->decimal('order_amount', 15, 2)->default(0);
            $table->string('commission_type', 20)->default('PERCENTAGE'); // PERCENTAGE, FIXED
            $table->decimal('commission_rate', 10, 2)->default(0);       // e.g. 1.00 (%) or fixed 50000
            $table->decimal('commission_amount', 15, 2)->default(0);     // Total commission rupiah
            $table->string('status', 20)->default('PENDING');            // PENDING, APPROVED, VOID, PAID
            $table->timestamp('hold_until')->nullable();                 // Cooling-off release date (Order COMPLETED + 7 days)
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['referrer_id', 'status']);
            $table->index(['referee_id', 'order_id']);
            $table->index('hold_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_commissions');
    }
};