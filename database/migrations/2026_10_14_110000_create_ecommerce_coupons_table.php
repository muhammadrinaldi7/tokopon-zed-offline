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
        Schema::create('ecommerce_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
            $table->string('code', 50)->unique()->index();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->text('terms_conditions')->nullable();
            // Tipe: PRODUCT_DISCOUNT_PERCENT, PRODUCT_DISCOUNT_FIXED, SHIPPING_DISCOUNT_PERCENT, SHIPPING_DISCOUNT_FIXED
            $table->string('type', 50)->default('PRODUCT_DISCOUNT_FIXED')->index();
            $table->decimal('discount_value', 15, 2);
            $table->decimal('max_discount_amount', 15, 2)->nullable();
            $table->decimal('min_purchase_amount', 15, 2)->default(0);
            $table->integer('usage_limit_total')->nullable();
            $table->integer('usage_limit_per_user')->default(1);
            $table->integer('used_count')->default(0);
            $table->dateTime('start_date')->nullable()->index();
            $table->dateTime('end_date')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_coupons');
    }
};
