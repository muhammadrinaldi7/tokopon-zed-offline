<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('business_units', function (Blueprint $table) {
            if (!Schema::hasColumn('business_units', 'requires_inbound_qc')) {
                $table->boolean('requires_inbound_qc')->default(false)->after('receipt_show_discount');
            }
        });

        // Backfill prefix & requires_inbound_qc untuk unit bisnis eksisting
        DB::table('business_units')
            ->where('code', 'syihab')
            ->where(function ($q) {
                $q->whereNull('prefix')->orWhere('prefix', '');
            })
            ->update(['prefix' => 'SYB']);

        DB::table('business_units')
            ->where('code', 'second')
            ->update(['requires_inbound_qc' => true]);

        DB::table('business_units')
            ->where('code', 'second')
            ->where(function ($q) {
                $q->whereNull('prefix')->orWhere('prefix', '');
            })
            ->update(['prefix' => 'GSK']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_units', function (Blueprint $table) {
            if (Schema::hasColumn('business_units', 'requires_inbound_qc')) {
                $table->dropColumn('requires_inbound_qc');
            }
        });
    }
};

