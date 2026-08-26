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
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE warranty_claims MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'in_repair', 'waiting_payment', 'waiting_refund', 'completed') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE warranty_claims MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'in_repair', 'completed') DEFAULT 'pending'");
        }
    }
};
