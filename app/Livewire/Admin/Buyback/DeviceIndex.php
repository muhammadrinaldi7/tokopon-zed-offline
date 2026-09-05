<?php

namespace App\Livewire\Admin\Buyback;

use App\Models\ProductAccurate;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.admin', ['title' => 'Daftar Perangkat Buyback'])]
class DeviceIndex extends Component
{
    use WithFileUploads, WithPagination;

    public $search = '';
    public $filterBrandName = '';
    public $filterCategoryName = '';
    public $filterProyek = '';
    public $filterOs = '';

    public $showEditModal = false;
    public $editingDeviceId = null;
    public $editItemNo = '';
    public $editName = '';
    public $editBuyPrice = 0;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterBrandName()
    {
        $this->resetPage();
    }

    public function updatingFilterCategoryName()
    {
        $this->resetPage();
    }

    public function updatingFilterProyek()
    {
        $this->resetPage();
    }

    public function updatingFilterOs()
    {
        $this->resetPage();
    }

    // CSV Import/Export
    public $showImportModal = false;
    public $csvFile;
    public $importSummary = null;

    public function closeImportModal()
    {
        $this->showImportModal = false;
        $this->csvFile = null;
        $this->importSummary = null;
    }

    protected function getBusinessUnitId()
    {
        return \Illuminate\Support\Facades\Auth::user()?->getActiveBusinessUnitId()
            ?? \Illuminate\Support\Facades\Auth::user()?->business_unit_id
            ?? 2;
    }

    public function editDevice($id)
    {
        $device = ProductAccurate::where('business_unit_id', $this->getBusinessUnitId())->find($id);
        if ($device) {
            $this->editingDeviceId = $id;
            $this->editItemNo = $device->item_no;
            $this->editName = $device->name;
            $this->editBuyPrice = $device->buy_price ?? 0;
            $this->showEditModal = true;
        }
    }

    public function updatedEditBuyPrice($value)
    {
        if (is_string($value)) {
            $cleaned = preg_replace('/[^0-9]/', '', $value);
            $this->editBuyPrice = $cleaned !== '' ? (int) $cleaned : 0;
        }
    }

    public function updateDevice()
    {
        $this->editBuyPrice = (int) preg_replace('/[^0-9]/', '', (string)$this->editBuyPrice);

        $this->validate([
            'editBuyPrice' => 'required|numeric|min:0',
        ]);

        $device = ProductAccurate::where('business_unit_id', $this->getBusinessUnitId())->find($this->editingDeviceId);
        if ($device) {
            $device->update([
                'buy_price' => $this->editBuyPrice,
            ]);
        }

        $this->showEditModal = false;
        $this->dispatch('toast', title: 'Berhasil', message: 'Harga beli (Buy Price) berhasil diperbarui.', type: 'success');
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingDeviceId = null;
    }

