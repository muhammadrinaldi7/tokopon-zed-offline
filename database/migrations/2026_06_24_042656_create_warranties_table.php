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
        Schema::create('warranties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warranty_policy_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_item_id')->nullable()->constrained()->onDelete('set null');
            $table->string('serial_number');
            $table->foreignId('customer_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->enum('status', ['active', 'expired', 'voided', 'claimed_out'])->default('active');
            $table->integer('claims_used')->default(0);
            $table->foreignId('device_inspection_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('source', ['activation', 'purchase'])->default('activation');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranties');
    }
};
