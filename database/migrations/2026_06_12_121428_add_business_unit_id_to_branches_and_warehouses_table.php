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
        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
        });

        // Data Migration Script
        $gskUnit = \App\Models\BusinessUnit::where('name', 'like', '%gsk%')
            ->orWhere('name', 'like', '%gadget%')
            ->orWhere('code', 'second')
            ->first();
            
        $syihabUnit = \App\Models\BusinessUnit::where('name', 'not like', '%gsk%')
            ->where('name', 'not like', '%gadget%')
            ->where('code', '!=', 'second')
            ->first();

        if ($gskUnit && $syihabUnit) {
            $branches = \App\Models\Branch::all();
            foreach ($branches as $branch) {
                $isGsk = str_contains(strtolower($branch->name), 'gsk') || str_contains(strtolower($branch->name), 'gadget');
                $branch->business_unit_id = $isGsk ? $gskUnit->id : $syihabUnit->id;
                $branch->save();
            }

            $warehouses = \App\Models\Warehouse::all();
            foreach ($warehouses as $warehouse) {
                $isGsk = str_contains(strtolower($warehouse->name), 'gsk') || str_contains(strtolower($warehouse->name), 'gadget');
                $warehouse->business_unit_id = $isGsk ? $gskUnit->id : $syihabUnit->id;
                $warehouse->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['business_unit_id']);
            $table->dropColumn('business_unit_id');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropForeign(['business_unit_id']);
            $table->dropColumn('business_unit_id');
        });
    }
};
