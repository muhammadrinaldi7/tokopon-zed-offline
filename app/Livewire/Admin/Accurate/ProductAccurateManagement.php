<?php

namespace App\Livewire\Admin\Accurate;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ProductAccurate;
use App\Services\AccurateService;
use App\Services\SerialNumberSyncService;
use App\Traits\GeneratesProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductAccurateManagement extends Component
{
    use WithPagination, GeneratesProductVariant;

    public $search = '';
    public $activeTab = 'syihab'; // 'syihab' or 'second'
    public $isLoading = false;

    public $isSyncing = false;
    public $syncCurrentPage = 1;
    public $syncImportedCount = 0;
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
    /**
     * Langkah 1: Tombol UI memicu ini untuk mereset state dan memulai estafet
     */

    #[On('trigger-next-page')]
    public function processNextPage()
    {
        // Pencegah bypass jika tidak sedang sync
        if (!$this->isSyncing) return;

        try {
            $service = app(AccurateService::class);
            $pageSize = 100;
            $bu = \App\Models\BusinessUnit::where('code', $this->activeTab)->first();
            $buId = $bu ? $bu->id : null;

            // Tarik HANYA 1 halaman saja (Super cepat, < 1 detik)
            $items = $service->getItemList($this->syncCurrentPage, $pageSize, $this->activeTab);

            if (empty($items) && $this->syncCurrentPage === 1) {
                $this->isSyncing = false;
                $this->dispatch('notify', [
                    'type' => 'warning',
                    'message' => 'Data tidak ditemukan dari server Accurate.'
                ]);
                return;
            }

            // --- PROSES BULK UPSERT ---
            $importData = [];
            foreach ($items as $item) {
                if (!isset($item['no'])) continue;
                $importData[] = [
                    'accurate_id'     => $item['no'],
                    'database_source' => $this->activeTab,
                    'business_unit_id' => $buId,
                    'item_no'         => $item['no'],
                    'name'            => $item['name'] ?? 'Unknown Item',
                    'base_price'      => (int) round($item['unitPrice'] ?? 0),
                    'base_cost'       => (int) round($item['balanceUnitCost'] ?? 0),
                    'stock'           => (int) round($item['availableToSell'] ?? 0),
                    'has_sn'          => $item['manageSN'],
                    'id_brand_accurate' => $item['itemBrand']['id'] ?? null,
                    'brandName' => $item['itemBrand']['name'] ?? null,
                    'id_category_accurate' => $item['itemCategory']['id'] ?? null,
                    'categoryName' => $item['itemCategory']['name'] ?? null,
                    'raw_data'        => json_encode($item),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            }
            if (!empty($importData)) {
                ProductAccurate::upsert(
                    $importData,
                    ['accurate_id', 'database_source'],
                    ['item_no', 'name', 'base_price', 'stock', 'has_sn', 'business_unit_id', 'id_brand_accurate', 'brandName', 'id_category_accurate', 'categoryName', 'raw_data', 'base_cost', 'updated_at']
                );

                // Sync harga ke ProductVariant / SecondProductVariant lokal
                foreach ($importData as $imported) {
                    $sku = $imported['item_no'];
                    $newPrice = $imported['base_price'];
                    if ($newPrice <= 0) continue;

                    $variant = \App\Models\ProductVariant::where('sku', $sku)->first();
                    if (!$variant) {
                        $variant = \App\Models\SecondProductVariant::where('sku', $sku)->first();
                    }
                    if ($variant && (int) $variant->price !== $newPrice) {
                        $variant->update(['price' => $newPrice]);
                    }
                }

                $this->syncImportedCount += count($importData);
            }

            // --- CEK KONDISI SELANJUTNYA ---
            if (count($items) < $pageSize) {
                // KONDISI A: Data habis. SELESAI!
                $this->isSyncing = false;
                $this->dispatch('notify', [
                    'type' => 'success',
                    'message' => "Selesai! {$this->syncImportedCount} data produk berhasil disinkronisasi."
                ]);
            } else {
                // KONDISI B: Masih ada data. Lanjut ke halaman berikutnya.
                $this->syncCurrentPage++;

                // UX MAGIC: Suruh browser mengirim request lagi secara otomatis
                // Ini membuat UI sempat me-render angka progress sebelum request berikutnya jalan.
                $this->dispatch('trigger-next-page');
            }
        } catch (\Exception $e) {
            $this->isSyncing = false;
            Log::error("Error Sync Accurate ({$this->activeTab}): " . $e->getMessage());
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Gagal sinkronisasi pada halaman ' . $this->syncCurrentPage . ': ' . $e->getMessage()
            ]);
        }
    }
    public function startSync()
    {
        $this->isSyncing = true;
        $this->syncCurrentPage = 1;
        $this->syncImportedCount = 0;

        // Perintahkan browser untuk langsung memanggil proses halaman pertama
        $this->dispatch('trigger-next-page');
    }
    public function syncItems()
    {
        $this->isLoading = true;

        try {
            $service = app(AccurateService::class);
            $bu = \App\Models\BusinessUnit::where('code', $this->activeTab)->first();
            $buId = $bu ? $bu->id : null;

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
                        'business_unit_id' => $buId,
                        'item_no'         => $item['no'],
                        // Gunakan 'name', bukan 'modifierName'
                        'name'            => $item['name'] ?? 'Unknown Item',
                        // Casting untuk membuang nol desimal bawaan Accurate
                        'base_price'      => (int) round($item['unitPrice'] ?? 0),
                        'stock'           => (int) round($item['availableToSell'] ?? 0),
                        'base_cost'       => (int) round($item['balanceUnitCost'] ?? 0),
                        'has_sn'          => (isset($item['serialNumberType']) && $item['serialNumberType'] === 'UNIQUE'),
                        'id_brand_accurate' => $item['itemBrand']['id'] ?? null,
                        'brandName' => $item['itemBrand']['name'] ?? null,
                        'id_category_accurate' => $item['itemCategory']['id'] ?? null,
                        'categoryName' => $item['itemCategory']['name'] ?? null,
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
                            [
                                'item_no',
                                'name',
                                'base_price',
                                'stock',
                                'base_cost',
                                'has_sn',
                                'business_unit_id',
                                'id_brand_accurate',
                                'brandName',
                                'id_category_accurate',
                                'categoryName',
                                'raw_data',
                                'updated_at'
                            ]
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
        $query = ProductAccurate::withCount(['productVariants', 'secondProductVariants'])
            ->where('database_source', $this->activeTab)
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

    /**
     * Manual trigger for Auto-Generating Product Variant from ProductAccurate.
     * Use this in the blade template: wire:click="generateVariantLocally({{ $item->id }})"
     */
    public function generateVariantLocally($productAccurateId)
    {
        try {
            $productAccurate = ProductAccurate::findOrFail($productAccurateId);

            // 1. Fetch full details from Accurate
            $service = app(AccurateService::class);
            $accurateItemData = $service->itemDetailDo($productAccurate->item_no, $this->activeTab);

            if (!$accurateItemData || empty($accurateItemData)) {
                $this->dispatch('toast', title: 'Gagal', message: 'Data detail item tidak ditemukan di Accurate API.', type: 'error');
                return;
            }

            // 2. Gunakan Trait GeneratesProductVariant — conditional berdasarkan tab aktif
            if ($this->activeTab === 'second') {
                // GSK: Generate SecondProduct + SecondProductVariant
                $result = $this->autoGenerateSecondProductAndVariant(
                    $productAccurate->item_no,
                    $accurateItemData,
                    $productAccurate->id
                );
            } else {
                // Syihab: Generate Product + ProductVariant (existing)
                $result = $this->autoGenerateProductAndVariant(
                    $productAccurate->item_no,
                    $accurateItemData,
                    $productAccurate->id
                );
            }

            if ($result['success']) {
                $this->dispatch('toast', title: 'Berhasil', message: $result['message'] ?? 'Variant berhasil di-generate dan siap dijual!', type: 'success');
            } else {
                $this->dispatch('toast', title: 'Gagal', message: $result['message'] ?? 'Variant gagal di-generate', type: 'error');
            }
        } catch (\Exception $e) {
            Log::error('Gagal generate variant manual: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Variant gagal di-generate', type: 'error');
        }
    }

    public function syncSerialNumber($accurateId)
    {
        try {
            $service = app(SerialNumberSyncService::class);
            $snCount = $service->syncFromAccurate($accurateId, $this->activeTab);

            // Sync harga juga
            $priceResult = $service->syncPriceFromAccurate($accurateId, $this->activeTab);
            $priceMsg = '';
            if ($priceResult['updated']) {
                $priceMsg = ' | Harga diperbarui: Rp ' . number_format($priceResult['old_price']) . ' → Rp ' . number_format($priceResult['new_price']);
            }

            if ($snCount > 0) {
                $this->dispatch('toast', title: 'Berhasil', message: "Berhasil sinkronisasi $snCount Serial Number." . $priceMsg, type: 'success');
            } else {
                $msg = 'Tidak ada data Serial Number di Accurate.';
                if ($priceResult['updated']) {
                    $msg = 'SN tidak ada, tapi harga berhasil diperbarui.' . $priceMsg;
                }
                $this->dispatch('toast', title: $priceResult['updated'] ? 'Berhasil' : 'Info', message: $msg, type: $priceResult['updated'] ? 'success' : 'warning');
            }
        } catch (\Exception $e) {
            Log::error('Gagal sync serial number: ' . $e->getMessage());
            $this->dispatch('toast', title: 'Gagal', message: 'Gagal sync serial number: ' . $e->getMessage(), type: 'error');
        }
    }
}
