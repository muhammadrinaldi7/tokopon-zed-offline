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
        Schema::create('accurate_gl_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_no');
            $table->string('name');
            $table->string('account_type')->nullable();
            $table->string('database_source')->default('syihab');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accurate_gl_accounts');
    }
};
