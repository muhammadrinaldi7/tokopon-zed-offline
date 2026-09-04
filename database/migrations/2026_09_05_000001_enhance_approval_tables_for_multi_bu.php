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
        // 1. Update approval_rules
        Schema::table('approval_rules', function (Blueprint $table) {
            $table->foreignId('business_unit_id')
                ->nullable()
                ->after('id')
                ->constrained('business_units')
                ->nullOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->after('business_unit_id')
                ->constrained('branches')
                ->nullOnDelete();

            $table->decimal('min_amount', 15, 2)->default(0)->after('level');
            $table->decimal('max_amount', 15, 2)->nullable()->after('min_amount');

            // Drop existing unique constraint
            $table->dropUnique(['module', 'level']);

            // Add new scoped unique constraint (business_unit_id can be NULL for global rules)
            $table->unique(['business_unit_id', 'module', 'level'], 'bu_module_level_unique');
        });

        // 2. Update approval_requests
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->foreignId('business_unit_id')
                ->nullable()
                ->after('approvable_id')
                ->constrained('business_units')
                ->nullOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->after('business_unit_id')
                ->constrained('branches')
                ->nullOnDelete();

            $table->decimal('total_amount', 15, 2)->nullable()->after('reason');

            $table->index(['business_unit_id', 'status']);
            $table->index('branch_id');
        });

        // 3. Update approval_histories
        Schema::table('approval_histories', function (Blueprint $table) {
            $table->string('role_snapshot')->nullable()->after('action');
        });

        // 4. Backfill existing approval_requests data safely
        $requests = DB::table('approval_requests')->get();
        foreach ($requests as $r) {
            $buId = null;
            $branchId = null;
            $amount = null;

            if ($r->approvable_type && $r->approvable_id) {
                if (str_contains($r->approvable_type, 'Order')) {
                    $order = DB::table('orders')->where('id', $r->approvable_id)->first();
                    if ($order) {
                        $buId = $order->business_unit_id;
                        $branchId = $order->branch_id;
                        $amount = $order->grand_total;
                    }
                } elseif (str_contains($r->approvable_type, 'SellPhone')) {
                    $sellPhone = DB::table('sell_phones')->where('id', $r->approvable_id)->first();
                    if ($sellPhone) {
                        $buId = $sellPhone->business_unit_id;
                        $branchId = $sellPhone->branch_id;
                        $amount = $sellPhone->appraised_value;
                    }
                } elseif (str_contains($r->approvable_type, 'WarrantyClaim')) {
                    $claim = DB::table('warranty_claims')->where('id', $r->approvable_id)->first();
                    if ($claim) {
                        $warranty = DB::table('warranties')->where('id', $claim->warranty_id)->first();
                        if ($warranty && $warranty->warranty_policy_id) {
                            $policy = DB::table('warranty_policies')->where('id', $warranty->warranty_policy_id)->first();
                            $buId = $policy?->business_unit_id;
                        }
                    }
                } elseif (str_contains($r->approvable_type, 'Warranty')) {
                    $warranty = DB::table('warranties')->where('id', $r->approvable_id)->first();
                    if ($warranty && $warranty->warranty_policy_id) {
                        $policy = DB::table('warranty_policies')->where('id', $warranty->warranty_policy_id)->first();
                        $buId = $policy?->business_unit_id;
                    }
                }
            }

            // Fallback to requester's unit/branch if not found
            if (!$buId || !$branchId) {
                $user = DB::table('users')->where('id', $r->requested_by)->first();
                if ($user) {
                    $buId = $buId ?: ($user->business_unit_id ?: 1);
                    $branchId = $branchId ?: $user->branch_id;
                }
            }

            DB::table('approval_requests')
                ->where('id', $r->id)
                ->update([
                    'business_unit_id' => $buId ?: 1,
                    'branch_id' => $branchId,
                    'total_amount' => $amount,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_histories', function (Blueprint $table) {
            $table->dropColumn('role_snapshot');
        });

        Schema::table('approval_requests', function (Blueprint $table) {
            $table->dropForeign(['business_unit_id']);
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['business_unit_id', 'status']);
            $table->dropIndex(['branch_id']);
            $table->dropColumn(['business_unit_id', 'branch_id', 'total_amount']);
        });

        Schema::table('approval_rules', function (Blueprint $table) {
            $table->dropUnique('bu_module_level_unique');
            $table->dropForeign(['business_unit_id']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['business_unit_id', 'branch_id', 'min_amount', 'max_amount']);
            $table->unique(['module', 'level']);
        });
    }
};
