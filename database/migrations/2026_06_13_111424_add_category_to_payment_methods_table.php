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
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('category')->default('NON-TUNAI')->after('name');
        });

        // Seed existing data
        \Illuminate\Support\Facades\DB::table('payment_methods')->get()->each(function ($method) {
            $category = stripos($method->name, 'tunai') !== false ? 'TUNAI' : 'NON-TUNAI';
            \Illuminate\Support\Facades\DB::table('payment_methods')
                ->where('id', $method->id)
                ->update(['category' => $category]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
