<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_leads') && !Schema::hasColumn('crm_leads', 'won_by_user_id')) {
            Schema::table('crm_leads', function (Blueprint $table) {
                $table->foreignId('won_by_user_id')
                    ->nullable()
                    ->after('assigned_to_user_id')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->index(['won_by_user_id', 'won_at'], 'crm_leads_won_by_at_idx');
            });

            // Backfill won_by_user_id untuk lead WON yang sudah ada
            $wonStageIds = DB::table('crm_pipeline_stages')
                ->where('stage_type', 'won')
                ->pluck('id');

            if ($wonStageIds->isNotEmpty()) {
                $wonLeads = DB::table('crm_leads')
                    ->whereIn('stage_id', $wonStageIds)
                    ->whereNull('won_by_user_id')
                    ->get(['id', 'conversation_id', 'assigned_to_user_id']);

                foreach ($wonLeads as $wl) {
                    $closerId = null;
                    if ($wl->conversation_id) {
                        $closerId = DB::table('conversations')
                            ->where('id', $wl->conversation_id)
                            ->value('closed_by_user_id');
                    }
                    $closerId = $closerId ?: $wl->assigned_to_user_id;
                    if ($closerId) {
                        DB::table('crm_leads')
                            ->where('id', $wl->id)
                            ->update(['won_by_user_id' => $closerId]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('crm_leads') && Schema::hasColumn('crm_leads', 'won_by_user_id')) {
            Schema::table('crm_leads', function (Blueprint $table) {
                $table->dropIndex('crm_leads_won_by_at_idx');
                $table->dropConstrainedForeignId('won_by_user_id');
            });
        }
    }
};
