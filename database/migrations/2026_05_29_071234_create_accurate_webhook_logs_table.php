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
        Schema::create('accurate_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type')->index();
            $table->string('database_source')->nullable();
            $table->string('event_id')->nullable()->index(); // ID hash atau referensi unik jika ada
            $table->json('payload')->nullable();
            $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending')->index();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accurate_webhook_logs');
    }
};
