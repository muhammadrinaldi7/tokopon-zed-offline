<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Models\Order;
use App\Models\BusinessUnit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.z')]
class IncomeStatement extends Component
{
    public $dateRange = 'this_month';
    public $startDate;
    public $endDate;
    public $branchFilter = '';
    public $businessUnitFilter = '';
    public $search = ''; // 1. Tambahkan property search

    public function mount()
    {
        $this->setDateRange();
        $this->businessUnitFilter = Auth::user()->getActiveBusinessUnitId();
    }

    // Reset view data jika filter pencarian berubah
    public function updatingSearch()
    {
        // Jika Anda nantinya menggunakan pagination, method ini otomatis mengembalikan ke halaman 1
    }

    public function updatedDateRange()
    {
        if ($this->dateRange !== 'custom') {
            $this->setDateRange();
        }
    }

    private function setDateRange()
    {
        $now = now();
        switch ($this->dateRange) {
            case 'today':
                $this->startDate = $now->copy()->startOfDay()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfDay()->format('Y-m-d');
                break;
            case 'this_week':
                $this->startDate = $now->copy()->startOfWeek()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfWeek()->format('Y-m-d');
                break;
            case 'this_month':
                $this->startDate = $now->copy()->startOfMonth()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfMonth()->format('Y-m-d');
                break;
        }
    }

