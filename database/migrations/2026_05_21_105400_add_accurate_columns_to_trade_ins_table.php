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
        Schema::table('trade_ins', function (Blueprint $table) {
            $table->string('purchase_invoice_number')->nullable();
            $table->string('sales_invoice_number')->nullable();
            $table->string('sales_receipt_number')->nullable();
            $table->decimal('topup_amount', 15, 2)->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trade_ins', function (Blueprint $table) {
            $table->dropForeign(['handled_by']);
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn([
                'purchase_invoice_number',
                'sales_invoice_number',
                'sales_receipt_number',
                'topup_amount',
                'handled_by',
                'product_variant_id'
            ]);
        });
    }
};
