<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Exports\SalesOrderReportExport;
use App\Models\Branch;
use App\Models\BusinessUnit;
use App\Models\Order;
use App\Models\ProductAccurate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.z')]
class SalesOrderReport extends Component
{
    use WithPagination;

    public $dateRange = 'this_month';
    public $startDate;
    public $endDate;
    public $dateFrom;
    public $dateTo;
    public $search = '';
    public $businessUnitFilter = '';
    public $branchFilter = '';
    public $proyekFilter = [];
    public $csvSeparator = ';';

    protected $queryString = [
        'businessUnitFilter' => ['except' => ''],
        'branchFilter'       => ['except' => ''],
        'dateRange'          => ['except' => 'this_month'],
        'startDate'          => ['except' => ''],
        'endDate'            => ['except' => ''],
        'search'             => ['except' => ''],
    ];

    public function mount()
    {
        $this->businessUnitFilter = (string)(Auth::user()?->getActiveBusinessUnitId() ?? '');
        $this->setDateRange();
    }

    public function updatedBusinessUnitFilter()
    {
        $this->branchFilter = '';
        $this->resetPage();
    }

    public function updatedBranchFilter()
    {
        $this->resetPage();
    }

    public function updatedProyekFilter()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedDateRange()
    {
        if ($this->dateRange !== 'custom') {
            $this->setDateRange();
        }
        $this->resetPage();
    }

