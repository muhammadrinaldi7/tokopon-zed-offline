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
        $syihabUnit = \App\Models\BusinessUnit::where('code', 'syihab')->first();
        $syihabId = $syihabUnit ? $syihabUnit->id : null;

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'business_unit',
                'accurate_customer_id',
                'accurate_customer_no',
                'accurate_customer_id_second',
                'accurate_customer_no_second'
            ]);
            $table->foreignId('business_unit_id')->nullable()->constrained()->nullOnDelete();
        });

        // Set default business_unit_id for existing users
        if ($syihabId) {
            \App\Models\User::whereNull('business_unit_id')->update(['business_unit_id' => $syihabId]);
        }

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('business_unit');
            $table->foreignId('business_unit_id')->nullable()->constrained()->nullOnDelete();
        });

        if ($syihabId) {
            \App\Models\PaymentMethod::whereNull('business_unit_id')->update(['business_unit_id' => $syihabId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropForeign(['business_unit_id']);
            $table->dropColumn('business_unit_id');
            $table->enum('business_unit', ['syihab', 'gsk', 'all'])->default('syihab');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['business_unit_id']);
            $table->dropColumn('business_unit_id');
            $table->string('business_unit')->nullable();
            $table->string('accurate_customer_id')->nullable();
            $table->string('accurate_customer_no')->nullable();
            $table->string('accurate_customer_id_second')->nullable();
            $table->string('accurate_customer_no_second')->nullable();
        });
    }
};
