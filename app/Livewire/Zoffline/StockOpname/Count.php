<?php

namespace App\Livewire\Zoffline\StockOpname;

use App\Models\ProductAccurate;
use App\Models\ProductSerialNumber;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\StockOpnameSerial;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.z', ['title' => 'Workstation Stock Opname'])]
class Count extends Component
{
    public StockOpname $opname;

    public $activeTab = 'ALL'; // ALL, SERIALIZED, NON_SERIALIZED, DISCREPANCY
    public $snStatusFilter = 'ALL'; // ALL, MATCHED, MISSING, UNEXPECTED
    public $search = '';

    public $barcodeScan = '';
    public $lastScannedItem = null;
    public $scanAlert = null; // ['type' => 'success'|'warning'|'error', 'message' => '...']

    // Modal untuk inspect serials pada suatu item
    public $selectedItemForSerials = null;
    public $showSerialModal = false;

    public function mount(StockOpname $opname)
    {
        $user = Auth::user();
        $buId = $user->getActiveBusinessUnitId();
        $isGlobal = $user->hasAnyRole(['superadmin', 'admin', 'director']);

        // Proteksi akses cabang
        if ($opname->business_unit_id !== $buId) {
            abort(403, 'Akses ditolak: Unit bisnis tidak sesuai.');
        }

        if (!$isGlobal && $user->branch_id && $opname->branch_id !== $user->branch_id) {
            abort(403, 'Akses ditolak: Anda hanya dapat mengakses opname cabang Anda sendiri.');
        }

        // Jika sudah selesai atau menunggu approval, arahkan ke summary
        if ($opname->status !== 'COUNTING') {
            return redirect()->route('zoffline.stock-opname.summary', $opname->id);
        }

        $this->opname = $opname;
    }

    /**
     * Proses Pemindaian Barcode / Input IMEI Cepat
     */
    public function processScan()
    {
        $cleanSn = strtoupper(trim($this->barcodeScan));
        if (empty($cleanSn)) {
            return;
        }

        $this->scanAlert = null;
        $this->lastScannedItem = null;

        // 1. Cek apakah nomor seri ini ada di daftar snapshot opname cabang ini
        $opnameSerial = StockOpnameSerial::with('stockOpnameItem')
            ->where('stock_opname_id', $this->opname->id)
            ->where('serial_number', $cleanSn)
            ->first();

        if ($opnameSerial) {
            // Kasus A: Sudah discan sebelumnya (Duplikat)
            if ($opnameSerial->status === 'MATCHED') {
                $this->scanAlert = [
                    'type'    => 'warning',
                    'message' => "Nomor Seri/IMEI [{$cleanSn}] SUDAH discan sebelumnya!",
                ];
                $this->dispatch('play-scan-sound', type: 'warning');
                $this->barcodeScan = '';
                return;
            }

            // Kasus B: Belum discan (MISSING -> MATCHED)
            $opnameSerial->update([
                'status'     => 'MATCHED',
                'scanned_at' => now(),
                'scanned_by' => Auth::id(),
            ]);

            // Update kuantitas fisik pada item terkait
            $item = $opnameSerial->stockOpnameItem;
            if ($item) {
                $item->increment('physical_qty');
                $item->recalculateDifference();
            }

            $this->opname->calculateTotals();

            $this->lastScannedItem = [
                'sn'           => $cleanSn,
                'name'         => $item->product_name ?? $opnameSerial->item_no,
                'status'       => 'MATCHED',
                'status_label' => 'Cocok (Terverifikasi)',
            ];

            $this->scanAlert = [
                'type'    => 'success',
                'message' => "IMEI [{$cleanSn}] berhasil diverifikasi!",
            ];
            $this->dispatch('play-scan-sound', type: 'success');

        } else {
            // Kasus C: Tidak ada di snapshot gudang cabang ini
            // Cek apakah IMEI ini terdaftar di database pusat Tokopon
            $existingSn = ProductSerialNumber::with(['warehouse', 'productAccurate'])
                ->where('serial_number', $cleanSn)
                ->first();

            $productName = 'Produk Tidak Dikenal';
            $itemNo = 'UNKNOWN';
            $hpp = 0;
            $originNote = 'Fisik ditemukan saat opname, tidak terdaftar di sistem cabang ini.';

            if ($existingSn) {
                $itemNo = $existingSn->item_no;
                $productName = $existingSn->product_name ?? ($existingSn->productAccurate->name ?? $existingSn->item_no);
                $hpp = (float) ($existingSn->hpp ?? 0);
                $warehouseOrigin = $existingSn->warehouse->name ?? 'Gudang Lain';
                $originNote = "Barang Nyasar / Fisik terdaftar di: [{$warehouseOrigin}] (Status: {$existingSn->status})";
            }

            // Cari atau buat StockOpnameItem untuk produk ini
            $item = StockOpnameItem::firstOrCreate(
                [
                    'stock_opname_id' => $this->opname->id,
                    'item_no'         => $itemNo,
                ],
                [
                    'product_name'     => $productName,
                    'is_serialized'    => true,
                    'system_qty'       => 0, // Di sistem cabang ini awalnya 0
                    'physical_qty'     => 0,
                    'difference_qty'   => 0,
                    'unit_cost'        => $hpp,
                    'difference_value' => 0,
                ]
            );

            // Tambahkan ke tabel serials dengan status UNEXPECTED
            StockOpnameSerial::create([
                'stock_opname_id'      => $this->opname->id,
                'stock_opname_item_id' => $item->id,
                'item_no'              => $itemNo,
                'serial_number'        => $cleanSn,
                'status'               => 'UNEXPECTED',
                'hpp'                  => $hpp,
                'scanned_at'           => now(),
                'scanned_by'           => Auth::id(),
                'notes'                => $originNote,
            ]);

            $item->increment('physical_qty');
            $item->recalculateDifference();

            $this->opname->calculateTotals();

            $this->lastScannedItem = [
                'sn'           => $cleanSn,
                'name'         => $productName,
                'status'       => 'UNEXPECTED',
                'status_label' => 'Barang Nyasar / Tidak Terdaftar di Cabang',
            ];

            $this->scanAlert = [
                'type'    => 'error',
                'message' => "PERINGATAN: IMEI [{$cleanSn}] adalah Barang Nyasar! {$originNote}",
            ];
            $this->dispatch('play-scan-sound', type: 'error');
        }

        $this->barcodeScan = '';
    }

