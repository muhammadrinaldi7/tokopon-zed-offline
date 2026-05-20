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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Hubungan referensial dengan Product Erzap (Webhook)
            // $table->string('erzap_item_id')->nullable()->index(); 
            // $table->foreign('erzap_item_id')->references('erzap_id')->on('product_erzaps')->nullOnDelete();

            $table->string('sku')->unique()->nullable();
            $table->string('condition'); // Baru, Bekas Like New, dll
            $table->string('ram')->nullable();
            $table->string('storage')->nullable(); // 128GB, 256GB
            $table->string('color')->nullable();
            $table->decimal('price', 15, 2);
            $table->integer('stock')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
