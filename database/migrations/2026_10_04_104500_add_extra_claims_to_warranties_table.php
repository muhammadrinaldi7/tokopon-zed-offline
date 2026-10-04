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
        Schema::table('warranties', function (Blueprint $table) {
            if (!Schema::hasColumn('warranties', 'extra_claims')) {
                $table->integer('extra_claims')->default(0)->after('replacement_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warranties', function (Blueprint $table) {
            if (Schema::hasColumn('warranties', 'extra_claims')) {
                $table->dropColumn('extra_claims');
            }
        });
    }
};
