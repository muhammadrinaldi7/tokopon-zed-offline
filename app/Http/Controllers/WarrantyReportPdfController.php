<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class WarrantyReportPdfController extends Controller
{
    public function export(Request $request)
    {
        $component = new \App\Livewire\Zoffline\Reporting\WarrantyReport();
        $component->startDate = $request->query('startDate', now()->startOfMonth()->format('Y-m-d'));
        $component->endDate = $request->query('endDate', now()->endOfMonth()->format('Y-m-d'));
        $component->search = $request->query('search', '');
        $component->branchId = $request->query('branchId', '');
        $component->activationStatus = $request->query('activationStatus', 'activated');
        
        $query = $component->getWarrantiesQueryProperty();
        $warranties = $query->get();

        $inspectors = collect();
        $promotors = collect();

        foreach ($warranties as $w) {
            if ($w->warranty && $w->warranty->deviceInspection && $w->warranty->deviceInspection->inspector) {
                $name = $w->warranty->deviceInspection->inspector->name;
                $inspectors[$name] = ($inspectors[$name] ?? 0) + 1;
            }
            if ($w->orderItem && $w->orderItem->order && $w->orderItem->order->salesBy) {
                $name = $w->orderItem->order->salesBy->name;
                $promotors[$name] = ($promotors[$name] ?? 0) + 1;
            }
        }

        $pdf = Pdf::loadView('pdf.warranty-report', [
            'warranties' => $warranties,
            'startDate' => $component->startDate,
            'endDate' => $component->endDate,
            'topPromotors' => $promotors->sortDesc()->take(5),
            'topInspectors' => $inspectors->sortDesc()->take(5),
            'total' => $warranties->count()
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('Laporan_Aktivasi_Garansi_' . now()->format('YmdHis') . '.pdf');
    }
}
