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
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('payment_method_rate_id')->nullable()->constrained('payment_method_rates')->nullOnDelete();
        });

        Schema::table('order_payments', function (Blueprint $table) {
            $table->foreignId('payment_method_rate_id')->nullable()->constrained('payment_method_rates')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['payment_method_rate_id']);
            $table->dropColumn('payment_method_rate_id');
        });

        Schema::table('order_payments', function (Blueprint $table) {
            $table->dropForeign(['payment_method_rate_id']);
            $table->dropColumn('payment_method_rate_id');
        });
    }
};
