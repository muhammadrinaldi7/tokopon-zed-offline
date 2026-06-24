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
        Schema::create('warranty_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_number')->unique();
            $table->foreignId('warranty_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('serial_number');
            $table->text('issue_description');
            $table->text('diagnosis')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'in_repair', 'completed'])->default('pending');
            $table->enum('resolution', ['repaired', 'replaced', 'refunded'])->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('claimed_by')->nullable()->constrained('users')->onDelete('set null'); // staff yang input
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // manager yang approve
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('device_inspection_id')->nullable()->constrained()->onDelete('set null'); // QC hasil repair
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranty_claims');
    }
};
