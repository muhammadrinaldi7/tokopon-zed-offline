<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Exports\ProjectSalesReportExport;
use App\Models\Branch;
use App\Models\BusinessUnit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductAccurate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class ProjectSalesReport extends Component
{
    public $dateRange = 'this_month';
    public $startDate;
    public $endDate;
    public $businessUnitFilter = '';
    public $branchFilter = '';
    public $proyekFilter = [];
    public $valueMode = 'nominal'; // 'nominal' (Rp) or 'qty' (Unit)
    public $csvSeparator = ';';

    // Drill-down Detail Modal
    public $showDetailModal = false;
    public $detailDate = null;
    public $detailDisplayDate = null;
    public $detailProject = null;
    public $detailSearch = '';

    public function mount()
    {
        $this->businessUnitFilter = (string)(Auth::user()->getActiveBusinessUnitId() ?? '');
        $this->setDateRange();
    }

    public function updatedBusinessUnitFilter()
    {
        $this->branchFilter = '';
    }

    public function updatedDateRange()
    {
        if ($this->dateRange !== 'custom') {
            $this->setDateRange();
        }
    }

    public function updatedStartDate()
    {
        $this->dateRange = 'custom';
    }

    public function updatedEndDate()
    {
        $this->dateRange = 'custom';
    }

    private function setDateRange()
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
    }

    public function selectAllProjects()
    {
        $this->proyekFilter = $this->availableProjects->toArray();
    }

    public function clearProjectFilter()
    {
        $this->proyekFilter = [];
    }

    public function getAvailableProjectsProperty()
    {
        return ProductAccurate::whereNotNull('proyek')
            ->where('proyek', '!=', '')
            ->orderBy('proyek')
            ->pluck('proyek')
            ->unique()
            ->values();
    }

    public function getMatrixDataProperty(): array
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();
        $buId = $this->businessUnitFilter;

        // Generate array of date objects for the period
        $period = CarbonPeriod::create($start, $end);
        $dates = [];
        foreach ($period as $dt) {
            $dates[] = [
                'raw' => $dt->format('Y-m-d'),
                'display' => $dt->format('d-m-Y'),
                'day_name' => $dt->translatedFormat('l')
            ];
        }

        // Query orders within period
        $orders = Order::with(['items.variant', 'items.promos', 'shippingAddress'])
            ->whereBetween('orders.order_date', [$start, $end])
            ->whereIn('orders.order_status', ['COMPLETED', 'piutang', 'PIUTANG'])
            ->when($buId && $buId !== 'all', function ($query) use ($buId) {
                $query->where('orders.business_unit_id', $buId);
            })
            ->when($this->branchFilter, function ($query) {
                $query->where('orders.shipping_address_snapshot->store', $this->branchFilter);
            })
            ->get();

        // Matrix aggregation
        $rawMatrix = [];
        $encounteredProjects = [];

        foreach ($orders as $order) {
            $orderDate = $order->order_date ? $order->order_date->format('Y-m-d') : $order->created_at->format('Y-m-d');

            foreach ($order->items as $item) {
                $variant = $item->variant;
                $proyekName = $variant?->proyek ?? ($variant?->accurateData?->proyek ?? 'NON-PROYEK');
                if (empty($proyekName) || trim($proyekName) === '') {
                    $proyekName = 'NON-PROYEK';
                }
                $proyekName = strtoupper(trim($proyekName));
                $encounteredProjects[$proyekName] = true;

                // Hitung subtotal bersih item (setelah diskon item & promo)
                $itemPromosTotal = $item->promos ? $item->promos->sum('pivot.discount_amount') : 0;
                $netSubtotal = (float)$item->subtotal - (float)($item->discount_amount ?? 0) - (float)$itemPromosTotal;
                $qty = (int)$item->qty;

                if (!isset($rawMatrix[$orderDate][$proyekName])) {
                    $rawMatrix[$orderDate][$proyekName] = [
                        'nominal' => 0,
                        'qty' => 0,
                        'count' => 0
                    ];
                }

                $rawMatrix[$orderDate][$proyekName]['nominal'] += $netSubtotal;
                $rawMatrix[$orderDate][$proyekName]['qty'] += $qty;
                $rawMatrix[$orderDate][$proyekName]['count'] += 1;
            }
        }

        // Tentukan kolom proyek yang akan ditampilkan
        if (!empty($this->proyekFilter)) {
            $selectedColumns = array_map(function ($p) {
                return strtoupper(trim($p));
            }, $this->proyekFilter);
            $columns = array_values(array_unique($selectedColumns));
        } else {
            // Jika filter proyek kosong, tampilkan semua proyek yang memiliki transaksi
            // atau jika belum ada transaksi, tampilkan 5 proyek default teratas dari database
            if (!empty($encounteredProjects)) {
                $columns = array_keys($encounteredProjects);
                sort($columns);
            } else {
                $columns = $this->availableProjects->map(fn($p) => strtoupper(trim($p)))->take(6)->toArray();
            }
        }

        // Hitung baris, kolom, dan total
        $matrix = [];
        $rowTotals = [];
        $columnTotals = array_fill_keys($columns, ['nominal' => 0, 'qty' => 0]);
        $grandTotal = ['nominal' => 0, 'qty' => 0];

        foreach ($dates as $d) {
            $dateKey = $d['raw'];
            $rowTotals[$dateKey] = ['nominal' => 0, 'qty' => 0];

            foreach ($columns as $col) {
                $cellData = $rawMatrix[$dateKey][$col] ?? ['nominal' => 0, 'qty' => 0, 'count' => 0];
                $matrix[$dateKey][$col] = $cellData;

                $rowTotals[$dateKey]['nominal'] += $cellData['nominal'];
                $rowTotals[$dateKey]['qty'] += $cellData['qty'];

                $columnTotals[$col]['nominal'] += $cellData['nominal'];
                $columnTotals[$col]['qty'] += $cellData['qty'];

                $grandTotal['nominal'] += $cellData['nominal'];
                $grandTotal['qty'] += $cellData['qty'];
            }
        }

        $totalDays = count($dates);
        $dailyAverage = $totalDays > 0 ? ($grandTotal['nominal'] / $totalDays) : 0;
        $activeProjectsCount = count(array_filter($columnTotals, fn($c) => $c['nominal'] > 0 || $c['qty'] > 0));

        return [
            'dates' => $dates,
            'columns' => $columns,
            'matrix' => $matrix,
            'rowTotals' => $rowTotals,
            'columnTotals' => $columnTotals,
            'grandTotal' => $grandTotal,
            'totalDays' => $totalDays,
            'dailyAverage' => $dailyAverage,
            'activeProjectsCount' => $activeProjectsCount,
        ];
    }

    public function openDetail($date, $project)
    {
        $this->detailDate = $date;
        $this->detailDisplayDate = Carbon::parse($date)->format('d M Y');
        $this->detailProject = $project;
        $this->detailSearch = '';
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->detailDate = null;
        $this->detailDisplayDate = null;
        $this->detailProject = null;
        $this->detailSearch = '';
    }

    public function getDetailItemsProperty()
    {
        if (!$this->showDetailModal || !$this->detailDate || !$this->detailProject) {
            return collect();
        }

        $targetDate = Carbon::parse($this->detailDate);
        $buId = $this->businessUnitFilter;
        $targetProject = strtoupper(trim($this->detailProject));

        $orders = Order::with([
            'user',
            'salesBy',
            'handledBy',
            'payments.paymentMethod',
            'items.variant',
            'items.promos'
        ])
        ->whereDate('orders.order_date', $targetDate)
        ->whereIn('orders.order_status', ['COMPLETED', 'piutang', 'PIUTANG'])
        ->when($buId && $buId !== 'all', function ($query) use ($buId) {
            $query->where('orders.business_unit_id', $buId);
        })
        ->when($this->branchFilter, function ($query) {
            $query->where('orders.shipping_address_snapshot->store', $this->branchFilter);
        })
        ->get();

        $items = collect();

        foreach ($orders as $order) {
            $branch = $order->shipping_address_snapshot['store'] ?? 'Unknown';

            foreach ($order->items as $item) {
                $variant = $item->variant;
                $proyekName = $variant?->proyek ?? ($variant?->accurateData?->proyek ?? 'NON-PROYEK');
                if (empty($proyekName) || trim($proyekName) === '') {
                    $proyekName = 'NON-PROYEK';
                }
                $proyekName = strtoupper(trim($proyekName));

                if ($proyekName === $targetProject) {
                    $itemPromosTotal = $item->promos ? $item->promos->sum('pivot.discount_amount') : 0;
                    $netSubtotal = (float)$item->subtotal - (float)($item->discount_amount ?? 0) - (float)$itemPromosTotal;

                    $productName = $variant?->name ?? ($variant?->product?->name ?? ($item->product_name ?? 'Unknown Product'));
                    $sku = $variant?->item_no ?? ($variant?->sku ?? '-');

                    // Filter search if present
                    if ($this->detailSearch) {
                        $search = strtolower($this->detailSearch);
                        $matched = str_contains(strtolower($order->order_number), $search)
                            || str_contains(strtolower($order->accurate_invoice_no ?? ''), $search)
                            || str_contains(strtolower($productName), $search)
                            || str_contains(strtolower($sku), $search)
                            || str_contains(strtolower($order->user?->name ?? ''), $search)
                            || str_contains(strtolower($order->salesBy?->name ?? ''), $search);

                        if (!$matched) {
                            continue;
                        }
                    }

                    $items->push([
                        'order_number' => $order->order_number,
                        'invoice_no' => $order->accurate_invoice_no ?? '-',
                        'created_at' => $order->order_date ? $order->order_date->format('H:i') : $order->created_at->format('H:i'),
                        'customer_name' => $order->user?->name ?? 'Walk-in Customer',
                        'sales_name' => $order->salesBy?->name ?? '-',
                        'branch' => $branch,
                        'product_name' => $productName,
                        'sku' => $sku,
                        'serial_number' => $item->serial_number ?? '-',
                        'qty' => $item->qty,
                        'price' => $item->price_at_checkout ?? 0,
                        'discount' => (float)($item->discount_amount ?? 0) + (float)$itemPromosTotal,
                        'subtotal' => $netSubtotal,
                        'payment_method' => $order->payments->first()?->paymentMethod?->name ?? '-'
                    ]);
                }
            }
        }

        return $items;
    }

    public function exportExcel()
    {
        $matrixData = $this->matrixData;
        $columns = $matrixData['columns'];
        $dates = $matrixData['dates'];
        $matrix = $matrixData['matrix'];
        $rowTotals = $matrixData['rowTotals'];
        $columnTotals = $matrixData['columnTotals'];
        $grandTotal = $matrixData['grandTotal'];
        $mode = $this->valueMode;

        // Headings: TANGGAL, PROYEK 1, PROYEK 2, ..., GRAND TOTAL
        $headings = array_merge(['TANGGAL'], $columns, ['GRAND TOTAL']);

        // Data Rows
        $rows = [];
        foreach ($dates as $d) {
            $dateKey = $d['raw'];
            $row = [$d['display']];

            foreach ($columns as $col) {
                $val = $matrix[$dateKey][$col][$mode] ?? 0;
                $row[] = $val > 0 ? ($mode === 'nominal' ? (int)$val : (int)$val) : 0;
            }

            $rowTotalVal = $rowTotals[$dateKey][$mode] ?? 0;
            $row[] = (int)$rowTotalVal;

            $rows[] = $row;
        }

        // Footer Row (Grand Total)
        $footerRow = ['GRAND TOTAL'];
        foreach ($columns as $col) {
            $colTotalVal = $columnTotals[$col][$mode] ?? 0;
            $footerRow[] = (int)$colTotalVal;
        }
        $footerRow[] = (int)($grandTotal[$mode] ?? 0);
        $rows[] = $footerRow;

        $fileName = 'laporan_penjualan_per_proyek_' . $this->startDate . '_sd_' . $this->endDate . '.xlsx';

        return Excel::download(new ProjectSalesReportExport($headings, $rows), $fileName);
    }

    public function exportCsv()
    {
        $matrixData = $this->matrixData;
        $columns = $matrixData['columns'];
        $dates = $matrixData['dates'];
        $matrix = $matrixData['matrix'];
        $rowTotals = $matrixData['rowTotals'];
        $columnTotals = $matrixData['columnTotals'];
        $grandTotal = $matrixData['grandTotal'];
        $mode = $this->valueMode;
        $separator = $this->csvSeparator;

        $headings = array_merge(['TANGGAL'], $columns, ['GRAND TOTAL']);

        $rows = [];
        foreach ($dates as $d) {
            $dateKey = $d['raw'];
            $row = [$d['display']];

            foreach ($columns as $col) {
                $val = $matrix[$dateKey][$col][$mode] ?? 0;
                $row[] = (int)$val;
            }

            $rowTotalVal = $rowTotals[$dateKey][$mode] ?? 0;
            $row[] = (int)$rowTotalVal;

            $rows[] = $row;
        }

        $footerRow = ['GRAND TOTAL'];
        foreach ($columns as $col) {
            $colTotalVal = $columnTotals[$col][$mode] ?? 0;
            $footerRow[] = (int)$colTotalVal;
        }
        $footerRow[] = (int)($grandTotal[$mode] ?? 0);
        $rows[] = $footerRow;

        $csvFileName = 'laporan_penjualan_per_proyek_' . $this->startDate . '_sd_' . $this->endDate . '.csv';

        return response()->streamDownload(function () use ($headings, $rows, $separator) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headings, $separator);

            foreach ($rows as $row) {
                fputcsv($file, $row, $separator);
            }

            fclose($file);
        }, $csvFileName);
    }

    public function render()
    {
        $businessUnits = BusinessUnit::orderBy('name')->get();

        $branchQuery = Branch::query();
        if ($this->businessUnitFilter && $this->businessUnitFilter !== 'all') {
            $branchQuery->where('business_unit_id', $this->businessUnitFilter);
        }
        $availableBranches = $branchQuery->orderBy('name')->pluck('name');

        return view('livewire.zoffline.reporting.project-sales-report', [
            'matrixData' => $this->matrixData,
            'businessUnits' => $businessUnits,
            'availableBranches' => $availableBranches,
            'availableProjects' => $this->availableProjects,
            'detailItems' => $this->detailItems,
        ])->layout('layouts.z');
    }
}
