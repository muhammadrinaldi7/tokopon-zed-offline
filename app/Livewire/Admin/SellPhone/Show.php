<?php

namespace App\Livewire\Admin\SellPhone;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SellPhone;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class Show extends Component
{
    public SellPhone $sellPhone;

    // QC Status
    public $qcPassed = false;

    // Appraisal Form
    public $appraisedValue = 0;

    // Convert to Second Product
    public $convertModal = false;
    public $sellPrice = 0;
    public $secondCondition = 'Bekas';
    public $existingProductId = null;
    public $dataParamPurchaseInvoice = [];
    // Revision
    public $isRevising = false;
    public $revisedAppraisedValue = 0;

    public function mount(SellPhone $sellPhone)
    {
        $this->sellPhone = $sellPhone->load(['user.bankAccounts', 'buybackDevice.tier']);
        $this->appraisedValue = $this->sellPhone->appraised_value ?? 0;
        $this->qcPassed = $this->sellPhone->hasPassedQc();
    }

    #[On('qc-inspection-saved')]
    public function handleQcSaved($verdict)
    {
        $this->qcPassed = ($verdict === 'pass');
    }

    #[Computed]
    public function phoneData()
    {
        // loadMissing akan me-load relasi hanya saat data ini dipanggil di Blade
        return $this->sellPhone->loadMissing(['buybackDevice.secondProductVariant', 'user']);
    }
    public function submitAppraisal()
    {
        $this->validate([
            'appraisedValue' => 'required|numeric|min:1000'
        ]);

        $this->sellPhone->update([
            'appraised_value' => $this->appraisedValue,
            'status' => 'OFFERED',
        ]);

        $this->dispatch('show-toast', type: 'success', message: 'Penawaran berhasil disimpan dan dikirim ke pengguna.');
    }

    public function submitRevision()
    {
        $this->validate([
            'revisedAppraisedValue' => 'required|numeric|min:1000'
        ]);

        $this->sellPhone->update([
            'appraised_value' => $this->revisedAppraisedValue,
            'status' => 'REVISED_OFFER',
        ]);

        $this->isRevising = false;
        $this->dispatch('show-toast', type: 'success', message: 'Revisi penawaran berhasil dikirim ke pengguna.');
    }

    public function markAsPaid()
    {
        if (!$this->qcPassed) {
            $this->dispatch('toast', ['type' => 'error', 'title' => 'Gagal', 'message' => 'Lakukan Inspeksi QC terlebih dahulu dan pastikan statusnya LAYAK BELI (PASS).']);
            return;
        }

        $billNumber = 'TPD-' . date('dmY') . str_pad($this->sellPhone->id, 4, '0', STR_PAD_LEFT);

        if ($this->sellPhone->status === 'COMPLETED' || $this->sellPhone->status === 'CANCELLED') return;
        if ($this->sellPhone->status === 'INSPECTING') {
            $this->sellPhone->update(['status' => 'PAYING']);
            $this->dispatch('toast', ['type' => 'success', 'title' => 'Inspected', 'message' => 'Status penjualan HP ditandai sebagai Checked.']);
        } else if ($this->sellPhone->status === 'PAYING') {
            $phoneData = $this->phoneData;
            $flUser = $this->sellPhone->handledBy;
            $accurateBranchName = $flUser && $flUser->branch ? $flUser->branch->name : 'Banjarbaru';
            $accurateWarehouseName = $flUser && $flUser->warehouse ? $flUser->warehouse->name : 'Head Office';

            // 1. Susun Array untuk detailItem terlebih dahulu agar lebih rapi
            $detailItem = [
                [
                    // Pastikan memanggil kolom yang sesuai dari tabel devices/sell_phones Anda
                    'itemNo' => $phoneData->buybackDevice->secondProductVariant->sku ?? 'TES-001',
                    'warehouseName' => $accurateWarehouseName,
                    'unitPrice' => (int) $this->sellPhone->appraised_value, // Harga yang disepakati
                    'quantity' => 1,

                    // Array di dalam array untuk serial number
                    'detailSerialNumber' => [
                        [
                            'serialNumberNo' => $this->sellPhone->imei ?? 'NO-IMEI-' . str_pad($this->sellPhone->id, 4, '0', STR_PAD_LEFT), // Kolom IMEI/SN HP
                            'quantity' => 1
                        ]
                    ]
                ]
            ];

            // 2. Masukkan ke dalam parameter utama Purchase Invoice Accurate
            $vendorNoAwal = $phoneData->user->getAccurateVendorNo('second') ?? 'V-CASH';
            $this->dataParamPurchaseInvoice = [
                'billNumber' => $billNumber,
                'vendorNo' => str_replace('"', '', $vendorNoAwal),
                'branchName' => $accurateBranchName,
                // Field tambahan yang Anda tulis sebelumnya (opsional/dibutuhkan Accurate)
                // 'name' => $phoneData->user->profile->full_name ?? '',
                'transDate' => date('d/m/Y'),
                'currencyCode' => 'IDR',
                'description' => 'Pembelian HP - NIK:' . ($phoneData->user->identity ?? '-'),
                // Sisipkan array detailItem yang sudah dibentuk di atas
                'detailItem' => $detailItem,
            ];
            // dd($this->dataParamPurchaseInvoice);

            // Opsional: Cek struktur datanya sebelum di-hit ke API Accurate
            // dd($this->dataParamPurchaseInvoice);
            try {
                // 3. Sync Vendor ke Accurate agar Vendor No pasti terisi/terupdate
                $customerUser = $phoneData->user;
                // dd($customerUser);
                $accurateService = app(AccurateService::class);
                $accurateService->syncVendor($customerUser, 'second');
                $customerUser->refresh();

                // Update vendor No di param
                $vendorNoBaru = $customerUser->getAccurateVendorNo('second') ?? 'V-CASH';
                $this->dataParamPurchaseInvoice['vendorNo'] = str_replace('"', '', $vendorNoBaru);

                // 4. Hit API menggunakan service yang di-inject JIKA BELUM ADA
                if (!$this->sellPhone->invoice_number) {
                    $accurateResponse = $accurateService->postPurchaseInvoice($this->dataParamPurchaseInvoice, 'second');
                    Log::info('data invoice yang masuk ke accurate : ', ['data' => $this->dataParamPurchaseInvoice, 'response' => $accurateResponse]);

                    if (isset($accurateResponse['r']['number'])) {
                        // Simpan state secara iteratif
                        $this->sellPhone->update(['invoice_number' => $accurateResponse['r']['number']]);
                    } else {
                        // Fallback jika tidak ada number
                        $this->sellPhone->update(['invoice_number' => $billNumber]);
                    }
                }

                // JIKA BERHASIL: Update status dan redirect
                $this->sellPhone->update([
                    'status' => 'COMPLETED'
                ]);

                $this->dispatch('toast', [
                    'type' => 'success',
                    'title' => 'Success',
                    'message' => 'Invoice Accurate Berhasil Dibuat. Pengajuan Jual HP Selesai.'
                ]);
                return $this->redirect(route('admin.sell-phone.index'));
            } catch (\Exception $e) {
                // JIKA GAGAL: Tangkap error dari service dan tampilkan ke user via Toast
                // Status SellPhone TIDAK diupdate ke COMPLETED, sehingga user bisa mencoba klik submit lagi
                Log::error('API Accurate Failed: ' . $e->getMessage());
                $this->dispatch('toast', [
                    'type' => 'error',
                    'title' => 'Error',
                    'message' => 'Gagal membuat faktur di Accurate: ' . $e->getMessage()
                ]);
            }
        } else {
            return;
        }
    }

