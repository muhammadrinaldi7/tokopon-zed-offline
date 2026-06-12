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
        Schema::create('order_accurate_docs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('doc_type'); // e.g. SALES_ORDER, DP_INVOICE, DP_RECEIPT, SALES_INVOICE, SALES_RECEIPT
            $table->string('doc_number')->nullable(); // accurate document number
            $table->bigInteger('accurate_id')->nullable(); // accurate document id
            $table->decimal('amount', 15, 2)->default(0); // amount related to this document
            $table->string('status')->nullable(); // e.g. SUCCESS, ERROR
            $table->json('payload')->nullable(); // Optional: to debug payload sent/received
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_accurate_docs');
    }
};
