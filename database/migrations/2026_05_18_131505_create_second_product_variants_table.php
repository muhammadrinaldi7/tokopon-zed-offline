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
        Schema::create('second_product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('second_product_id')->constrained('second_products')->cascadeOnDelete();
            
            $table->foreignId('sell_phone_id')->nullable()->constrained('sell_phones')->nullOnDelete();
            $table->foreignId('product_accurate_id')->nullable()->constrained('product_accurates')->nullOnDelete();
            
            $table->string('sku')->unique()->nullable();
            $table->string('condition_desc')->nullable(); // Ex: Mulus, Layar retak, dll
            $table->string('ram')->nullable();
            $table->string('storage')->nullable();
            $table->string('color')->nullable();
            
            $table->decimal('buy_price', 15, 2)->default(0); // COGS
            $table->decimal('price', 15, 2); // Harga Jual
            $table->integer('stock')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('second_product_variants');
    }
};
