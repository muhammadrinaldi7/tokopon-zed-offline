<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Models\Order;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use App\Exports\PromoReportExport;
use Maatwebsite\Excel\Facades\Excel;

class PromoReport extends Component
{
    use WithPagination;

    public $dateRange = 'this_month';
    public $startDate;
    public $endDate;
    public $search = '';
    public $brandFilter = '';
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
    public function updatedBrandFilter()
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
                $this->startDate = $now->startOfDay()->format('Y-m-d');
                $this->endDate = $now->endOfDay()->format('Y-m-d');
                break;
            case 'yesterday':
                $this->startDate = $now->subDay()->startOfDay()->format('Y-m-d');
                $this->endDate = clone $now->endOfDay()->format('Y-m-d');
                break;
            case 'this_week':
                $this->startDate = $now->startOfWeek()->format('Y-m-d');
                $this->endDate = $now->endOfWeek()->format('Y-m-d');
                break;
            case 'last_week':
                $this->startDate = clone $now->subWeek()->startOfWeek()->format('Y-m-d');
                $this->endDate = clone $now->endOfWeek()->format('Y-m-d');
                break;
            case 'this_month':
                $this->startDate = $now->startOfMonth()->format('Y-m-d');
                $this->endDate = $now->endOfMonth()->format('Y-m-d');
                break;
            case 'last_month':
                $this->startDate = clone $now->subMonth()->startOfMonth()->format('Y-m-d');
                $this->endDate = clone $now->endOfMonth()->format('Y-m-d');
                break;
            case 'this_year':
                $this->startDate = clone $now->startOfYear()->format('Y-m-d');
                $this->endDate = clone $now->endOfYear()->format('Y-m-d');
                break;
        }
    }

    public function getOrdersQueryProperty()
    {
        return Order::with(['items.variant', 'items.promos', 'promos'])
            ->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ])
            ->whereHas('promos') // Hanya ambil order yang pakai promo
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('order_number', 'like', '%' . $this->search . '%')
                        ->orWhere('accurate_invoice_no', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->brandFilter, function ($query) {
                // Filter order yang punya item dengan brand ini
                $query->whereHas('items', function ($q) {
                    $q->where(function ($qItem) {
                        $qItem->whereHasMorph('variant', [\App\Models\ProductAccurate::class], function ($q2) {
                            $q2->where('brandName', $this->brandFilter);
                        });
                    });
                });
            })
            ->when($this->businessUnitFilter, function ($query) {
                $query->where('business_unit_id', $this->businessUnitFilter);
            })
            ->when(!$this->businessUnitFilter, function ($query) {
                $buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();
                $query->where(function ($q) use ($buId) {
                    $q->where('business_unit_id', $buId)
                      ->orWhereNull('business_unit_id');
                });
            })
            ->latest();
    }

    protected function getClaimRows(): array
    {
        $orders = $this->ordersQuery->get();
        $rows = [];

        foreach ($orders as $order) {
            $branch = $order->shipping_address_snapshot['store'] ?? 'Unknown';
            $orderDate = $order->created_at->format('Y-m-d H:i');
            $orderNo = $order->order_number;
            $invNo = $order->accurate_invoice_no ?? '-';

            foreach ($order->items as $item) {
                $variant = $item->variant;
                $name = $variant?->name ?? $variant?->product?->name ?? $item->product_name ?? 'Unknown Product';
                $merk = $variant?->brandName ?? $variant?->product?->brand?->name ?? 'Unknown';

                foreach ($item->promos as $promo) {
                    $promoName = $promo->name;
                    $discountAmount = $promo->pivot->discount_amount;
                    $sn = $promo->pivot->serial_number ?? '-';
                    $vendorName = $promo->pivot->vendor_name ?? $merk;

                    if ($this->brandFilter && $merk !== $this->brandFilter) {
                        continue;
                    }

                    if ($discountAmount > 0) {
                        $rows[] = [
                            'tanggal' => $orderDate,
                            'order_no' => $orderNo,
                            'inv_no' => $invNo,
                            'cabang' => $branch,
                            'merk' => $merk,
                            'vendor' => $vendorName,
                            'nama_produk' => $name,
                            'sn' => $sn,
                            'nama_promo' => $promoName,
                            'nilai_klaim' => (float) $discountAmount,
                        ];
                    }
                }
            }
        }

        return $rows;
    }

    public function exportExcelClaim()
    {
        $rows = $this->getClaimRows();

        if (empty($rows)) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Tidak ada data klaim promo untuk diexport pada rentang tanggal ini.', type: 'warning');
            return;
        }

        $excelFileName = 'laporan_klaim_promo_' . $this->startDate . '_sd_' . $this->endDate . '.xlsx';
        return Excel::download(new PromoReportExport($rows), $excelFileName);
    }

    public function exportCsvClaim()
    {
        $rows = $this->getClaimRows();

        if (empty($rows)) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Tidak ada data klaim promo untuk diexport pada rentang tanggal ini.', type: 'warning');
            return;
        }

        $csvFileName = 'laporan_klaim_promo_' . $this->startDate . '_sd_' . $this->endDate . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'TANGGAL',
                'NO. ORDER',
                'NO. INVOICE',
                'CABANG',
                'MERK PRODUK',
                'NAMA VENDOR',
                'NAMA PRODUK',
                'SN',
                'NAMA PROMO',
                'NILAI KLAIM PROMO (Rp)'
            ]);

            foreach ($rows as $row) {
                fputcsv($file, array_values($row));
            }

            fclose($file);
        }, $csvFileName);
    }

    public function render()
    {
        $orders = $this->ordersQuery->paginate(20);

        // Ambil list brand yang unik dari order-order yang ada (untuk filter)
        $availableBrands = \App\Models\ProductAccurate::whereNotNull('brandName')
            ->distinct()
            ->pluck('brandName')
            ->unique(fn($brand) => strtolower(trim($brand)))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return view('livewire.zoffline.reporting.promo-report', compact('orders', 'availableBrands'))->layout('layouts.z');
    }
}
