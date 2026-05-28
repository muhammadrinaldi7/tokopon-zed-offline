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
        Schema::table('promos', function (Blueprint $table) {
            $table->decimal('min_transaction_amount', 15, 2)->nullable()->after('max_discount');
            $table->integer('min_qty')->nullable()->after('min_transaction_amount');
            $table->boolean('apply_to_all_items')->default(true)->after('min_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->dropColumn(['min_transaction_amount', 'min_qty', 'apply_to_all_items']);
        });
    }
};
