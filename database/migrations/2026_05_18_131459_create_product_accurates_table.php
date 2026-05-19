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
        Schema::create('product_accurates', function (Blueprint $table) {
            $table->id(); // Use AI ID as primary key to avoid collision between databases
            $table->string('accurate_id'); // ID dari Accurate
            $table->string('database_source')->default('syihab'); // 'syihab' atau 'second'
            $table->string('item_no')->nullable();    // SKU / No Barang
            $table->string('name')->nullable();
            $table->decimal('base_price', 15, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->json('raw_data')->nullable(); // JSON mentah dari webhook/API
            $table->timestamps();

            // Kombinasi ID Accurate dan Sumber Database harus unik
            $table->unique(['accurate_id', 'database_source']);
        });
        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreignId('product_accurate_id')->nullable()->after('product_id')->constrained('product_accurates')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_accurates');
        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'product_accurate_id')) {
                $table->dropForeign(['product_accurate_id']);
                $table->dropColumn('product_accurate_id');
            }
        });
    }
};
