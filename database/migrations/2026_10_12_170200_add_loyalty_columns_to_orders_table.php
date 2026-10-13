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
            if (!Schema::hasColumn('orders', 'loyalty_points_earned')) {
                $table->integer('loyalty_points_earned')->default(0)->after('grand_total');
            }
            if (!Schema::hasColumn('orders', 'loyalty_points_redeemed')) {
                $table->integer('loyalty_points_redeemed')->default(0)->after('loyalty_points_earned');
            }
            if (!Schema::hasColumn('orders', 'loyalty_discount_amount')) {
                $table->decimal('loyalty_discount_amount', 12, 2)->default(0)->after('loyalty_points_redeemed');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['loyalty_points_earned', 'loyalty_points_redeemed', 'loyalty_discount_amount']);
        });
    }
};
