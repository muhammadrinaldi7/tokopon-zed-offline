<?php

namespace App\Livewire\Admin\Accurate;

use App\Models\Branch;
use App\Models\BusinessUnit;
use App\Models\Order;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin', ['title' => 'Faktur Penjualan Accurate (Sales Invoices)'])]
class SalesInvoicesIndex extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';
    public string $invoiceFilter = 'all'; // 'all', 'has_invoice', 'no_invoice'
    public string $paymentFilter = 'all'; // 'all', 'paid', 'unpaid'
    public string $orderChannelFilter = ''; // '', 'pos', 'sales_order', 'online'
    public string $branchFilter = '';
    public string $businessUnitFilter = '';
    public string $datePreset = 'this_month';
    public string $startDate = '';
    public string $endDate = '';
    public int $perPage = 15;

    // Detail Modal State
    public bool $showDetailModal = false;
    public ?Order $selectedOrder = null;

    protected $queryString = [
        'search'             => ['except' => ''],
        'invoiceFilter'      => ['except' => 'all'],
        'paymentFilter'      => ['except' => 'all'],
        'orderChannelFilter' => ['except' => ''],
        'branchFilter'       => ['except' => ''],
        'businessUnitFilter' => ['except' => ''],
        'datePreset'         => ['except' => 'this_month'],
    ];

    public function mount()
    {
        $this->setDatePreset($this->datePreset);
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingInvoiceFilter() { $this->resetPage(); }
    public function updatingPaymentFilter() { $this->resetPage(); }
    public function updatingOrderChannelFilter() { $this->resetPage(); }
    public function updatingBranchFilter() { $this->resetPage(); }
    public function updatingBusinessUnitFilter() { $this->resetPage(); }

    public function setDatePreset(string $preset)
    {
        $this->datePreset = $preset;
        $now = Carbon::now();

        match ($preset) {
            'today' => [
                $this->startDate = $now->copy()->startOfDay()->toDateString(),
                $this->endDate   = $now->copy()->endOfDay()->toDateString(),
            ],
            'yesterday' => [
                $this->startDate = $now->copy()->subDay()->startOfDay()->toDateString(),
                $this->endDate   = $now->copy()->subDay()->endOfDay()->toDateString(),
            ],
            'this_week' => [
                $this->startDate = $now->copy()->startOfWeek()->toDateString(),
                $this->endDate   = $now->copy()->endOfWeek()->toDateString(),
            ],
            'this_month' => [
                $this->startDate = $now->copy()->startOfMonth()->toDateString(),
                $this->endDate   = $now->copy()->endOfMonth()->toDateString(),
            ],
            'all' => [
                $this->startDate = '',
                $this->endDate   = '',
            ],
            default => null,
        };

        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'invoiceFilter', 'paymentFilter', 'orderChannelFilter', 'branchFilter', 'businessUnitFilter']);
        $this->setDatePreset('this_month');
    }

    public function openDetail(int $orderId)
    {
        $this->selectedOrder = Order::with([
            'items',
            'payments.paymentMethod',
            'branch',
            'businessUnit',
            'user.profile',
            'handledBy',
            'accurateDocs',
            'accurateSyncLogs' => fn($q) => $q->latest()->limit(5)
        ])->find($orderId);

        $this->showDetailModal = true;
    }

    public function closeDetail()
    {
        $this->showDetailModal = false;
        $this->selectedOrder = null;
    }

    public function render()
    {
        $baseQuery = Order::query()
            ->whereIn('order_status', ['COMPLETED', 'paid', 'down_payment', 'PIUTANG'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('order_number', 'like', "%{$this->search}%")
                        ->orWhere('accurate_invoice_no', 'like', "%{$this->search}%")
                        ->orWhere('accurate_receipt_no', 'like', "%{$this->search}%")
                        ->orWhere('accurate_so_number', 'like', "%{$this->search}%")
                        ->orWhereHas('user', function ($u) {
                            $u->where('name', 'like', "%{$this->search}%")
                              ->orWhere('email', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->invoiceFilter === 'has_invoice', function ($q) {
                $q->whereNotNull('accurate_invoice_no')->where('accurate_invoice_no', '!=', '');
            })
            ->when($this->invoiceFilter === 'no_invoice', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('accurate_invoice_no')->orWhere('accurate_invoice_no', '');
                });
            })
            ->when($this->paymentFilter === 'paid', function ($q) {
                $q->whereNotNull('accurate_receipt_no')->where('accurate_receipt_no', '!=', '');
            })
            ->when($this->paymentFilter === 'unpaid', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('accurate_receipt_no')->orWhere('accurate_receipt_no', '');
                });
            })
            ->when($this->orderChannelFilter, function ($q) {
                $q->where('order_channel', $this->orderChannelFilter);
            })
            ->when($this->branchFilter, function ($q) {
                $q->where('branch_id', $this->branchFilter);
            })
            ->when($this->businessUnitFilter, function ($q) {
                $q->where('business_unit_id', $this->businessUnitFilter);
            })
            ->when($this->startDate, function ($q) {
                $q->where(function ($dateQ) {
                    $dateQ->whereDate('order_date', '>=', $this->startDate)
                          ->orWhere(function ($sub) {
                              $sub->whereNull('order_date')->whereDate('created_at', '>=', $this->startDate);
                          });
                });
            })
            ->when($this->endDate, function ($q) {
                $q->where(function ($dateQ) {
                    $dateQ->whereDate('order_date', '<=', $this->endDate)
                          ->orWhere(function ($sub) {
                              $sub->whereNull('order_date')->whereDate('created_at', '<=', $this->endDate);
                          });
                });
            });

        // KPI Metrik
        $kpiQuery = clone $baseQuery;
        $totalInvoices = (clone $kpiQuery)->whereNotNull('accurate_invoice_no')->where('accurate_invoice_no', '!=', '')->count();
        $totalAmount   = (clone $kpiQuery)->whereNotNull('accurate_invoice_no')->where('accurate_invoice_no', '!=', '')->sum('grand_total');
        $paidCount     = (clone $kpiQuery)->whereNotNull('accurate_receipt_no')->where('accurate_receipt_no', '!=', '')->count();
        $unpaidCount   = (clone $kpiQuery)->whereNotNull('accurate_invoice_no')->where('accurate_invoice_no', '!=', '')
            ->where(function ($q) {
                $q->whereNull('accurate_receipt_no')->orWhere('accurate_receipt_no', '');
            })->count();

        $invoices = $baseQuery
            ->with(['branch', 'businessUnit', 'user', 'handledBy'])
            ->latest('id')
            ->paginate($this->perPage);

        $branches = Branch::orderBy('name')->get();
        $businessUnits = BusinessUnit::all();

        return view('livewire.admin.accurate.sales-invoices-index', [
            'invoices'      => $invoices,
            'totalInvoices' => $totalInvoices,
            'totalAmount'   => $totalAmount,
            'paidCount'     => $paidCount,
            'unpaidCount'   => $unpaidCount,
            'branches'      => $branches,
            'businessUnits' => $businessUnits,
        ]);
    }
}

