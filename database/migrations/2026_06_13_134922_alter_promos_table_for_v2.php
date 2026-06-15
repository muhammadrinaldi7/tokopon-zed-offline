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
            $table->boolean('is_multiply')->default(false)->after('is_active');
            $table->boolean('is_combinable')->default(true)->after('is_multiply');
            $table->integer('quota')->nullable()->after('is_combinable');
            $table->integer('used_quota')->default(0)->after('quota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->dropColumn(['is_multiply', 'is_combinable', 'quota', 'used_quota']);
        });
    }
};
