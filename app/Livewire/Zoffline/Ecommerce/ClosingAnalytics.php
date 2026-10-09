<?php

namespace App\Livewire\Zoffline\Ecommerce;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.z')]
class ClosingAnalytics extends Component
{
    public string $period = 'all'; // all, today, this_week, this_month

    public function setPeriod(string $p)
    {
        $this->period = $p;
    }

    public function render()
    {
        $query = Conversation::query();

        if ($this->period === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($this->period === 'this_week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($this->period === 'this_month') {
            $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        }

        $totalChats = (clone $query)->count();
        $totalDeals = (clone $query)->where('closing_status', 'deal')->count();
        $totalFollowUps = (clone $query)->where('closing_status', 'follow_up')->count();
        $totalLost = (clone $query)->where('closing_status', 'lost')->count();
        $totalRevenue = (clone $query)->where('closing_status', 'deal')->sum('closing_amount');

        $conversionRate = $totalChats > 0 ? round(($totalDeals / $totalChats) * 100, 1) : 0;

        // CS Leaderboard
        $csLeaderboard = (clone $query)
            ->whereNotNull('closed_by_user_id')
            ->select(
                'closed_by_user_id',
                DB::raw('count(*) as total_handled'),
                DB::raw("sum(case when closing_status = 'deal' then 1 else 0 end) as deal_count"),
                DB::raw("sum(case when closing_status = 'deal' then closing_amount else 0 end) as total_omset")
            )
            ->groupBy('closed_by_user_id')
            ->orderByDesc('total_omset')
            ->get()
            ->map(function ($row) {
                $user = User::find($row->closed_by_user_id);
                $cRate = $row->total_handled > 0 ? round(($row->deal_count / $row->total_handled) * 100, 1) : 0;
                return [
                    'agent_name' => $user?->name ?? 'Staff #' . $row->closed_by_user_id,
                    'total_handled' => $row->total_handled,
                    'deal_count' => $row->deal_count,
                    'total_omset' => (float) $row->total_omset,
                    'rate' => $cRate,
                ];
            });

        // Recent Deals
        $recentDeals = (clone $query)
            ->where('closing_status', 'deal')
            ->with(['user', 'closedBy', 'productAccurate'])
            ->orderByDesc('closed_at')
            ->take(15)
            ->get();

        return view('livewire.zoffline.ecommerce.closing-analytics', [
            'totalChats' => $totalChats,
            'totalDeals' => $totalDeals,
            'totalFollowUps' => $totalFollowUps,
            'totalLost' => $totalLost,
            'totalRevenue' => $totalRevenue,
            'conversionRate' => $conversionRate,
            'csLeaderboard' => $csLeaderboard,
            'recentDeals' => $recentDeals,
        ]);
    }
}
