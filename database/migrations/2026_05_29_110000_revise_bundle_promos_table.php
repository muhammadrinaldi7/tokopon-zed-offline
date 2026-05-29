<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->enum('bundle_discount_type', ['fixed', 'percentage'])->nullable()->after('max_reward_qty');
            $table->decimal('bundle_discount_value', 15, 2)->nullable()->after('bundle_discount_type');
            $table->decimal('bundle_max_discount', 15, 2)->nullable()->after('bundle_discount_value');
            $table->renameColumn('max_reward_qty', 'bundle_max_qty');
        });

        Schema::rename('promo_bundle_triggers', 'promo_bundle_skus');
    }

    public function down(): void
    {
        Schema::rename('promo_bundle_skus', 'promo_bundle_triggers');
        
        Schema::table('promos', function (Blueprint $table) {
            $table->renameColumn('bundle_max_qty', 'max_reward_qty');
            $table->dropColumn(['bundle_discount_type', 'bundle_discount_value', 'bundle_max_discount']);
        });
    }
};
