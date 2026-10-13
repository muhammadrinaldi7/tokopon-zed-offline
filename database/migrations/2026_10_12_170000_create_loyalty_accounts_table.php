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
        Schema::create('loyalty_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 30)->unique()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->integer('points_balance')->default(0)->index();
            $table->integer('lifetime_points')->default(0);
            $table->decimal('total_spend', 15, 2)->default(0);
            $table->enum('tier', ['BRONZE', 'SILVER', 'GOLD', 'PLATINUM'])->default('BRONZE')->index();
            $table->timestamp('tier_updated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_accounts');
    }
};
