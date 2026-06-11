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
        Schema::table('warehouses', function (Blueprint $table) {
            $table->unsignedBigInteger('second_warehouse_id')->nullable()->after('warehouse_id');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->unsignedBigInteger('second_branch_id')->nullable()->after('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn('second_warehouse_id');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('second_branch_id');
        });
    }
};
