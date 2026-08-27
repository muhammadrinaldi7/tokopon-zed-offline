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
        Schema::table('sell_phones', function (Blueprint $table) {
            $table->unsignedBigInteger('original_appraised_value')->nullable()->after('appraised_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sell_phones', function (Blueprint $table) {
            $table->dropColumn('original_appraised_value');
        });
    }
};
