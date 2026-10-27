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
            if (!Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code', 30)->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'referred_by_id')) {
                $table->foreignId('referred_by_id')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'registration_channel')) {
                $table->string('registration_channel', 30)->default('POS')->after('referred_by_id');
            }
            if (!Schema::hasColumn('users', 'mobile_activated_at')) {
                $table->timestamp('mobile_activated_at')->nullable()->after('registration_channel');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'referred_by_id')) {
                $table->dropForeign(['referred_by_id']);
                $table->dropColumn('referred_by_id');
            }
            if (Schema::hasColumn('users', 'referral_code')) {
                $table->dropColumn('referral_code');
            }
            if (Schema::hasColumn('users', 'registration_channel')) {
                $table->dropColumn('registration_channel');
            }
            if (Schema::hasColumn('users', 'mobile_activated_at')) {
                $table->dropColumn('mobile_activated_at');
            }
        });
    }
};