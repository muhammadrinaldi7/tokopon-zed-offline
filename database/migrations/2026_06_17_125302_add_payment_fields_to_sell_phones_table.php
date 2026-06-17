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
        Schema::table('sell_phones', function (Blueprint $table) {
            $table->string('payment_receipt_path')->nullable()->after('status');
            $table->string('store_bank_no')->nullable()->after('payment_receipt_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sell_phones', function (Blueprint $table) {
            //
        });
    }
};
