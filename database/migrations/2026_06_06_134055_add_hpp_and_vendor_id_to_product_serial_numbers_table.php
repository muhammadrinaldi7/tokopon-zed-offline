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
        Schema::table('product_serial_numbers', function (Blueprint $table) {
            // 1. Menambahkan field HPP (Harga Pokok Penjualan)
            // Menggunakan decimal (15 digit total, 2 digit di belakang koma) dan nullable agar fleksibel
            $table->decimal('hpp', 15, 2)->nullable()->default(0)->after('status');

            // 2. Menambahkan Relasi ke tabel vendors (Foreign Key)
            // Menggunakan nullable() karena mungkin ada data SN lama yang belum punya vendor_id
            $table->foreignId('vendor_id')
                ->nullable()
                ->after('hpp')
                ->constrained('vendors')
                ->onDelete('set null'); // Jika vendor dihapus, vendor_id di SN berubah jadi null (aman)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_serial_numbers', function (Blueprint $table) {
            // Wajib hapus foreign key-nya dulu sebelum menghapus kolomnya
            $table->dropForeign(['vendor_id']);

            // Hapus kolomnya jika migrasi di-rollback
            $table->dropColumn(['hpp', 'vendor_id']);
        });
    }
};
