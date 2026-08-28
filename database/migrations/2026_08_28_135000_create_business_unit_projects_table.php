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
        Schema::create('business_unit_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->unsignedBigInteger('project_id')->nullable()->comment('ID proyek dari Accurate');
            $table->string('project_no')->comment('Nomor proyek dari Accurate, misal P.00008');
            $table->string('name')->comment('Nama proyek, misal RESMI');
            $table->timestamps();

            // Kombinasi business_unit_id dan project_no unik (karena tiap DB Source memiliki project_no sendiri)
            $table->unique(['business_unit_id', 'project_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_unit_projects');
    }
};
