<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnit;
use App\Models\Conversation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'filters' => [
                'period' => $period,
                'bu' => $filterBu,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
