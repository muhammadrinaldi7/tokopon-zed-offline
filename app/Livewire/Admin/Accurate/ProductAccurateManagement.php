<?php

namespace App\Livewire\Admin\Accurate;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProductAccurate;
use App\Services\AccurateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductAccurateManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $activeTab = 'syihab'; // 'syihab' or 'second'
    public $isLoading = false;

    // Menangani perubahan search agar reset pagination
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedActiveTab()
    {
        $this->resetPage();
        $this->search = '';
    }

    public function syncItems()
    {
        $this->isLoading = true;

        try {
            $service = app(AccurateService::class);

            $page = 1;
            $pageSize = 100; // Limit maksimal Accurate
            $hasMoreData = true;

            $allImportData = []; // Wadah penampung data sebelum di-insert massal

            // 1. TARIK SEMUA DATA HALAMAN DEMI HALAMAN
            while ($hasMoreData) {
                // Pastikan getItemList Anda sudah dimodifikasi agar menerima parameter $page dan $pageSize
                $response = $service->getItemList($page, $pageSize, $this->activeTab);

                // Karena getItemList yang kita buat sebelumnya me-return langsung array dari 'd'
                $items = $response;

                if (empty($items)) {
                    $hasMoreData = false;
                    break;
                }

                foreach ($items as $item) {
                    if (!isset($item['no'])) continue;

                    // Siapkan data dalam bentuk array mentah untuk Bulk Insert
                    $allImportData[] = [
                        'accurate_id'     => $item['no'],
                        'database_source' => $this->activeTab,
                        'item_no'         => $item['no'],
                        // Gunakan 'name', bukan 'modifierName'
                        'name'            => $item['name'] ?? 'Unknown Item',
                        // Casting untuk membuang nol desimal bawaan Accurate
                        'base_price'      => (int) round($item['unitPrice'] ?? 0),
                        'stock'           => (int) round($item['availableToSell'] ?? 0),
                        // raw_data harus di-json_encode jika kolomnya berjenis text/json di MySQL
                        'raw_data'        => json_encode($item),

                        // Kolom timestamp wajib diisi manual saat menggunakan Bulk Upsert
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                }

                // Cek apakah data yang diterima genap 100. 
                // Jika kurang dari 100, berarti ini halaman terakhir.
                if (count($items) < $pageSize) {
                    $hasMoreData = false;
                } else {
                    $page++;
                }
            }

            // 2. SIMPAN KE DATABASE SECARA MASSAL (BULK UPSERT)
            if (!empty($allImportData)) {
                // Pecah array menjadi potongan (chunk) isi 500 agar MySQL tidak kepenuhan memori
                $chunks = array_chunk($allImportData, 500);

                DB::beginTransaction();
                try {
                    foreach ($chunks as $chunk) {
                        ProductAccurate::upsert(
                            $chunk,
                            // Parameter 2: Kunci unik untuk mengecek apakah data sudah ada
                            ['accurate_id', 'database_source'],
                            // Parameter 3: Kolom apa saja yang di-update jika data sudah ada (duplicate key)
                            ['item_no', 'name', 'base_price', 'stock', 'raw_data', 'updated_at']
                        );
                    }
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e; // Lempar ke blok catch utama di bawah
                }
            }

            $importedCount = count($allImportData);

            if ($importedCount === 0) {
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => 'Tidak ada data barang yang ditarik dari server Accurate.'
                ]);
            } else {
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => "Berhasil sinkronisasi $importedCount data produk secara massal."
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error Sync Accurate ({$this->activeTab}): " . $e->getMessage());
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Gagal sinkronisasi: ' . $e->getMessage()
            ]);
        }

        $this->isLoading = false;
    }

    public function render()
    {
        $query = ProductAccurate::where('database_source', $this->activeTab)
            ->orderBy('updated_at', 'desc');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('item_no', 'like', '%' . $this->search . '%')
                    ->orWhere('accurate_id', 'like', '%' . $this->search . '%');
            });
        }

        return view('livewire.admin.accurate.product-accurate-management', [
            'products' => $query->paginate(15)
        ])->layout('layouts.admin');
    }
}
