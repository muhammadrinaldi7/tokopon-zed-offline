<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\CrmLead;
use App\Models\CrmLeadActivity;
use App\Models\CrmPipelineStage;
use App\Models\Order;
use App\Models\ProductAccurate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CrmPipelineService
{
    public const DEFAULT_STAGES = [
        [
            'name' => 'New Lead',
            'slug' => 'new-lead',
            'color' => 'slate',
            'stage_type' => CrmPipelineStage::TYPE_OPEN,
            'sort_order' => 1,
            'is_default' => true,
        ],
        [
            'name' => 'Qualified',
            'slug' => 'qualified',
            'color' => 'sky',
            'stage_type' => CrmPipelineStage::TYPE_OPEN,
            'sort_order' => 2,
            'is_default' => false,
        ],
        [
            'name' => 'Penawaran & Estimasi',
            'slug' => 'proposal',
            'color' => 'indigo',
            'stage_type' => CrmPipelineStage::TYPE_OPEN,
            'sort_order' => 3,
            'is_default' => false,
        ],
        [
            'name' => 'Negosiasi / Follow-Up',
            'slug' => 'negotiation',
            'color' => 'amber',
            'stage_type' => CrmPipelineStage::TYPE_OPEN,
            'sort_order' => 4,
            'is_default' => false,
        ],
        [
            'name' => 'Menunggu Bayar / Booking',
            'slug' => 'pending-payment',
            'color' => 'violet',
            'stage_type' => CrmPipelineStage::TYPE_OPEN,
            'sort_order' => 5,
            'is_default' => false,
        ],
        [
            'name' => 'WON',
            'slug' => 'won',
            'color' => 'emerald',
            'stage_type' => CrmPipelineStage::TYPE_WON,
            'sort_order' => 6,
            'is_default' => false,
        ],
        [
            'name' => 'LOST',
            'slug' => 'lost',
            'color' => 'rose',
            'stage_type' => CrmPipelineStage::TYPE_LOST,
            'sort_order' => 7,
            'is_default' => false,
        ],
    ];

    /**
     * Pastikan 7 tahap default tersedia di database (idempotent)
     */
    public function ensureDefaultStagesExist(): void
    {
        if (!Schema::hasTable('crm_pipeline_stages')) {
            return;
        }

        if (CrmPipelineStage::count() > 0) {
            return;
        }

        foreach (self::DEFAULT_STAGES as $stageData) {
            CrmPipelineStage::firstOrCreate(
                ['slug' => $stageData['slug']],
                array_merge($stageData, ['is_active' => true])
            );
        }
    }

    /**
     * Pastikan 3 permission CRM Pipeline tersedia dan diberikan ke role default (idempotent)
     */
    public function ensurePermissionsExist(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $crmPermissions = [
            'view-crm-pipeline',
            'view-all-crm-leads',
            'manage-crm-stages',
        ];

        $createdAny = false;
        foreach ($crmPermissions as $permName) {
            $perm = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            if ($perm->wasRecentlyCreated) {
                $createdAny = true;
            }
        }

        if ($createdAny) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            if ($superadmin = Role::where('name', 'superadmin')->where('guard_name', 'web')->first()) {
                $superadmin->givePermissionTo($crmPermissions);
            }
            if ($admin = Role::where('name', 'admin')->where('guard_name', 'web')->first()) {
                $admin->givePermissionTo($crmPermissions);
            }
            if ($cs = Role::where('name', 'cs')->where('guard_name', 'web')->first()) {
                $cs->givePermissionTo('view-crm-pipeline');
            }
        }
    }

    public function getDefaultOpenStage(): CrmPipelineStage
    {
        $this->ensureDefaultStagesExist();

        return CrmPipelineStage::where('is_active', true)
            ->where('stage_type', CrmPipelineStage::TYPE_OPEN)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->firstOrFail();
    }

    public function getWonStage(): CrmPipelineStage
    {
        $this->ensureDefaultStagesExist();

        return CrmPipelineStage::where('stage_type', CrmPipelineStage::TYPE_WON)
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->firstOrFail();
    }

    public function getLostStage(): CrmPipelineStage
    {
        $this->ensureDefaultStagesExist();

        return CrmPipelineStage::where('stage_type', CrmPipelineStage::TYPE_LOST)
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->firstOrFail();
    }

    public function getFollowUpStage(): CrmPipelineStage
    {
        $this->ensureDefaultStagesExist();

        return CrmPipelineStage::where('slug', 'negotiation')->first()
            ?: CrmPipelineStage::where('is_active', true)
                ->where('stage_type', CrmPipelineStage::TYPE_OPEN)
                ->orderByDesc('sort_order')
                ->firstOrFail();
    }

    /**
     * Backfill percakapan lama yang belum memiliki kartu CRM Lead
     */
    public function backfillFromConversations(int $limit = 300): int
    {
        if (!Schema::hasTable('crm_leads') || !Schema::hasTable('crm_pipeline_stages')) {
            return 0;
        }

        $this->ensureDefaultStagesExist();

        $existingConvIds = CrmLead::whereNotNull('conversation_id')->pluck('conversation_id');
        $conversations = Conversation::with(['user.profile', 'productAccurate', 'businessUnit'])
            ->whereNotIn('id', $existingConvIds)
            ->orderByDesc('updated_at')
            ->take($limit)
            ->get();

        $created = 0;
        foreach ($conversations as $conv) {
            $this->syncLeadFromConversation($conv, null, true);
            $created++;
        }

        return $created;
    }

    /**
     * Auto-Create atau sinkronisasi CrmLead dari sebuah Conversation
     */
    public function syncLeadFromConversation(
        Conversation $conv,
        ?int $actorUserId = null,
        bool $isBackfill = false
    ): ?CrmLead {
        if (!Schema::hasTable('crm_leads') || !Schema::hasTable('crm_pipeline_stages')) {
            return null;
        }

        $this->ensureDefaultStagesExist();
        $conv->loadMissing(['user.profile', 'productAccurate', 'businessUnit']);

        $existing = CrmLead::where('conversation_id', $conv->id)->latest('id')->first();

        $customerName = $conv->user?->name ?? ($conv->guest_name ?: 'Pengunjung Tokopon');
        $customerPhone = $conv->guest_phone
            ?: ($conv->user?->customer_phone ?? $conv->user?->profile?->phone_number ?? null);

        $estimatedValue = (float) ($conv->closing_amount ?: ($conv->productAccurate?->base_price ?? 0));
        $interestType = 'new';
        if ($conv->productAccurate?->is_second || str_contains(strtoupper((string) ($conv->businessUnit?->name ?? '')), 'SECOND')) {
            $interestType = 'second';
        }

        if ($existing) {
            $updates = [];
            if ($customerName && $existing->customer_name !== $customerName) {
                $updates['customer_name'] = $customerName;
            }
            if ($customerPhone && empty($existing->customer_phone)) {
                $updates['customer_phone'] = $customerPhone;
            }
            if ($conv->user_id && !$existing->user_id) {
                $updates['user_id'] = $conv->user_id;
            }
            if ($conv->business_unit_id && !$existing->business_unit_id) {
                $updates['business_unit_id'] = $conv->business_unit_id;
            }
            if ($conv->assigned_to_user_id !== $existing->assigned_to_user_id) {
                $updates['assigned_to_user_id'] = $conv->assigned_to_user_id;
            }
            if ($conv->product_accurate_id && !$existing->product_accurate_id) {
                $updates['product_accurate_id'] = $conv->product_accurate_id;
                if ((float) $existing->estimated_value <= 0 && $estimatedValue > 0) {
                    $updates['estimated_value'] = $estimatedValue;
                }
            }
            if (!empty($updates)) {
                $existing->update($updates);
            }
            return $existing;
        }

        // Tentukan tahap awal berdasarkan closing_status percakapan
        $stage = match ($conv->closing_status) {
            'deal' => $this->getWonStage(),
            'lost' => $this->getLostStage(),
            'follow_up' => $this->getFollowUpStage(),
            default => $this->getDefaultOpenStage(),
        };

        $lead = CrmLead::create([
            'stage_id' => $stage->id,
            'conversation_id' => $conv->id,
            'user_id' => $conv->user_id,
            'business_unit_id' => $conv->business_unit_id,
            'assigned_to_user_id' => $conv->assigned_to_user_id ?: $conv->closed_by_user_id,
            'product_accurate_id' => $conv->product_accurate_id,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'temperature' => $conv->closing_status === 'deal' ? CrmLead::TEMP_HOT : CrmLead::TEMP_WARM,
            'interest_type' => $interestType,
            'source' => 'mobile_chat',
            'estimated_value' => $estimatedValue,
            'won_amount' => $stage->isWon() ? (float) ($conv->closing_amount ?? 0) : null,
            'won_at' => $stage->isWon() ? ($conv->closed_at ?? $conv->updated_at ?? now()) : null,
            'lost_at' => $stage->isLost() ? ($conv->closed_at ?? $conv->updated_at ?? now()) : null,
            'lost_reason' => $stage->isLost() ? ($conv->closing_notes ?: 'Lainnya') : null,
            'notes' => $conv->closing_notes,
        ]);

        CrmLeadActivity::create([
            'lead_id' => $lead->id,
            'actor_user_id' => $actorUserId,
            'activity_type' => 'created',
            'to_stage_id' => $stage->id,
            'description' => $isBackfill
                ? "Sinkronisasi awal dari percakapan #{$conv->id} ke tahap {$stage->name}."
                : "Lead baru dibuat otomatis dari Live Chat #{$conv->id} di tahap {$stage->name}.",
        ]);

        return $lead;
    }

    /**
     * Pindahkan lead ke tahap lain + sinkron 2 arah ke Conversation + catat audit log
     */
    public function moveLeadToStage(
        CrmLead $lead,
        CrmPipelineStage $targetStage,
        ?int $actorUserId = null,
        array $payload = []
    ): CrmLead {
        return DB::transaction(function () use ($lead, $targetStage, $actorUserId, $payload) {
            $oldStage = $lead->stage;
            $updateData = [
                'stage_id' => $targetStage->id,
            ];

            if (array_key_exists('notes', $payload) && $payload['notes'] !== null) {
                $updateData['notes'] = $payload['notes'];
            }
            if (array_key_exists('next_follow_up_at', $payload)) {
                $updateData['next_follow_up_at'] = $payload['next_follow_up_at'] ?: null;
            }
            if (!empty($payload['temperature'])) {
                $updateData['temperature'] = $payload['temperature'];
            }

            if ($targetStage->isWon()) {
                $wonAmount = isset($payload['won_amount'])
                    ? (float) $payload['won_amount']
                    : (float) ($lead->won_amount ?: $lead->estimated_value ?: 0);
                $updateData['won_amount'] = $wonAmount;
                $updateData['won_at'] = now();
                $updateData['lost_at'] = null;
                $updateData['lost_reason'] = null;
                $updateData['temperature'] = CrmLead::TEMP_HOT;
                if (!empty($payload['order_id'])) {
                    $updateData['order_id'] = (int) $payload['order_id'];
                }
            } elseif ($targetStage->isLost()) {
                $updateData['lost_at'] = now();
                $updateData['lost_reason'] = $payload['lost_reason'] ?? ($lead->lost_reason ?: 'Lainnya');
                $updateData['won_at'] = null;
            } else {
                // Kembali ke tahap aktif (open)
                $updateData['won_at'] = null;
                $updateData['lost_at'] = null;
            }

            $lead->update($updateData);
            $lead->refresh()->load(['stage', 'conversation']);

            // Sinkronisasi 2 arah ke Conversation terkait agar analitik lama & sidebar chat tetap konsisten
            if ($lead->conversation) {
                $convUpdate = [];
                if ($targetStage->isWon()) {
                    $convUpdate = [
                        'closing_status' => 'deal',
                        'closing_amount' => $lead->won_amount ?? 0,
                        'closing_notes' => $lead->notes,
                        'closed_by_user_id' => $actorUserId ?: $lead->assigned_to_user_id,
                        'closed_at' => $lead->won_at ?? now(),
                        'status' => 'closed',
                    ];
                } elseif ($targetStage->isLost()) {
                    $convUpdate = [
                        'closing_status' => 'lost',
                        'closing_notes' => trim(($lead->lost_reason ? "[{$lead->lost_reason}] " : '') . ($lead->notes ?? '')),
                        'closed_by_user_id' => $actorUserId ?: $lead->assigned_to_user_id,
                        'closed_at' => $lead->lost_at ?? now(),
                    ];
                } elseif ($targetStage->is_default) {
                    $convUpdate = [
                        'closing_status' => 'none',
                        'status' => 'open',
                    ];
                } else {
                    $convUpdate = [
                        'closing_status' => 'follow_up',
                        'closing_notes' => $lead->notes,
                        'status' => 'open',
                    ];
                }
                $lead->conversation->update($convUpdate);
            }

            // Catat log aktivitas perpindahan tahap
            $activityType = $payload['activity_type'] ?? 'stage_changed';
            $fromName = $oldStage?->name ?? '-';
            $desc = $payload['activity_description']
                ?? "Memindahkan kualifikasi dari {$fromName} → {$targetStage->name}"
                    . ($targetStage->isWon() ? ' (Omset: Rp ' . number_format((float) ($lead->won_amount ?? 0), 0, ',', '.') . ')' : '')
                    . ($targetStage->isLost() && $lead->lost_reason ? " (Alasan: {$lead->lost_reason})" : '');

            CrmLeadActivity::create([
                'lead_id' => $lead->id,
                'actor_user_id' => $actorUserId,
                'activity_type' => $activityType,
                'from_stage_id' => $oldStage?->id,
                'to_stage_id' => $targetStage->id,
                'description' => $desc,
                'meta' => [
                    'won_amount' => $targetStage->isWon() ? (float) ($lead->won_amount ?? 0) : null,
                    'lost_reason' => $targetStage->isLost() ? $lead->lost_reason : null,
                    'order_id' => $lead->order_id,
                ],
            ]);

            return $lead;
        });
    }

    /**
     * Auto-Promote ke WON ketika order e-commerce/mobile berubah status menjadi lunas (PAID)
     */
    public function autoPromoteWonForOrder(Order $order): ?CrmLead
    {
        if (!Schema::hasTable('crm_leads') || !Schema::hasTable('crm_pipeline_stages')) {
            return null;
        }

        $this->ensureDefaultStagesExist();

        // Cari lead aktif (tahap open) terbaru milik customer ini (via user_id atau nomor HP)
        $openStageIds = CrmPipelineStage::where('stage_type', CrmPipelineStage::TYPE_OPEN)->pluck('id');
        if ($openStageIds->isEmpty()) {
            return null;
        }

        $leadQuery = CrmLead::whereIn('stage_id', $openStageIds);

        $phoneDigits = preg_replace('/[^0-9]/', '', (string) ($order->customer_phone ?? ''));
        $phoneSuffix = strlen($phoneDigits) >= 8 ? substr($phoneDigits, -8) : null;

        $leadQuery->where(function ($q) use ($order, $phoneSuffix) {
            if ($order->user_id) {
                $q->where('user_id', $order->user_id);
            }
            if ($phoneSuffix) {
                if ($order->user_id) {
                    $q->orWhere('customer_phone', 'like', "%{$phoneSuffix}");
                } else {
                    $q->where('customer_phone', 'like', "%{$phoneSuffix}");
                }
            }
        });

        $lead = $leadQuery->latest('updated_at')->first();
        if (!$lead) {
            return null;
        }

        $wonStage = $this->getWonStage();
        $amount = (float) ($order->total_amount ?? $order->grand_total ?? $lead->estimated_value ?? 0);

        return $this->moveLeadToStage($lead, $wonStage, null, [
            'won_amount' => $amount,
            'order_id' => $order->id,
            'activity_type' => 'auto_won',
            'activity_description' => "Auto-WON: Pembayaran Order #{$order->order_number} lunas (Rp " . number_format($amount, 0, ',', '.') . ').',
        ]);
    }

    /**
     * Helper membuat slug unik untuk tahap baru
     */
    public function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'stage';
        $slug = $base;
        $counter = 2;

        while (
            CrmPipelineStage::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
