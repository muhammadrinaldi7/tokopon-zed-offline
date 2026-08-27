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

    // CSV Import/Export
    public $showImportModal = false;
    public $csvFile;

    public function editDevice($id)
    {
        $device = ProductAccurate::where('business_unit_id', 2)->find($id);
        if ($device) {
            $this->editingDeviceId = $id;
            $this->editItemNo = $device->item_no;
            $this->editName = $device->name;
            $this->editBuyPrice = $device->buy_price ?? 0;
            $this->showEditModal = true;
        }
    }

    public function updateDevice()
    {
        $this->validate([
            'editBuyPrice' => 'required|numeric|min:0',
        ]);

        $device = ProductAccurate::where('business_unit_id', 2)->find($this->editingDeviceId);
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

    public function exportCsv()
    {
        $query = ProductAccurate::where('business_unit_id', 2);

        if (!empty($this->search)) {
            $query->where(function($q) {
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

        $devices = $query->orderBy('name')->get();
        
        $csvFileName = 'product_accurates_' . date('Ymd_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'SKU', 'Nama Barang', 'Buy Price'];

        $callback = function () use ($devices, $columns) {
            $file = fopen('php://output', 'w');

            // Tambahkan BOM untuk Excel agar mengenali UTF-8
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, $columns, ';'); // Menggunakan ; agar ramah Excel Indonesia

            foreach ($devices as $device) {
                fputcsv($file, [
                    $device->id,
                    $device->item_no,
                    $device->name,
                    $device->buy_price ?? 0,
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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
        // Clean BOM if exists
        $firstLine = preg_replace('/\xEF\xBB\xBF/', '', $firstLine);
        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
        rewind($file);

        // Ambil header
        $headerLine = fgets($file);
        $headerLine = preg_replace('/\xEF\xBB\xBF/', '', $headerLine); // Remove BOM
        $header = str_getcsv($headerLine, $delimiter);

        if (!$header) {
            $this->addError('csvFile', 'Format file CSV tidak valid.');
            return;
        }

        // Peta index kolom (lowercase)
        $headerMap = array_flip(array_map('strtolower', array_map('trim', $header)));

        if (!isset($headerMap['id']) || !isset($headerMap['buy price'])) {
            $this->addError('csvFile', 'Kolom CSV harus mengandung ID dan Buy Price.');
            return;
        }

        $count = 0;
        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            while (($row = fgetcsv($file, 1000, $delimiter)) !== false) {
                if (count($row) < 2) continue;

                $id = isset($headerMap['id']) ? trim($row[$headerMap['id']] ?? '') : '';
                $buyPrice = isset($headerMap['buy price']) ? trim($row[$headerMap['buy price']] ?? '0') : '0';

                if (empty($id)) continue;

                // Hapus format ribuan jika ada (misal 10.000 atau 10,000)
                $buyPrice = str_replace([',', '.', 'Rp', 'rp', ' '], '', $buyPrice);

                $device = ProductAccurate::where('business_unit_id', 2)->find($id);
                if ($device) {
                    $updateData = [
                        'buy_price' => is_numeric($buyPrice) ? $buyPrice : 0,
                    ];

                    $device->update($updateData);
                    $count++;
                }
            }
            \Illuminate\Support\Facades\DB::commit();

            $this->showImportModal = false;
            $this->csvFile = null;

            $this->dispatch('toast', title: 'Import Berhasil', message: "Berhasil mengupdate {$count} perangkat dari CSV.", type: 'success');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->addError('csvFile', 'Gagal memproses file: ' . $e->getMessage());
        }

        fclose($file);
    }
    
    public function render()
    {
        $baseQuery = ProductAccurate::where('business_unit_id', 2);

        // Ambil data untuk dropdown filters
        $availableBrands = (clone $baseQuery)->whereNotNull('brandName')->where('brandName', '!=', '')->distinct()->pluck('brandName')->sort()->values();
        $availableCategories = (clone $baseQuery)->whereNotNull('categoryName')->where('categoryName', '!=', '')->distinct()->pluck('categoryName')->sort()->values();
        $availableProyeks = (clone $baseQuery)->whereNotNull('proyek')->where('proyek', '!=', '')->distinct()->pluck('proyek')->sort()->values();

        $query = clone $baseQuery;

        if (!empty($this->search)) {
            $query->where(function($q) {
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

        $devices = $query->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.buyback.device-index', compact('devices', 'availableBrands', 'availableCategories', 'availableProyeks'));
    }
}
