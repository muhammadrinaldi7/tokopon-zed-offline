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
        Schema::create('product_serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('accurate_sn_id')->nullable()->index();
            $table->string('item_no')->index();
            $table->unsignedBigInteger('warehouse_id')->nullable()->index();
            $table->string('serial_number')->unique();
            $table->enum('status', ['Available', 'Sold', 'Reserved', 'Unavailable'])->default('Available');
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_serial_numbers');
    }
};
