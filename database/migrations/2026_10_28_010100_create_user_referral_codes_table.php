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
        Schema::create('user_referral_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('code', 30)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('total_clicks')->default(0);
            $table->unsignedInteger('total_registered')->default(0);
            $table->unsignedInteger('total_converted_orders')->default(0);
            $table->timestamps();

            $table->index('code');
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_referral_codes');
    }
};