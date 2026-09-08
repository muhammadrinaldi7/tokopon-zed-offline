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
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('opname_number', 50)->unique();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('COUNTING'); // DRAFT, COUNTING, PENDING_APPROVAL, APPROVED, REJECTED, COMPLETED, CANCELLED
            $table->string('type', 30)->default('ALL'); // ALL, SERIALIZED_ONLY, NON_SERIALIZED_ONLY, CATEGORY
            $table->string('category_filter', 100)->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->integer('total_system_qty')->default(0);
            $table->integer('total_physical_qty')->default(0);
            $table->integer('total_difference_qty')->default(0);
            $table->decimal('total_loss_value', 15, 2)->default(0);
            $table->decimal('total_surplus_value', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_unit_id', 'branch_id']);
            $table->index(['warehouse_id', 'status']);
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->string('item_no', 100);
            $table->string('product_name', 255);
            $table->boolean('is_serialized')->default(false);
            $table->integer('system_qty')->default(0);
            $table->integer('physical_qty')->default(0);
            $table->integer('difference_qty')->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('difference_value', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['stock_opname_id', 'item_no']);
        });

        Schema::create('stock_opname_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('stock_opname_item_id')->constrained('stock_opname_items')->cascadeOnDelete();
            $table->string('item_no', 100);
            $table->string('serial_number', 100);
            $table->string('status', 30)->default('MISSING'); // MATCHED, MISSING, UNEXPECTED
            $table->decimal('hpp', 15, 2)->default(0);
            $table->dateTime('scanned_at')->nullable();
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['stock_opname_id', 'serial_number']);
            $table->index(['stock_opname_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_serials');
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
    }
};
