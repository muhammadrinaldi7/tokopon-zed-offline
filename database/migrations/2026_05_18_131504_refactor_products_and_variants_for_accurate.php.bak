<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix for SQLite foreign key drop missing index issue
        if (DB::getDriverName() === 'sqlite') {
            // Create the indexes so Laravel can successfully drop them when dropping foreign keys
            Schema::table('product_variants', function (Blueprint $table) {
                // Ignore if it already exists, but we need it for sell_phone_id and trade_in_id
                // We use raw queries to suppress errors if they exist
            });
            try {
                DB::statement('CREATE INDEX product_variants_sell_phone_id_index ON product_variants (sell_phone_id)');
            } catch (\Exception $e) {}
            try {
                DB::statement('CREATE INDEX product_variants_trade_in_id_index ON product_variants (trade_in_id)');
            } catch (\Exception $e) {}
        }

        // 1. Drop Explicit Indexes First
        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'erzap_item_id')) {
                $table->dropIndex(['erzap_item_id']);
            }
            if (Schema::hasColumn('product_variants', 'sell_phone_id')) {
                try {
                    $table->dropIndex(['sell_phone_id']);
                } catch (\Exception $e) {}
            }
            if (Schema::hasColumn('product_variants', 'trade_in_id')) {
                try {
                    $table->dropIndex(['trade_in_id']);
                } catch (\Exception $e) {}
            }
        });

        // 2. Drop Foreign Keys
        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'erzap_item_id')) {
                $table->dropForeign(['erzap_item_id']);
            }
            if (Schema::hasColumn('product_variants', 'sell_phone_id')) {
                $table->dropForeign(['sell_phone_id']);
            }
            if (Schema::hasColumn('product_variants', 'trade_in_id')) {
                $table->dropForeign(['trade_in_id']);
            }
        });

        // 3. Drop Columns & Add New Columns
        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'erzap_item_id')) {
                $table->dropColumn('erzap_item_id');
            }
            if (Schema::hasColumn('product_variants', 'sell_phone_id')) {
                $table->dropColumn('sell_phone_id');
            }
            if (Schema::hasColumn('product_variants', 'trade_in_id')) {
                $table->dropColumn('trade_in_id');
            }
            
            $table->foreignId('product_accurate_id')->nullable()->after('product_id')->constrained('product_accurates')->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'has_active_erzap')) {
                $table->dropColumn('has_active_erzap');
            }
            if (Schema::hasColumn('products', 'is_second')) {
                $table->dropColumn('is_second');
            }
        });

        Schema::dropIfExists('product_erzaps');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('product_erzaps', function (Blueprint $table) {
            $table->string('erzap_id')->primary();
            $table->string('name')->nullable();
            $table->decimal('base_price', 15, 2)->default(0);
            $table->decimal('discount_price', 15, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->string('barcode')->nullable();
            $table->json('raw_data')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('has_active_erzap')->default(false);
            $table->boolean('is_second')->default(false);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'product_accurate_id')) {
                $table->dropForeign(['product_accurate_id']);
                $table->dropColumn('product_accurate_id');
            }
            $table->string('erzap_item_id')->nullable()->index();
            $table->foreignId('sell_phone_id')->nullable()->constrained('sell_phones')->nullOnDelete();
            $table->foreignId('trade_in_id')->nullable()->constrained('trade_ins')->nullOnDelete();
        });
    }
};
