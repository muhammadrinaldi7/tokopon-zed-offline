<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->boolean('is_bundle')->default(false)->after('apply_to_all_items');
            $table->integer('max_reward_qty')->nullable()->after('is_bundle');
        });
    }

    public function down(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->dropColumn(['is_bundle', 'max_reward_qty']);
        });
    }
};
