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
        Schema::create('user_accurate_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_unit_id')->constrained()->cascadeOnDelete();
            $table->string('accurate_customer_id')->nullable();
            $table->string('accurate_customer_no')->nullable();
            $table->timestamps();
        });

        // Data migration
        $syihabUnit = \App\Models\BusinessUnit::where('code', 'syihab')->first();
        if ($syihabUnit) {
            $users = \App\Models\User::whereNotNull('accurate_customer_no')->get();
            foreach ($users as $user) {
                \App\Models\UserAccurateCustomer::create([
                    'user_id' => $user->id,
                    'business_unit_id' => $syihabUnit->id,
                    'accurate_customer_id' => $user->accurate_customer_id,
                    'accurate_customer_no' => $user->accurate_customer_no,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_accurate_customers');
    }
};
