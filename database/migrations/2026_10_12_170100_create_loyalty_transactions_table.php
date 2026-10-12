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
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loyalty_account_id')->constrained('loyalty_accounts')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->enum('channel', ['POS', 'MOBILE_APP', 'ADMIN_MANUAL'])->default('POS')->index();
            $table->enum('type', ['EARN', 'REDEEM', 'REFUND', 'ADJUSTMENT', 'EXPIRED'])->index();
            $table->integer('points'); // Positif untuk EARN/REFUND, Negatif untuk REDEEM
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('description', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
    }
};
