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
        Schema::create('user_accurate_vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->string('accurate_vendor_id')->nullable();
            $table->string('accurate_vendor_no')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'business_unit_id']);
        });

        // Migrate existing vendor data to Syihab (assuming existing data belongs to the primary unit)
        $syihabUnit = \App\Models\BusinessUnit::where('code', 'syihab')->first();
        if ($syihabUnit) {
            $usersWithVendor = \Illuminate\Support\Facades\DB::table('users')
                ->whereNotNull('accurate_vendor_id')
                ->get();

            foreach ($usersWithVendor as $user) {
                \Illuminate\Support\Facades\DB::table('user_accurate_vendors')->insert([
                    'user_id' => $user->id,
                    'business_unit_id' => $syihabUnit->id,
                    'accurate_vendor_id' => $user->accurate_vendor_id,
                    'accurate_vendor_no' => $user->accurate_vendor_no,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Drop the old columns from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['accurate_vendor_id', 'accurate_vendor_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('accurate_vendor_id')->nullable();
            $table->string('accurate_vendor_no')->nullable();
        });

        Schema::dropIfExists('user_accurate_vendors');
    }
};
