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
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->integer('quantity_pushed')->default(0)->after('quantity_received');
        });

        Schema::table('device_inspections', function (Blueprint $table) {
            $table->boolean('is_pushed')->default(false)->after('verdict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('quantity_pushed');
        });

        Schema::table('device_inspections', function (Blueprint $table) {
            $table->dropColumn('is_pushed');
        });
    }
};
