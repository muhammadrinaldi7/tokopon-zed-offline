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
        Schema::create('sell_phone_reset_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sell_phone_id')->constrained('sell_phones')->cascadeOnDelete();
            $table->string('action_type', 50)->default('CORRECTION_SKU'); // CORRECTION_SKU, RESET_TO_DRAFT, CANCELLED
            $table->foreignId('reset_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('previous_status', 50)->nullable();
            $table->string('new_status', 50)->nullable();
            $table->string('previous_phone_model')->nullable();
            $table->string('new_phone_model')->nullable();
            $table->string('previous_item_no')->nullable();
            $table->string('new_item_no')->nullable();
            $table->unsignedBigInteger('previous_product_accurate_id')->nullable();
            $table->unsignedBigInteger('new_product_accurate_id')->nullable();
            $table->decimal('previous_appraised_value', 15, 2)->nullable();
            $table->decimal('new_appraised_value', 15, 2)->nullable();
            $table->string('previous_invoice_number')->nullable();
            $table->string('new_invoice_number')->nullable();
            $table->json('previous_accurate_docs_snapshot')->nullable();
            $table->json('previous_payments_snapshot')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sell_phone_reset_logs');
    }
};
