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
            if (!Schema::hasColumn('users', 'push_token')) {
                $table->string('push_token')->nullable()->after('remember_token');
            }
        });

        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'push_token')) {
                $table->string('push_token')->nullable()->after('guest_phone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'push_token')) {
                $table->dropColumn('push_token');
            }
        });

        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'push_token')) {
                $table->dropColumn('push_token');
            }
        });
    }
};
