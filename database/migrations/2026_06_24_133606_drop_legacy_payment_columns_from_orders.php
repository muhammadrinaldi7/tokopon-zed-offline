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
            $table->dropForeign(['payment_method_id']);
            $table->dropForeign(['payment_method_rate_id']);
            $table->dropColumn([
                'payment_method_id',
                'payment_method_rate_id',
                'mdr_percentage',
                'mdr_amount',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->unsignedBigInteger('payment_method_rate_id')->nullable();
            $table->decimal('mdr_percentage', 5, 2)->default(0);
            $table->decimal('mdr_amount', 15, 2)->default(0);

            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('set null');
            $table->foreign('payment_method_rate_id')->references('id')->on('payment_method_rates')->onDelete('set null');
        });
    }
};
