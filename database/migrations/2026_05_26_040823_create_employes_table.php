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
        Schema::create('employes', function (Blueprint $table) {
            $table->id();
            // --- FIELD KHUSUS SINKRONISASI ACCURATE ---
            // ID unik dari Accurate (sangat penting untuk updateOrCreate)
            $table->bigInteger('accurate_employee_id')->unique()->nullable();

            // Nomor/Kode Karyawan di Accurate (Cth: EMP001)
            $table->string('employee_no')->unique()->nullable();

            // --- DATA PERSONAL KARYAWAN ---
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('position')->nullable(); // Jabatan
            $table->boolean('is_active')->default(true); // Status Aktif di Accurate

            // --- INTEGRASI SISTEM LOKAL (OPSIONAL) ---
            // Hubungkan ke tabel users jika karyawan ini bisa login ke POS Laravel Anda
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Indexing untuk mempercepat pencarian nama saat transaksi POS
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employes');
    }
};
