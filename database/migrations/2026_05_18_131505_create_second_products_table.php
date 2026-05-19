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
        Schema::create('second_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignIdFor(\App\Models\Category::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(\App\Models\Brand::class)->nullable()->constrained()->nullOnDelete();
            $table->longText('description')->nullable();
            
            // Denormalized for UI
            $table->decimal('starting_price', 15, 2)->nullable();
            $table->integer('total_stock')->default(0);
            $table->string('thumbnail_image')->nullable();
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('second_products');
    }
};
