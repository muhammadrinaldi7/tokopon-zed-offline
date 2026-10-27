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
        Schema::table('customer_deposits', function (Blueprint $table) {
            $table->string('refund_bank_no')->nullable()->after('notes');
            $table->string('refund_customer_bank_name')->nullable()->after('refund_bank_no');
            $table->string('refund_customer_account_no')->nullable()->after('refund_customer_bank_name');
            $table->string('refund_customer_account_name')->nullable()->after('refund_customer_account_no');
            $table->string('refund_receipt_path')->nullable()->after('refund_customer_account_name');
            $table->timestamp('refunded_at')->nullable()->after('refund_receipt_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_deposits', function (Blueprint $table) {
            $table->dropColumn([
                'refund_bank_no',
                'refund_customer_bank_name',
                'refund_customer_account_no',
                'refund_customer_account_name',
                'refund_receipt_path',
                'refunded_at',
            ]);
        });
    }
};
