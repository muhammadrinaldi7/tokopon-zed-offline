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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();

            // ID unik dari Accurate (nullable jika tidak semua vendor dari Accurate)
            $table->string('accurate_vendor_id')->nullable()->unique();

            // Nomor Vendor (biasanya berupa kode unik seperti VND-001, jadi gunakan string & unique)
            $table->string('vendor_no')->unique();

            // Nama Vendor
            $table->string('vendor_name');

            // Email & Phone (diberi nullable agar opsional jika data vendor belum lengkap)
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
