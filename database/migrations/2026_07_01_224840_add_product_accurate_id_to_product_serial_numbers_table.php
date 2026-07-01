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
        if (!Schema::hasColumn('product_serial_numbers', 'product_accurate_id')) {
            Schema::table('product_serial_numbers', function (Blueprint $table) {
                $table->foreignId('product_accurate_id')->nullable()->constrained('product_accurates')->nullOnDelete();
            });
        }

        // Backfill data
        DB::table('product_serial_numbers')
            ->orderBy('id')
            ->chunk(100, function ($sns) {
                foreach ($sns as $sn) {
                    if ($sn->business_unit_id) {
                        $pa = DB::table('product_accurates')
                            ->where('item_no', $sn->item_no)
                            ->where('business_unit_id', $sn->business_unit_id)
                            ->first();
                        
                        if ($pa) {
                            DB::table('product_serial_numbers')
                                ->where('id', $sn->id)
                                ->update(['product_accurate_id' => $pa->id]);
                        }
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
            $table->dropForeign(['product_accurate_id']);
            $table->dropColumn('product_accurate_id');
        });
    }
};
