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
        Schema::table('warranty_policies', function (Blueprint $table) {
            // Drop confusing columns
            $table->dropColumn(['item_condition', 'price_status', 'priority']);
            
            // Add new flexible columns
            $table->foreignId('business_unit_id')->nullable()->after('id')->constrained('business_units')->nullOnDelete();
            $table->json('coverage_scope')->nullable()->after('brand_list');
        });
        
        // SQLite doesn't fully support changing enums easily, so we can just leave the 'type' column as string 
        // since it was created as string in the previous migration, we just use the new string values in our code.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warranty_policies', function (Blueprint $table) {
            $table->string('item_condition', 20)->default('all');
            $table->string('price_status', 20)->default('all');
            $table->integer('priority')->default(1);
            
            $table->dropForeign(['business_unit_id']);
            $table->dropColumn('business_unit_id');
            $table->dropColumn('coverage_scope');
        });
    }
};
