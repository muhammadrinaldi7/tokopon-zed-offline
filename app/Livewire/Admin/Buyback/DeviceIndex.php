<?php

namespace App\Livewire\Admin\Buyback;

use App\Models\BuybackDevice;
use App\Models\Brand;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.admin', ['title' => 'Daftar Perangkat Buyback'])]
class DeviceIndex extends Component
{
    use WithFileUploads, WithPagination;

    public $search = '';
    public $filterBrand = '';

    public $showEditModal = false;
    public $editingDeviceId = null;
    public $editModelName = '';
    public $editBasePrice = 0;
    public $editRam = '';
    public $editStorage = '';
    public $editColor = '';
    public $editIsActive = true;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterBrand()
    {
        $this->resetPage();
    }

    // Sync Master Data
    public $showSyncAccurateModal = false;
    public $syncTargetBuId = null;
    public $syncKeyword = '';

    // CSV Import/Export
    public $showImportModal = false;
    public $csvFile;

    public function editDevice($id)
    {
        $device = BuybackDevice::find($id);
        if ($device) {
            $this->editingDeviceId = $id;
            $this->editModelName = $device->model_name;
            $this->editRam = $device->ram ?? '';
            $this->editStorage = $device->storage ?? '';
            $this->editColor = $device->color ?? '';
            $this->editBasePrice = $device->base_price;
            $this->editIsActive = $device->is_active;
            $this->showEditModal = true;
        }
    }

    public function updateDevice()
    {
        $this->validate([
            'editModelName' => 'required|string|max:255',
            'editBasePrice' => 'required|numeric|min:0',
            'editRam' => 'nullable|string|max:255',
            'editStorage' => 'nullable|string|max:255',
            'editColor' => 'nullable|string|max:255',
        ]);

        $device = BuybackDevice::find($this->editingDeviceId);
        if ($device) {
            $device->update([
                'model_name' => $this->editModelName,
                'ram' => $this->editRam,
                'storage' => $this->editStorage,
                'color' => $this->editColor,
                'base_price' => $this->editBasePrice,
                'is_active' => $this->editIsActive,
            ]);

            // Auto re-assign tier berdasarkan harga baru
            $device->assignTierByPrice();
        }

        $this->showEditModal = false;
        $this->dispatch('toast', title: 'Berhasil', message: 'Data perangkat berhasil diperbarui.', type: 'success');
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingDeviceId = null;
    }
    public function syncTierDevice()
    {
        $devices = BuybackDevice::whereNotNull('base_price')->get();

        $count = 0;
        foreach ($devices as $device) {
            $device->assignTierByPrice();
            $count++;
        }

        $this->dispatch(
            'toast',
            title: 'Berhasil Disinkronisasi',
            message: "Berhasil meng-assign tier untuk {$count} perangkat berdasarkan harganya.",
            type: 'success'
        );
    }

    public function openSyncAccurateModal()
    {
        $this->syncTargetBuId = \App\Models\BusinessUnit::first()->id ?? null;
        $this->syncKeyword = '';
        $this->showSyncAccurateModal = true;
    }

