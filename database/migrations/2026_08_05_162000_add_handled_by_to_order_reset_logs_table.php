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
        Schema::table('order_reset_logs', function (Blueprint $table) {
            $table->foreignId('previous_handled_by')->nullable()->after('previous_status')->constrained('users')->nullOnDelete();
            $table->foreignId('new_handled_by')->nullable()->after('previous_handled_by')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_reset_logs', function (Blueprint $table) {
            $table->dropForeign(['previous_handled_by']);
            $table->dropForeign(['new_handled_by']);
            $table->dropColumn(['previous_handled_by', 'new_handled_by']);
        });
    }
};
