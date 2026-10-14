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
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_hpp', 15, 2)->default(0)->after('subtotal')->comment('HPP per unit produk');
            $table->decimal('total_hpp', 15, 2)->default(0)->after('unit_hpp')->comment('Total HPP untuk baris item ini (unit_hpp * qty atau sum HPP SN)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['unit_hpp', 'total_hpp']);
        });
    }
};
