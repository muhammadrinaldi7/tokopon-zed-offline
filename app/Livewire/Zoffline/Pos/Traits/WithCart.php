<?php

namespace App\Livewire\Zoffline\Pos\Traits;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SecondProduct;
use App\Models\SecondProductVariant;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Auth;

trait WithCart
{
    // ─── Search & Filter ───────────────────────────────────────
    public $search = '';
    public $productType = 'new'; // all, new, second
    public $scanned_sn = '';
    // ─── Cart (in-memory) ──────────────────────────────────────
    public $cart = []; // [{variant_id, variant_type, name, storage, color, price, qty, serial_number, sku}]

    // ─── Variant Selection ─────────────────────────────────────
    public $showVariantModal = false;
    public $variantModalProduct = null;
    public $variantModalVariants = [];
    public $variantModalIsSecond = false;

    // ─── QC Serah Terima ───────────────────────────────────────
    public $showQcModal = false;
    public $targetSnId = null;
    public $targetImei = '';

    #[\Livewire\Attributes\On('qc-inspection-saved')]

    public function openQcSerahTerima($sn)
    {
        $snRecord = \App\Models\ProductSerialNumber::where('serial_number', $sn)->first();
        if ($snRecord) {
            $this->targetSnId = $snRecord->id;
            $this->targetImei = $sn;
            $this->showQcModal = true;
        } else {
            $this->dispatch('toast', title: 'Error', message: 'Serial Number tidak valid.', type: 'error');
        }
    }

    public function onInspectionSaved($verdict)
    {
        $this->showQcModal = false;
        // Pengecekan status sudah dilakukan saat openCheckout
    }

    public $showCustomerQcModal = false;
    public $customerQcData = null;

    public function openCustomerQcModal($sn)
    {
        $inspection = \App\Models\DeviceInspection::with(['inspector', 'media'])
            ->where('imei', $sn)
            ->where('label', '!=', 'QC Serah Terima')
            ->orderBy('inspected_at', 'desc')
            ->first();

        if (!$inspection) {
            $this->dispatch('toast', title: 'Info', message: "Belum ada riwayat Sertifikat QC untuk IMEI/SN: {$sn}", type: 'warning');
            return;
        }

        $this->customerQcData = $inspection;
        $this->showCustomerQcModal = true;
    }

