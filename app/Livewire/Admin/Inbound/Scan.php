<?php

namespace App\Livewire\Admin\Inbound;

use Livewire\Component;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\DeviceInspection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use App\Services\AccurateService;

#[Layout('layouts.admin', ['title' => 'Inbound QC - TokoPun'])]
class Scan extends Component
{
    public PurchaseOrder $po;

    // UI States
    public $activeItemNo = null; // Item yang sedang discan SKU nya
    public $barcodeInput = '';
    public $errorMessage = '';

    // Detailed QC State
    public $scannedImei = '';
    public $activeItemId = null;

    public function mount(PurchaseOrder $po)
    {
        $this->po = $po->load('items.inspections');
    }

    public function processScan()
    {
        $this->errorMessage = '';
        $barcode = trim($this->barcodeInput);
        if (empty($barcode)) return;

        // 1. Cek apakah ini barcode SKU (Item No)
        $item = $this->po->items->where('item_no', $barcode)->first();
        if ($item) {
            if ($item->quantity_received >= $item->quantity_ordered) {
                $this->errorMessage = "Item {$item->item_name} sudah mencapai kuantitas pesanan.";
                $this->barcodeInput = '';
                return;
            }
            $this->activeItemNo = $barcode;
            $this->barcodeInput = '';
            return;
        }

        // 2. Jika bukan SKU, maka ini IMEI (Serial Number). Harus ada activeItemNo
        if (!$this->activeItemNo) {
            $this->errorMessage = "Scan SKU (Barcode Produk) terlebih dahulu sebelum scan IMEI.";
            $this->barcodeInput = '';
            return;
        }

        // 3. Proses IMEI
        $activeItem = $this->po->items->where('item_no', $this->activeItemNo)->first();
        if (!$activeItem) return;

        if ($activeItem->quantity_received >= $activeItem->quantity_ordered) {
            $this->errorMessage = "Target kuantitas sudah terpenuhi. Silakan scan SKU produk lain.";
            $this->barcodeInput = '';
            $this->activeItemNo = null;
            return;
        }

        // Cek duplikasi IMEI di tabel device_inspections (untuk semua inbound PO agar tidak dobel scan)
        $exists = DeviceInspection::where('imei', $barcode)->where('inspectable_type', PurchaseOrderItem::class)->exists();
        if ($exists) {
            $this->errorMessage = "IMEI {$barcode} sudah discan sebelumnya.";
            $this->barcodeInput = '';
            return;
        }

        // Tampilkan Form QC Inline
        $this->scannedImei = $barcode;
        $this->activeItemId = $activeItem->id;
        $this->barcodeInput = '';
    }

    #[On('qc-inspection-saved')]
    public function handleQcSaved($verdict = 'pass')
    {
        $activeItem = $this->po->items->where('item_no', $this->activeItemNo)->first();
        if ($activeItem) {
            $activeItem->increment('quantity_received');
            $this->po->refresh();
        }
        $this->scannedImei = '';
        $this->activeItemId = null;
    }



    public function deleteQc($inspectionId)
    {
        $inspection = DeviceInspection::find($inspectionId);
        if ($inspection) {
            $item = PurchaseOrderItem::find($inspection->inspectable_id);
            $inspection->delete();
            if ($item) {
                $item->decrement('quantity_received');
            }
            $this->po->refresh();
        }
    }

    public function completeReceiveItem()
    {
        // Validasi
        $ordered = $this->po->items->sum('quantity_ordered');
        $received = $this->po->items->sum('quantity_received');

        if ($received === 0) {
            $this->dispatch('admin-alert', type: 'error', message: 'Tidak ada item yang di-scan.');
            return;
        }

        // Jika tidak 100%, tandai PARTIAL, jika 100% COMPLETED. 
        // Accurate akan mencatat apa yang dipush.
        $status = ($received < $ordered) ? 'PARTIAL' : 'COMPLETED';

        try {
            $service = app(AccurateService::class);
            list($host, $token, $secretKey) = $service->getCredentials($this->po->database_source);

            $detailItem = [];
            foreach ($this->po->items as $item) {
                if ($item->quantity_received > 0) {
                    $serialNumbers = [];
                    foreach ($item->inspections as $ins) {
                        $serialNumbers[] = [
                            'serialNumberNo' => $ins->imei,
                            'quantity' => 1
                        ];
                    }

                    $detailItem[] = [
                        'itemNo' => $item->item_no,
                        'unitPrice' => (float)$item->unit_price,
                        'quantity' => (float)$item->quantity_received,
                        'purchaseOrderNumber' => $this->po->po_number,
                        'detailSerialNumber' => $serialNumbers
                    ];
                }
            }

            $payload = [
                'receiveNumber' => 'SJ-' . $this->po->po_number . '-' . date('dmY'),
                'vendorNo' => $this->po->vendor->vendor_no ?? '',
                'detailItem' => $detailItem,
                'branchName' => Auth::user()->branch->name ?? null
            ];

            $timestamp = now()->toIso8601String();
            $signature = hash_hmac('sha256', $timestamp, $secretKey);

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization'   => 'Bearer ' . $token,
                'X-Api-Timestamp' => $timestamp,
                'X-Api-Signature' => $signature,
                'Content-Type'    => 'application/json',
            ])->post($host . '/receive-item/save.do', $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['s']) && $data['s'] === true) {
                    $this->po->update(['status' => $status]);
                    session()->flash('success', 'Receive Item berhasil dikirim ke Accurate.');
                    return redirect()->route('admin.inbound.index');
                } else {
                    $this->errorMessage = "Gagal kirim ke Accurate: " . json_encode($data);
                }
            } else {
                $this->errorMessage = "Gagal kirim ke Accurate: " . $response->body();
            }
        } catch (\Exception $e) {
            $this->errorMessage = "Error: " . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.admin.inbound.scan');
    }
}
