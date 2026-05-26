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
        Schema::create('qc_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');                     // "Template iPhone", "Template Android", "Default"
            $table->foreignId('brand_id')->nullable()   // Khusus brand tertentu (opsional)
                  ->constrained('brands')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->json('items');                      // [{name, type: "boolean"|"text"}]
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qc_templates');
    }
};
