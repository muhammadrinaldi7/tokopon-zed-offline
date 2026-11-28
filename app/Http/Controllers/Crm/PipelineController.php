<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\CrmLead;
use App\Models\CrmLeadActivity;
use App\Models\CrmPipelineStage;
use App\Models\ProductAccurate;
use App\Models\User;
use App\Services\CrmPipelineService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PipelineController extends Controller
{
    public function __construct(protected CrmPipelineService $pipelineService)
    {
    }

    /**
     * Tampilan Utama CRM Sales Pipeline (Kanban Board + List View)
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        if (!$user || (!$user->hasRole('superadmin') && !$user->can('view-crm-pipeline') && !$user->can('access-cs-chat'))) {
            abort(403, 'Anda tidak memiliki izin mengakses CRM Pipeline.');
        }

        $this->pipelineService->ensureDefaultStagesExist();
        $this->pipelineService->ensurePermissionsExist();

        // Backfill otomatis jika tabel crm_leads masih kosong agar data chat lama langsung muncul
        if (CrmLead::count() === 0 && Conversation::count() > 0) {
            $this->pipelineService->backfillFromConversations(200);
        }

        $canViewAll = $user->hasRole('superadmin') || $user->can('view-all-crm-leads');
        $canManageStages = $user->hasRole('superadmin') || $user->can('manage-crm-stages');

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'bu' => $request->input('bu', ''),
            'assignee' => $canViewAll ? $request->input('assignee', '') : '',
            'temperature' => $request->input('temperature', ''),
            'interest_type' => $request->input('interest_type', ''),
            'follow_up' => $request->input('follow_up', 'all'),
            'period' => $request->input('period', 'all'),
        ];

        $query = $this->buildFilteredQuery($user, $filters, $canViewAll);

        $leads = $query
            ->with([
                'stage',
                'assignedTo:id,name,avatar',
                'businessUnit:id,name',
                'productAccurate:id,name,base_price',
                'order:id,order_number,total_amount,order_status',
                'activities' => fn ($q) => $q->with(['actor:id,name', 'fromStage:id,name', 'toStage:id,name'])->take(8),
            ])
            ->orderByRaw('CASE WHEN next_follow_up_at IS NOT NULL AND next_follow_up_at < ? THEN 0 ELSE 1 END', [now()])
            ->orderByDesc('updated_at')
            ->take(300)
            ->get();

        $allStages = CrmPipelineStage::orderBy('sort_order')->orderBy('id')->get();
        $activeStages = $allStages->where('is_active', true)->values();

        $formattedLeads = $leads->map(fn (CrmLead $lead) => $this->formatLead($lead))->values()->all();

        // Hitung ringkasan KPI Pipeline
        $openLeads = $leads->filter(fn ($l) => $l->stage?->stage_type === CrmPipelineStage::TYPE_OPEN);
        $wonLeads = $leads->filter(fn ($l) => $l->stage?->stage_type === CrmPipelineStage::TYPE_WON);
        $lostLeads = $leads->filter(fn ($l) => $l->stage?->stage_type === CrmPipelineStage::TYPE_LOST);

        $pipelineValue = (float) $openLeads->sum('estimated_value');
        $wonRevenue = (float) $wonLeads->sum(fn ($l) => (float) ($l->won_amount ?? $l->estimated_value ?? 0));
        $overdueCount = $openLeads->filter(fn ($l) => $l->next_follow_up_at && $l->next_follow_up_at->isPast())->count();
        $todayCount = $openLeads->filter(fn ($l) => $l->next_follow_up_at && $l->next_follow_up_at->isToday())->count();
        $totalCount = $leads->count();
        $conversionRate = $totalCount > 0 ? round(($wonLeads->count() / $totalCount) * 100, 1) : 0;

        // Daftar agen CS untuk dropdown penugasan
        $csAgents = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['superadmin', 'admin', 'cs']);
        })->orderBy('name')->get(['id', 'name', 'email', 'avatar']);

        return Inertia::render('Pipeline/Index', [
            'stages' => $activeStages->map(fn ($s) => $this->formatStage($s))->values()->all(),
            'allStages' => $allStages->map(fn ($s) => $this->formatStage($s))->values()->all(),
            'leads' => $formattedLeads,
            'summary' => [
                'total_leads' => $totalCount,
                'open_count' => $openLeads->count(),
                'won_count' => $wonLeads->count(),
                'lost_count' => $lostLeads->count(),
                'pipeline_value' => $pipelineValue,
                'formatted_pipeline_value' => 'Rp ' . number_format($pipelineValue, 0, ',', '.'),
                'won_revenue' => $wonRevenue,
                'formatted_won_revenue' => 'Rp ' . number_format($wonRevenue, 0, ',', '.'),
                'overdue_follow_ups' => $overdueCount,
                'today_follow_ups' => $todayCount,
                'conversion_rate' => $conversionRate,
            ],
            'csAgents' => $csAgents,
            'lostReasons' => CrmLead::LOST_REASONS,
            'permissions' => [
                'canViewAll' => $canViewAll,
                'canManageStages' => $canManageStages,
            ],
            'filters' => $filters,
        ]);
    }

    /**
     * Tambah Lead Manual (WhatsApp / Walk-in / Instagram / Lainnya)
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        $canViewAll = $user->hasRole('superadmin') || $user->can('view-all-crm-leads');

        $validated = $request->validate([
            'customer_name' => 'required|string|max:150',
            'customer_phone' => 'nullable|string|max:40',
            'stage_id' => 'nullable|exists:crm_pipeline_stages,id',
            'business_unit_id' => 'nullable|exists:business_units,id',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'product_accurate_id' => 'nullable|exists:product_accurates,id',
            'temperature' => 'nullable|in:cold,warm,hot',
            'interest_type' => 'nullable|in:new,second,trade_in,other',
            'source' => 'nullable|in:mobile_chat,trade_in,whatsapp,walk_in,manual',
            'estimated_value' => 'nullable|numeric|min:0',
            'next_follow_up_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $stage = !empty($validated['stage_id'])
            ? CrmPipelineStage::findOrFail($validated['stage_id'])
            : $this->pipelineService->getDefaultOpenStage();

        $estimatedValue = (float) ($validated['estimated_value'] ?? 0);
        if ($estimatedValue <= 0 && !empty($validated['product_accurate_id'])) {
            $estimatedValue = (float) (ProductAccurate::where('id', $validated['product_accurate_id'])->value('base_price') ?? 0);
        }

        $assigneeId = $canViewAll && array_key_exists('assigned_to_user_id', $validated)
            ? ($validated['assigned_to_user_id'] ?: null)
            : $user->id;

        $lead = CrmLead::create([
            'stage_id' => $stage->id,
            'business_unit_id' => $validated['business_unit_id'] ?? $user->business_unit_id,
            'assigned_to_user_id' => $assigneeId,
            'product_accurate_id' => $validated['product_accurate_id'] ?? null,
            'customer_name' => trim($validated['customer_name']),
            'customer_phone' => $validated['customer_phone'] ? trim($validated['customer_phone']) : null,
            'temperature' => $validated['temperature'] ?? CrmLead::TEMP_WARM,
            'interest_type' => $validated['interest_type'] ?? 'new',
            'source' => $validated['source'] ?? 'whatsapp',
            'estimated_value' => $estimatedValue,
            'won_amount' => $stage->isWon() ? $estimatedValue : null,
            'won_at' => $stage->isWon() ? now() : null,
            'lost_at' => $stage->isLost() ? now() : null,
            'next_follow_up_at' => !empty($validated['next_follow_up_at']) ? Carbon::parse($validated['next_follow_up_at']) : null,
            'notes' => $validated['notes'] ?? null,
        ]);

        CrmLeadActivity::create([
            'lead_id' => $lead->id,
            'actor_user_id' => $user->id,
            'activity_type' => 'created',
            'to_stage_id' => $stage->id,
            'description' => "Lead manual ditambahkan oleh {$user->name} di tahap {$stage->name}.",
        ]);

        $lead->load(['stage', 'assignedTo:id,name,avatar', 'businessUnit:id,name', 'productAccurate:id,name,base_price', 'order:id,order_number,total_amount,order_status', 'activities.actor:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Lead customer berhasil ditambahkan ke pipeline.',
            'lead' => $this->formatLead($lead),
        ], 201);
    }

    /**
     * Perbarui detail prospek (suhu, nominal estimasi, jadwal follow-up, penanggung jawab, catatan)
     */
    public function update(Request $request, CrmLead $lead): JsonResponse
    {
        $user = Auth::user();
        $this->authorizeLeadAccess($user, $lead);
        $canViewAll = $user->hasRole('superadmin') || $user->can('view-all-crm-leads');

        $validated = $request->validate([
            'customer_name' => 'sometimes|required|string|max:150',
            'customer_phone' => 'nullable|string|max:40',
            'business_unit_id' => 'nullable|exists:business_units,id',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'product_accurate_id' => 'nullable|exists:product_accurates,id',
            'temperature' => 'nullable|in:cold,warm,hot',
            'interest_type' => 'nullable|in:new,second,trade_in,other',
            'source' => 'nullable|in:mobile_chat,trade_in,whatsapp,walk_in,manual',
            'estimated_value' => 'nullable|numeric|min:0',
            'won_amount' => 'nullable|numeric|min:0',
            'lost_reason' => 'nullable|string|max:150',
            'next_follow_up_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $oldAssigneeId = $lead->assigned_to_user_id;
        if (!$canViewAll && array_key_exists('assigned_to_user_id', $validated)) {
            // CS biasa hanya boleh mengambil untuk dirinya atau melepas ke unassigned
            $reqAssignee = $validated['assigned_to_user_id'];
            if ($reqAssignee !== null && (int) $reqAssignee !== (int) $user->id) {
                unset($validated['assigned_to_user_id']);
            }
        }

        if (array_key_exists('next_follow_up_at', $validated)) {
            $validated['next_follow_up_at'] = !empty($validated['next_follow_up_at'])
                ? Carbon::parse($validated['next_follow_up_at'])
                : null;
        }

        $lead->update($validated);

        // Sinkronkan penugasan CS ke Conversation jika terhubung
        if (array_key_exists('assigned_to_user_id', $validated) && $oldAssigneeId != $lead->assigned_to_user_id) {
            if ($lead->conversation) {
                $lead->conversation->update([
                    'assigned_to_user_id' => $lead->assigned_to_user_id,
                    'assigned_at' => $lead->assigned_to_user_id ? now() : null,
                ]);
            }
            $newAgentName = $lead->assigned_to_user_id
                ? (User::where('id', $lead->assigned_to_user_id)->value('name') ?: 'CS')
                : 'Antrean Bebas';
            CrmLeadActivity::create([
                'lead_id' => $lead->id,
                'actor_user_id' => $user->id,
                'activity_type' => 'assigned',
                'description' => "Penanggung jawab diubah ke {$newAgentName}.",
            ]);
        }

        $lead->refresh()->load(['stage', 'assignedTo:id,name,avatar', 'businessUnit:id,name', 'productAccurate:id,name,base_price', 'order:id,order_number,total_amount,order_status', 'activities' => fn ($q) => $q->with(['actor:id,name', 'fromStage:id,name', 'toStage:id,name'])->take(8)]);

        return response()->json([
            'success' => true,
            'message' => 'Data prospek berhasil diperbarui.',
            'lead' => $this->formatLead($lead),
        ]);
    }

    /**
     * Pindahkan tahap kualifikasi lead (Drag & Drop Kanban atau Quick Stage di Live Chat)
     */
    public function moveStage(Request $request, CrmLead $lead): JsonResponse
    {
        $user = Auth::user();
        $this->authorizeLeadAccess($user, $lead);

        $validated = $request->validate([
            'stage_id' => 'required|exists:crm_pipeline_stages,id',
            'won_amount' => 'nullable|numeric|min:0',
            'lost_reason' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:2000',
            'next_follow_up_at' => 'nullable|date',
            'temperature' => 'nullable|in:cold,warm,hot',
        ]);

        $targetStage = CrmPipelineStage::findOrFail($validated['stage_id']);

        $updatedLead = $this->pipelineService->moveLeadToStage($lead, $targetStage, $user->id, $validated);
        $updatedLead->load(['stage', 'assignedTo:id,name,avatar', 'businessUnit:id,name', 'productAccurate:id,name,base_price', 'order:id,order_number,total_amount,order_status', 'activities' => fn ($q) => $q->with(['actor:id,name', 'fromStage:id,name', 'toStage:id,name'])->take(8)]);

        return response()->json([
            'success' => true,
            'message' => "Customer dipindahkan ke tahap {$targetStage->name}.",
            'lead' => $this->formatLead($updatedLead),
        ]);
    }

    /**
     * Ubah tahap kualifikasi langsung dari Live Chat CS berdasarkan conversation_id
     */
    public function moveByConversation(Request $request, Conversation $conversation): JsonResponse
    {
        $user = Auth::user();
        $lead = $this->pipelineService->syncLeadFromConversation($conversation, $user->id);

        if (!$lead) {
            return response()->json(['success' => false, 'message' => 'Gagal menginisialisasi data pipeline.'], 500);
        }

        return $this->moveStage($request, $lead);
    }

    /**
     * Tambah catatan aktivitas / log follow-up baru pada lead
     */
    public function addActivity(Request $request, CrmLead $lead): JsonResponse
    {
        $user = Auth::user();
        $this->authorizeLeadAccess($user, $lead);

        $validated = $request->validate([
            'description' => 'required|string|max:2000',
            'activity_type' => 'nullable|in:note,follow_up',
            'next_follow_up_at' => 'nullable|date',
        ]);

        if (!empty($validated['next_follow_up_at'])) {
            $lead->update(['next_follow_up_at' => Carbon::parse($validated['next_follow_up_at'])]);
        }

        $activity = CrmLeadActivity::create([
            'lead_id' => $lead->id,
            'actor_user_id' => $user->id,
            'activity_type' => $validated['activity_type'] ?? 'note',
            'description' => trim($validated['description']),
        ]);
        $activity->load('actor:id,name');

        return response()->json([
            'success' => true,
            'activity' => $this->formatActivity($activity),
            'nextFollowUpAt' => $lead->fresh()->next_follow_up_at?->format('Y-m-d\TH:i'),
            'nextFollowUpLabel' => $lead->fresh()->next_follow_up_at?->format('d M Y, H:i'),
        ]);
    }

    /**
     * Hapus lead manual / duplikat
     */
    public function destroy(CrmLead $lead): JsonResponse
    {
        $user = Auth::user();
        $this->authorizeLeadAccess($user, $lead);

        $lead->delete();

        return response()->json(['success' => true, 'message' => 'Lead berhasil dihapus.']);
    }

    /**
     * Sinkronisasi percakapan lama yang belum ada di papan pipeline
     */
    public function syncConversations(): JsonResponse
    {
        $count = $this->pipelineService->backfillFromConversations(300);

        return response()->json([
            'success' => true,
            'synced_count' => $count,
            'message' => $count > 0
                ? "{$count} percakapan berhasil disinkronkan ke CRM Pipeline."
                : 'Seluruh percakapan sudah tersinkronisasi di CRM Pipeline.',
        ]);
    }

    /**
     * Export daftar lead sesuai filter aktif ke CSV
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $canViewAll = $user->hasRole('superadmin') || $user->can('view-all-crm-leads');

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'bu' => $request->input('bu', ''),
            'assignee' => $canViewAll ? $request->input('assignee', '') : '',
            'temperature' => $request->input('temperature', ''),
            'interest_type' => $request->input('interest_type', ''),
            'follow_up' => $request->input('follow_up', 'all'),
            'period' => $request->input('period', 'all'),
        ];

        $leads = $this->buildFilteredQuery($user, $filters, $canViewAll)
            ->with(['stage', 'assignedTo:id,name', 'businessUnit:id,name', 'productAccurate:id,name'])
            ->orderByDesc('updated_at')
            ->get();

        $filename = 'crm-pipeline-leads-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($leads) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM agar Excel terbaca rapi
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, [
                'ID', 'Customer', 'No HP', 'Tahap Kualifikasi', 'Tipe Tahap', 'Suhu',
                'Minat', 'Sumber', 'Toko', 'Produk Incaran', 'CS Penanggung Jawab',
                'Estimasi Nilai (Rp)', 'Nominal WON (Rp)', 'Alasan Batal',
                'Jadwal Follow-Up', 'Tanggal WON', 'Catatan', 'Dibuat Pada',
            ]);

            foreach ($leads as $l) {
                fputcsv($out, [
                    $l->id,
                    $l->customer_name,
                    $l->customer_phone ?? '-',
                    $l->stage?->name ?? '-',
                    strtoupper((string) ($l->stage?->stage_type ?? 'open')),
                    strtoupper((string) $l->temperature),
                    $l->interest_type,
                    $l->source,
                    $l->businessUnit?->name ?? '-',
                    $l->productAccurate?->name ?? '-',
                    $l->assignedTo?->name ?? 'Belum Diambil',
                    (float) $l->estimated_value,
                    (float) ($l->won_amount ?? 0),
                    $l->lost_reason ?? '',
                    $l->next_follow_up_at?->format('Y-m-d H:i') ?? '',
                    $l->won_at?->format('Y-m-d H:i') ?? '',
                    $l->notes ?? '',
                    $l->created_at?->format('Y-m-d H:i') ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ─── Kelola Tahapan Kualifikasi Dinamis (Label, Warna, Urutan) ───

    public function storeStage(Request $request): JsonResponse
    {
        $this->authorizeStageManagement();

        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'color' => 'required|in:slate,sky,indigo,amber,violet,emerald,rose,teal,orange',
            'stage_type' => 'required|in:open,won,lost',
        ]);

        $maxOrder = (int) CrmPipelineStage::max('sort_order');
        $stage = CrmPipelineStage::create([
            'name' => trim($validated['name']),
            'slug' => $this->pipelineService->generateUniqueSlug($validated['name']),
            'color' => $validated['color'],
            'stage_type' => $validated['stage_type'],
            'sort_order' => $maxOrder + 1,
            'is_default' => false,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Tahap \"{$stage->name}\" berhasil ditambahkan.",
            'stage' => $this->formatStage($stage),
        ], 201);
    }

    public function updateStage(Request $request, CrmPipelineStage $stage): JsonResponse
    {
        $this->authorizeStageManagement();

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:80',
            'color' => 'sometimes|required|in:slate,sky,indigo,amber,violet,emerald,rose,teal,orange',
            'stage_type' => 'sometimes|required|in:open,won,lost',
            'is_active' => 'sometimes|boolean',
            'is_default' => 'sometimes|boolean',
        ]);

        // Lindungi minimal harus ada 1 tahap aktif untuk tiap tipe utama (open, won, lost)
        $nextType = $validated['stage_type'] ?? $stage->stage_type;
        $nextActive = array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $stage->is_active;

        if (!$nextActive || $nextType !== $stage->stage_type) {
            $otherActiveSameType = CrmPipelineStage::where('stage_type', $stage->stage_type)
                ->where('is_active', true)
                ->where('id', '!=', $stage->id)
                ->count();

            if ($otherActiveSameType === 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Minimal harus ada 1 tahap aktif bertipe \"{$stage->stage_type}\" agar otomatisasi tetap berjalan.",
                ], 422);
            }
        }

        if (!empty($validated['is_default'])) {
            if ($nextType !== CrmPipelineStage::TYPE_OPEN) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya tahap bertipe open yang dapat dijadikan tahap default lead baru.',
                ], 422);
            }
            CrmPipelineStage::where('id', '!=', $stage->id)->update(['is_default' => false]);
        }

        $stage->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Tahap \"{$stage->name}\" berhasil diperbarui.",
            'stage' => $this->formatStage($stage->fresh()),
        ]);
    }

    public function reorderStages(Request $request): JsonResponse
    {
        $this->authorizeStageManagement();

        $validated = $request->validate([
            'stage_ids' => 'required|array|min:1',
            'stage_ids.*' => 'integer|exists:crm_pipeline_stages,id',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['stage_ids'] as $index => $stageId) {
                CrmPipelineStage::where('id', $stageId)->update(['sort_order' => $index + 1]);
            }
        });

        $stages = CrmPipelineStage::orderBy('sort_order')->orderBy('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Urutan tahap kualifikasi berhasil disimpan.',
            'stages' => $stages->map(fn ($s) => $this->formatStage($s))->values()->all(),
        ]);
    }

    public function destroyStage(Request $request, CrmPipelineStage $stage): JsonResponse
    {
        $this->authorizeStageManagement();

        $otherSameType = CrmPipelineStage::where('stage_type', $stage->stage_type)
            ->where('is_active', true)
            ->where('id', '!=', $stage->id)
            ->first();

        if (!$otherSameType) {
            return response()->json([
                'success' => false,
                'message' => "Tidak bisa menghapus satu-satunya tahap aktif bertipe \"{$stage->stage_type}\".",
            ], 422);
        }

        $leadCount = $stage->leads()->count();
        if ($leadCount > 0) {
            $fallbackId = $request->input('fallback_stage_id') ?: $otherSameType->id;
            if ((int) $fallbackId === (int) $stage->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pilih tahap tujuan untuk memindahkan lead yang ada terlebih dahulu.',
                ], 422);
            }
            CrmLead::where('stage_id', $stage->id)->update(['stage_id' => $fallbackId]);
        }

        if ($stage->is_default) {
            $otherSameType->update(['is_default' => true]);
        }

        $stage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tahap berhasil dihapus.',
        ]);
    }

    // ─── Private Helpers ───

    protected function authorizeLeadAccess(User $user, CrmLead $lead): void
    {
        if ($user->hasRole('superadmin') || $user->can('view-all-crm-leads')) {
            return;
        }
        if ($lead->assigned_to_user_id !== null && (int) $lead->assigned_to_user_id !== (int) $user->id) {
            abort(403, 'Anda tidak memiliki izin mengelola lead milik CS lain.');
        }
    }

    protected function authorizeStageManagement(): void
    {
        $user = Auth::user();
        if (!$user || (!$user->hasRole('superadmin') && !$user->can('manage-crm-stages'))) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah konfigurasi tahapan CRM.');
        }
    }

    protected function buildFilteredQuery(User $user, array $filters, bool $canViewAll)
    {
        $query = CrmLead::visibleTo($user);

        if (!empty($filters['bu'])) {
            $query->where('business_unit_id', $filters['bu']);
        }

        if ($canViewAll && !empty($filters['assignee'])) {
            if ($filters['assignee'] === 'unassigned') {
                $query->whereNull('assigned_to_user_id');
            } elseif ($filters['assignee'] === 'mine') {
                $query->where('assigned_to_user_id', $user->id);
            } else {
                $query->where('assigned_to_user_id', $filters['assignee']);
            }
        }

        if (!empty($filters['temperature'])) {
            $query->where('temperature', $filters['temperature']);
        }

        if (!empty($filters['interest_type'])) {
            $query->where('interest_type', $filters['interest_type']);
        }

        if ($filters['follow_up'] === 'overdue') {
            $query->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<', now())
                ->whereHas('stage', fn ($q) => $q->where('stage_type', CrmPipelineStage::TYPE_OPEN));
        } elseif ($filters['follow_up'] === 'today') {
            $query->whereDate('next_follow_up_at', Carbon::today())
                ->whereHas('stage', fn ($q) => $q->where('stage_type', CrmPipelineStage::TYPE_OPEN));
        }

        $now = Carbon::now();
        if ($filters['period'] === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($filters['period'] === 'this_week') {
            $query->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
        } elseif ($filters['period'] === 'this_month') {
            $query->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year);
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $digits = preg_replace('/[^0-9]/', '', $term);
            $query->where(function ($q) use ($term, $digits) {
                $q->where('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_phone', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%")
                    ->orWhereHas('productAccurate', fn ($p) => $p->where('name', 'like', "%{$term}%"));
                if (strlen($digits) >= 4) {
                    $q->orWhere('customer_phone', 'like', "%{$digits}%");
                }
            });
        }

        return $query;
    }

    protected function formatStage(CrmPipelineStage $stage): array
    {
        return [
            'id' => $stage->id,
            'name' => $stage->name,
            'slug' => $stage->slug,
            'color' => $stage->color,
            'stageType' => $stage->stage_type,
            'sortOrder' => $stage->sort_order,
            'isDefault' => (bool) $stage->is_default,
            'isActive' => (bool) $stage->is_active,
        ];
    }

    protected function formatLead(CrmLead $lead): array
    {
        $waUrl = null;
        if (!empty($lead->customer_phone)) {
            $clean = preg_replace('/[^0-9]/', '', (string) $lead->customer_phone);
            if (str_starts_with($clean, '0')) {
                $clean = '62' . substr($clean, 1);
            } elseif (str_starts_with($clean, '8')) {
                $clean = '62' . $clean;
            }
            if (strlen($clean) >= 9) {
                $msg = "Halo {$lead->customer_name}, saya " . (Auth::user()?->name ?: 'CS Tokopon') . ' dari Tokopon Gadget.';
                $waUrl = 'https://wa.me/' . $clean . '?text=' . urlencode($msg);
            }
        }

        $isOpen = $lead->stage?->stage_type === CrmPipelineStage::TYPE_OPEN;
        $isOverdue = $isOpen && $lead->next_follow_up_at && $lead->next_follow_up_at->isPast();
        $isToday = $isOpen && $lead->next_follow_up_at && $lead->next_follow_up_at->isToday();

        return [
            'id' => $lead->id,
            'stageId' => $lead->stage_id,
            'stageName' => $lead->stage?->name ?? 'New Lead',
            'stageColor' => $lead->stage?->color ?? 'slate',
            'stageType' => $lead->stage?->stage_type ?? 'open',
            'conversationId' => $lead->conversation_id,
            'userId' => $lead->user_id,
            'businessUnitId' => $lead->business_unit_id,
            'businessUnitName' => $lead->businessUnit?->name ?? 'Semua Toko',
            'assignedToId' => $lead->assigned_to_user_id,
            'assignedToName' => $lead->assignedTo?->name,
            'assignedToAvatar' => $lead->assignedTo?->avatar,
            'isAssignedToMe' => $lead->assigned_to_user_id === Auth::id(),
            'productId' => $lead->product_accurate_id,
            'productName' => $lead->productAccurate?->name,
            'orderId' => $lead->order_id,
            'orderNumber' => $lead->order?->order_number,
            'customerName' => $lead->customer_name,
            'customerInitial' => strtoupper(substr($lead->customer_name ?: 'P', 0, 1)),
            'customerPhone' => $lead->customer_phone,
            'waUrl' => $waUrl,
            'temperature' => $lead->temperature ?: 'warm',
            'interestType' => $lead->interest_type ?: 'new',
            'source' => $lead->source ?: 'mobile_chat',
            'estimatedValue' => (float) ($lead->estimated_value ?? 0),
            'wonAmount' => $lead->won_amount !== null ? (float) $lead->won_amount : null,
            'lostReason' => $lead->lost_reason,
            'nextFollowUpAt' => $lead->next_follow_up_at?->format('Y-m-d\TH:i'),
            'nextFollowUpLabel' => $lead->next_follow_up_at?->format('d M Y, H:i'),
            'isOverdue' => $isOverdue,
            'isFollowUpToday' => $isToday,
            'wonAt' => $lead->won_at?->format('d M Y, H:i'),
            'lostAt' => $lead->lost_at?->format('d M Y, H:i'),
            'notes' => $lead->notes,
            'createdAt' => $lead->created_at?->format('d M Y, H:i'),
            'updatedAtHuman' => $lead->updated_at?->diffForHumans(),
            'activities' => $lead->relationLoaded('activities')
                ? $lead->activities->map(fn ($a) => $this->formatActivity($a))->values()->all()
                : [],
        ];
    }

    protected function formatActivity(CrmLeadActivity $activity): array
    {
        return [
            'id' => $activity->id,
            'type' => $activity->activity_type,
            'actorName' => $activity->actor?->name ?? 'Sistem',
            'fromStageName' => $activity->fromStage?->name,
            'toStageName' => $activity->toStage?->name,
            'description' => $activity->description,
            'createdAt' => $activity->created_at?->format('d M Y, H:i'),
        ];
    }
}
