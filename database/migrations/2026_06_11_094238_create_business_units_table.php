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
        Schema::create('business_units', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // syihab, gsk, khadija
            $table->string('name');
            $table->string('accurate_host')->nullable();
            $table->text('accurate_token')->nullable();
            $table->text('accurate_secret_key')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Data Seeder to ensure zero downtime
        \App\Models\BusinessUnit::create([
            'code' => 'syihab',
            'name' => 'Syihab',
            'accurate_host' => env('ACCURATE_HOST', ''),
            'accurate_token' => env('ACCURATE_TOKEN', ''),
            'accurate_secret_key' => env('ACCURATE_SECRET_KEY', ''),
            'is_active' => true,
        ]);

        \App\Models\BusinessUnit::create([
            'code' => 'second',
            'name' => 'GSK Second',
            'accurate_host' => env('ACCURATE_HOST_SECOND', ''),
            'accurate_token' => env('ACCURATE_TOKEN_SECOND', ''),
            'accurate_secret_key' => env('ACCURATE_SECRET_KEY_SECOND', ''),
            'is_active' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_units');
    }
};
