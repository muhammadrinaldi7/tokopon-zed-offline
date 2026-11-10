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
        Schema::create('accurate_sync_issues', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64)->unique()->index();
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
            $table->unsignedBigInteger('webhook_log_id')->nullable()->index();
            $table->string('event_type', 64)->nullable()->index();
            $table->string('stage', 64)->default('general')->index();
            $table->string('reason_code', 64)->index();
            $table->enum('severity', ['info', 'warning', 'error', 'critical'])->default('error')->index();
            $table->string('item_no', 100)->nullable()->index();
            $table->string('serial_number', 100)->nullable()->index();
            $table->string('warehouse_name', 150)->nullable()->index();
            $table->json('expected')->nullable();
            $table->json('actual')->nullable();
            $table->json('context')->nullable();
            $table->enum('status', ['open', 'resolved', 'ignored'])->default('open')->index();
            $table->unsignedInteger('occurrences')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accurate_sync_issues');
    }
};
