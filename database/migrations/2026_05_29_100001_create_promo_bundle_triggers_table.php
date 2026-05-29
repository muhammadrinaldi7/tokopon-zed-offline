<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_bundle_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_bundle_triggers');
    }
};
