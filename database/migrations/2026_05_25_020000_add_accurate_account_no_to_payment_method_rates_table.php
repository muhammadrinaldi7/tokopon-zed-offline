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
        Schema::table('payment_method_rates', function (Blueprint $table) {
            $table->string('accurate_account_no')->nullable()->after('mdr_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_method_rates', function (Blueprint $table) {
            $table->dropColumn('accurate_account_no');
        });
    }
};