    /**
     * Update Kuantitas Fisik Barang Non-Serialized (Aksesoris)
     */
    public function updatePhysicalQty(int $itemId, int $newQty)
    {
        $newQty = max(0, $newQty);

        $item = StockOpnameItem::where('stock_opname_id', $this->opname->id)
            ->where('id', $itemId)
            ->first();

        if ($item && !$item->is_serialized) {
            $item->update(['physical_qty' => $newQty]);
            $item->recalculateDifference();
            $this->opname->calculateTotals();
        }
    }

    public function incrementQty(int $itemId, int $amount = 1)
    {
        $item = StockOpnameItem::where('stock_opname_id', $this->opname->id)
            ->where('id', $itemId)
            ->first();

        if ($item && !$item->is_serialized) {
            $newQty = $item->physical_qty + $amount;
            $this->updatePhysicalQty($itemId, $newQty);
        }
    }

    public function decrementQty(int $itemId, int $amount = 1)
    {
        $item = StockOpnameItem::where('stock_opname_id', $this->opname->id)
            ->where('id', $itemId)
            ->first();

        if ($item && !$item->is_serialized) {
            $newQty = max(0, $item->physical_qty - $amount);
            $this->updatePhysicalQty($itemId, $newQty);
        }
    }

    /**
     * Buka modal untuk melihat serials suatu item
     */
    public function openSerialModal(int $itemId)
    {
        $this->selectedItemForSerials = StockOpnameItem::with(['serials' => function ($q) {
            $q->orderBy('status', 'asc');
        }])
            ->where('stock_opname_id', $this->opname->id)
            ->find($itemId);

        $this->showSerialModal = true;
    }

    public function closeSerialModal()
    {
        $this->showSerialModal = false;
        $this->selectedItemForSerials = null;
    }

    /**
     * Selesaikan penghitungan dan lanjutkan ke Berita Acara
     */
    public function proceedToSummary()
    {
        return $this->redirectRoute('zoffline.stock-opname.summary', $this->opname->id, navigate: true);
    }

    public function render()
    {
        $this->opname->refresh();

        $query = StockOpnameItem::withCount([
            'serials as matched_count' => function ($q) {
                $q->where('status', 'MATCHED');
            },
            'serials as missing_count' => function ($q) {
                $q->where('status', 'MISSING');
            },
            'serials as unexpected_count' => function ($q) {
                $q->where('status', 'UNEXPECTED');
            }
        ])
            ->where('stock_opname_id', $this->opname->id)
            ->when($this->activeTab === 'SERIALIZED', function ($q) {
                $q->where('is_serialized', true);
            })
            ->when($this->activeTab === 'NON_SERIALIZED', function ($q) {
                $q->where('is_serialized', false);
            })
            ->when($this->activeTab === 'DISCREPANCY', function ($q) {
                $q->where('difference_qty', '!=', 0);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('product_name', 'like', '%' . $this->search . '%')
                        ->orWhere('item_no', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('is_serialized', 'desc')
            ->orderBy('difference_qty', 'asc');

        $items = $query->get();

        // Rekap ringkas status serials
        $serialStats = [
            'total'      => StockOpnameSerial::where('stock_opname_id', $this->opname->id)->count(),
            'matched'    => StockOpnameSerial::where('stock_opname_id', $this->opname->id)->where('status', 'MATCHED')->count(),
            'missing'    => StockOpnameSerial::where('stock_opname_id', $this->opname->id)->where('status', 'MISSING')->count(),
            'unexpected' => StockOpnameSerial::where('stock_opname_id', $this->opname->id)->where('status', 'UNEXPECTED')->count(),
        ];

        return view('livewire.zoffline.stock-opname.count', [
            'items'       => $items,
            'serialStats' => $serialStats,
        ]);
    }
}
