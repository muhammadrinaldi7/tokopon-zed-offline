<?php

namespace App\Livewire\Admin\Reporting;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Livewire\Component;
use Livewire\WithPagination;

class ProductReport extends Component
{
    use WithPagination;

    public $dateRange = 'this_month';
    public $startDate;
    public $endDate;


    public $search = '';
    public $branchFilter = '';
    public $businessUnitFilter = '';

    public function mount()
    {
        $this->setDateRange();
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
        $this->resetPage();
    }
    public function updatedEndDate()
    {
        $this->dateRange = 'custom';
        $this->resetPage();
    }
    public function updatedSearch()
    {
        $this->resetPage();
    }
    public function updatedBranchFilter()
    {
        $this->resetPage();
    }
    public function updatedBusinessUnitFilter()
    {
        $this->resetPage();
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
            case 'this_year':
                $this->startDate = $now->copy()->startOfYear()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfYear()->format('Y-m-d');
                break;
        }
    }

    public function getProductsProperty()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $orderIds = Order::whereBetween('created_at', [$start, $end])
            ->where('order_status', 'COMPLETED')
            ->when($this->businessUnitFilter, function ($q) {
                $q->where('business_unit_id', $this->businessUnitFilter);
            })
            ->pluck('id');

        $orderItems = OrderItem::whereIn('order_id', $orderIds)
            ->with(['variant', 'variant.product', 'order']) // Asumsi polymorphic variant punya relasi product (bila ada)
            ->get();

        $grouped = $orderItems->groupBy(function ($item) {
            $branch = $item->order->shipping_address_snapshot['store'] ?? 'Unknown';
            return $item->product_variant_type . '_' . $item->product_variant_id . '_' . $branch;
        });

        $products = $grouped->map(function ($group) {
            $first = $group->first();
            $variant = $first->variant;

            $sku = $variant->sku ?? ($variant->imei ?? ($variant->code ?? '-'));
            $name = $variant->name ?? ($variant->product ? $variant->product->name : 'Unknown Product');

            // Tambahkan RAM/Storage jika ada
            if (isset($variant->ram) || isset($variant->storage)) {
                $name .= ' (' . ($variant->ram ?? '') . '/' . ($variant->storage ?? '') . ')';
            }

            $branch = $first->order->shipping_address_snapshot['store'] ?? 'Unknown';

            return (object) [
                'sku' => $sku,
                'name' => $name,
                'branch' => $branch,
                'total_qty' => $group->sum('qty'),
                'gross_revenue' => $group->sum(function ($q) {
                    return $q->price_at_checkout * $q->qty;
                }),
                'total_discount' => $group->sum('discount_amount'),
                'net_revenue' => $group->sum('subtotal')
            ];
        });

        if ($this->branchFilter) {
            $bFilter = strtolower($this->branchFilter);
            $products = $products->filter(function ($item) use ($bFilter) {
                return str_contains(strtolower($item->branch), $bFilter);
            });
        }

        if ($this->search) {
            $search = strtolower($this->search);
            $products = $products->filter(function ($item) use ($search) {
                return str_contains(strtolower($item->name), $search) || str_contains(strtolower($item->sku), $search);
            });
        }

        $sortedProducts = $products->sortByDesc('total_qty')->values();

        // Manual Pagination
        $page = $this->getPage();
        $perPage = 20;

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $sortedProducts->forPage($page, $perPage),
            $sortedProducts->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }

    public function exportCsv()
    {
        // Get all products without pagination for export
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $orderIds = Order::whereBetween('created_at', [$start, $end])
            ->where('order_status', 'COMPLETED')
            ->when($this->businessUnitFilter, function ($q) {
                $q->where('business_unit_id', $this->businessUnitFilter);
            })
            ->pluck('id');

        $orderItems = OrderItem::whereIn('order_id', $orderIds)
            ->with(['variant', 'variant.product', 'order'])
            ->get();

        $grouped = $orderItems->groupBy(function ($item) {
            $branch = $item->order->shipping_address_snapshot['store'] ?? 'Unknown';
            return $item->product_variant_type . '_' . $item->product_variant_id . '_' . $branch;
        });

        $products = $grouped->map(function ($group) {
            $first = $group->first();
            $variant = $first->variant;

            $sku = $variant->sku ?? ($variant->imei ?? ($variant->code ?? '-'));
            $name = $variant->name ?? ($variant->product ? $variant->product->name : 'Unknown Product');

            $branch = $first->order->shipping_address_snapshot['store'] ?? 'Unknown';

            return (object) [
                'sku' => $sku,
                'name' => $name,
                'branch' => $branch,
                'total_qty' => $group->sum('qty'),
                'gross_revenue' => $group->sum(function ($q) {
                    return $q->price_at_checkout * $q->qty;
                }),
                'total_discount' => $group->sum('discount_amount'),
                'net_revenue' => $group->sum('subtotal')
            ];
        });

        if ($this->branchFilter) {
            $bFilter = strtolower($this->branchFilter);
            $products = $products->filter(function ($item) use ($bFilter) {
                return str_contains(strtolower($item->branch), $bFilter);
            });
        }

        if ($this->search) {
            $search = strtolower($this->search);
            $products = $products->filter(function ($item) use ($search) {
                return str_contains(strtolower($item->name), $search) || str_contains(strtolower($item->sku), $search);
            });
        }

        $products = $products->sortByDesc('total_qty')->values();

        $csvFileName = 'kinerja_produk_' . $this->startDate . '_sd_' . $this->endDate . '.csv';

        return response()->streamDownload(function () use ($products) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'SKU',
                'NAMA PRODUK',
                'CABANG/GUDANG',
                'TERJUAL (Qty)',
                'GROSS REVENUE (Rp)',
                'TOTAL DISKON (Rp)',
                'NET REVENUE (Rp)'
            ]);

            foreach ($products as $product) {
                fputcsv($file, [
                    $product->sku,
                    $product->name,
                    $product->branch,
                    $product->total_qty,
                    $product->gross_revenue,
                    $product->total_discount,
                    $product->net_revenue
                ]);
            }
            fclose($file);
        }, $csvFileName);
    }

    public function render()
    {
        return view('livewire.admin.reporting.product-report', [
            'products' => $this->products
        ])->layout('layouts.admin');
    }
}
