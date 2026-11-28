<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Models\Conversation;
use App\Models\CrmLead;
use App\Models\CrmPipelineStage;
use App\Models\User;
use App\Services\CrmPipelineService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ClosingAnalyticsController extends Controller
{
    /**
     * Tampilan utama Dashboard Analitik Closing CS & CRM (Inertia React)
     */
    public function index(Request $request): Response
    {
        $period = $request->input('period', 'this_month'); // today, this_week, this_month, all, custom
        $filterBu = $request->input('bu', null);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Conversation::query();

        // Filter unit bisnis
        if (!empty($filterBu)) {
            $query->where('business_unit_id', $filterBu);
        }

        // Filter periode tanggal
        $now = Carbon::now();
        if ($period === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($period === 'this_week') {
            $query->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
        } elseif ($period === 'this_month') {
            $query->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year);
        } elseif ($period === 'custom' && !empty($startDate) && !empty($endDate)) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        // Metrik KPI Utama
        $totalChats = (clone $query)->count();
        $totalDeals = (clone $query)->where('closing_status', 'deal')->count();
        $totalFollowUps = (clone $query)->where('closing_status', 'follow_up')->count();
        $totalLost = (clone $query)->where('closing_status', 'lost')->count();
        $totalRevenue = (float) (clone $query)->where('closing_status', 'deal')->sum('closing_amount');

        $conversionRate = $totalChats > 0 ? round(($totalDeals / $totalChats) * 100, 1) : 0;

        // Rata-rata nilai closing per deal (Average Order Value / Deal Size)
        $avgDealSize = $totalDeals > 0 ? round($totalRevenue / $totalDeals) : 0;

        // Leaderboard Performa CS
        $csLeaderboard = (clone $query)
            ->whereNotNull('closed_by_user_id')
            ->select(
                'closed_by_user_id',
                DB::raw('count(*) as total_handled'),
                DB::raw("sum(case when closing_status = 'deal' then 1 else 0 end) as deal_count"),
                DB::raw("sum(case when closing_status = 'follow_up' then 1 else 0 end) as follow_up_count"),
                DB::raw("sum(case when closing_status = 'lost' then 1 else 0 end) as lost_count"),
                DB::raw("sum(case when closing_status = 'deal' then closing_amount else 0 end) as total_omset")
            )
            ->groupBy('closed_by_user_id')
            ->orderByDesc('total_omset')
            ->get()
            ->map(function ($row, $index) {
                $user = User::find($row->closed_by_user_id);
                $cRate = $row->total_handled > 0 ? round(($row->deal_count / $row->total_handled) * 100, 1) : 0;
                return [
                    'rank' => $index + 1,
                    'user_id' => $row->closed_by_user_id,
                    'agent_name' => $user?->name ?? 'Staf CS #' . $row->closed_by_user_id,
                    'agent_email' => $user?->email,
                    'agent_avatar' => $user?->avatar,
                    'total_handled' => (int) $row->total_handled,
                    'deal_count' => (int) $row->deal_count,
                    'follow_up_count' => (int) $row->follow_up_count,
                    'lost_count' => (int) $row->lost_count,
                    'total_omset' => (float) $row->total_omset,
                    'formatted_omset' => 'Rp ' . number_format($row->total_omset, 0, ',', '.'),
                    'rate' => $cRate,
                ];
            });

        // 15 Transaksi Closing Terakhir
        $recentDeals = (clone $query)
            ->where('closing_status', 'deal')
            ->with([
                'user.profile',
                'closedBy:id,name,avatar',
                'productAccurate:id,name,base_price',
                'businessUnit:id,name',
            ])
            ->orderByDesc('closed_at')
            ->take(15)
            ->get()
            ->map(function ($deal) {
                $customerName = $deal->user?->name ?? ($deal->guest_name ?: 'Pelanggan');
                $customerPhone = $deal->guest_phone ?: ($deal->user?->profile?->phone_number ?? '-');

                return [
                    'id' => $deal->id,
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'closed_by_name' => $deal->closedBy?->name ?? 'Admin',
                    'closed_by_avatar' => $deal->closedBy?->avatar,
                    'amount' => (float) ($deal->closing_amount ?? 0),
                    'formatted_amount' => 'Rp ' . number_format($deal->closing_amount ?? 0, 0, ',', '.'),
                    'notes' => $deal->closing_notes,
                    'product_name' => $deal->productAccurate?->name,
                    'business_unit' => $deal->businessUnit?->name,
                    'closed_at' => $deal->closed_at ? $deal->closed_at->format('d M Y, H:i') : ($deal->updated_at?->format('d M Y, H:i') ?? '-'),
                ];
            });

        // ─── Fase 3: Analitik Funnel & Pipeline CRM ───
        $funnelStages = [];
        $topLostReasons = [];
        $pipelineMetrics = [
            'total_leads' => 0,
            'active_leads_count' => 0,
            'won_leads_count' => 0,
            'lost_leads_count' => 0,
            'pipeline_value' => 0,
            'formatted_pipeline_value' => 'Rp 0',
            'won_pipeline_revenue' => 0,
            'formatted_won_pipeline_revenue' => 'Rp 0',
            'pipeline_conversion_rate' => 0,
            'avg_hours_to_win' => 0,
            'overdue_follow_ups' => 0,
        ];

        if (Schema::hasTable('crm_leads') && Schema::hasTable('crm_pipeline_stages')) {
            app(CrmPipelineService::class)->ensureDefaultStagesExist();

            $leadQuery = CrmLead::visibleTo(Auth::user());
            if (!empty($filterBu)) {
                $leadQuery->where('business_unit_id', $filterBu);
            }
            if ($period === 'today') {
                $leadQuery->whereDate('created_at', Carbon::today());
            } elseif ($period === 'this_week') {
                $leadQuery->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]);
            } elseif ($period === 'this_month') {
                $leadQuery->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year);
            } elseif ($period === 'custom' && !empty($startDate) && !empty($endDate)) {
                $leadQuery->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay(),
                ]);
            }

            $allLeads = (clone $leadQuery)->with('stage')->get();
            $totalLeadsCount = $allLeads->count();
            $stages = CrmPipelineStage::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get();

            foreach ($stages as $st) {
                $stageLeads = $allLeads->where('stage_id', $st->id);
                $count = $stageLeads->count();
                $val = $st->isWon()
                    ? (float) $stageLeads->sum(fn ($l) => (float) ($l->won_amount ?? $l->estimated_value ?? 0))
                    : (float) $stageLeads->sum('estimated_value');
                $funnelStages[] = [
                    'id' => $st->id,
                    'name' => $st->name,
                    'color' => $st->color,
                    'stageType' => $st->stage_type,
                    'count' => $count,
                    'share_percent' => $totalLeadsCount > 0 ? round(($count / $totalLeadsCount) * 100, 1) : 0,
                    'value' => $val,
                    'formatted_value' => 'Rp ' . number_format($val, 0, ',', '.'),
                ];
            }

            $openLeads = $allLeads->filter(fn ($l) => $l->stage?->stage_type === CrmPipelineStage::TYPE_OPEN);
            $wonLeads = $allLeads->filter(fn ($l) => $l->stage?->stage_type === CrmPipelineStage::TYPE_WON);
            $lostLeads = $allLeads->filter(fn ($l) => $l->stage?->stage_type === CrmPipelineStage::TYPE_LOST);

            $pipeVal = (float) $openLeads->sum('estimated_value');
            $wonPipeRev = (float) $wonLeads->sum(fn ($l) => (float) ($l->won_amount ?? $l->estimated_value ?? 0));

            $avgHoursToWin = 0;
            $wonWithDates = $wonLeads->filter(fn ($l) => $l->won_at && $l->created_at);
            if ($wonWithDates->isNotEmpty()) {
                $totalHours = $wonWithDates->sum(fn ($l) => max(0, $l->created_at->diffInMinutes($l->won_at) / 60));
                $avgHoursToWin = round($totalHours / $wonWithDates->count(), 1);
            }

            $overdueFollowUps = $openLeads->filter(fn ($l) => $l->next_follow_up_at && $l->next_follow_up_at->isPast())->count();

            $pipelineMetrics = [
                'total_leads' => $totalLeadsCount,
                'active_leads_count' => $openLeads->count(),
                'won_leads_count' => $wonLeads->count(),
                'lost_leads_count' => $lostLeads->count(),
                'pipeline_value' => $pipeVal,
                'formatted_pipeline_value' => 'Rp ' . number_format($pipeVal, 0, ',', '.'),
                'won_pipeline_revenue' => $wonPipeRev,
                'formatted_won_pipeline_revenue' => 'Rp ' . number_format($wonPipeRev, 0, ',', '.'),
                'pipeline_conversion_rate' => $totalLeadsCount > 0 ? round(($wonLeads->count() / $totalLeadsCount) * 100, 1) : 0,
                'avg_hours_to_win' => $avgHoursToWin,
                'overdue_follow_ups' => $overdueFollowUps,
            ];

            $topLostReasons = $lostLeads
                ->groupBy(fn ($l) => $l->lost_reason ?: 'Lainnya')
                ->map(fn ($group, $reason) => [
                    'reason' => $reason,
                    'count' => $group->count(),
                    'percent' => $lostLeads->count() > 0 ? round(($group->count() / $lostLeads->count()) * 100, 1) : 0,
                ])
                ->sortByDesc('count')
                ->values()
                ->take(6)
                ->all();
        }

        return Inertia::render('Analytics/Index', [
            'metrics' => [
                'total_chats' => $totalChats,
                'total_deals' => $totalDeals,
                'total_follow_ups' => $totalFollowUps,
                'total_lost' => $totalLost,
                'total_revenue' => $totalRevenue,
                'formatted_revenue' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
                'conversion_rate' => $conversionRate,
                'avg_deal_size' => $avgDealSize,
                'formatted_avg_deal_size' => 'Rp ' . number_format($avgDealSize, 0, ',', '.'),
            ],
            'csLeaderboard' => $csLeaderboard,
            'recentDeals' => $recentDeals,
            'funnelStages' => $funnelStages,
            'pipelineMetrics' => $pipelineMetrics,
            'topLostReasons' => $topLostReasons,
            'filters' => [
                'period' => $period,
                'bu' => $filterBu,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
