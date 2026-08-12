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
            $table->foreignId('business_unit_id')->nullable()->after('user_id')->constrained('business_units')->nullOnDelete();
            $table->decimal('balance', 15, 2)->default(0)->after('amount');
        });

        // Update balance for existing available deposits
        \Illuminate\Support\Facades\DB::table('customer_deposits')->where('status', 'AVAILABLE')->update([
            'balance' => \Illuminate\Support\Facades\DB::raw('amount')
        ]);

        Schema::table('customer_deposits', function (Blueprint $table) {
            $table->renameColumn('order_id', 'origin_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_deposits', function (Blueprint $table) {
            $table->renameColumn('origin_order_id', 'order_id');
        });

        Schema::table('customer_deposits', function (Blueprint $table) {
            $table->dropForeign(['business_unit_id']);
            $table->dropColumn(['business_unit_id', 'balance']);
        });
    }
};
