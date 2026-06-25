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
        Schema::table('business_units', function (Blueprint $table) {
            $table->string('order_prefix', 20)->nullable()->after('customer_prefix');
            $table->string('draft_prefix', 20)->nullable()->after('order_prefix');
            $table->string('store_title', 100)->nullable()->after('draft_prefix');
            $table->boolean('receipt_show_discount')->default(false)->after('store_title');
        });

        // Set default values for existing legacy data
        \Illuminate\Support\Facades\DB::table('business_units')
            ->where('code', 'syihab')
            ->update([
                'order_prefix' => 'POS-SYB-',
                'draft_prefix' => 'POS-DRF-SYB-',
                'store_title' => 'SYIHAB STORE',
                'receipt_show_discount' => false
            ]);

        \Illuminate\Support\Facades\DB::table('business_units')
            ->where('code', 'second')
            ->update([
                'order_prefix' => 'POS-GSK-',
                'draft_prefix' => 'POS-DRF-GSK-',
                'store_title' => 'GSK STORE',
                'receipt_show_discount' => true
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_units', function (Blueprint $table) {
            $table->dropColumn(['order_prefix', 'draft_prefix', 'store_title', 'receipt_show_discount']);
        });
    }
};
