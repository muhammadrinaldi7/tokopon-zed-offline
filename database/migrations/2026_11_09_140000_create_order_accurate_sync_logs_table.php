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
        // 1. Tambahkan kolom pendukung pelacakan sinkronisasi Accurate pada tabel orders
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'accurate_sync_status')) {
                $table->string('accurate_sync_status', 20)->default('PENDING')->after('accurate_receipt_no')->index();
            }
            if (!Schema::hasColumn('orders', 'accurate_sync_attempts')) {
                $table->unsignedInteger('accurate_sync_attempts')->default(0)->after('accurate_sync_status');
            }
            if (!Schema::hasColumn('orders', 'accurate_sync_last_error')) {
                $table->text('accurate_sync_last_error')->nullable()->after('accurate_sync_attempts');
            }
            if (!Schema::hasColumn('orders', 'accurate_synced_at')) {
                $table->timestamp('accurate_synced_at')->nullable()->after('accurate_sync_last_error');
            }
        });

        // 2. Buat tabel audit log riwayat sinkronisasi & retry penjualan
        Schema::create('order_accurate_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            
            // Pemicu eksekusi: REALTIME_POS, AUTO_RETRY_SCHEDULE, MANUAL_ADMIN
            $table->string('trigger_type', 30)->default('MANUAL_ADMIN')->index();
            
            // Status hasil: SUCCESS, FAILED, PENDING
            $table->string('status', 20)->default('PENDING')->index();
            
            $table->unsignedInteger('attempt_number')->default(1);
            $table->string('accurate_invoice_no', 100)->nullable();
            $table->string('accurate_receipt_no', 100)->nullable();
            
            // Kategori error: NETWORK_TIMEOUT, INSUFFICIENT_STOCK, ITEM_NOT_FOUND, ACCOUNT_NOT_FOUND, AUTH_ERROR, VALIDATION_ERROR, UNKNOWN
            $table->string('error_category', 50)->nullable()->index();
            $table->text('error_message')->nullable();
            $table->json('payload_snapshot')->nullable();
            
            // Staf yang mengeksekusi (jika MANUAL_ADMIN, null jika scheduler/cron)
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        // 3. Inisialisasi status untuk pesanan yang sudah memiliki nomor faktur Accurate sebelumnya
        try {
            \Illuminate\Support\Facades\DB::table('orders')
                ->whereNotNull('accurate_invoice_no')
                ->where('accurate_invoice_no', '!=', '')
                ->update([
                    'accurate_sync_status' => 'SYNCED',
                    'accurate_synced_at' => now(),
                ]);

            // Tandai pesanan lampau (> 7 hari lalu) yang tidak memiliki faktur sebagai FAILED agar antrean tetap bersih
            \Illuminate\Support\Facades\DB::table('orders')
                ->where(function ($q) {
                    $q->whereNull('accurate_invoice_no')
                      ->orWhere('accurate_invoice_no', '');
                })
                ->where('created_at', '<', now()->subDays(7))
                ->update([
                    'accurate_sync_status' => 'FAILED',
                    'accurate_sync_last_error' => 'Transaksi lampau sebelum sistem tracking aktif (> 7 hari)',
                ]);
        } catch (\Throwable $e) {
            // Abaikan jika database kosong saat migrasi baru
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_accurate_sync_logs');

        Schema::table('orders', function (Blueprint $table) {
            $dropCols = [];
            if (Schema::hasColumn('orders', 'accurate_sync_status')) $dropCols[] = 'accurate_sync_status';
            if (Schema::hasColumn('orders', 'accurate_sync_attempts')) $dropCols[] = 'accurate_sync_attempts';
            if (Schema::hasColumn('orders', 'accurate_sync_last_error')) $dropCols[] = 'accurate_sync_last_error';
            if (Schema::hasColumn('orders', 'accurate_synced_at')) $dropCols[] = 'accurate_synced_at';
            
            if (!empty($dropCols)) {
                $table->dropColumn($dropCols);
            }
        });
    }
};

