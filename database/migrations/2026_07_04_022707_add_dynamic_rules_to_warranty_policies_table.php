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
        Schema::table('warranty_policies', function (Blueprint $table) {
            $table->enum('replacement_type', ['continue', 'reset'])->default('continue')->comment('continue = teruskan masa aktif lama, reset = garansi penuh baru');
            $table->integer('max_claims')->default(1)->comment('Batas maksimal pelanggan boleh melakukan klaim per garansi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warranty_policies', function (Blueprint $table) {
            $table->dropColumn(['replacement_type', 'max_claims']);
        });
    }
};
