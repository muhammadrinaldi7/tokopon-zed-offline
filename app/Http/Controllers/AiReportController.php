<?php

namespace App\Http\Controllers;

use App\Exports\AiGenericExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AiReportController extends Controller
{
    public function download()
    {
        // 1. Ambil data dari session (yang diset di Chat.php Livewire tadi)
        $exportData = session('ai_export_data');

        if (!$exportData || empty($exportData['query'])) {
            abort(404, 'Data laporan tidak ditemukan atau sesi telah berakhir.');
        }

        $query = $exportData['query'];
        $type = strtolower($exportData['type']);

        // [SABUK PENGAMAN] 
        // Hapus titik koma di akhir jika AI menambahkannya
        $query = rtrim(trim($query), ';');

        // Jika AI tidak memberikan batas LIMIT, kita paksa maksimal 100 baris
        // agar server tidak mati saat memproses ribuan data
        if (!preg_match('/\bLIMIT\b/i', $query)) {
            $query .= " LIMIT 100";
        }

        $connectionName = config('database.ai_connection', 'mysql_readonly');

        try {
            $results = DB::connection($connectionName)->select($query);
        } catch (\Exception $e) {
            abort(500, 'Gagal mengeksekusi query database: ' . $e->getMessage());
        }

        // try {
        //     $results = DB::connection('mysql_readonly')->select($query);
        // } catch (\Exception $e) {
        //     abort(500, 'Gagal mengeksekusi query database: ' . $e->getMessage());
        // }

        // Jika data kosong
        if (empty($results)) {
            return "Data kosong untuk laporan ini.";
        }

        // 3. Ubah format Object dari DB::select menjadi Array murni
        $dataArray = json_decode(json_encode($results), true);

        // 4. Ambil nama-nama kolom secara dinamis dari baris pertama data
        $headings = array_keys($dataArray[0]);
        $fileName = 'Laporan_AI_' . date('Ymd_His');

        // 5. Eksekusi ke bentuk Excel
        if ($type === 'excel') {
            return Excel::download(new AiGenericExport($dataArray, $headings), $fileName . '.xlsx');
        }

        // 6. Eksekusi ke bentuk PDF
        if ($type === 'pdf') {
            $pdf = Pdf::loadView('reports.ai-pdf', [
                'data' => $dataArray,
                'headings' => $headings
            ])->setPaper('a4', 'landscape'); // Format landscape agar muat banyak kolom

            return $pdf->download($fileName . '.pdf');
        }

        abort(400, 'Format tidak didukung.');
    }
}