    public function reject()
    {
        $this->sellPhone->update(['status' => 'CANCELLED']);
        $this->dispatch('toast', title: 'Ditolak', message: 'Pembelian dibatalkan secara sepihak.', type: 'info');
    }

    public function convertToProduct()
    {
        if ($this->sellPhone->status !== 'COMPLETED') return;

        $this->validate([
            'sellPrice' => 'required|numeric|min:1000',
            'secondCondition' => 'required|string',
        ]);

        DB::transaction(function () {
            $productName = $this->sellPhone->phone_brand . ' ' . $this->sellPhone->phone_model;

            $product = null;
            if ($this->existingProductId) {
                $product = \App\Models\SecondProduct::find($this->existingProductId);
            } else {
                $brand = \App\Models\Brand::where('name', $this->sellPhone->phone_brand)->first();
                $businessUnitId = $this->sellPhone->handledBy->business_unit_id ?? Auth::user()->getActiveBusinessUnitId();

                $product = \App\Models\SecondProduct::firstOrCreate(
                    ['name' => $productName],
                    [
                        'slug' => Str::slug($productName . ' Second ' . rand(100, 999)),
                        'brand_id' => $brand?->id,
                        'category_id' => \App\Models\Category::first()?->id,
                        'description' => 'Produk unit seken / bekas pakai dari pembelian pelanggan.',
                        'is_active' => true,
                        'starting_price' => $this->sellPrice,
                        'total_stock' => 0,
                        'has_active_accurate' => true,
                        'business_unit_id' => $businessUnitId
                    ]
                );
            }

            // Generate SKU format for local secondary items
            $sku = 'GSK-' . str_pad($this->sellPhone->id, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(Str::random(3));

            $variant = \App\Models\SecondProductVariant::create([
                'second_product_id' => $product->id,
                'sell_phone_id' => $this->sellPhone->id,
                'sku' => $sku,
                'storage' => $this->sellPhone->phone_storage ?? '-',
                'color' => '-',
                'condition_desc' => $this->secondCondition,
                'price' => $this->sellPrice,
                'buy_price' => $this->sellPhone->appraised_value,
                'stock' => 1,
                'has_sn' => true
            ]);

            $warehouseId = Auth::user()->warehouse_id ?? \App\Models\Warehouse::first()?->id;
            if ($warehouseId) {
                \App\Models\WarehouseStock::create([
                    'warehouse_id' => $warehouseId,
                    'variant_id' => $variant->id,
                    'variant_type' => get_class($variant),
                    'stock' => 1,
                ]);

                // Update denormalized total stock
                $product->increment('total_stock');

                // Create Serial Number
                $imei = $this->sellPhone->imei ?? ('SN-SELL-' . $this->sellPhone->id);
                \App\Models\ProductSerialNumber::create([
                    'item_no' => $sku,
                    'serial_number' => $imei,
                    'warehouse_id' => $warehouseId,
                    'status' => 'Available',
                    'hpp' => $this->sellPhone->appraised_value,
                ]);
            }
        });

        $this->convertModal = false;
        $this->dispatch('toast', title: 'Berhasil', message: 'Unit HP lama masuk ke Katalog Second GSK.', type: 'success');
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        return view('livewire.admin.sell-phone.show');
    }
}
