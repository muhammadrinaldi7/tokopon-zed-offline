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
            $table->boolean('is_wa_sent')->default(false)->after('status');
            $table->boolean('is_email_sent')->default(false)->after('is_wa_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sell_phones', function (Blueprint $table) {
            $table->dropColumn(['is_wa_sent', 'is_email_sent']);
        });
    }
};
