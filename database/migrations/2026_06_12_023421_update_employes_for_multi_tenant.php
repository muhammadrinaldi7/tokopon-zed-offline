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
        // 1. Tambahkan kolom business_unit_id
        Schema::table('employes', function (Blueprint $table) {
            $table->foreignId('business_unit_id')->nullable()->after('id')->constrained('business_units')->nullOnDelete();
        });

        // 2. Set semua karyawan yang sudah ada menjadi milik Syihab (biar aman)
        $syihabUnit = \App\Models\BusinessUnit::where('code', 'syihab')->first();
        if ($syihabUnit) {
            \Illuminate\Support\Facades\DB::table('employes')->update(['business_unit_id' => $syihabUnit->id]);
        }

        // 3. Ubah index unique lama menjadi composite unique (karena nama index bisa jadi 'employes_employee_no_unique' dll)
        Schema::table('employes', function (Blueprint $table) {
            // Drop unique index lama
            $table->dropUnique(['accurate_employee_id']);
            $table->dropUnique(['employee_no']);

            // Buat composite unique index baru
            $table->unique(['business_unit_id', 'accurate_employee_id'], 'emp_bu_acc_id_unique');
            $table->unique(['business_unit_id', 'employee_no'], 'emp_bu_emp_no_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employes', function (Blueprint $table) {
            $table->dropUnique('emp_bu_acc_id_unique');
            $table->dropUnique('emp_bu_emp_no_unique');

            $table->unique('accurate_employee_id');
            $table->unique('employee_no');

            $table->dropForeign(['business_unit_id']);
            $table->dropColumn('business_unit_id');
        });
    }
};
