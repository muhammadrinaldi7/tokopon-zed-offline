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
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 20)->index(); // 'whatsapp', 'email'
            $table->string('recipient')->index(); // phone number or email address
            $table->string('recipient_name')->nullable();
            $table->string('subject')->nullable(); // Subject or template name
            $table->string('message_type', 50)->nullable()->index(); // 'receipt_order', 'receipt_sellphone', 'payment_proof', etc.
            $table->longText('content')->nullable(); // Human-readable summary of what was sent
            $table->text('attachment_url')->nullable(); // Public URL of the PDF receipt or image
            $table->string('attachment_name')->nullable(); // Filename of attachment
            $table->json('payload')->nullable(); // Outgoing payload sent to API/Mailer
            $table->json('response_payload')->nullable(); // API response data
            $table->boolean('is_sent')->default(false)->index(); // True if sent successfully
            $table->string('status', 30)->default('pending')->index(); // 'sent', 'failed', 'pending'
            $table->text('error_message')->nullable(); // Error description if failed

            // Polymorphic source relation (Order, SellPhone, etc.)
            $table->nullableMorphs('source');
            $table->string('reference_number')->nullable()->index(); // e.g. INV/ORD number, SPL number

            // Sender and organization metadata
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();

            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};
