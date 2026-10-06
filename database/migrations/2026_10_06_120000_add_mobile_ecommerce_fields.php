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
        // 1. Warehouses: flag untuk gudang yang stoknya dijual di Mobile App
        Schema::table('warehouses', function (Blueprint $table) {
            $table->boolean('is_online_store')->default(false)->after('status');
        });

        // 2. Orders: timer kadaluwarsa pembayaran & tracking gudang pemenuhan stok
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('payment_expired_at')->nullable()->after('order_status');
            $table->foreignId('warehouse_id')->nullable()->after('branch_id')->constrained('warehouses')->nullOnDelete();
        });

        // 3. Payment Methods: flag untuk rekening bank yang ditampilkan di Mobile App
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->boolean('is_visible_mobile')->default(false)->after('is_active');
        });

        // 4. Conversations: dukung guest chat & konteks produk yang ditanyakan
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreignId('business_unit_id')->nullable()->after('user_id')->constrained('business_units')->nullOnDelete();
            $table->string('guest_token')->nullable()->after('business_unit_id')->index();
            $table->string('guest_name')->nullable()->after('guest_token');
            $table->string('guest_phone')->nullable()->after('guest_name');
            $table->foreignId('product_accurate_id')->nullable()->after('guest_phone')->constrained('product_accurates')->nullOnDelete();
        });

        // 5. Messages: dukung pesan dari guest, penanda tipe pengirim, dan waktu dibaca
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('sender_type')->default('customer')->after('user_id'); // customer, guest, cs, system
            $table->timestamp('read_at')->nullable()->after('message');
        });

        // 6. Order Payments: jadikan xendit_external_id nullable untuk pembayaran transfer manual
        Schema::table('order_payments', function (Blueprint $table) {
            $table->string('xendit_external_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_payments', function (Blueprint $table) {
            $table->string('xendit_external_id')->nullable(false)->change();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['sender_type', 'read_at']);
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['business_unit_id']);
            $table->dropForeign(['product_accurate_id']);
            $table->dropIndex(['guest_token']);
            $table->dropColumn([
                'business_unit_id',
                'guest_token',
                'guest_name',
                'guest_phone',
                'product_accurate_id',
            ]);
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('is_visible_mobile');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn(['payment_expired_at', 'warehouse_id']);
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn('is_online_store');
        });
    }
};
