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
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable'); // This adds approvable_id and approvable_type
            $table->string('request_type')->comment('e.g. cancellation, discount, refund');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->text('reason')->nullable();
            $table->string('status')->default('PENDING')->comment('PENDING, APPROVED, REJECTED, COMPLETED');
            $table->integer('required_level')->default(1);
            $table->integer('current_level')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
