<?php

namespace App\Exports;

use App\Models\OrderCashSettlement;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonitoringKasirExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = OrderCashSettlement::query()
            ->select('order_cash_settlements.*')
            ->join('orders', 'order_cash_settlements.order_id', '=', 'orders.id')
            ->whereDate('order_cash_settlements.created_at', '>=', $this->filters['startDate'])
            ->whereDate('order_cash_settlements.created_at', '<=', $this->filters['endDate']);

        if (!empty($this->filters['businessUnitFilter'])) {
            $query->whereHas('order.businessUnit', function ($q) {
                $q->where('name', $this->filters['businessUnitFilter']);
            });
        }

        if (!empty($this->filters['branchFilter'])) {
            $query->whereHas('order.branch', function ($q) {
                $q->where('name', $this->filters['branchFilter']);
            });
        }

        if (!empty($this->filters['search'])) {
            $query->where(function ($q) {
                $q->whereHas('handledBy', function ($sub) {
                    $sub->where('name', 'like', '%' . $this->filters['search'] . '%');
                })->orWhereHas('monitoringBy', function ($sub) {
                    $sub->where('name', 'like', '%' . $this->filters['search'] . '%');
                })->orWhere('orders.order_number', 'like', '%' . $this->filters['search'] . '%');
            });
        }

        return $query->with(['handledBy', 'monitoringBy', 'order.branch'])
            ->orderBy('order_cash_settlements.created_at', 'desc')
            ->orderBy('order_cash_settlements.id', 'desc');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tanggal Settle',
            'No Order',
            'Cabang',
            'Kasir (FL)',
            'Penerima (Monitoring By)',
            'Nominal Tunai Sistem',
            'Nominal Settle Fisik',
            'Selisih',
            'Status'
        ];
    }

    public function map($settlement): array
    {
        return [
            $settlement->id,
            $settlement->created_at ? $settlement->created_at->format('Y-m-d H:i:s') : '',
            $settlement->order ? $settlement->order->order_number : '-',
            ($settlement->order && $settlement->order->branch) ? $settlement->order->branch->name : '-',
            $settlement->handledBy ? $settlement->handledBy->name : '-',
            $settlement->monitoringBy ? $settlement->monitoringBy->name : '-',
            $settlement->nominal_tunai,
            $settlement->nominal_settle,
            $settlement->selisih,
            strtoupper($settlement->status)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
