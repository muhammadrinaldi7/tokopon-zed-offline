<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductAccurate;
use App\Services\AccurateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccurateImportController extends Controller
{
    protected $accurateService;

    public function __construct(AccurateService $accurateService)
    {
        $this->accurateService = $accurateService;
    }

    /**
     * Sinkronisasi data Item dari Accurate ke tabel product_accurates
     * URL Endpoint dapat menerima parameter ?source=syihab atau ?source=second
     */
    public function importItems(Request $request)
    {
        // Tentukan sumber database (syihab atau second)
        $source = $request->query('source', 'syihab');

        try {
            // Ambil daftar barang dari Accurate Online
            $response = $this->accurateService->getItemList($source);

            // Response Accurate biasanya ada di $response['d'] (data list) 
            // Pastikan struktur response Accurate Item List sesuai dengan dokumentasi (misal $response['d'] atau array langsung)
            $items = $response['d'] ?? $response;

            if (empty($items) || !is_array($items)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data tidak ditemukan atau struktur response berbeda.',
                    'source' => $source
                ]);
            }

            $importedCount = 0;

            foreach ($items as $item) {
                // Pastikan 'id' dan 'no' dari Accurate ada
                if (!isset($item['id'])) continue;

                ProductAccurate::updateOrCreate(
                    [
                        'accurate_id' => $item['id'],
                        'database_source' => $source,
                    ],
                    [
                        'item_no' => $item['no'] ?? null,
                        'name' => $item['name'] ?? null,
                        'base_price' => $item['unitPrice'] ?? 0, // Disesuaikan dengan field harga Accurate
                        'stock' => $item['quantity'] ?? 0, // Disesuaikan dengan field stok Accurate
                        'itemType' => $item['itemType'] ?? null,
                        'raw_data' => $item,
                    ]
                );
                
                $importedCount++;
            }

            return response()->json([
                'status' => 'success',
                'message' => "Berhasil menarik $importedCount data barang dari database $source.",
                'source' => $source
            ]);

        } catch (\Exception $e) {
            Log::error("Error importItems dari $source: " . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
