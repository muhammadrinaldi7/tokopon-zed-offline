<?php

namespace App\Livewire\Admin\Accurate;

use App\Models\Branch;
use App\Models\BusinessUnit;
use App\Models\SellPhone;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin', ['title' => 'Faktur Pembelian Accurate (Purchase Invoices)'])]
class PurchaseInvoicesIndex extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';
    public string $sourceType = 'all'; // 'all', 'sellphone', 'trade_in'
    public string $invoiceFilter = 'all'; // 'all', 'has_invoice', 'no_invoice'
    public string $branchFilter = '';
    public string $businessUnitFilter = '';
    public string $datePreset = 'this_month';
    public string $startDate = '';
    public string $endDate = '';
    public int $perPage = 15;

    // Detail Modal State
    public bool $showDetailModal = false;
    public ?SellPhone $selectedSellPhone = null;

    protected $queryString = [
        'search'             => ['except' => ''],
        'sourceType'         => ['except' => 'all'],
        'invoiceFilter'      => ['except' => 'all'],
        'branchFilter'       => ['except' => ''],
        'businessUnitFilter' => ['except' => ''],
        'datePreset'         => ['except' => 'this_month'],
    ];

    public function mount()
    {
        $this->setDatePreset($this->datePreset);
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingSourceType() { $this->resetPage(); }
    public function updatingInvoiceFilter() { $this->resetPage(); }
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
        $this->reset(['search', 'sourceType', 'invoiceFilter', 'branchFilter', 'businessUnitFilter']);
        $this->setDatePreset('this_month');
    }

    public function openDetail(int $sellPhoneId)
    {
        $this->selectedSellPhone = SellPhone::with([
            'user.profile',
            'branch',
            'businessUnit',
            'salesBy',
            'productAccurate'
        ])->find($sellPhoneId);

        $this->showDetailModal = true;
    }

    public function closeDetail()
    {
        $this->showDetailModal = false;
        $this->selectedSellPhone = null;
    }

    public function render()
    {
        // Query Pembelian HP Bekas (SellPhone)
        $query = SellPhone::query()
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('invoice_number', 'like', "%{$this->search}%")
                        ->orWhere('imei', 'like', "%{$this->search}%")
                        ->orWhere('phone_brand', 'like', "%{$this->search}%")
                        ->orWhere('phone_model', 'like', "%{$this->search}%")
                        ->orWhereHas('user', function ($u) {
                            $u->where('name', 'like', "%{$this->search}%")
                              ->orWhere('email', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->invoiceFilter === 'has_invoice', function ($q) {
                $q->whereNotNull('invoice_number')->where('invoice_number', '!=', '');
            })
            ->when($this->invoiceFilter === 'no_invoice', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('invoice_number')->orWhere('invoice_number', '');
                });
            })
            ->when($this->branchFilter, function ($q) {
                $q->where('branch_id', $this->branchFilter);
            })
            ->when($this->businessUnitFilter, function ($q) {
                $q->where('business_unit_id', $this->businessUnitFilter);
            })
            ->when($this->startDate, function ($q) {
                $q->whereDate('created_at', '>=', $this->startDate);
            })
            ->when($this->endDate, function ($q) {
                $q->whereDate('created_at', '<=', $this->endDate);
            });

        // KPI Metrik
        $kpiQuery = clone $query;
        $totalInvoices = (clone $kpiQuery)->whereNotNull('invoice_number')->where('invoice_number', '!=', '')->count();
        $totalAmount   = (clone $kpiQuery)->whereNotNull('invoice_number')->where('invoice_number', '!=', '')->sum('appraised_value');
        $totalPending  = (clone $kpiQuery)->where(function ($q) {
            $q->whereNull('invoice_number')->orWhere('invoice_number', '');
        })->count();
        $averageValue  = $totalInvoices > 0 ? round($totalAmount / $totalInvoices, 0) : 0;

        $purchases = $query
            ->with(['branch', 'businessUnit', 'user'])
            ->latest('id')
            ->paginate($this->perPage);

        $branches = Branch::orderBy('name')->get();
        $businessUnits = BusinessUnit::all();

        return view('livewire.admin.accurate.purchase-invoices-index', [
            'purchases'     => $purchases,
            'totalInvoices' => $totalInvoices,
            'totalAmount'   => $totalAmount,
            'totalPending'  => $totalPending,
            'averageValue'  => $averageValue,
            'branches'      => $branches,
            'businessUnits' => $businessUnits,
        ]);
    }
}

