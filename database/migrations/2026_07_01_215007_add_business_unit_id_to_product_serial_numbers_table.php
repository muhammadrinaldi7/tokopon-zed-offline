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
        if (!Schema::hasColumn('product_serial_numbers', 'business_unit_id')) {
            Schema::table('product_serial_numbers', function (Blueprint $table) {
                $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
            });
        }

        // Populate business_unit_id from warehouse_id using Query Builder for DB agnostic
        DB::table('product_serial_numbers')
            ->whereNotNull('warehouse_id')
            ->orderBy('id')
            ->chunk(100, function ($sns) {
                foreach ($sns as $sn) {
                    $warehouse = DB::table('warehouses')->where('id', $sn->warehouse_id)->first();
                    if ($warehouse && $warehouse->business_unit_id) {
                        DB::table('product_serial_numbers')
                            ->where('id', $sn->id)
                            ->update(['business_unit_id' => $warehouse->business_unit_id]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_serial_numbers', function (Blueprint $table) {
            $table->dropForeign(['business_unit_id']);
            $table->dropColumn('business_unit_id');
        });
    }
};
