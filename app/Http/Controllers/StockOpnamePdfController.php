<?php

namespace App\Http\Controllers;

use App\Models\StockOpname;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Exports\StockOpnameExport;
use Maatwebsite\Excel\Facades\Excel;

class StockOpnamePdfController extends Controller
{
    /**
     * Cetak Berita Acara Stock Opname Resmi ke format PDF
     */
    public function export(StockOpname $opname)
    {
        $user = Auth::user();
        $buId = $user->getActiveBusinessUnitId();
        $isGlobal = $user->hasAnyRole(['superadmin', 'admin', 'director']);

        if ($opname->business_unit_id !== $buId) {
            abort(403, 'Akses ditolak: Unit bisnis tidak sesuai.');
        }

        if (!$isGlobal && $user->branch_id && $opname->branch_id !== $user->branch_id) {
            abort(403, 'Akses ditolak: Anda hanya dapat mengakses opname cabang Anda sendiri.');
        }

        $opname->load(['branch', 'warehouse', 'user', 'businessUnit', 'items.lastCountedBy', 'serials.stockOpnameItem', 'serials.scannedByUser']);

        $isCompleted = in_array($opname->status, ['COMPLETED', 'APPROVED']);

        $discrepancyItems = $opname->items->filter(fn($i) => $i->difference_qty != 0);

        $missingSerialsRaw = $opname->serials->where('status', 'MISSING');
        $unexpectedSerialsRaw = $opname->serials->where('status', 'UNEXPECTED');

        $missingSerials = $missingSerialsRaw->map(function ($sn) use ($isCompleted) {
            return (object) [
                'id'              => $sn->id,
                'serial_number'   => $isCompleted ? $sn->serial_number : \App\Livewire\Zoffline\StockOpname\Count::maskSerialNumber($sn->serial_number, 'MISSING'),
                'is_masked'       => !$isCompleted,
                'stockOpnameItem' => $sn->stockOpnameItem,
                'item_no'         => $sn->item_no,
                'hpp'             => $sn->hpp,
                'status'          => $sn->status,
                'notes'           => $sn->notes,
            ];
        });

        $unexpectedSerials = $unexpectedSerialsRaw->map(function ($sn) use ($isCompleted) {
            return (object) [
                'id'              => $sn->id,
                'serial_number'   => $isCompleted ? $sn->serial_number : \App\Livewire\Zoffline\StockOpname\Count::maskSerialNumber($sn->serial_number, 'UNEXPECTED'),
                'is_masked'       => !$isCompleted,
                'stockOpnameItem' => $sn->stockOpnameItem,
                'scannedByUser'   => $sn->scannedByUser,
                'item_no'         => $sn->item_no,
                'hpp'             => $sn->hpp,
                'status'          => $sn->status,
                'notes'           => $sn->notes,
            ];
        });

        $scannersSummary = $opname->getScannersSummary();

        $pdf = Pdf::loadView('pdf.stock-opname', [
            'opname'            => $opname,
            'isCompleted'       => $isCompleted,
            'discrepancyItems'  => $discrepancyItems,
            'missingSerials'    => $missingSerials,
            'unexpectedSerials' => $unexpectedSerials,
            'scannersSummary'   => $scannersSummary,
        ])->setPaper('a4', 'portrait');

        $filename = "Berita_Acara_SO_{$opname->opname_number}.pdf";
        return $pdf->stream($filename);
    }

    /**
     * Export Hasil Audit Stock Opname ke Excel
     */
    public function exportExcel(StockOpname $opname)
    {
        $user = Auth::user();
        $buId = $user->getActiveBusinessUnitId();
        $isGlobal = $user->hasAnyRole(['superadmin', 'admin', 'director']);

        if ($opname->business_unit_id !== $buId) {
            abort(403, 'Akses ditolak: Unit bisnis tidak sesuai.');
        }

        if (!$isGlobal && $user->branch_id && $opname->branch_id !== $user->branch_id) {
            abort(403, 'Akses ditolak: Anda hanya dapat mengakses opname cabang Anda sendiri.');
        }

        $filename = "Stock_Opname_{$opname->opname_number}.xlsx";
        return Excel::download(new StockOpnameExport($opname), $filename);
    }
}