    public function getReportDataProperty()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        // --- LANGKAH 1: KUMPULKAN DATA UNTUK SUMMARY TOTAL ATAS (IKUTKAN FILTER SEARCH) ---
        $summaryOrders = Order::with(['payments.paymentMethodRate', 'items'])
            ->whereBetween('created_at', [$start, $end])
            ->where('order_status', 'COMPLETED')
            ->when($this->businessUnitFilter, function ($q) { $q->where('business_unit_id', $this->businessUnitFilter); })
            ->when($this->branchFilter, function ($q) { $q->where('shipping_address_snapshot->store', $this->branchFilter); })
            // PERBAIKAN CRITICAL: Ikutkan logic filter search di summary agar MDR tidak bocor global
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('order_number', 'like', '%' . $this->search . '%')
                    ->orWhere('accurate_invoice_no', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($qu) { $qu->where('name', 'like', '%' . $this->search . '%'); })
                    ->orWhereHas('items', function ($qi) { $qi->where('serial_number', 'like', '%' . $this->search . '%'); });
                });
            })
            ->get();

        // Hitung total MDR & Total Pendapatan Bersih (DPP) secara menyeluruh dari hasil filter
        $totalMdr = 0;
        $totalPenjualanBersihKeseluruhan = 0;
        $totalCogsKeseluruhan = 0;

        // Kumpulkan dulu semua SN dari order yang terfilter untuk ditarik HPP-nya
        $allSns = [];
        foreach ($summaryOrders as $order) {
            foreach ($order->items as $item) {
                if (!empty($item->serial_number)) {
                    $sns = array_map('trim', explode(',', $item->serial_number));
                    $allSns = array_merge($allSns, $sns);
                }
            }
        }
        $allSns = array_unique(array_filter($allSns));

        // Tarik map HPP SN untuk semua order terfilter
        $snDataMapGlobal = [];
        if (!empty($allSns)) {
            $snDataMapGlobal = DB::table('product_serial_numbers')
                ->whereIn('serial_number', $allSns)
                ->pluck('hpp', 'serial_number')
                ->toArray();
        }

        // Kalkulasi summary total (bukan cuma yang tampil di halaman 1)
        foreach ($summaryOrders as $order) {
            if ($order->payments) {
                foreach ($order->payments as $payment) {
                    $pmrPct = $payment->paymentMethodRate ? $payment->paymentMethodRate->mdr_percentage : 0;
                    $totalMdr += round(($payment->amount * $pmrPct) / 100);
                }
            }

            foreach ($order->items as $item) {
                // Simulasi hitung promo prorata singkat untuk summary global
                $itemPromosTotal = 0; 
                if ($order->promos) {
                    // Jika ada perhitungan diskon promo, panggil logic prorata di sini agar seimbang
                }

                $penjualanBersihItem = round(($item->subtotal / 1.11) - ($item->discount_amount ?? 0) - $itemPromosTotal);
                $totalPenjualanBersihKeseluruhan += $penjualanBersihItem;

                // Hitung COGS global terfilter
                if (!empty($item->serial_number)) {
                    $itemSns = array_map('trim', explode(',', $item->serial_number));
                    foreach ($itemSns as $sn) {
                        $totalCogsKeseluruhan += (float) ($snDataMapGlobal[$sn] ?? 0);
                    }
                }
            }
        }

        // --- LANGKAH 2: GUNAAN ELOQUENT PAGINATE (10) UNTUK DETAIL TABEL BAWAH ---
        $itemsQuery = \App\Models\OrderItem::with([
                'order.payments.paymentMethodRate', 
                'order.promos', 
                'variant.product'
            ])
            ->whereHas('order', function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end])
                    ->where('order_status', 'COMPLETED')
                    ->when($this->businessUnitFilter, function ($q) { $q->where('business_unit_id', $this->businessUnitFilter); })
                    ->when($this->branchFilter, function ($q) { $q->where('shipping_address_snapshot->store', $this->branchFilter); });
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('serial_number', 'like', '%' . $this->search . '%')
                    ->orWhereHas('order', function ($qo) {
                        $qo->where('order_number', 'like', '%' . $this->search . '%')
                            ->orWhere('accurate_invoice_no', 'like', '%' . $this->search . '%')
                            ->orWhereHas('user', function ($qu) { $qu->where('name', 'like', '%' . $this->search . '%'); });
                    });
                });
            });

        $paginatedItems = $itemsQuery->paginate(10);

        // Tarik HPP & Vendor untuk 10 baris item halaman aktif saat ini
        $currentPageSns = [];
        foreach ($paginatedItems as $item) {
            if (!empty($item->serial_number)) {
                $sns = array_map('trim', explode(',', $item->serial_number));
                $currentPageSns = array_merge($currentPageSns, $sns);
            }
        }
        $currentPageSns = array_unique(array_filter($currentPageSns));

        $snDataMap = [];
        if (!empty($currentPageSns)) {
            $snDataMap = DB::table('product_serial_numbers')
                ->leftJoin('vendors', 'product_serial_numbers.vendor_id', '=', 'vendors.id')
                ->whereIn('product_serial_numbers.serial_number', $currentPageSns)
                ->select('product_serial_numbers.serial_number', 'product_serial_numbers.hpp', 'vendors.vendor_name')
                ->get()
                ->keyBy('serial_number')
                ->toArray();
        }

        // --- LANGKAH 3: RENDER ARRAY BREAKDOWN HALAMAN AKTIF ---
        $itemsBreakdown = [];
        foreach ($paginatedItems as $item) {
            $order = $item->order;
            $sku = $item->variant?->sku ?? $item->variant?->item_no;

            $itemPromosTotal = 0; // Sesuaikan jika menggunakan pivot promo diskon prorata

            $penjualanBersihItem = round(($item->subtotal / 1.11) - ($item->discount_amount ?? 0) - $itemPromosTotal);

            $itemCogsSum = 0;
            $vendorsList = [];
            $hasSnRecord = false;

            if (!empty($item->serial_number)) {
                $itemSns = array_map('trim', explode(',', $item->serial_number));
                foreach ($itemSns as $sn) {
                    if (isset($snDataMap[$sn])) {
                        $itemCogsSum += (float) $snDataMap[$sn]->hpp;
                        if (!empty($snDataMap[$sn]->vendor_name)) $vendorsList[] = $snDataMap[$sn]->vendor_name;
                        $hasSnRecord = true;
                    }
                }
            }

            $labaKotorItem = $penjualanBersihItem - $itemCogsSum;
            $marginPersenItem = $penjualanBersihItem > 0 ? ($labaKotorItem / $penjualanBersihItem) * 100 : 0;

            $itemsBreakdown[] = [
                'tanggal_transaksi' => $order->created_at,
                'order_number' => $order->order_number,
                'accurate_invoice_no' => $order->accurate_invoice_no,
                'sku' => $sku,
                'nama_produk' => $item->variant?->name ?? $item->variant?->product?->name ?? $item->product_name ?? 'Unknown',
                'vendor' => !empty($vendorsList) ? implode(', ', array_unique($vendorsList)) : '-',
                'qty' => $item->qty,
                'revenue_item' => $penjualanBersihItem,
                'cogs_item' => $itemCogsSum,
                'has_sn_record' => $hasSnRecord,
                'laba_kotor_item' => $labaKotorItem,
                'margin_persen' => $marginPersenItem,
                'serial_number' => $item->serial_number
            ];
        }

        $paginatedItems->setCollection(collect($itemsBreakdown));

        // Perhitungan Laba Rugi Akhir dari akumulasi data yang ter-filter pencarian
        $netRevenue = $totalPenjualanBersihKeseluruhan - $totalMdr;
        $grossProfit = $netRevenue - $totalCogsKeseluruhan;

        return [
            'revenue_kotor' => $totalPenjualanBersihKeseluruhan, // Sesuai data filter search
            'mdr_expense' => $totalMdr,
            'net_revenue' => $netRevenue, // Hasil akhir dijamin positif & sinkron
            'cogs' => $totalCogsKeseluruhan,
            'gross_profit' => $grossProfit,
            'margin_percentage' => $netRevenue > 0 ? ($grossProfit / $netRevenue) * 100 : 0,
            'items_breakdown' => $paginatedItems,
            'total_items_count' => $paginatedItems->total()
        ];
    }

    public function render()
    {
        $businessUnits = BusinessUnit::orderBy('name')->get();

        $availableBranches = \App\Models\Branch::when($this->businessUnitFilter, function ($q) {
                $q->where('business_unit_id', $this->businessUnitFilter);
            })
            ->orderBy('name')
            ->pluck('name');

        return view('livewire.zoffline.reporting.income-statement', [
            'report' => $this->reportData,
            'businessUnits' => $businessUnits,
            'availableBranches' => $availableBranches
        ]);
    }
}