    public function processScan(AccurateService $accurateService)
    {
        $sn = trim($this->scanned_sn);

        if (empty($sn)) {
            return;
        }

        // 1. Hit ke Accurate via Service untuk mendapatkan No SKU
        $skuFromAccurate = $accurateService->findSkuBySerialNumber($sn, $this->databaseSource);

        $unit = \Illuminate\Support\Facades\Auth::user()->businessUnit?->code ?? 'all';

        // Jika user adalah 'all' dan SN tidak ditemukan di syihab, coba cari di second
        if ((!$skuFromAccurate || $skuFromAccurate === 'error') && $unit === 'all' && $this->databaseSource === 'syihab') {
            $skuFromAccurateSecond = $accurateService->findSkuBySerialNumber($sn, 'second');
            if ($skuFromAccurateSecond && $skuFromAccurateSecond !== 'error' && $skuFromAccurateSecond !== 'invalid_type') {
                $skuFromAccurate = $skuFromAccurateSecond;
                $this->databaseSource = 'second'; // Switch source temporarily for this transaction
            }
        }

        if ($skuFromAccurate === 'error') {
            $this->dispatch('toast', title: 'Error', message: 'Terjadi gangguan koneksi ke Accurate.', type: 'error');
            return;
        }

        // KONDISI B (BARU): Data ada di Accurate, tapi yang di-scan BUKAN Serial Number
        if ($skuFromAccurate === 'invalid_type') {
            $this->dispatch('toast', title: 'Peringatan', message: "Kode '{$sn}' terdeteksi sebagai Barcode/SKU barang, mohon scan Serial Number produk.", type: 'warning');
            $this->scanned_sn = '';
            return;
        }

        if (!$skuFromAccurate) {
            $this->dispatch('toast', title: 'Error', message: "Serial Number '{$sn}' tidak ditemukan di Accurate.", type: 'error');
            $this->scanned_sn = '';
            return;
        }

        // 2. Cek apakah SN ini sudah discan dan ada di cart (Mencegah scan ganda)
        if ($this->isSnAlreadyInCart($sn)) {
            $this->dispatch('toast', title: 'Peringatan', message: "Serial Number '{$sn}' sudah ada di dalam keranjang.", type: 'warning');
            $this->scanned_sn = '';
            return;
        }

        // 3. Cari SKU di Database Lokal (Cek Baru, lalu Bekas)
        $warehouseId = \Illuminate\Support\Facades\Auth::user()->warehouse_id;

        // =========================================================================
        // 3. CEK KESESUAIAN WAREHOUSE DI TABEL product_serial_numbers
        // =========================================================================
        // Catatan: Pastikan nama kolom 'sn' atau 'serial_number' sesuai dengan yang ada di database-mu
        $localSnRecord = \Illuminate\Support\Facades\DB::table('product_serial_numbers')
            ->where('serial_number', $sn)
            ->first();

        if (!$localSnRecord) {
            $this->dispatch('toast', title: 'Gagal', message: "Serial Number '{$sn}' tidak ditemukan di ZPOS.", type: 'error');
            $this->scanned_sn = '';
            return;
        }

        if ($localSnRecord->warehouse_id != $warehouseId) {
            // Ambil nama gudang yang memiliki SN tersebut
            $actualWarehouseName = \Illuminate\Support\Facades\DB::table('warehouses')
                ->where('id', $localSnRecord->warehouse_id)
                ->value('name'); // Ganti 'name' dengan nama kolom gudang di databasemu (misal: 'nama_gudang')

            // Antisipasi jika data gudangnya ternyata tidak ketemu di DB
            $warehouseTarget = $actualWarehouseName ?? 'Gudang Lain';

            $this->dispatch(
                'toast',
                title: 'Gagal',
                message: "Serial Number '{$sn}' ada di gudang {$warehouseTarget} Silahkan lakukan pemindahan barang di accurate.",
                type: 'error'
            );

            $this->scanned_sn = '';
            return;
        }

        $isSecond = false;
        $variantType = \App\Models\ProductVariant::class;

        // Cek di Varian Produk Baru
        $variant = \App\Models\ProductVariant::with(['product', 'warehouseStocks' => function ($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId);
        }])->where('sku', $skuFromAccurate)->first();

        // Jika tidak ada di Baru, Cek di Varian Produk Bekas
        if (!$variant) {
            $variant = \App\Models\SecondProductVariant::with(['secondProduct', 'warehouseStocks' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }])->where('sku', $skuFromAccurate)->first();

