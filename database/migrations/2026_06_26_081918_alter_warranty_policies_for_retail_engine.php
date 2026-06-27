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
        Schema::table('warranty_policies', function (Blueprint $table) {
            // Kita drop kolom lama yang tidak relevan
            $table->dropColumn('payment_keywords');

            // Kita tambahkan kolom baru
            $table->string('type')->default('main_warranty')->change(); // SQLite drop enum is tricky, just change to string if possible, or we just drop and recreate type? Wait, SQLite enum change might fail. Let's just drop the column and recreate it as string.
            $table->string('item_condition')->default('all')->after('type'); // all, new_only, second_only
            $table->string('price_status')->default('all')->after('item_condition'); // all, normal_price, discounted_price
            $table->text('addon_trigger_keywords')->nullable()->after('brand_list');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warranty_policies', function (Blueprint $table) {
            $table->text('payment_keywords')->nullable();
            $table->dropColumn('item_condition');
            $table->dropColumn('price_status');
            $table->dropColumn('addon_trigger_keywords');
        });
    }
};