    public function downloadTemplate()
    {
        $csvFileName = 'template_import_buy_price.csv';
        $columns = ['ID', 'SKU', 'Nama Barang', 'Buy Price'];

        return response()->streamDownload(function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns, ';');
            fputcsv($file, ['101', 'SKU-CONTOH-1', 'Contoh Perangkat iPhone 11 64GB', '2500000'], ';');
            fputcsv($file, ['102', 'SKU-CONTOH-2', 'Contoh Perangkat Samsung A54 8/128', '1800000'], ';');
            fclose($file);
        }, $csvFileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $csvFileName . '"',
        ]);
    }

    public function exportCsv()
    {
        $buId = $this->getBusinessUnitId();
        $query = ProductAccurate::where('business_unit_id', $buId)
            ->select('id', 'item_no', 'name', 'brandName', 'categoryName', 'os', 'buy_price');

        $hasFilter = !empty($this->search)
            || !empty($this->filterBrandName)
            || !empty($this->filterCategoryName)
            || !empty($this->filterProyek)
            || !empty($this->filterOs);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('item_no', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->filterBrandName)) {
            $query->where('brandName', $this->filterBrandName);
        }

        if (!empty($this->filterCategoryName)) {
            $query->where('categoryName', $this->filterCategoryName);
        }

        if (!empty($this->filterProyek)) {
            $query->where('proyek', $this->filterProyek);
        }

        if (!empty($this->filterOs)) {
            $query->where('os', $this->filterOs);
        }

        // Safety Guard: jika tanpa filter dan data melebihi 1.500 (misal 11.000 item GSK), cegah timeout/crash server
        if (!$hasFilter) {
            $totalCount = (clone $query)->count();
            if ($totalCount > 1500) {
                $this->dispatch('toast',
                    title: 'Filter Diperlukan',
                    message: "Data terlalu besar ({$totalCount} produk). Silakan pilih filter Merek, OS, atau Kategori terlebih dahulu sebelum mengekspor agar server tetap stabil.",
                    type: 'warning'
                );
                return;
            }
        }

        $csvFileName = 'buyback_devices_' . date('Ymd_His') . '.csv';
        $columns = ['ID', 'SKU', 'Nama Barang', 'Merek', 'Kategori', 'OS', 'Buy Price'];

        return response()->streamDownload(function () use ($query, $columns) {
            $file = fopen('php://output', 'w');

            // Tambahkan BOM untuk Excel agar mengenali UTF-8
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, $columns, ';'); // Menggunakan ; agar ramah Excel Indonesia

            foreach ($query->orderBy('name')->cursor() as $device) {
                fputcsv($file, [
                    $device->id,
                    $device->item_no,
                    $device->name,
                    $device->brandName ?: '-',
                    $device->categoryName ?: '-',
                    $device->os ?: '-',
                    $device->buy_price ?? 0,
                ], ';');
            }

            fclose($file);
        }, $csvFileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $csvFileName . '"',
        ]);
    }

    public function importCsv()
    {
        $this->validate([
            'csvFile' => 'required|file|mimes:csv,txt|max:10240', // max 10MB
        ]);

        $filePath = $this->csvFile->getRealPath();
        $file = fopen($filePath, "r");

        // Baca header pertama untuk deteksi delimiter
        $firstLine = fgets($file);
        $firstLine = preg_replace('/\xEF\xBB\xBF/', '', $firstLine);
        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
        rewind($file);

        // Ambil header
        $headerLine = fgets($file);
        $headerLine = preg_replace('/\xEF\xBB\xBF/', '', $headerLine); // Remove BOM
        $header = str_getcsv($headerLine, $delimiter);

        if (!$header) {
            $this->addError('csvFile', 'Format file CSV tidak valid.');
            fclose($file);
            return;
        }

        // Peta index kolom (lowercase)
        $headerMap = array_flip(array_map('strtolower', array_map('trim', $header)));

        if (!isset($headerMap['id']) || !isset($headerMap['buy price'])) {
            $this->addError('csvFile', 'Kolom CSV harus mengandung kolom "ID" dan "Buy Price".');
            fclose($file);
            return;
        }

        // Kumpulkan semua baris terlebih dahulu
        $rows = [];
        while (($row = fgetcsv($file, 1000, $delimiter)) !== false) {
            if (count($row) < 2) continue;

            $id = isset($headerMap['id']) ? trim($row[$headerMap['id']] ?? '') : '';
            $buyPrice = isset($headerMap['buy price']) ? trim($row[$headerMap['buy price']] ?? '0') : '0';

            if (!empty($id) && is_numeric($id)) {
                $cleanPrice = str_replace([',', '.', 'Rp', 'rp', ' '], '', $buyPrice);
                $rows[(int)$id] = is_numeric($cleanPrice) ? (float)$cleanPrice : 0;
            }
        }
        fclose($file);

        $totalRows = count($rows);
        if ($totalRows === 0) {
            $this->addError('csvFile', 'Tidak ada data baris yang valid di dalam file CSV.');
            return;
        }

        // Batas aman baris per file import untuk menjaga stabilitas database & antrean background
        if ($totalRows > 20000) {
            $this->addError('csvFile', "File terlalu besar ({$totalRows} baris). Maksimal 20.000 baris per file import untuk menjaga performa server.");
            return;
        }

        $buId = $this->getBusinessUnitId();
        $chunks = array_chunk($rows, 250, true);

        $updatedCount = 0;
        $unchangedCount = 0;
        $skippedCount = 0;

        try {
            foreach ($chunks as $chunk) {
                $ids = array_keys($chunk);

                // Tarik harga saat ini dalam 1 query batch
                $existingProducts = ProductAccurate::where('business_unit_id', $buId)
                    ->whereIn('id', $ids)
                    ->pluck('buy_price', 'id')
                    ->toArray();

                $toUpdate = [];
                foreach ($chunk as $id => $newPrice) {
                    if (!array_key_exists($id, $existingProducts)) {
                        $skippedCount++;
                        continue;
                    }

                    $currentPrice = (float)($existingProducts[$id] ?? 0);
                    if ($currentPrice !== (float)$newPrice) {
                        $toUpdate[$id] = $newPrice;
                    } else {
                        $unchangedCount++;
                    }
                }

                // Micro-transaction per chunk 250 data: sangat cepat, tidak mengunci table lama
                if (!empty($toUpdate)) {
                    \Illuminate\Support\Facades\DB::transaction(function () use ($toUpdate, $buId) {
                        foreach ($toUpdate as $id => $price) {
                            ProductAccurate::where('business_unit_id', $buId)
                                ->where('id', $id)
                                ->update(['buy_price' => $price]);
                        }
                    });
                    $updatedCount += count($toUpdate);
                }
            }

            $this->csvFile = null;
            $this->importSummary = [
                'total' => $totalRows,
                'updated' => $updatedCount,
                'unchanged' => $unchangedCount,
                'skipped' => $skippedCount,
            ];

            $msg = "Berhasil mengupdate {$updatedCount} harga perangkat.";
            if ($unchangedCount > 0) {
                $msg .= " ({$unchangedCount} tidak berubah";
                $msg .= $skippedCount > 0 ? ", {$skippedCount} tidak ditemukan)." : ").";
            } elseif ($skippedCount > 0) {
                $msg .= " ({$skippedCount} tidak ditemukan).";
            }

            $this->dispatch('toast', title: 'Import Selesai', message: $msg, type: 'success');
        } catch (\Exception $e) {
            $this->addError('csvFile', 'Gagal memproses file: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $baseQuery = ProductAccurate::where('business_unit_id', $this->getBusinessUnitId());

        // Ambil data untuk dropdown filters
        $availableBrands = (clone $baseQuery)->whereNotNull('brandName')->where('brandName', '!=', '')->distinct()->pluck('brandName')->sort()->values();
        $availableCategories = (clone $baseQuery)->whereNotNull('categoryName')->where('categoryName', '!=', '')->distinct()->pluck('categoryName')->sort()->values();
        $availableProyeks = (clone $baseQuery)->whereNotNull('proyek')->where('proyek', '!=', '')->distinct()->pluck('proyek')->sort()->values();
        $availableOs = (clone $baseQuery)->whereNotNull('os')->where('os', '!=', '')->distinct()->pluck('os')->sort()->values();

        $query = clone $baseQuery;

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('item_no', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->filterBrandName)) {
            $query->where('brandName', $this->filterBrandName);
        }

        if (!empty($this->filterCategoryName)) {
            $query->where('categoryName', $this->filterCategoryName);
        }

        if (!empty($this->filterProyek)) {
            $query->where('proyek', $this->filterProyek);
        }

        if (!empty($this->filterOs)) {
            $query->where('os', $this->filterOs);
        }

        $devices = $query->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.buyback.device-index', compact('devices', 'availableBrands', 'availableCategories', 'availableProyeks', 'availableOs'));
    }
}
