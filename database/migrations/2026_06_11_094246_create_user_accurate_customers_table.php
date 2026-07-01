<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Buat Tabel
        Schema::create('user_accurate_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_unit_id')->constrained()->cascadeOnDelete();
            $table->string('accurate_customer_id')->nullable();
            $table->string('accurate_customer_no')->nullable();
            $table->timestamps();
        });

        // 2. Data Migration (Optimized)
        // Gunakan DB facade agar lebih aman dan tidak bergantung pada Model Eloquent
        $syihabUnit = DB::table('business_units')->where('code', 'syihab')->first();

        if ($syihabUnit) {
            $now = now();

            // Ambil data per 500 baris agar RAM tidak jebol
            DB::table('users')
                ->whereNotNull('accurate_customer_no')
                ->orderBy('id')
                ->chunk(500, function ($users) use ($syihabUnit, $now) {
                    $insertData = [];

                    foreach ($users as $user) {
                        $insertData[] = [
                            'user_id' => $user->id,
                            'business_unit_id' => $syihabUnit->id,
                            'accurate_customer_id' => $user->accurate_customer_id,
                            'accurate_customer_no' => $user->accurate_customer_no,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    // Insert 500 baris sekaligus dalam 1 query!
                    DB::table('user_accurate_customers')->insert($insertData);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_accurate_customers');
    }
};
