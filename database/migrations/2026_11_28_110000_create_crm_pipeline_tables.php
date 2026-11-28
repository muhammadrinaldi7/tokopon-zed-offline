<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_pipeline_stages')) {
            Schema::create('crm_pipeline_stages', function (Blueprint $table) {
                $table->id();
                $table->string('name', 80);
                $table->string('slug', 80)->unique();
                $table->string('color', 30)->default('slate'); // slate, sky, indigo, amber, violet, emerald, rose, teal, orange
                $table->string('stage_type', 20)->default('open'); // open | won | lost
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['stage_type', 'is_active', 'sort_order'], 'crm_stages_type_active_sort_idx');
            });
        }

        if (!Schema::hasTable('crm_leads')) {
            Schema::create('crm_leads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stage_id')->constrained('crm_pipeline_stages')->restrictOnDelete();
                $table->foreignId('conversation_id')->nullable()->constrained('conversations')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->nullOnDelete();
                $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('product_accurate_id')->nullable()->constrained('product_accurates')->nullOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();

                $table->string('customer_name', 150);
                $table->string('customer_phone', 40)->nullable();
                $table->string('temperature', 20)->default('warm'); // cold | warm | hot
                $table->string('interest_type', 30)->default('new'); // new | second | trade_in | other
                $table->string('source', 30)->default('mobile_chat'); // mobile_chat | trade_in | whatsapp | walk_in | manual

                $table->decimal('estimated_value', 15, 2)->default(0);
                $table->decimal('won_amount', 15, 2)->nullable();
                $table->string('lost_reason', 150)->nullable();

                $table->timestamp('next_follow_up_at')->nullable();
                $table->timestamp('won_at')->nullable();
                $table->timestamp('lost_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['stage_id', 'assigned_to_user_id'], 'crm_leads_stage_assignee_idx');
                $table->index(['business_unit_id', 'stage_id'], 'crm_leads_bu_stage_idx');
                $table->index(['conversation_id'], 'crm_leads_conv_idx');
                $table->index(['user_id'], 'crm_leads_user_idx');
                $table->index(['next_follow_up_at'], 'crm_leads_followup_idx');
            });
        }

        if (!Schema::hasTable('crm_lead_activities')) {
            Schema::create('crm_lead_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->constrained('crm_leads')->cascadeOnDelete();
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('activity_type', 40)->default('note'); // created | stage_changed | note | follow_up | assigned | auto_won
                $table->foreignId('from_stage_id')->nullable()->constrained('crm_pipeline_stages')->nullOnDelete();
                $table->foreignId('to_stage_id')->nullable()->constrained('crm_pipeline_stages')->nullOnDelete();
                $table->text('description')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['lead_id', 'created_at'], 'crm_lead_act_lead_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_lead_activities');
        Schema::dropIfExists('crm_leads');
        Schema::dropIfExists('crm_pipeline_stages');
    }
};
