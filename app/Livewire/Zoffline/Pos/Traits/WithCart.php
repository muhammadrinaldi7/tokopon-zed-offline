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
    public $new_sns = []; // To hold temporary SN inputs per cart item

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

    // ─── Addons SN Modal ───────────────────────────────────────
    public $addonScanModalOpen = false;
    public $selectedAddonForSn = null;
    public $addonSnInput = '';

    public function selectAddon($id)
    {
        $product = \App\Models\ProductAccurate::find($id);
        if (!$product) return;

        if ($product->has_sn) {
            $this->selectedAddonForSn = $product->id;
            $this->addonSnInput = '';
            $this->addonScanModalOpen = true;
            $this->dispatch('focus-addon-sn-input');
        } else {
            // Langsung tambahkan ke keranjang (tanpa SN)
            $warehouseId = \Illuminate\Support\Facades\Auth::user()->warehouse_id;
            $isNonInventory = in_array(strtoupper($product->itemType ?? ''), ['SERVICE', 'NON_INVENTORY']);

            if ($isNonInventory) {
                $stock = 9999;
            } else {
                $warehouseStock = \App\Models\WarehouseStock::where([
                    'variant_id' => $product->id,
                    'variant_type' => \App\Models\ProductAccurate::class,
                    'warehouse_id' => $warehouseId
                ])->first();

                $stock = $warehouseStock ? (int) $warehouseStock->stock : 0;

                if ($stock <= 0) {
                    $this->dispatch('toast', title: 'Stok Kosong', message: "Stok untuk produk '{$product->name}' di gudang Anda saat ini habis (0).", type: 'error');
                    return;
                }
            }
            $this->addScannedAccurateToCart($product, null, $stock);
        }
    }

    public function closeAddonModal()
    {
        $this->addonScanModalOpen = false;
        $this->selectedAddonForSn = null;
        $this->addonSnInput = '';
    }

    public function submitAddonSn()
    {
        $sn = trim($this->addonSnInput);
        if (empty($sn)) return;

        $productAccurate = \App\Models\ProductAccurate::find($this->selectedAddonForSn);
        if (!$productAccurate) {
            $this->dispatch('toast', title: 'Error', message: 'Produk tidak ditemukan.', type: 'error');
            return;
        }

        $warehouseId = \Illuminate\Support\Facades\Auth::user()->warehouse_id;
        $buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();
        $buName = Auth::user()->getActiveBusinessUnit()->code;

        $accurateService = app(\App\Services\AccurateService::class);
        $skuFromAccurate = $accurateService->findSkuBySerialNumber($sn, $buName);

        if ($skuFromAccurate === 'error') {
            $this->dispatch('toast', title: 'Warning', message: 'Koneksi ke Accurate bermasalah, mencoba pengecekan lokal.', type: 'warning');
            $this->closeAddonModal();
        } else if ($skuFromAccurate === null) {
            $this->dispatch('toast', title: 'Gagal', message: "SN '{$sn}' tidak ditemukan di Accurate.", type: 'error');
            $this->closeAddonModal();
            return;
        }

        $isConfirmedSn = ($skuFromAccurate && $skuFromAccurate !== 'invalid_type' && $skuFromAccurate !== 'error');

        if ($isConfirmedSn || $skuFromAccurate === 'error') {
            $localSnRecordGlobal = \App\Models\ProductSerialNumber::with('productAccurate')
                ->where('serial_number', $sn)
                ->first();

            if ($localSnRecordGlobal) {
                // Pastikan SN ini BENAR milik produk Addon yang diklik

                if ($localSnRecordGlobal->productAccurate->id != $this->selectedAddonForSn) {
                    $this->dispatch('toast', title: 'Gagal', message: "SN '{$sn}' BUKAN milik produk {$productAccurate->name}.", type: 'error');
                    $this->closeAddonModal();
                    return;
                }

                $productBuId = $localSnRecordGlobal->productAccurate->business_unit_id ?? null;
                if ($productBuId !== null && $productBuId != $buId) {
                    $this->dispatch('toast', title: 'Gagal', message: "SN '{$sn}' terdaftar untuk Business Unit lain.", type: 'error');
                    $this->closeAddonModal();
                    return;
                }

                if ($localSnRecordGlobal->warehouse_id != $warehouseId) {
                    $actualWarehouseName = \App\Models\Warehouse::where('id', $localSnRecordGlobal->warehouse_id)->value('name');
                    $warehouseTarget = $actualWarehouseName ?? 'Gudang Lain';
                    $this->dispatch('toast', title: 'Gagal', message: "SN '{$sn}' ada di {$warehouseTarget}.", type: 'error');
                    $this->closeAddonModal();
                    return;
                }

                if ($localSnRecordGlobal->status === 'Unavailable') {
                    $this->dispatch('toast', title: 'SN Tidak Tersedia', message: "Serial Number '{$sn}' sudah dalam status Draft atau Terjual.", type: 'warning');
                    $this->scanned_sn = '';
                    $this->closeAddonModal();
                    return;
                }

                $this->addScannedAccurateToCart($productAccurate, $sn, 1);
                $this->closeAddonModal();
                return;
            } else if ($isConfirmedSn) {
                $this->dispatch('toast', title: 'Gagal', message: "SN '{$sn}' terdaftar di Accurate, namun belum tersinkronisasi di sistem lokal.", type: 'error');
                $this->closeAddonModal();
                return;
            }
        }

        $this->dispatch('toast', title: 'Gagal', message: "SN '{$sn}' tidak ditemukan atau bukan SN yang valid.", type: 'error');
        $this->closeAddonModal();
    }

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

    public function processScan()
    {
        $sn = trim($this->scanned_sn);

        if (empty($sn)) {
            return;
        }

        $warehouseId = \Illuminate\Support\Facades\Auth::user()->warehouse_id;
        $buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();

        $activeBu = Auth::user()->getActiveBusinessUnit();
        $buName = $activeBu ? $activeBu->code : 'second'; // Default ke syihab jika null
        // 1. Cek dulu ke Accurate Service
        $accurateService = app(\App\Services\AccurateService::class);
        $skuFromAccurate = $accurateService->findSkuBySerialNumber($sn, $buName);
        if ($skuFromAccurate === 'error') {
            $this->dispatch('toast', title: 'Warning', message: 'Koneksi ke Accurate bermasalah, mencoba pengecekan lokal.', type: 'warning');
        } else if ($skuFromAccurate === null) {
            $this->dispatch('toast', title: 'Gagal', message: "Barcode / SN '{$sn}' tidak ditemukan di Accurate.", type: 'error');
            $this->scanned_sn = '';
            return;
        }

        // Cek apakah hasil Accurate terkonfirmasi sebagai Serial Number yang valid
        $isConfirmedSn = ($skuFromAccurate && $skuFromAccurate !== 'invalid_type' && $skuFromAccurate !== 'error');

        // 2. Jika valid SN atau sedang offline (error), coba cari di tabel SN lokal
        if ($isConfirmedSn || $skuFromAccurate === 'error') {
            // Cari data SN secara global tanpa memfilter Business Unit (BU) ID terlebih dahulu
            $localSnRecordGlobal = \App\Models\ProductSerialNumber::with('productAccurate')
                ->where('serial_number', $sn)
                ->first();

            if ($localSnRecordGlobal) {
                // Cek apakah Business Unit (BU) Sesuai
                $productBuId = $localSnRecordGlobal->productAccurate->business_unit_id ?? null;

                if ($productBuId !== null && $productBuId != $buId) {
                    // SN Terdaftar tapi milik Business Unit (Cabang) lain
                    $this->dispatch('toast', title: 'Gagal', message: "Serial Number '{$sn}' terdaftar untuk Business Unit / Cabang lain.", type: 'error');
                    $this->scanned_sn = '';
                    return;
                }

                // BU Sesuai, lanjut Validasi Gudang
                if ($localSnRecordGlobal->warehouse_id != $warehouseId) {
                    $actualWarehouseName = \App\Models\Warehouse::where('id', $localSnRecordGlobal->warehouse_id)->value('name');
                    $warehouseTarget = $actualWarehouseName ?? 'Gudang Lain';
                    $this->dispatch('toast', title: 'Gagal', message: "Serial Number '{$sn}' ada di gudang {$warehouseTarget}. Silahkan lakukan pemindahan barang di accurate.", type: 'error');
                    $this->scanned_sn = '';
                    return;
                }

                // Cek apakah SN statusnya Unavailable (sudah di draft / terjual)
                if ($localSnRecordGlobal->status === 'Unavailable') {
                    $this->dispatch('toast', title: 'SN Tidak Tersedia', message: "Serial Number '{$sn}' sudah dalam status Draft atau Terjual.", type: 'warning');
                    $this->scanned_sn = '';
                    return;
                }

                // SN Valid, BU Cocok, dan Gudang Cocok. Ambil ProductAccurate
                $productAccurate = $localSnRecordGlobal->productAccurate;

                $this->addScannedAccurateToCart($productAccurate, $sn, 1);
                $this->scanned_sn = '';
                return;
            } else if ($isConfirmedSn) {
                // SN valid di Accurate tapi belum tersinkron di tabel lokal sama sekali.
                $this->dispatch('toast', title: 'Gagal', message: "Serial Number '{$sn}' terdaftar di Accurate, namun belum tersinkronisasi di sistem lokal. Pastikan sudah di-receive atau di-transfer ke sistem.", type: 'error');
                $this->scanned_sn = '';
                return;
            }
        }

        // 3. Jika bukan SN (invalid_type) atau error tapi tidak ketemu di lokal, cari sebagai SKU
        $productAccurate = \App\Models\ProductAccurate::where('item_no', $sn)
            ->where(function ($q) use ($buId) {
                $q->where('business_unit_id', $buId)->orWhereNull('business_unit_id');
            })
            ->first();

        if ($productAccurate) {
            // Validasi Wajib SN
            if ($productAccurate->has_sn) {
                $this->dispatch('toast', title: 'Peringatan', message: "Produk '{$sn}' wajib di-scan menggunakan IMEI / Serial Number.", type: 'warning');
                $this->scanned_sn = '';
                return;
            }

            $isNonInventory = in_array(strtoupper($productAccurate->itemType ?? ''), ['SERVICE', 'NON_INVENTORY']);

            if ($isNonInventory) {
                $stock = 9999;
            } else {
                // Ambil stock dari WarehouseStock (gudang aktif)
                $warehouseStock = \App\Models\WarehouseStock::where([
                    'variant_id' => $productAccurate->id,
                    'variant_type' => \App\Models\ProductAccurate::class,
                    'warehouse_id' => $warehouseId
                ])->first();

                $stock = $warehouseStock ? (int) $warehouseStock->stock : 0;

                if ($stock <= 0) {
                    $this->dispatch('toast', title: 'Stok Kosong', message: "Stok untuk produk '{$productAccurate->name}' di gudang Anda saat ini habis (0).", type: 'error');
                    $this->scanned_sn = '';
                    return;
                }
            }

            $this->addScannedAccurateToCart($productAccurate, null, $stock);
            $this->scanned_sn = '';
            return;
        }

        $this->dispatch('toast', title: 'Gagal', message: "Barcode / SN '{$sn}' tidak ditemukan di sistem lokal.", type: 'error');
        $this->scanned_sn = '';
    }

    private function addScannedAccurateToCart($productAccurate, $sn, $maxStock)
    {
        $variantType = \App\Models\ProductAccurate::class;
        $variantId = $productAccurate->id;

        $existingIndex = collect($this->cart)->search(
            fn($item) => $item['variant_id'] == $variantId && $item['variant_type'] == $variantType
        );

        if ($existingIndex !== false) {
            $currentQty = $this->cart[$existingIndex]['qty'];

            if ($currentQty < $maxStock || $sn !== null) { // if SN is not null, maxStock isn't strictly checked here, we rely on user scanning individual SNs
                if ($sn !== null) {
                    if (in_array($sn, $this->cart[$existingIndex]['serial_numbers'])) {
                        $this->dispatch('toast', title: 'Peringatan', message: 'SN sudah ada di keranjang.', type: 'warning');
                        return;
                    }
                    $this->cart[$existingIndex]['serial_numbers'][] = $sn;
                }

                $this->cart[$existingIndex]['qty']++;
                $this->dispatch('toast', title: 'Sukses', message: 'Kuantitas ditambah.', type: 'success');
            } else {
                $this->dispatch('toast', title: 'Stok Tidak Cukup', message: 'Sudah mencapai batas stok.', type: 'warning');
            }
        } else {
            $this->cart[] = [
                'variant_id' => $variantId,
                'variant_type' => $variantType,
                'name' => $productAccurate->name,
                'ram' => '-',
                'storage' => '-',
                'color' => '-',
                'price' => (int) $productAccurate->base_price,
                'discount_amount' => 0,
                'qty' => 1,
                'serial_numbers' => $sn ? [$sn] : [],
                'sku' => $productAccurate->item_no,
                'has_sn' => (bool) $productAccurate->has_sn,
                // 'is_second' => false,
                'database_source' => $productAccurate->database_source,
                'brand_id' => null, // Accurate doesn't map brand directly this way
                // 'condition' => '',
            ];

            $this->dispatch('toast', title: 'Sukses', message: "Berhasil menambahkan {$productAccurate->name} ke keranjang.", type: 'success');
        }

        if (!empty($this->selectedPromos)) {
            $this->applyPromosToCart();
        }
        $this->syncSinglePaymentAmount();
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

    // public function openVariantPicker($productId, $isSecond = false)
    // {
    //     $warehouseId = Auth::user()->warehouse_id;

    //     if ($isSecond) {
    //         $product = SecondProduct::with([
    //             'variants' => function ($q) use ($warehouseId) {
    //                 $q->with(['warehouseStocks' => function ($q2) use ($warehouseId) {
    //                     $q2->where('warehouse_id', $warehouseId);
    //                 }]);
    //             },
    //             'brand'
    //         ])->find($productId);

    //         $this->variantModalVariants = $product->variants->map(fn($v) => [
    //             'id' => $v->id,
    //             // PERBAIKAN DI SINI
    //             'label' => trim(($v->ram ? $v->ram . ' / ' : '') . $v->storage . ' ' . $v->color),
    //             'condition' => $v->condition ?? '',
    //             'price' => $v->price,
    //             'stock' => $v->warehouseStocks->first()?->stock ?? 0,
    //             'sku' => $v->sku ?? '',
    //         ])->toArray();
    //     } else {
    //         $product = Product::with([
    //             'variants' => function ($q) use ($warehouseId) {
    //                 $q->with(['warehouseStocks' => function ($q2) use ($warehouseId) {
    //                     $q2->where('warehouse_id', $warehouseId);
    //                 }]);
    //             },
    //             'brand'
    //         ])->find($productId);

    //         $this->variantModalVariants = $product->variants->map(fn($v) => [
    //             'id' => $v->id,
    //             // PERBAIKAN DI SINI
    //             'label' => trim(($v->ram ? $v->ram . ' / ' : '') . $v->storage . ' ' . $v->color),
    //             'condition' => '',
    //             'price' => $v->price,
    //             'stock' => $v->warehouseStocks->first()?->stock ?? 0,
    //             'sku' => $v->sku ?? '',
    //         ])->toArray();
    //     }
    //     $this->variantModalProduct = $product;
    //     $this->variantModalIsSecond = $isSecond;
    //     $this->showVariantModal = true;
    // }

    // public function addVariantToCart($variantId)
    // {
    //     $isSecond = $this->variantModalIsSecond;
    //     $product = $this->variantModalProduct;
    //     $warehouseId = Auth::user()->warehouse_id;

    //     if ($isSecond) {
    //         $variant = SecondProductVariant::with(['warehouseStocks' => function ($q) use ($warehouseId) {
    //             $q->where('warehouse_id', $warehouseId);
    //         }])->find($variantId);
    //         $variantType = SecondProductVariant::class;
    //     } else {
    //         $variant = ProductVariant::with(['warehouseStocks' => function ($q) use ($warehouseId) {
    //             $q->where('warehouse_id', $warehouseId);
    //         }])->find($variantId);
    //         $variantType = ProductVariant::class;
    //     }

    //     $stock = $variant ? ($variant->warehouseStocks->first()?->stock ?? 0) : 0;

    //     if (!$variant || $stock <= 0) {
    //         $this->dispatch('toast', title: 'Stok Habis', message: 'Varian ini tidak tersedia.', type: 'warning');
    //         return;
    //     }

    //     $existingIndex = collect($this->cart)->search(
    //         fn($item) => $item['variant_id'] == $variant->id && $item['variant_type'] == $variantType
    //     );

    //     if ($existingIndex !== false) {
    //         $currentQty = $this->cart[$existingIndex]['qty'];
    //         if ($currentQty < $stock) {
    //             $this->cart[$existingIndex]['qty']++;

    //             if (!isset($this->cart[$existingIndex]['serial_numbers'])) {
    //                 $this->cart[$existingIndex]['serial_numbers'] = [];
    //             }
    //         } else {
    //             $this->dispatch('toast', title: 'Stok Tidak Cukup', message: 'Sudah mencapai batas stok.', type: 'warning');
    //         }
    //     } else {
    //         $this->cart[] = [
    //             'variant_id' => $variant->id,
    //             'variant_type' => $variantType,
    //             'name' => $product->name,
    //             'ram' => $variant->ram ?? '-',
    //             'storage' => $variant->storage ?? '-',
    //             'color' => $variant->color ?? '-',
    //             'price' => (int) $variant->price,
    //             'discount_amount' => 0,
    //             'qty' => 1,
    //             'serial_numbers' => [], // empty array initially
    //             'sku' => $variant->sku ?? '',
    //             'has_sn' => (bool) $variant->has_sn,
    //             'is_second' => $isSecond,
    //             'database_source' => $isSecond ? 'second' : 'syihab',
    //             'brand_id' => $isSecond ? ($product->brand_id ?? null) : ($product->brand_id ?? null),
    //             'condition' => $variant->condition ?? $variant->condition_desc ?? '',
    //         ];
    //     }
    //     $this->showVariantModal = false;
    //     $this->variantModalProduct = null;
    //     $this->variantModalVariants = [];
    //     $this->syncSinglePaymentAmount();
    // }

    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart); // re-index
        
        if (!empty($this->selectedPromos)) {
            $this->applyPromosToCart();
        }
        $this->syncSinglePaymentAmount();
    }

    public function validateCartItemQty($index, $newQty)
    {
        if (!isset($this->cart[$index])) return;

        $newQty = (int) $newQty;
        if ($newQty < 1) {
            $newQty = 1;
        }

        $variantType = $this->cart[$index]['variant_type'];
        $variantId = $this->cart[$index]['variant_id'];
        $warehouseId = \Illuminate\Support\Facades\Auth::user()->warehouse_id;

        $warehouseStock = \App\Models\WarehouseStock::where([
            'variant_id' => $variantId,
            'variant_type' => $variantType,
            'warehouse_id' => $warehouseId
        ])->first();

        $maxStock = $warehouseStock ? (int) $warehouseStock->stock : 0;

        // Hitung total qty dari item yang sama di baris lain
        $otherRowsQty = 0;
        foreach ($this->cart as $i => $item) {
            if ($i != $index && $item['variant_id'] == $variantId && $item['variant_type'] == $variantType) {
                $otherRowsQty += $item['qty'];
            }
        }

        if (($newQty + $otherRowsQty) > $maxStock) {
            $allowedQty = max(1, $maxStock - $otherRowsQty);
            $this->dispatch('toast', title: 'Gagal', message: "Stok Tidak Cukup. Sisa stok di gudang Anda adalah {$allowedQty}.", type: 'error');
            $newQty = $allowedQty;
        }

        $this->cart[$index]['qty'] = $newQty;

        // Jika jumlah array SN melebihi qty yang baru,
        // hapus elemen/slot paling terakhir agar sinkron.
        if (isset($this->cart[$index]['serial_numbers'])) {
            while (count($this->cart[$index]['serial_numbers']) > $this->cart[$index]['qty']) {
                array_pop($this->cart[$index]['serial_numbers']);
            }
        }
        
        if (!empty($this->selectedPromos)) {
            $this->applyPromosToCart();
        }
    }

    public function incrementCartItem($index)
    {
        if (isset($this->cart[$index])) {
            $this->validateCartItemQty($index, $this->cart[$index]['qty'] + 1);
            $this->syncSinglePaymentAmount();
        }
    }

    public function decrementCartItem($index)
    {
        if (isset($this->cart[$index]) && $this->cart[$index]['qty'] > 1) {
            $this->validateCartItemQty($index, $this->cart[$index]['qty'] - 1);
            $this->syncSinglePaymentAmount();
        }
    }


    public function addSerialNumber($index)
    {
        $value = trim($this->new_sns[$index] ?? '');

        if (isset($this->cart[$index]) && !empty($value)) {

            $currentSns = $this->cart[$index]['serial_numbers'] ?? [];
            if (count($currentSns) >= $this->cart[$index]['qty']) {
                $this->dispatch('toast', title: 'Peringatan', message: 'Jumlah SN sudah memenuhi Kuantitas (Qty).', type: 'warning');
                $this->new_sns[$index] = '';
                return;
            }

            if (in_array($value, $currentSns)) {
                $this->dispatch('toast', title: 'Peringatan', message: 'Serial Number ini sudah ditambahkan pada produk ini.', type: 'warning');
                $this->new_sns[$index] = '';
                return;
            }

            $expectedSku = $this->cart[$index]['sku'] ?? null;

            if (empty($expectedSku)) {
                $this->dispatch('toast', title: 'Error Data', message: 'SKU untuk produk ini tidak ditemukan di keranjang.', type: 'error');
                $this->new_sns[$index] = '';
                return;
            }

            $warehouseId = \Illuminate\Support\Facades\Auth::user()->warehouse_id;
            $buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();
            $activeBu = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnit();
            $buName = $activeBu ? $activeBu->code : 'second';

            // 1. Cek dulu ke Accurate Service
            $accurateService = app(\App\Services\AccurateService::class);
            $skuFromAccurate = $accurateService->findSkuBySerialNumber($value, $buName);

            if ($skuFromAccurate === 'error') {
                $this->dispatch('toast', title: 'Warning', message: 'Koneksi ke Accurate bermasalah, mencoba pengecekan lokal.', type: 'warning');
            } else if ($skuFromAccurate === null) {
                $this->dispatch('toast', title: 'Gagal', message: "Barcode / SN '{$value}' tidak ditemukan di Accurate.", type: 'error');
                $this->new_sns[$index] = '';
                return;
            }

            $isConfirmedSn = ($skuFromAccurate && $skuFromAccurate !== 'invalid_type' && $skuFromAccurate !== 'error');

            if ($isConfirmedSn || $skuFromAccurate === 'error') {
                $localSnRecordGlobal = \App\Models\ProductSerialNumber::with('productAccurate')
                    ->where('serial_number', $value)
                    ->first();

                if ($localSnRecordGlobal) {
                    // Cek SKU
                    if ($localSnRecordGlobal->item_no !== $expectedSku) {
                        $this->dispatch('toast', title: 'SN Tidak Sesuai', message: "SN '{$value}' ada, TAPI milik produk/barang lain.", type: 'error');
                        $this->new_sns[$index] = '';
                        return;
                    }

                    // Cek Business Unit
                    $productBuId = $localSnRecordGlobal->productAccurate->business_unit_id ?? null;
                    if ($productBuId !== null && $productBuId != $buId) {
                        $this->dispatch('toast', title: 'Gagal', message: "Serial Number '{$value}' terdaftar untuk Business Unit / Cabang lain.", type: 'error');
                        $this->new_sns[$index] = '';
                        return;
                    }

                    // Cek Gudang
                    if ($localSnRecordGlobal->warehouse_id != $warehouseId) {
                        $actualWarehouseName = \App\Models\Warehouse::where('id', $localSnRecordGlobal->warehouse_id)->value('name');
                        $warehouseTarget = $actualWarehouseName ?? 'Gudang Lain';
                        $this->dispatch('toast', title: 'Gagal', message: "Serial Number '{$value}' ada di gudang {$warehouseTarget}. Silahkan lakukan pemindahan barang di accurate.", type: 'error');
                        $this->new_sns[$index] = '';
                        return;
                    }

                    // Cek Status
                    if ($localSnRecordGlobal->status === 'Unavailable') {
                        $this->dispatch('toast', title: 'SN Tidak Tersedia', message: "Serial Number '{$value}' sudah dalam status Draft atau Terjual.", type: 'warning');
                        $this->new_sns[$index] = '';
                        return;
                    }

                    // Lolos semua validasi, tambahkan SN ke array
                    if (!isset($this->cart[$index]['serial_numbers'])) {
                        $this->cart[$index]['serial_numbers'] = [];
                    }
                    $this->cart[$index]['serial_numbers'][] = $value;
                    $this->new_sns[$index] = '';
                    return;
                } else if ($isConfirmedSn) {
                    $this->dispatch('toast', title: 'Gagal', message: "Serial Number '{$value}' terdaftar di Accurate, namun belum tersinkronisasi di sistem lokal. Pastikan sudah di-receive atau di-transfer ke sistem.", type: 'error');
                    $this->new_sns[$index] = '';
                    return;
                }
            }

            $this->dispatch('toast', title: 'Gagal', message: "Barcode / SN '{$value}' tidak ditemukan di sistem lokal atau tidak valid.", type: 'error');
            $this->new_sns[$index] = '';
        }
    }
    // ─── Stock Modal Properties ────────────────────────────────
    public $showStockModal = false;
    public $stockModalData = [];
    public $stockModalItemTitle = '';

    // public function checkStock($index)
    // {
    //     // 1. Pastikan item ada di keranjang
    //     if (!isset($this->cart[$index])) {
    //         $this->dispatch('toast', title: 'Error', message: 'Item tidak ditemukan di keranjang.', type: 'error');
    //         return;
    //     }

    //     $item = $this->cart[$index];
    //     $userWarehouseId = Auth::user()->warehouse_id;

    //     // 2. Ambil data varian beserta SEMUA stok gudang. 
    //     // Pastikan relasi 'warehouse' ada di model WarehouseStock kamu.
    //     if (isset($item['is_second']) && $item['is_second']) {
    //         $variant = SecondProductVariant::with(['warehouseStocks.warehouse'])->find($item['variant_id']);
    //     } else {
    //         $variant = ProductVariant::with(['warehouseStocks.warehouse'])->find($item['variant_id']);
    //     }

    //     // 3. Mapping data untuk ditampilkan di modal
    //     if ($variant) {
    //         $this->stockModalItemTitle = "{$item['name']} ({$item['color']} - {$item['storage']})";

    //         $this->stockModalData = $variant->warehouseStocks->map(function ($ws) use ($userWarehouseId) {
    //             return [
    //                 // Sesuaikan 'name' jika field nama gudang di tabelmu beda (misal: nama_gudang)
    //                 'warehouse_name' => $ws->warehouse->name ?? 'Gudang Tidak Diketahui',
    //                 'stock' => $ws->stock,
    //                 'is_current_user_warehouse' => $ws->warehouse_id === $userWarehouseId,
    //             ];
    //         })->toArray();

    //         // Tampilkan Modal
    //         $this->showStockModal = true;
    //     } else {
    //         $this->dispatch('toast', title: 'Gagal', message: 'Data varian tidak ditemukan di database.', type: 'error');
    //     }
    // }

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

    // ─── Manual Discount Presets ───────────────────────────────
    public function getActiveManualDiscountPresets()
    {
        return \App\Models\ManualDiscountPreset::where('is_active', true)->get();
    }

    public $showManualDiscountModal = false;
    public $manualDiscountCartIndex = null;

    public function openManualDiscountModal($index)
    {
        $this->manualDiscountCartIndex = $index;
        $this->showManualDiscountModal = true;
    }

    public function closeManualDiscountModal()
    {
        $this->showManualDiscountModal = false;
        $this->manualDiscountCartIndex = null;
    }

    public function toggleManualDiscount($cartIndex, $amount)
    {
        if (isset($this->cart[$cartIndex])) {
            // Jika amount yang sama diklik lagi, batalkan (toggle off)
            if (($this->cart[$cartIndex]['discount_amount'] ?? 0) == $amount) {
                $this->cart[$cartIndex]['discount_amount'] = 0;
            } else {
                // Set diskon (hanya salah satu yang terpilih)
                $this->cart[$cartIndex]['discount_amount'] = (int) $amount;
            }
            $this->syncSinglePaymentAmount();
        }
        $this->closeManualDiscountModal();
    }

    // ─── Edit Price Modal ──────────────────────────────────────
    public $showEditPriceModal = false;
    public $editPriceCartIndex = null;
    public $editPriceValue = 0;

    public function openEditPriceModal($index)
    {
        if (isset($this->cart[$index])) {
            $this->editPriceCartIndex = $index;
            $this->editPriceValue = $this->cart[$index]['price'] ?? 0;
            $this->showEditPriceModal = true;
        }
    }

    public function closeEditPriceModal()
    {
        $this->showEditPriceModal = false;
        $this->editPriceCartIndex = null;
        $this->editPriceValue = 0;
    }

    public function saveEditedPrice()
    {
        if ($this->editPriceCartIndex !== null && isset($this->cart[$this->editPriceCartIndex])) {
            $item = $this->cart[$this->editPriceCartIndex];

            // Dapatkan model aslinya untuk mengecek harga minimum
            $variantClass = $item['variant_type'];
            $variant = $variantClass::find($item['variant_id']);
            $basePrice = $variant ? (int) ($variant->base_price ?? $variant->price ?? 0) : 0;

            $newPrice = (int) $this->editPriceValue;

            if ($newPrice < $basePrice) {
                $this->dispatch('toast', title: 'Gagal', message: 'Harga tidak boleh kurang dari harga dasar (Rp ' . number_format($basePrice, 0, ',', '.') . ').', type: 'error');
                $this->closeEditPriceModal();
                return;
            }

            $this->cart[$this->editPriceCartIndex]['price'] = $newPrice;
            $this->syncSinglePaymentAmount();
            $this->dispatch('toast', title: 'Berhasil', message: 'Harga satuan berhasil diubah.', type: 'success');
            $this->closeEditPriceModal();
        }
    }
}
