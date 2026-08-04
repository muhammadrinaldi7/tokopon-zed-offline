<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('layouts.z')]
class SalesOrderReport extends Component
{
    use WithPagination;

    public $dateFrom;
    public $dateTo;

    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['dateFrom', 'dateTo'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        // Query for Outstanding SO (down_payment status)
        $baseQuery = Order::where('order_channel', 'SO')
            ->whereBetween('created_at', [
                Carbon::parse($this->dateFrom)->startOfDay(),
                Carbon::parse($this->dateTo)->endOfDay(),
            ]);

        // Outstanding SOs
        $outstandingQuery = (clone $baseQuery)->whereIn('order_status', ['down_payment', 'pending']);
        $outstandingOrders = $outstandingQuery->with(['items.variant', 'user'])->latest()->paginate(20);

        // Metrics
        $totalOutstanding = (clone $outstandingQuery)->count();
        
        $totalPiutang = (clone $outstandingQuery)->get()->sum(function($order) {
            // Sisa tagihan = grand total - total DP
            $dpPaid = $order->payments()->where('status', 'PAID')->sum('amount');
            return max(0, $order->grand_total - $dpPaid);
        });

        // Closing Rate
        $totalSO = (clone $baseQuery)->count();
        $completedSO = (clone $baseQuery)->where('order_status', 'COMPLETED')->count();
        $closingRate = $totalSO > 0 ? round(($completedSO / $totalSO) * 100, 1) : 0;

        // Aging Data
        $agingData = [
            '< 7 Hari' => (clone $outstandingQuery)->where('created_at', '>=', Carbon::now()->subDays(7))->count(),
            '7 - 14 Hari' => (clone $outstandingQuery)->whereBetween('created_at', [Carbon::now()->subDays(14), Carbon::now()->subDays(7)])->count(),
            '15 - 30 Hari' => (clone $outstandingQuery)->whereBetween('created_at', [Carbon::now()->subDays(30), Carbon::now()->subDays(14)])->count(),
            '> 30 Hari' => (clone $outstandingQuery)->where('created_at', '<', Carbon::now()->subDays(30))->count(),
        ];

        return view('livewire.zoffline.reporting.sales-order-report', [
            'outstandingOrders' => $outstandingOrders,
            'totalOutstanding' => $totalOutstanding,
            'totalPiutang' => $totalPiutang,
            'closingRate' => $closingRate,
            'totalSO' => $totalSO,
            'agingData' => $agingData,
        ]);
    }

    public function exportExcel()
    {
        $baseQuery = Order::where('order_channel', 'SO')
            ->whereBetween('created_at', [
                Carbon::parse($this->dateFrom)->startOfDay(),
                Carbon::parse($this->dateTo)->endOfDay(),
            ]);

        $outstandingOrders = (clone $baseQuery)->whereIn('order_status', ['down_payment', 'pending'])->latest()->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=laporan-so-outstanding-" . date('Ymd-His') . ".csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($outstandingOrders) {
            $file = fopen('php://output', 'w');
            
            // Output UTF-8 BOM
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, ['Tanggal SO', 'Umur (Hari)', 'No SO', 'Nama Pelanggan', 'No HP', 'Item (Barang)', 'Total Tagihan (Rp)', 'DP Masuk (Rp)', 'Sisa Tagihan (Rp)']);

            foreach ($outstandingOrders as $order) {
                $dpPaid = $order->payments()->where('status', 'PAID')->sum('amount');
                $sisaTagihan = max(0, $order->grand_total - $dpPaid);
                $umurHari = $order->created_at->diffInDays(now());
                
                $itemsNames = $order->items->map(function ($item) {
                    $name = $item->variant->name ?? $item->product_name ?? 'Item';
                    return $name . ' (x' . $item->qty . ')';
                })->implode(', ');
                
                fputcsv($file, [
                    $order->created_at->format('Y-m-d H:i:s'),
                    $umurHari,
                    $order->order_number,
                    $order->user->name ?? '-',
                    $order->user->phone ?? '-',
                    $itemsNames,
                    $order->grand_total,
                    $dpPaid,
                    $sisaTagihan
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, 'laporan-so-outstanding-' . date('Ymd-His') . '.csv', $headers);
    }
}
