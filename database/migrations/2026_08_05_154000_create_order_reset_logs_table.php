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
        Schema::create('order_reset_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reset_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('previous_status')->nullable();
            $table->decimal('previous_grand_total', 15, 2)->default(0);
            $table->string('previous_accurate_invoice_no')->nullable();
            $table->string('previous_accurate_receipt_no')->nullable();
            $table->string('previous_accurate_so_number')->nullable();
            $table->json('previous_payments_snapshot')->nullable();
            $table->json('previous_accurate_docs_snapshot')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_reset_logs');
    }
};
