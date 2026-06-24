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
        Schema::create('warranty_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('brand_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['store_default', 'insurance'])->default('store_default');
            $table->integer('duration_days');
            $table->json('coverage')->nullable(); // [{"name": "LCD Rusak", "covered": true}]
            $table->integer('max_claims')->default(1);
            $table->string('item_category')->nullable(); // Kategori asuransi di Accurate
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warranty_policies');
    }
};