    public function updatedStartDate()
    {
        $this->dateRange = 'custom';
        $this->dateFrom = $this->startDate;
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->dateRange = 'custom';
        $this->dateTo = $this->endDate;
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->dateRange = 'custom';
        $this->startDate = $this->dateFrom;
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->dateRange = 'custom';
        $this->endDate = $this->dateTo;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'branchFilter', 'proyekFilter']);
        $this->businessUnitFilter = (string)(Auth::user()?->getActiveBusinessUnitId() ?? '');
        $this->dateRange = 'this_month';
        $this->setDateRange();
        $this->resetPage();
    }

    private function setDateRange(): void
    {
        $now = now();
        switch ($this->dateRange) {
            case 'today':
                $this->startDate = $now->copy()->startOfDay()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfDay()->format('Y-m-d');
                break;
            case 'yesterday':
                $this->startDate = $now->copy()->subDay()->startOfDay()->format('Y-m-d');
                $this->endDate = $now->copy()->subDay()->endOfDay()->format('Y-m-d');
                break;
            case 'this_week':
                $this->startDate = $now->copy()->startOfWeek()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfWeek()->format('Y-m-d');
                break;
            case 'this_month':
                $this->startDate = $now->copy()->startOfMonth()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfMonth()->format('Y-m-d');
                break;
            case 'last_month':
                $this->startDate = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->endDate = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case 'this_year':
                $this->startDate = $now->copy()->startOfYear()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfYear()->format('Y-m-d');
                break;
        }

        $this->dateFrom = $this->startDate;
        $this->dateTo = $this->endDate;
    }

    public function getBaseQueryProperty()
    {
        $fromDate = $this->startDate ?: ($this->dateFrom ?: now()->startOfMonth()->format('Y-m-d'));
        $toDate = $this->endDate ?: ($this->dateTo ?: now()->endOfMonth()->format('Y-m-d'));

        $start = Carbon::parse($fromDate)->startOfDay();
        $end = Carbon::parse($toDate)->endOfDay();

        return Order::where('order_channel', 'SO')
            ->whereBetween('orders.created_at', [$start, $end])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('orders.order_number', 'like', '%' . $this->search . '%')
                        ->orWhere('orders.accurate_invoice_no', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($qc) {
                            $qc->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%')
                                ->orWhereHas('profile', function ($qp) {
                                    $qp->where('phone_number', 'like', '%' . $this->search . '%');
                                });
                        })
                        ->orWhereHas('salesBy', function ($qs) {
                            $qs->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('handledBy', function ($qh) {
                            $qh->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('items', function ($qi) {
                            $qi->where('product_name', 'like', '%' . $this->search . '%')
                                ->orWhere('serial_number', 'like', '%' . $this->search . '%')
                                ->orWhereHasMorph('variant', [ProductAccurate::class], function ($qv) {
                                    $qv->where('name', 'like', '%' . $this->search . '%')
                                       ->orWhere('item_no', 'like', '%' . $this->search . '%');
                                })
                                ->orWhereHasMorph('variant', [\App\Models\ProductVariant::class], function ($qv) {
                                    $qv->where('sku', 'like', '%' . $this->search . '%')
                                       ->orWhereHas('product', function ($qp) {
                                           $qp->where('name', 'like', '%' . $this->search . '%');
                                       });
                                })
                                ->orWhereHasMorph('variant', [\App\Models\SecondProductVariant::class], function ($qv) {
                                    $qv->where('sku', 'like', '%' . $this->search . '%')
                                       ->orWhereHas('secondProduct', function ($qsp) {
                                           $qsp->where('name', 'like', '%' . $this->search . '%');
                                       });
                                });
                        });
                });
            })
            ->when($this->branchFilter, function ($query) {
                $query->where(function ($bq) {
                    $bq->where('orders.shipping_address_snapshot->store', $this->branchFilter)
                        ->orWhereHas('branch', function ($b) {
                            $b->where('name', $this->branchFilter);
                        });
                });
            })
            ->when(!empty($this->proyekFilter), function ($query) {
                $query->whereHas('items', function ($iq) {
                    $iq->whereHasMorph('variant', [ProductAccurate::class], function ($vq) {
                        $vq->whereIn('proyek', $this->proyekFilter);
                    });
                });
            })
            ->when($this->businessUnitFilter && $this->businessUnitFilter !== 'all', function ($query) {
                $query->where('orders.business_unit_id', $this->businessUnitFilter);
            });
    }

    public function render()
    {
        $baseQuery = $this->baseQuery;

        // Outstanding SOs (down_payment, pending, paid)
        $outstandingQuery = (clone $baseQuery)->whereIn('order_status', ['down_payment', 'pending', 'paid']);
        $outstandingOrders = (clone $outstandingQuery)
            ->with(['items.variant', 'user.profile', 'branch', 'handledBy', 'salesBy', 'businessUnit'])
            ->latest()
            ->paginate(20);

        // Metrics
        $totalOutstanding = (clone $outstandingQuery)->count();

        $totalPiutang = (clone $outstandingQuery)->get()->sum(function ($order) {
            $dpPaid = $order->payments()->where('status', 'PAID')->sum('amount');
            return max(0, $order->grand_total - $dpPaid);
        });

        // Closing Rate
        $totalSO = (clone $baseQuery)->count();
        $completedSO = (clone $baseQuery)->where('order_status', 'COMPLETED')->count();
        $closingRate = $totalSO > 0 ? round(($completedSO / $totalSO) * 100, 1) : 0;

        // Aging Data
        $agingData = [
            '< 7 Hari'    => (clone $outstandingQuery)->where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            '7 - 14 Hari' => (clone $outstandingQuery)->whereBetween('created_at', [Carbon::now()->subDays(14), Carbon::now()->subDays(7)])->count(),
            '15 - 30 Hari'=> (clone $outstandingQuery)->whereBetween('created_at', [Carbon::now()->subDays(30), Carbon::now()->subDays(14)])->count(),
            '> 30 Hari'   => (clone $outstandingQuery)->where('created_at', '<', Carbon::now()->subDays(30))->count(),
        ];

        // Dropdown Data
        $branchQuery = Branch::query();
        if ($this->businessUnitFilter && $this->businessUnitFilter !== 'all') {
            $branchQuery->where('business_unit_id', $this->businessUnitFilter);
        }
        $availableBranches = $branchQuery->orderBy('name')->pluck('name');

        $availableProjects = ProductAccurate::whereNotNull('proyek')
            ->where('proyek', '!=', '')
            ->when($this->businessUnitFilter && $this->businessUnitFilter !== 'all', function ($q) {
                $q->where('business_unit_id', $this->businessUnitFilter);
            })
            ->orderBy('proyek')
            ->pluck('proyek')
            ->unique()
            ->values();

        $businessUnits = BusinessUnit::orderBy('name')->get();

        return view('livewire.zoffline.reporting.sales-order-report', [
            'outstandingOrders' => $outstandingOrders,
            'totalOutstanding'  => $totalOutstanding,
            'totalPiutang'      => $totalPiutang,
            'closingRate'       => $closingRate,
            'totalSO'           => $totalSO,
            'agingData'         => $agingData,
            'businessUnits'     => $businessUnits,
            'availableBranches' => $availableBranches,
            'availableProjects' => $availableProjects,
        ]);
    }

    protected function getExportRows(): array
    {
        $baseQuery = $this->baseQuery;

        $outstandingOrders = (clone $baseQuery)->whereIn('order_status', ['down_payment', 'pending', 'paid'])
            ->with(['items.variant', 'user.profile', 'branch', 'handledBy', 'salesBy', 'businessUnit'])
            ->latest()
            ->get();

        $rows = [];
        foreach ($outstandingOrders as $order) {
            $dpPaid = $order->payments()->where('status', 'PAID')->sum('amount');
            $sisaTagihan = max(0, $order->grand_total - $dpPaid);
            $umurHari = $order->created_at->diffInDays(now());
            $buName = $order->businessUnit->name ?? '-';
            $branchName = $order->branch->name ?? ($order->shipping_address_snapshot['store'] ?? '-');

            if ($order->items->isEmpty()) {
                $rows[] = [
                    $order->created_at->format('Y-m-d H:i:s'),
                    $umurHari,
                    $buName,
                    $branchName,
                    $order->order_number,
                    $order->handledBy->name ?? '-',
                    $order->salesBy->name ?? '-',
                    $order->user->name ?? '-',
                    $order->user->profile->phone_number ?? $order->user->phone ?? '-',
                    '-',
                    '-',
                    0,
                    $order->grand_total,
                    $dpPaid,
                    $sisaTagihan,
                ];
            } else {
                foreach ($order->items as $item) {
                    $itemName = $item->variant->name ?? $item->product_name ?? 'Item';
                    $itemProyek = $item->variant->proyek ?? '-';
                    $rows[] = [
                        $order->created_at->format('Y-m-d H:i:s'),
                        $umurHari,
                        $buName,
                        $branchName,
                        $order->order_number,
                        $order->handledBy->name ?? '-',
                        $order->salesBy->name ?? '-',
                        $order->user->name ?? '-',
                        $order->user->profile->phone_number ?? $order->user->phone ?? '-',
                        $itemName,
                        $itemProyek,
                        $item->qty,
                        $order->grand_total,
                        $dpPaid,
                        $sisaTagihan,
                    ];
                }
            }
        }

        return $rows;
    }

    public function exportExcel()
    {
        $rows = $this->getExportRows();
        $fileName = 'laporan-so-outstanding-' . date('Ymd-His') . '.xlsx';
        return Excel::download(new SalesOrderReportExport($rows), $fileName);
    }

    public function exportCsv()
    {
        $rows = $this->getExportRows();
        $separator = $this->csvSeparator ?: ';';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=laporan-so-outstanding-" . date('Ymd-His') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        $callback = function () use ($rows, $separator) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'Tanggal SO',
                'Umur (Hari)',
                'Unit Bisnis',
                'Cabang',
                'No SO',
                'Kasir',
                'Sales',
                'Nama Pelanggan',
                'No HP',
                'Item (Barang)',
                'Proyek',
                'Qty',
                'Total Tagihan (Rp)',
                'DP Masuk (Rp)',
                'Sisa Tagihan (Rp)',
            ], $separator);

            foreach ($rows as $row) {
                fputcsv($file, $row, $separator);
            }

            fclose($file);
        };

        return response()->streamDownload($callback, 'laporan-so-outstanding-' . date('Ymd-His') . '.csv', $headers);
    }
}
