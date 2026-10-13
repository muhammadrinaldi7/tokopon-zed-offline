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
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'assigned_to_user_id')) {
                $table->foreignId('assigned_to_user_id')
                    ->nullable()
                    ->after('business_unit_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('conversations', 'assigned_at')) {
                $table->timestamp('assigned_at')
                    ->nullable()
                    ->after('assigned_to_user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'assigned_to_user_id')) {
                $table->dropForeign(['assigned_to_user_id']);
                $table->dropColumn(['assigned_to_user_id', 'assigned_at']);
            }
        });
    }
};
