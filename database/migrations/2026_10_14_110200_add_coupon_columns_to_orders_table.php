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
            if (!Schema::hasColumn('orders', 'coupon_id')) {
                $table->foreignId('coupon_id')->nullable()->after('loyalty_discount_amount')->constrained('ecommerce_coupons')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'coupon_code')) {
                $table->string('coupon_code', 50)->nullable()->after('coupon_id');
            }
            if (!Schema::hasColumn('orders', 'coupon_discount_amount')) {
                $table->decimal('coupon_discount_amount', 15, 2)->default(0)->after('coupon_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'coupon_id')) {
                $table->dropForeign(['coupon_id']);
                $table->dropColumn('coupon_id');
            }
            if (Schema::hasColumn('orders', 'coupon_code')) {
                $table->dropColumn('coupon_code');
            }
            if (Schema::hasColumn('orders', 'coupon_discount_amount')) {
                $table->dropColumn('coupon_discount_amount');
            }
        });
    }
};
