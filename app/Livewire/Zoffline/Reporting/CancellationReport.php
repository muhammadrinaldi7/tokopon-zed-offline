<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Exports\CancellationReportExport;
use App\Models\ApprovalRequest;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.z')]
class CancellationReport extends Component
{
    use WithPagination;

    public $dateFrom;
    public $dateTo;
    public $search = '';
    public $statusFilter = '';
    public $channelFilter = '';

    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['dateFrom', 'dateTo', 'search', 'statusFilter', 'channelFilter'])) {
            $this->resetPage();
        }
    }

    public function resetFilters()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->search = '';
        $this->statusFilter = '';
        $this->channelFilter = '';
        $this->resetPage();
    }

    protected function getFilteredQuery()
    {
        $query = ApprovalRequest::with([
            'requestedBy.branch',
            'histories.actedBy',
            'approvable.user.profile',
            'approvable.branch',
        ])
            ->where('request_type', 'ORDER_CANCELLATION')
            ->whereBetween('created_at', [
                Carbon::parse($this->dateFrom)->startOfDay(),
                Carbon::parse($this->dateTo)->endOfDay(),
            ]);

        if ($this->statusFilter) {
            if ($this->statusFilter === 'APPROVED') {
                $query->whereIn('status', ['APPROVED', 'COMPLETED']);
            } else {
                $query->where('status', $this->statusFilter);
            }
        }

        if ($this->channelFilter) {
            $query->whereHasMorph('approvable', [Order::class], function ($oq) {
                $oq->where('order_channel', $this->channelFilter);
            });
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('reason', 'like', '%' . $this->search . '%')
                    ->orWhereHas('requestedBy', function ($uq) {
                        $uq->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHasMorph('approvable', [Order::class], function ($oq) {
                        $oq->where('order_number', 'like', '%' . $this->search . '%')
                            ->orWhere('accurate_so_number', 'like', '%' . $this->search . '%')
                            ->orWhere('accurate_invoice_no', 'like', '%' . $this->search . '%')
                            ->orWhereHas('user', function ($uq) {
                                $uq->where('name', 'like', '%' . $this->search . '%')
                                    ->orWhereHas('profile', function ($pq) {
                                        $pq->where('phone_number', 'like', '%' . $this->search . '%');
                                    });
                            });
                    });
            });
        }

        return $query;
    }

    protected function getExportRows(): array
    {
        $requests = $this->getFilteredQuery()->latest()->get();

        $rows = [];
        $statusMap = [
            'APPROVED'  => 'Disetujui',
            'COMPLETED' => 'Disetujui',
            'REJECTED'  => 'Ditolak',
            'PENDING'   => 'Menunggu'
        ];

        foreach ($requests as $req) {
            $order = $req->approvable;
            $orderNumber = $order ? $order->order_number : '-';
            $channel = $order ? ($order->order_channel ?? 'POS') : '-';
            $grandTotal = $order ? (float)$order->grand_total : 0;
            $statusText = $statusMap[$req->status] ?? $req->status;

            $accurateNo = '-';
            if ($order) {
                $accurateNo = $order->order_channel === 'SO'
                    ? ($order->accurate_so_number ?? '-')
                    : ($order->accurate_invoice_no ?? '-');
            }

            $customerName = '-';
            $customerPhone = '-';
            if ($order && $order->user) {
                $customerName = $order->user->name ?? '-';
                $customerPhone = $order->user->profile?->phone_number ?? '-';
            }

            $branchName = $order?->branch?->name
                ?? $req->requestedBy?->branch?->name
                ?? ($order?->shipping_address_snapshot['store'] ?? '-');

            $lastHistory = $req->histories->last();
            $processedBy = $lastHistory?->actedBy?->name ?? '-';
            $processedAt = $lastHistory?->created_at ? $lastHistory->created_at->format('Y-m-d H:i:s') : '-';
            $notes = $lastHistory?->notes ?? '-';

            $rows[] = [
                'request_date'   => $req->created_at->format('Y-m-d H:i:s'),
                'order_number'   => $orderNumber,
                'channel'        => $channel,
                'accurate_no'    => $accurateNo,
                'cashier_name'   => $req->requestedBy->name ?? '-',
                'branch_name'    => $branchName,
                'customer_name'  => $customerName,
                'customer_phone' => $customerPhone,
                'grand_total'    => $grandTotal,
                'reason'         => $req->reason ?? '-',
                'status'         => $statusText,
                'processed_by'   => $processedBy,
                'processed_at'   => $processedAt,
                'approval_notes' => $notes,
            ];
        }

        return $rows;
    }

    public function exportExcel()
    {
        $rows = $this->getExportRows();

        if (empty($rows)) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Tidak ada data pembatalan untuk diexport sesuai filter yang dipilih.', type: 'warning');
            return;
        }

        $filename = 'laporan_pembatalan_' . $this->dateFrom . '_sd_' . $this->dateTo . '.xlsx';
        return Excel::download(new CancellationReportExport($rows), $filename);
    }

    public function exportCsv()
    {
        $rows = $this->getExportRows();

        if (empty($rows)) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Tidak ada data pembatalan untuk diexport sesuai filter yang dipilih.', type: 'warning');
            return;
        }

        $filename = 'laporan_pembatalan_' . $this->dateFrom . '_sd_' . $this->dateTo . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=" . $filename,
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');

            // Output UTF-8 BOM for Excel/CSV compatibility
            fputs($file, "\xEF\xBB\xBF");

            // CSV Headings
            fputcsv($file, [
                'No.',
                'Tanggal Pengajuan',
                'No. Order',
                'Channel',
                'No. Accurate',
                'Kasir Pengaju',
                'Cabang',
                'Pelanggan',
                'No. Telepon',
                'Total Transaksi (Rp)',
                'Alasan Pembatalan',
                'Status Pengajuan',
                'Diproses Oleh',
                'Waktu Proses',
                'Catatan Approval'
            ]);

            $no = 1;
            foreach ($rows as $item) {
                fputcsv($file, [
                    $no++,
                    $item['request_date'],
                    $item['order_number'],
                    $item['channel'],
                    $item['accurate_no'],
                    $item['cashier_name'],
                    $item['branch_name'],
                    $item['customer_name'],
                    $item['customer_phone'],
                    $item['grand_total'],
                    $item['reason'],
                    $item['status'],
                    $item['processed_by'],
                    $item['processed_at'],
                    $item['approval_notes'],
                ]);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }

    public function render()
    {
        // Base query without search & specific status/channel filters for summary metrics
        $baseQuery = ApprovalRequest::where('request_type', 'ORDER_CANCELLATION')
            ->whereBetween('created_at', [
                Carbon::parse($this->dateFrom)->startOfDay(),
                Carbon::parse($this->dateTo)->endOfDay(),
            ]);

        $totalCancellations = (clone $baseQuery)->count();
        $totalApproved = (clone $baseQuery)->whereIn('status', ['APPROVED', 'COMPLETED'])->count();
        $totalPending = (clone $baseQuery)->where('status', 'PENDING')->count();
        $totalRejected = (clone $baseQuery)->where('status', 'REJECTED')->count();

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

        // Paginated results with all active filters
        $requests = $this->getFilteredQuery()->latest()->paginate(20);

        return view('livewire.zoffline.reporting.cancellation-report', [
            'requests'           => $requests,
            'totalCancellations' => $totalCancellations,
            'totalApproved'      => $totalApproved,
            'totalPending'       => $totalPending,
            'totalRejected'      => $totalRejected,
            'totalValue'         => $totalValue,
            'topCashiers'        => $topCashiers,
        ]);
    }
}
