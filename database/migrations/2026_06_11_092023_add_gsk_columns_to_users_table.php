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
        Schema::table('users', function (Blueprint $table) {
            $table->string('accurate_customer_id_second')->nullable()->after('accurate_customer_no');
            $table->string('accurate_customer_no_second')->nullable()->after('accurate_customer_id_second');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['accurate_customer_id_second', 'accurate_customer_no_second']);
        });
    }
};
