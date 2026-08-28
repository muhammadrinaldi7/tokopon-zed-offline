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
            $table->string('telegram_approval_webhook')->nullable()->after('is_active');
            $table->string('telegram_log_webhook')->nullable()->after('telegram_approval_webhook');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_units', function (Blueprint $table) {
            $table->dropColumn(['telegram_approval_webhook', 'telegram_log_webhook']);
        });
    }
};
