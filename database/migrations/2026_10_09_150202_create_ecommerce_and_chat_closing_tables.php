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
        // 1. Tabel Banner Promo Mobile E-Commerce
        if (!Schema::hasTable('ecommerce_banners')) {
            Schema::create('ecommerce_banners', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('image_url');
                $table->string('target_type')->default('none'); // 'none', 'category', 'brand', 'product', 'url'
                $table->string('target_value')->nullable();
                $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Tabel Event Flash Sale
        if (!Schema::hasTable('ecommerce_flash_sales')) {
            Schema::create('ecommerce_flash_sales', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
                $table->dateTime('start_time');
                $table->dateTime('end_time');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Tabel Item Produk Flash Sale
        if (!Schema::hasTable('ecommerce_flash_sale_items')) {
            Schema::create('ecommerce_flash_sale_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('flash_sale_id')->constrained('ecommerce_flash_sales')->cascadeOnDelete();
                $table->foreignId('product_accurate_id')->constrained('product_accurates')->cascadeOnDelete();
                $table->decimal('flash_sale_price', 15, 2);
                $table->decimal('original_price', 15, 2)->nullable();
                $table->integer('quota_stock')->default(10);
                $table->integer('sold_stock')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 4. Kolom Analisa Closing CS pada tabel Conversations
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'closing_status')) {
                $table->string('closing_status')->default('none')->after('status'); // 'none', 'deal', 'follow_up', 'lost'
            }
            if (!Schema::hasColumn('conversations', 'closing_amount')) {
                $table->decimal('closing_amount', 15, 2)->nullable()->after('closing_status');
            }
            if (!Schema::hasColumn('conversations', 'closing_notes')) {
                $table->text('closing_notes')->nullable()->after('closing_amount');
            }
            if (!Schema::hasColumn('conversations', 'closed_by_user_id')) {
                $table->foreignId('closed_by_user_id')->nullable()->after('closing_notes')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('conversations', 'closed_at')) {
                $table->dateTime('closed_at')->nullable()->after('closed_by_user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['closed_by_user_id']);
            $table->dropColumn(['closing_status', 'closing_amount', 'closing_notes', 'closed_by_user_id', 'closed_at']);
        });

        Schema::dropIfExists('ecommerce_flash_sale_items');
        Schema::dropIfExists('ecommerce_flash_sales');
        Schema::dropIfExists('ecommerce_banners');
    }
};
