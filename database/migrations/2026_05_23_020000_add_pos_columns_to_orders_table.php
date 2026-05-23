<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_channel')->default('ONLINE')->after('order_status'); // POS or ONLINE
            $table->foreignId('handled_by')->nullable()->after('order_channel')->constrained('users')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->after('handled_by')->constrained('payment_methods')->nullOnDelete();
            $table->string('accurate_invoice_no')->nullable()->after('payment_method_id');
            $table->string('accurate_receipt_no')->nullable()->after('accurate_invoice_no');
            $table->decimal('mdr_percentage', 5, 2)->default(0)->after('discount_amount'); // e.g. 0.70 for QRIS
            $table->decimal('mdr_amount', 15, 2)->default(0)->after('mdr_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['handled_by']);
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn([
                'order_channel', 'handled_by', 'payment_method_id',
                'accurate_invoice_no', 'accurate_receipt_no',
                'mdr_percentage', 'mdr_amount'
            ]);
        });
    }
};