            if ($variant) {
                $isSecond = true;
                $variantType = \App\Models\SecondProductVariant::class;
            }
        }

        if (!$variant) {
            $this->dispatch('toast', title: 'Peringatan', message: "Produk (SKU: {$skuFromAccurate}) belum terdaftar di sistem lokal.", type: 'warning');
            $this->scanned_sn = '';
            return;
        }

        // 4. Masukkan ke cart dengan SN yang berhasil discan
        $this->addScannedVariantToCart($variant, $isSecond, $variantType, $sn);

        // Kosongkan kembali input scanner
        $this->scanned_sn = '';
    }

    public $scannedItemConfirm = null;
    public $showScannedItemModal = false;

    /**
     * Memasukkan varian hasil scan ke cart (menggunakan Modal Konfirmasi)
     */
    private function addScannedVariantToCart($variant, $isSecond, $variantType, $sn)
    {
        $stock = $variant->warehouseStocks->first()?->stock ?? 0;

        if ($stock <= 0) {
            $this->dispatch('toast', title: 'Stok Habis', message: 'Varian ini kosong di gudang Anda.', type: 'warning');
            return;
        }

        $this->scannedItemConfirm = [
            'variant' => $variant,
            'isSecond' => $isSecond,
            'variantType' => $variantType,
            'sn' => $sn,
            'price' => (int) $variant->price,
            'name' => $variant->product->name,
            'color' => $variant->color ?? '-',
            'storage' => $variant->storage ?? '-',
            'ram' => $variant->ram ?? '-',
            'sku' => $variant->sku ?? '',
            'has_sn' => (bool) $variant->has_sn,
            'stock' => $stock
        ];

        $this->showScannedItemModal = true;
    }

    public function confirmScannedItem()
    {
        if (!$this->scannedItemConfirm) return;

        $itemData = $this->scannedItemConfirm;
        $variantId = $itemData['variant']->id;
        $variantType = $itemData['variantType'];
        $sn = $itemData['sn'];
        $stock = $itemData['stock'];

        // Cek apakah produk varian ini sudah ada di keranjang
        $existingIndex = collect($this->cart)->search(
            fn($item) => $item['variant_id'] == $variantId && $item['variant_type'] == $variantType
        );

        if ($existingIndex !== false) {
            $currentQty = $this->cart[$existingIndex]['qty'];

            if ($currentQty < $stock) {
                $this->cart[$existingIndex]['qty']++;

                if (!isset($this->cart[$existingIndex]['serial_numbers'])) {
                    $this->cart[$existingIndex]['serial_numbers'] = [];
                }

                $this->cart[$existingIndex]['serial_numbers'][] = $sn;

                $this->dispatch('toast', title: 'Sukses', message: 'Kuantitas ditambah & SN tercatat.', type: 'success');
            } else {
                $this->dispatch('toast', title: 'Stok Tidak Cukup', message: 'Sudah mencapai batas stok.', type: 'warning');
            }
        } else {
            // Jika produk belum ada di keranjang, buat item baru
            $this->cart[] = [
                'variant_id' => $variantId,
                'variant_type' => $variantType,
                'name' => $itemData['name'],
                'ram' => $itemData['ram'],
                'storage' => $itemData['storage'],
                'color' => $itemData['color'],
                'price' => $itemData['price'],
                'discount_amount' => 0,
                'qty' => 1,
                'serial_numbers' => [$sn],
                'sku' => $itemData['sku'],
                'has_sn' => $itemData['has_sn'],
                'is_second' => $itemData['isSecond'],
            ];

            $this->dispatch('toast', title: 'Sukses', message: "Berhasil menambahkan {$itemData['name']} ke keranjang.", type: 'success');
        }

        $this->syncSinglePaymentAmount();
        
        $this->showScannedItemModal = false;
        $this->scannedItemConfirm = null;
    }

    public function cancelScannedItem()
    {
        $this->showScannedItemModal = false;
        $this->scannedItemConfirm = null;
    }

    /**
     * Helper untuk mencegah scan SN yang sama berkali-kali
     */
    private function isSnAlreadyInCart($sn)
    {
        foreach ($this->cart as $item) {
            if (isset($item['serial_numbers']) && in_array($sn, $item['serial_numbers'])) {
                return true;
            }
        }
        return false;
    }

    public function loadHistory()
    {
        $userWarehouseName = Auth::user()->warehouse->name ?? null;

        $query = Order::with(['items', 'user', 'paymentMethod', 'handledBy'])
            ->where('order_channel', 'POS')
            ->where('shipping_address_snapshot->store', $userWarehouseName)
            ->whereNotIn('order_status', ['DRAFT', 'DRAFT_LOADED', 'CANCELLED', 'RETURNED']);

        if (!empty($this->searchHistory)) {
            $query->where(function ($q) {
                $q->where('order_number', 'like', '%' . $this->searchHistory . '%')
                    ->orWhere('accurate_invoice_no', 'like', '%' . $this->searchHistory . '%')
                    ->orWhereHas('user', function ($q2) {
                        $q2->where('name', 'like', '%' . $this->searchHistory . '%')
                            ->orWhereHas('profile', function ($q3) {
                                $q3->where('phone_number', 'like', '%' . $this->searchHistory . '%');
                            });
                    });
            });
        }

        if (!empty($this->searchHistoryDate)) {
            $query->whereDate('created_at', $this->searchHistoryDate);
        }

        $this->historyOrders = $query->latest()->take(50)->get();
    }

    public function updatedSearchHistory()
    {
        $this->loadHistory();
    }

    public function updatedSearchHistoryDate()
    {
        $this->loadHistory();
    }

    // Method untuk membuka modal dan memuat data transaksi POS terbaru
    public function openHistory()
    {
        $this->searchHistory = '';
        $this->searchHistoryDate = '';
        $this->loadHistory();
        $this->showHistoryModal = true;
    }

    // ─── Cart Actions ──────────────────────────────────────────

    public function openVariantPicker($productId, $isSecond = false)
    {
        $warehouseId = Auth::user()->warehouse_id;

        if ($isSecond) {
            $product = SecondProduct::with([
                'variants' => function ($q) use ($warehouseId) {
                    $q->with(['warehouseStocks' => function ($q2) use ($warehouseId) {
                        $q2->where('warehouse_id', $warehouseId);
                    }]);
                },
                'brand'
            ])->find($productId);

            $this->variantModalVariants = $product->variants->map(fn($v) => [
                'id' => $v->id,
                // PERBAIKAN DI SINI
                'label' => trim(($v->ram ? $v->ram . ' / ' : '') . $v->storage . ' ' . $v->color),
                'condition' => $v->condition ?? '',
                'price' => $v->price,
                'stock' => $v->warehouseStocks->first()?->stock ?? 0,
                'sku' => $v->sku ?? '',
            ])->toArray();
        } else {
            $product = Product::with([
                'variants' => function ($q) use ($warehouseId) {
                    $q->with(['warehouseStocks' => function ($q2) use ($warehouseId) {
                        $q2->where('warehouse_id', $warehouseId);
                    }]);
                },
                'brand'
            ])->find($productId);

            $this->variantModalVariants = $product->variants->map(fn($v) => [
                'id' => $v->id,
                // PERBAIKAN DI SINI
                'label' => trim(($v->ram ? $v->ram . ' / ' : '') . $v->storage . ' ' . $v->color),
                'condition' => '',
                'price' => $v->price,
                'stock' => $v->warehouseStocks->first()?->stock ?? 0,
                'sku' => $v->sku ?? '',
            ])->toArray();
        }
        $this->variantModalProduct = $product;
        $this->variantModalIsSecond = $isSecond;
        $this->showVariantModal = true;
    }

    public function addVariantToCart($variantId)
    {
        $isSecond = $this->variantModalIsSecond;
        $product = $this->variantModalProduct;
        $warehouseId = Auth::user()->warehouse_id;

        if ($isSecond) {
            $variant = SecondProductVariant::with(['warehouseStocks' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }])->find($variantId);
            $variantType = SecondProductVariant::class;
        } else {
            $variant = ProductVariant::with(['warehouseStocks' => function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            }])->find($variantId);
            $variantType = ProductVariant::class;
        }

        $stock = $variant ? ($variant->warehouseStocks->first()?->stock ?? 0) : 0;

        if (!$variant || $stock <= 0) {
            $this->dispatch('toast', title: 'Stok Habis', message: 'Varian ini tidak tersedia.', type: 'warning');
            return;
        }

        // Check if already in cart
        $existingIndex = collect($this->cart)->search(
            fn($item) =>
            $item['variant_id'] == $variantId && $item['variant_type'] == $variantType
        );

        if ($existingIndex !== false) {
            $currentQty = $this->cart[$existingIndex]['qty'];
            if ($currentQty < $stock) {
                $this->cart[$existingIndex]['qty']++;

                // PERBAIKAN: Langsung push slot kosong ke array serial_numbers tanpa mengecek legacy
                if (!isset($this->cart[$existingIndex]['serial_numbers'])) {
                    $this->cart[$existingIndex]['serial_numbers'] = [];
                }
                $this->cart[$existingIndex]['serial_numbers'][] = '';
            } else {
                $this->dispatch('toast', title: 'Stok Tidak Cukup', message: 'Sudah mencapai batas stok.', type: 'warning');
            }
        } else {
            $this->cart[] = [
                'variant_id' => $variant->id,
                'variant_type' => $variantType,
                'name' => $product->name,
                'ram' => $variant->ram ?? '-',
                'storage' => $variant->storage ?? '-',
                'color' => $variant->color ?? '-',
                'price' => (int) $variant->price,
                'discount_amount' => 0,
                'qty' => 1,
                'serial_numbers' => [''], // array of SNs based on qty
                'sku' => $variant->sku ?? '',
                'has_sn' => (bool) $variant->has_sn,
                'is_second' => $isSecond,
            ];
        }
        $this->showVariantModal = false;
        $this->variantModalProduct = null;
        $this->variantModalVariants = [];
        $this->syncSinglePaymentAmount();
    }

    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart); // re-index
        $this->syncSinglePaymentAmount();
    }

    public function incrementCartItem($index)
    {
        if (isset($this->cart[$index])) {
            // 1. Naikkan jumlah kuantitas barang
            $this->cart[$index]['qty']++;

            // JANGAN lakukan push string kosong ('') lagi di sini.
            // Biarkan array serial_numbers tetap apa adanya sampai user melakukan scan.

            $this->syncSinglePaymentAmount();
        }
    }

    public function decrementCartItem($index)
    {
        if (isset($this->cart[$index]) && $this->cart[$index]['qty'] > 1) {
            // 1. Turunkan jumlah kuantitas barang
            $this->cart[$index]['qty']--;

            // 2. Jika jumlah array SN melebihi qty yang baru,
            // kita hapus elemen/slot paling terakhir agar sinkron.
            if (isset($this->cart[$index]['serial_numbers'])) {
                while (count($this->cart[$index]['serial_numbers']) > $this->cart[$index]['qty']) {
                    array_pop($this->cart[$index]['serial_numbers']);
                }

                // Catatan: Sinkronisasi legacy di sini sudah dihapus!
            }

            $this->syncSinglePaymentAmount();
        }
    }


    public function updateSerialNumber($index, $snIndex, $value)
    {
        $value = trim($value);

        if (isset($this->cart[$index]) && !empty($value)) {

            $expectedSku = $this->cart[$index]['sku'] ?? null;

            if (empty($expectedSku)) {
                $this->dispatch('toast', title: 'Error Data', message: 'SKU untuk produk ini tidak ditemukan di keranjang.', type: 'error');
                $this->js("document.getElementById('sn_input_{$index}_{$snIndex}').value = '';");
                return;
            }

            // =================================================================
            // PROSES VALIDASI SN KE ACCURATE ONLINE
            // =================================================================
            $accurateService = app(\App\Services\AccurateService::class);
            $dbSource = $this->databaseSource ?? 'syihab';

            // Menampung string status ('valid', 'not_found', 'mismatch', 'error')
            $status = $accurateService->checkSerialNumberExistance($value, $expectedSku, $dbSource);

            if ($status !== 'valid') {
                $title = 'Gagal Validasi';
                $message = 'Terjadi kesalahan saat memvalidasi SN.';

                // Pilah pesan error sesuai kondisi riil dari Accurate
                if ($status === 'not_found') {
                    $title = 'SN Tidak Ditemukan';
                    $message = "Serial Number '{$value}' tidak terdaftar di Accurate ({$dbSource}).";
                } elseif ($status === 'mismatch') {
                    $title = 'SN Tidak Sesuai';
                    $message = "SN '{$value}' ada di Accurate, TAPI milik produk/barang lain.";
                } elseif ($status === 'invalid_type') {
                    // KONDISI BARU: Menangkap input yang bukan Serial Number
                    $title = 'Input Salah';
                    $message = "Kode '{$value}' terdeteksi sebagai Barcode/SKU, harap masukkan Serial Number.";
                } elseif ($status === 'error') {
                    $title = 'Gangguan Sistem';
                    $message = "Gagal menghubungi Accurate. Silakan coba beberapa saat lagi.";
                }

                // Kirim toast spesifik sesuai error-nya
                $this->dispatch(
                    'toast',
                    title: $title,
                    message: $message,
                    type: 'error',
                    duration: 4000
                );

                // Kosongkan input text pencarian di browser
                $this->js("document.getElementById('sn_input_{$index}_{$snIndex}').value = '';");

                return; // Gagalkan pengisian SN ke cart
            }
            // =================================================================

            // =================================================================
            // TAMBAHAN: CEK KESESUAIAN WAREHOUSE DI TABEL product_serial_numbers
            // =================================================================
            $warehouseId = \Illuminate\Support\Facades\Auth::user()->warehouse_id;

            // Cari record SN di database lokal
            $localSnRecord = \Illuminate\Support\Facades\DB::table('product_serial_numbers')
                ->where('serial_number', $value) // sesuaikan kolom jika namanya 'sn'
                ->first();

            if (!$localSnRecord) {
                $this->dispatch('toast', title: 'Error', message: "Serial Number '{$value}' tidak ditemukan di database lokal.", type: 'error');
                $this->js("document.getElementById('sn_input_{$index}_{$snIndex}').value = '';");
                return;
            }

            // Cek jika warehouse tidak cocok
            if ($localSnRecord->warehouse_id != $warehouseId) {
                $actualWarehouseName = \Illuminate\Support\Facades\DB::table('warehouses')
                    ->where('id', $localSnRecord->warehouse_id)
                    ->value('name'); // sesuaikan kolom nama gudang Anda

                $warehouseTarget = $actualWarehouseName ?? 'Gudang Lain';

                $this->dispatch(
                    'toast',
                    title: 'Error',
                    message: "Serial Number '{$value}' ada di gudang {$warehouseTarget}. Silahkan lakukan pemindahan barang di accurate.",
                    type: 'error'
                );

                // Kosongkan kembali input di browser jika salah gudang
                $this->js("document.getElementById('sn_input_{$index}_{$snIndex}').value = '';");
                return;
            }
            // =================================================================

            // 2. Pastikan array serial_numbers sudah ada (buat jaga-jaga saja)
            if (!isset($this->cart[$index]['serial_numbers'])) {
                $this->cart[$index]['serial_numbers'] = [];
            }

            // 3. Langsung masukkan nilai SN baru ke index yang dituju
            $this->cart[$index]['serial_numbers'][$snIndex] = $value;

            // Catatan: Baris kode step ke-4 (Legacy) sudah dihapus total!
        }
    }
    // ─── Stock Modal Properties ────────────────────────────────
    public $showStockModal = false;
    public $stockModalData = [];
    public $stockModalItemTitle = '';

    public function checkStock($index)
    {
        // 1. Pastikan item ada di keranjang
        if (!isset($this->cart[$index])) {
            $this->dispatch('toast', title: 'Error', message: 'Item tidak ditemukan di keranjang.', type: 'error');
            return;
        }

        $item = $this->cart[$index];
        $userWarehouseId = Auth::user()->warehouse_id;

        // 2. Ambil data varian beserta SEMUA stok gudang. 
        // Pastikan relasi 'warehouse' ada di model WarehouseStock kamu.
        if (isset($item['is_second']) && $item['is_second']) {
            $variant = SecondProductVariant::with(['warehouseStocks.warehouse'])->find($item['variant_id']);
        } else {
            $variant = ProductVariant::with(['warehouseStocks.warehouse'])->find($item['variant_id']);
        }

        // 3. Mapping data untuk ditampilkan di modal
        if ($variant) {
            $this->stockModalItemTitle = "{$item['name']} ({$item['color']} - {$item['storage']})";

            $this->stockModalData = $variant->warehouseStocks->map(function ($ws) use ($userWarehouseId) {
                return [
                    // Sesuaikan 'name' jika field nama gudang di tabelmu beda (misal: nama_gudang)
                    'warehouse_name' => $ws->warehouse->name ?? 'Gudang Tidak Diketahui',
                    'stock' => $ws->stock,
                    'is_current_user_warehouse' => $ws->warehouse_id === $userWarehouseId,
                ];
            })->toArray();

            // Tampilkan Modal
            $this->showStockModal = true;
        } else {
            $this->dispatch('toast', title: 'Gagal', message: 'Data varian tidak ditemukan di database.', type: 'error');
        }
    }

    public function closeStockModal()
    {
        $this->showStockModal = false;
        $this->stockModalData = [];
        $this->stockModalItemTitle = '';
    }

    public function removeSerialNumber($index, $snIndex)
    {
        // Fungsi untuk menghapus badge SN saat tombol X diklik
        if (isset($this->cart[$index]['serial_numbers'][$snIndex])) {

            // 1. Hapus SN berdasarkan index-nya
            unset($this->cart[$index]['serial_numbers'][$snIndex]);

            // 2. WAJIB: Reset urutan key array agar kembali berurutan (0, 1, 2...)
            $this->cart[$index]['serial_numbers'] = array_values($this->cart[$index]['serial_numbers']);

            // Catatan: Bagian "Sinkronisasi ulang data legacy backward compatibility" sudah dihapus total!
        }
    }
}