    public function processSyncAccurate()
    {
        $query = \App\Models\ProductAccurate::query();

        if ($this->syncTargetBuId) {
            $query->where('business_unit_id', $this->syncTargetBuId);
            $query->where('categoryName', 'HP SECOND');
        }

        if (!empty($this->syncKeyword)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->syncKeyword . '%')
                    ->orWhere('item_no', 'like', '%' . $this->syncKeyword . '%');
            });
        }

        $products = $query->get();
        $count = 0;

        foreach ($products as $prod) {
            // Jangan sinkronisasi jika sudah ada
            $existing = BuybackDevice::where('product_accurate_id', $prod->id)
                ->orWhere('model_name', $prod->name)
                ->first();

            if (!$existing) {
                // Coba cocokan Brand atau buat baru
                $brandId = null;
                $brandName = trim($prod->brandName);

                if (empty($brandName)) {
                    $brandName = 'Lainnya'; // Fallback jika kosong
                }

                // Cari brand atau buat baru jika belum ada berdasarkan slug
                $slug = \Illuminate\Support\Str::slug($brandName);
                $brand = \App\Models\Brand::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => ucwords(strtolower($brandName))]
                );
                $brandId = $brand->id;

                BuybackDevice::create([
                    'brand_id'            => $brandId,
                    'product_accurate_id' => $prod->id,
                    'model_name'          => $prod->name,
                    'ram'                 => null,
                    'storage'             => '-', // Default
                    'color'               => '-', // Default
                    'base_price'          => $prod->base_price ?? 0,
                    'is_active'           => false,
                ]);

                $count++;
            }
        }

        // Auto assign tier untuk semua data
        $this->syncTierDeviceSilently();

        $this->showSyncAccurateModal = false;

        $this->dispatch(
            'toast',
            title: 'Sinkronisasi Selesai',
            message: "Berhasil menambahkan {$count} perangkat baru dari Master Data Accurate.",
            type: 'success'
        );
    }

    public function syncTierDeviceSilently()
    {
        $devices = BuybackDevice::whereNotNull('base_price')->where('base_price', '>', 0)->get();
        foreach ($devices as $device) {
            $device->assignTierByPrice();
        }
    }

    public function exportCsv()
    {
        $devices = BuybackDevice::with('brand')->get();
        $csvFileName = 'buyback_devices_' . date('Ymd_His') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['ID', 'Brand', 'Model Name', 'RAM', 'Storage', 'Color', 'Base Price', 'Is Active'];

        $callback = function () use ($devices, $columns) {
            $file = fopen('php://output', 'w');

            // Tambahkan BOM untuk Excel agar mengenali UTF-8
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, $columns, ';'); // Menggunakan ; agar ramah Excel Indonesia

            foreach ($devices as $device) {
                fputcsv($file, [
                    $device->id,
                    $device->brand ? $device->brand->name : '',
                    $device->model_name,
                    $device->ram,
                    $device->storage,
                    $device->color,
                    $device->base_price,
                    $device->is_active ? '1' : '0',
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

        if (!isset($headerMap['id']) || !isset($headerMap['model name']) || !isset($headerMap['base price'])) {
            $this->addError('csvFile', 'Kolom CSV harus mengandung ID, Model Name, dan Base Price.');
            return;
        }

        $count = 0;
        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            while (($row = fgetcsv($file, 1000, $delimiter)) !== false) {
                if (count($row) < 3) continue;

                $id = isset($headerMap['id']) ? trim($row[$headerMap['id']] ?? '') : '';
                $modelName = isset($headerMap['model name']) ? trim($row[$headerMap['model name']] ?? '') : '';
                $ram = isset($headerMap['ram']) ? trim($row[$headerMap['ram']] ?? '') : '';
                $storage = isset($headerMap['storage']) ? trim($row[$headerMap['storage']] ?? '') : '';
                $color = isset($headerMap['color']) ? trim($row[$headerMap['color']] ?? '') : '';
                $basePrice = isset($headerMap['base price']) ? trim($row[$headerMap['base price']] ?? '0') : '0';
                $isActiveCsv = isset($headerMap['is active']) ? trim($row[$headerMap['is active']] ?? '') : null;

                if (empty($id) || empty($modelName)) continue;

                // Hapus format ribuan jika ada (misal 10.000 atau 10,000)
                $basePrice = str_replace([',', '.', 'Rp', 'rp', ' '], '', $basePrice);

                $device = BuybackDevice::find($id);
                if ($device) {
                    $updateData = [
                        'model_name' => $modelName,
                        'ram'        => $ram ?: null,
                        'storage'    => $storage ?: '-',
                        'color'      => $color ?: '-',
                        'base_price' => is_numeric($basePrice) ? $basePrice : 0,
                    ];

                    // Jika ada kolom Is Active di CSV, update statusnya (1=Aktif, 0=Nonaktif)
                    if ($isActiveCsv !== null && $isActiveCsv !== '') {
                        // Terjemahkan 1, true, y, yes menjadi Aktif
                        $updateData['is_active'] = filter_var($isActiveCsv, FILTER_VALIDATE_BOOLEAN);
                    }

                    $device->update($updateData);
                    $device->assignTierByPrice();
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
        $query = BuybackDevice::with(['brand', 'tier']);

        if (!empty($this->search)) {
            $query->where('model_name', 'like', '%' . $this->search . '%')
                  ->orWhere('ram', 'like', '%' . $this->search . '%')
                  ->orWhere('storage', 'like', '%' . $this->search . '%')
                  ->orWhere('color', 'like', '%' . $this->search . '%');
        }

        if (!empty($this->filterBrand)) {
            $query->where('brand_id', $this->filterBrand);
        }

        $devices = $query->orderBy('brand_id')
            ->orderBy('model_name')
            ->paginate(15);

        $brands = Brand::orderBy('name')->get();

        return view('livewire.admin.buyback.device-index', compact('devices', 'brands'));
    }
}
