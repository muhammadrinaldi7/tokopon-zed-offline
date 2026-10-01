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
        $isGlobal = $user->hasAnyRole(['superadmin', 'admin', 'director', 'direktur', 'manager_operasional', 'manager_operasional_gsk']);
        $isBm = $user->hasAnyRole(['bm', 'bm_gsk']);

        // Proteksi hak akses BM di cabang tersebut
        if (!$isGlobal && !$isBm) {
            abort(403, 'Akses ditolak: Hanya Branch Manager (BM) yang berhak melakukan pemindaian stock opname.');
        }

        if ($opname->business_unit_id !== $buId) {
            abort(403, 'Akses ditolak: Unit bisnis tidak sesuai.');
        }

        if (!$isGlobal && $user->branch_id && $opname->branch_id !== $user->branch_id) {
            abort(403, 'Akses ditolak: Anda hanya dapat mengakses dan memindai opname cabang Anda sendiri.');
        }

        // Jika sudah selesai atau menunggu approval, arahkan ke summary
        if ($opname->status !== 'COUNTING') {
            return redirect()->route('zoffline.stock-opname.summary', $opname->id);
        }

        $this->opname = $opname;
    }

    /**
     * Helper untuk menyensor nomor IMEI / Serial Number agar tidak terjadi kecurangan copy-paste.
     * Untuk status MISSING (belum discan), hanya 4 digit terakhir yang ditampilkan.
     * Untuk status MATCHED / UNEXPECTED, digit tengah disensor.
     */
    public static function maskSerialNumber(?string $sn, string $status = 'MISSING'): string
    {
        if (!$sn) {
            return '-';
        }

        $clean = trim($sn);
        $len = strlen($clean);

        if ($len <= 4) {
            return str_repeat('•', $len);
        }

        if ($status === 'MISSING') {
            return str_repeat('•', max(0, $len - 4)) . substr($clean, -4);
        }

        if ($len <= 8) {
            return substr($clean, 0, 2) . str_repeat('•', max(0, $len - 4)) . substr($clean, -2);
        }

        return substr($clean, 0, 4) . str_repeat('•', max(0, $len - 8)) . substr($clean, -4);
    }

    /**
     * Proses Pemindaian Barcode / Input IMEI Cepat (Kolaborasi Multi-BM)
     */
    public function processScan()
    {
        $cleanSn = strtoupper(trim($this->barcodeScan));
        if (empty($cleanSn)) {
            return;
        }

        $user = Auth::user();
        $scannerName = $user->name;

        $this->scanAlert = null;
        $this->lastScannedItem = null;

        // 1. Cek apakah nomor seri ini ada di daftar snapshot opname cabang ini
        $opnameSerial = StockOpnameSerial::with(['stockOpnameItem', 'scannedByUser'])
            ->where('stock_opname_id', $this->opname->id)
            ->where('serial_number', $cleanSn)
            ->first();

        if ($opnameSerial) {
            $maskedSn = self::maskSerialNumber($cleanSn, 'MATCHED');

            // Kasus A: Sudah discan sebelumnya (Duplikat)
            if ($opnameSerial->status === 'MATCHED') {
                $previouslyScannedBy = $opnameSerial->scannedByUser->name ?? 'BM Lain';
                $time = $opnameSerial->scanned_at ? $opnameSerial->scanned_at->format('H:i:s') : '-';

                $this->scanAlert = [
                    'type'    => 'warning',
                    'message' => "Nomor Seri/IMEI [{$maskedSn}] SUDAH discan sebelumnya oleh {$previouslyScannedBy} pada pukul {$time}!",
                ];
                $this->dispatch('play-scan-sound', type: 'warning');
                $this->barcodeScan = '';
                return;
            }

            // Kasus B: Belum discan (MISSING -> MATCHED)
            $opnameSerial->update([
                'status'     => 'MATCHED',
                'scanned_at' => now(),
                'scanned_by' => $user->id,
            ]);

            // Update kuantitas fisik pada item terkait
            $item = $opnameSerial->stockOpnameItem;
            if ($item) {
                $item->increment('physical_qty');
                $item->update(['last_counted_by' => $user->id]);
                $item->recalculateDifference();
            }

            $this->opname->calculateTotals();

            $this->lastScannedItem = [
                'id'           => $opnameSerial->id,
                'sn'           => $maskedSn,
                'name'         => $item->product_name ?? $opnameSerial->item_no,
                'status'       => 'MATCHED',
                'status_label' => 'Cocok (Terverifikasi)',
                'scanned_by'   => $scannerName,
                'scanned_at'   => now()->format('H:i:s'),
            ];

            $this->scanAlert = [
                'type'    => 'success',
                'message' => "IMEI [{$maskedSn}] berhasil diverifikasi oleh {$scannerName}!",
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
            $originNote = 'Fisik ditemukan saat opname, di luar cakupan snapshot sesi ini.';

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
                    'last_counted_by'  => $user->id,
                ]
            );

            // Tambahkan ke tabel serials dengan status UNEXPECTED
            $createdSerial = StockOpnameSerial::create([
                'stock_opname_id'      => $this->opname->id,
                'stock_opname_item_id' => $item->id,
                'item_no'              => $itemNo,
                'serial_number'        => $cleanSn,
                'status'               => 'UNEXPECTED',
                'hpp'                  => $hpp,
                'scanned_at'           => now(),
                'scanned_by'           => $user->id,
                'notes'                => $originNote,
            ]);

            $item->increment('physical_qty');
            $item->update(['last_counted_by' => $user->id]);
            $item->recalculateDifference();

            $this->opname->calculateTotals();

            $maskedSn = self::maskSerialNumber($cleanSn, 'UNEXPECTED');

            $this->lastScannedItem = [
                'id'           => $createdSerial->id,
                'sn'           => $maskedSn,
                'name'         => $productName,
                'status'       => 'UNEXPECTED',
                'status_label' => 'Barang Nyasar / Di Luar Cakupan Sesi',
                'scanned_by'   => $scannerName,
                'scanned_at'   => now()->format('H:i:s'),
            ];

            $this->scanAlert = [
                'type'    => 'error',
                'message' => "PERINGATAN: Scan [{$maskedSn}] adalah Barang Nyasar / Di Luar Sesi! Dicatat oleh {$scannerName}.",
            ];
            $this->dispatch('play-scan-sound', type: 'error');
        }

        $this->barcodeScan = '';
    }

    /**
     * Batalkan atau hapus pemindaian IMEI / Barcode salah.
     * - Jika UNEXPECTED (barang nyasar / barcode lain ke-scan): Hapus record serial, dan hapus item jika dibuat otomatis.
     * - Jika MATCHED (IMEI resmi toko tapi salah scan): Kembalikan ke MISSING.
     */
    public function deleteScan(int $serialId)
    {
        if ($this->opname->status !== 'COUNTING') {
            $this->scanAlert = [
                'type'    => 'error',
                'message' => 'Sesi stock opname ini sudah tidak dalam tahap pemindaian (counting).',
            ];
            return;
        }

        $serial = StockOpnameSerial::where('stock_opname_id', $this->opname->id)
            ->with('stockOpnameItem')
            ->find($serialId);

        if (!$serial) {
            $this->scanAlert = [
                'type'    => 'error',
                'message' => 'Data scan tidak ditemukan atau sudah dibatalkan sebelumnya.',
            ];
            return;
        }

        $item = $serial->stockOpnameItem;
        $maskedSn = self::maskSerialNumber($serial->serial_number, $serial->status);

        if ($serial->status === 'UNEXPECTED') {
            $serial->delete();

            if ($item) {
                $item->decrement('physical_qty');

                // Jika item dibuat otomatis karena scan nyasar (system_qty == 0)
                // dan kuantitas fisik kembali 0 serta tidak ada serial lain tersisa, hapus itemnya
                if ($item->system_qty <= 0 && $item->physical_qty <= 0 && $item->serials()->count() === 0) {
                    $item->delete();
                } else {
                    $item->recalculateDifference();
                }
            }

            $this->scanAlert = [
                'type'    => 'success',
                'message' => "Scan salah/nyasar [{$maskedSn}] berhasil dihapus dari opname.",
            ];
        } elseif ($serial->status === 'MATCHED') {
            $serial->update([
                'status'     => 'MISSING',
                'scanned_at' => null,
                'scanned_by' => null,
            ]);

            if ($item) {
                $item->decrement('physical_qty');
                $item->recalculateDifference();
            }

            $this->scanAlert = [
                'type'    => 'success',
                'message' => "Scan IMEI [{$maskedSn}] berhasil dibatalkan (status kembali Belum Discan).",
            ];
        }

        $this->opname->calculateTotals();

        // Reset lastScannedItem jika yang dihapus adalah item terakhir yang discan
        if ($this->lastScannedItem && ($this->lastScannedItem['id'] ?? null) === $serialId) {
            $this->lastScannedItem = null;
        }

        // Refresh modal jika modal sedang terbuka
        if ($this->showSerialModal && $this->selectedItemForSerials) {
            $activeItemId = is_array($this->selectedItemForSerials)
                ? ($this->selectedItemForSerials['id'] ?? null)
                : ($this->selectedItemForSerials->id ?? null);

            if ($activeItemId) {
                $this->openSerialModal($activeItemId);
            }
        }
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
            $item->update([
                'physical_qty'    => $newQty,
                'last_counted_by' => Auth::id(),
            ]);
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
     * Buka modal untuk melihat serials suatu item (lengkap dengan info scanner & sensor anti-copy)
     */
    public function openSerialModal(int $itemId)
    {
        $item = StockOpnameItem::with(['serials' => function ($q) {
            $q->with('scannedByUser')->orderBy('status', 'asc')->orderBy('scanned_at', 'desc');
        }])
            ->where('stock_opname_id', $this->opname->id)
            ->find($itemId);

        if (!$item) {
            $this->selectedItemForSerials = null;
            $this->showSerialModal = false;
            return;
        }

        // Mapping serials dengan sensor IMEI langsung di backend
        // Demi keamanan, nomor seri utuh berstatus MISSING tidak pernah dikirim ke browser/client
        $serialsList = $item->serials->map(function ($sn) {
            return [
                'id'                   => $sn->id,
                'masked_sn'            => self::maskSerialNumber($sn->serial_number, $sn->status),
                'status'               => $sn->status,
                'scanned_by_name'      => $sn->scannedByUser->name ?? null,
                'scanned_at_formatted' => $sn->scanned_at ? $sn->scanned_at->format('H:i:s') : null,
                'notes'                => $sn->notes,
            ];
        })->toArray();

        $this->selectedItemForSerials = [
            'id'           => $item->id,
            'product_name' => $item->product_name,
            'item_no'      => $item->item_no,
            'serials'      => $serialsList,
        ];

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

        $query = StockOpnameItem::with('lastCountedBy')
            ->withCount([
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

        // 8 Riwayat Scan Terkini oleh Tim BM
        $recentScans = StockOpnameSerial::with(['scannedByUser', 'stockOpnameItem'])
            ->where('stock_opname_id', $this->opname->id)
            ->whereNotNull('scanned_at')
            ->orderBy('scanned_at', 'desc')
            ->take(8)
            ->get();

        // Rekap personil BM yang berkontribusi scan pada sesi ini
        $scannersSummary = $this->opname->getScannersSummary();

        return view('livewire.zoffline.stock-opname.count', [
            'items'           => $items,
            'serialStats'     => $serialStats,
            'recentScans'     => $recentScans,
            'scannersSummary' => $scannersSummary,
        ]);
    }
}
