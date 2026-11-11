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
        if (!Schema::hasTable('accurate_document_audit_logs')) {
            Schema::create('accurate_document_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->nullable()->constrained('orders')->cascadeOnDelete();
                $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
                
                // Document type: SALES_INVOICE, SALES_RECEIPT, SALES_ORDER, PURCHASE_INVOICE, etc.
                $table->string('document_type', 50)->index();
                
                // Action: UPDATE_DATE, RELINK_DOC, UNLINK_DOC, ROLLBACK_AND_RESYNC
                $table->string('action_type', 50)->index();
                
                $table->string('target_doc_number', 100)->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->text('reason'); // Alasan perubahan wajib diisi
                $table->string('status', 30)->default('SUCCESS'); // SUCCESS, FAILED
                $table->text('error_message')->nullable();
                $table->json('accurate_response')->nullable();
                
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['order_id', 'action_type']);
                $table->index(['created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accurate_document_audit_logs');
    }
};

