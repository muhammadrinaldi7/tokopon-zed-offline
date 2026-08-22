<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Models\ApprovalRequest;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('layouts.z')]
class CancellationReport extends Component
{
    use WithPagination;

    public $dateFrom;
    public $dateTo;
    public $search = '';

    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['dateFrom', 'dateTo', 'search'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        // Clone query for base filtering
        $baseQuery = ApprovalRequest::with(['requestedBy', 'histories.actedBy', 'approvable'])
            ->where('request_type', 'ORDER_CANCELLATION')
            ->whereBetween('created_at', [
                Carbon::parse($this->dateFrom)->startOfDay(),
                Carbon::parse($this->dateTo)->endOfDay(),
            ]);

        $requestsQuery = (clone $baseQuery);

        if ($this->search) {
            $requestsQuery->where(function ($q) {
                $q->where('reason', 'like', '%' . $this->search . '%')
                    ->orWhereHas('requestedBy', function ($uq) {
                        $uq->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHasMorph('approvable', [\App\Models\Order::class], function ($oq) {
                        $oq->where('order_number', 'like', '%' . $this->search . '%')
                            ->orWhere('accurate_so_number', 'like', '%' . $this->search . '%')
                            ->orWhere('accurate_invoice_no', 'like', '%' . $this->search . '%');
                    });
            });
        }

        $requests = $requestsQuery->latest()->paginate(20);

        // Calculate Metrics
        $totalCancellations = (clone $baseQuery)->count();

        $orderIds = (clone $baseQuery)->pluck('approvable_id')->toArray();
        $totalValue = Order::whereIn('id', $orderIds)->sum('grand_total');

        // Top Cashiers
        $topCashiers = ApprovalRequest::with('requestedBy')
            ->where('request_type', 'ORDER_CANCELLATION')
            ->whereBetween('created_at', [
                Carbon::parse($this->dateFrom)->startOfDay(),
                Carbon::parse($this->dateTo)->endOfDay(),
            ])
            ->select('requested_by', DB::raw('count(*) as total'))
            ->groupBy('requested_by')
            ->orderBy('total', 'desc')
            ->take(5)
            ->get();

        return view('livewire.zoffline.reporting.cancellation-report', [
            'requests' => $requests,
            'totalCancellations' => $totalCancellations,
            'totalValue' => $totalValue,
            'topCashiers' => $topCashiers,
        ]);
    }

    public function exportExcel()
    {
        $baseQueryBuilder = ApprovalRequest::with(['requestedBy', 'histories.actedBy', 'approvable'])
            ->where('request_type', 'ORDER_CANCELLATION')
            ->whereBetween('created_at', [
                Carbon::parse($this->dateFrom)->startOfDay(),
                Carbon::parse($this->dateTo)->endOfDay(),
            ]);

        if ($this->search) {
            $baseQueryBuilder->where(function ($q) {
                $q->where('reason', 'like', '%' . $this->search . '%')
                    ->orWhereHas('requestedBy', function ($uq) {
                        $uq->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHasMorph('approvable', [\App\Models\Order::class], function ($oq) {
                        $oq->where('order_number', 'like', '%' . $this->search . '%')
                            ->orWhere('accurate_so_number', 'like', '%' . $this->search . '%')
                            ->orWhere('accurate_invoice_no', 'like', '%' . $this->search . '%');
                    });
            });
        }

        $baseQuery = $baseQueryBuilder->latest()->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=laporan-pembatalan-" . date('Ymd-His') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($baseQuery) {
            $file = fopen('php://output', 'w');

            // Output UTF-8 BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, ['Tanggal', 'Kasir', 'No Order', 'No Accurate', 'Channel', 'Nilai Transaksi (Rp)', 'Alasan', 'Status', 'Approved By']);

            foreach ($baseQuery as $req) {
                $orderNumber = $req->approvable ? $req->approvable->order_number : '-';
                $channel = $req->approvable ? $req->approvable->order_channel : '-';
                $grandTotal = $req->approvable ? $req->approvable->grand_total : 0;
                $statusMap = [
                    'APPROVED' => 'Disetujui',
                    'REJECTED' => 'Ditolak',
                    'PENDING'  => 'Menunggu'
                ];
                $statusText = $statusMap[$req->status] ?? $req->status;

                $accurateNo = '-';
                if ($req->approvable) {
                    $accurateNo = $req->approvable->order_channel === 'SO' 
                        ? ($req->approvable->accurate_so_number ?? '-') 
                        : ($req->approvable->accurate_invoice_no ?? '-');
                }

                fputcsv($file, [
                    $req->created_at->format('Y-m-d H:i:s'),
                    $req->requestedBy->name ?? '-',
                    $orderNumber,
                    $accurateNo,
                    $channel,
                    $grandTotal,
                    $req->reason ?? '-',
                    $statusText,
                    $req->histories->last()?->actedBy->name ?? '-'
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'laporan-pembatalan-' . date('Ymd-His') . '.csv', $headers);
    }
}
