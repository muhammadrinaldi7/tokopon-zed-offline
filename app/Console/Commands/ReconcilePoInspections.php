<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PurchaseOrder;
use App\Models\DeviceInspection;
use App\Services\AccurateService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReconcilePoInspections extends Command
{
    protected $signature = 'accurate:reconcile-po';
    protected $description = 'Otomatis mencocokkan IMEI di device_inspections ke purchase_order_items baru via Accurate';

    public function handle()
    {
        $this->info("=== Memulai Rekonsiliasi Otomatis PO & IMEI via Accurate ===");

        $service = app(AccurateService::class);

        // Ambil semua PO yang memiliki item
        $pos = PurchaseOrder::with('items')->get();

        if ($pos->isEmpty()) {
            $this->warn("Tidak ada data Purchase Order di database lokal.");
            return;
        }

        $totalMatchedImeis = 0;

        foreach ($pos as $po) {
            $this->line("--------------------------------------------------");
            $this->info("Memproses PO: {$po->po_number} (ID: {$po->id})");

            try {
                // Ambil credentials sesuai database_source PO
                list($host, $token, $secretKey) = $service->getCredentials($po->database_source);

                if (!$host || !$token || !$secretKey) {
                    $this->error(" -> Credentials Accurate tidak valid untuk database: {$po->database_source}");
                    continue;
                }

                // 1. Cari Penerimaan Barang (Receive Item) di Accurate berdasarkan nomor PO
                $timestamp = now()->toIso8601String();
                $signature = hash_hmac('sha256', $timestamp, $secretKey);

                $response = Http::withHeaders([
                    'Authorization'   => 'Bearer ' . $token,
                    'X-Api-Timestamp' => $timestamp,
                    'X-Api-Signature' => $signature,
                    'Content-Type'    => 'application/json',
                ])->get($host . '/receive-item/list.do', [
                    'fields'              => 'id,number',
                    'filter.keywords.op'  => 'CONTAIN',
                    'filter.keywords.val' => $po->po_number,
                ]);

                if (!$response->successful() || empty($response->json()['d'])) {
                    $this->warn(" -> Tidak ada Penerimaan Barang ditemukan di Accurate untuk PO: {$po->po_number}");
                    continue;
                }

                $receiveList = $response->json()['d'];

                // 2. Loop setiap Surat Jalan / Penerimaan Barang yang ditemukan
                foreach ($receiveList as $receiveHeader) {
                    $receiveId = $receiveHeader['id'];
                    $receiveNo = $receiveHeader['number'] ?? '-';
                    $this->line(" -> Menemukan SJ: {$receiveNo} (ID: {$receiveId})");

                    $timestamp = now()->toIso8601String();
                    $signature = hash_hmac('sha256', $timestamp, $secretKey);

                    // Ambil detail lengkap Surat Jalan dari Accurate
                    $detailResp = Http::withHeaders([
                        'Authorization'   => 'Bearer ' . $token,
                        'X-Api-Timestamp' => $timestamp,
                        'X-Api-Signature' => $signature,
                        'Content-Type'    => 'application/json',
                    ])->get($host . '/receive-item/detail.do', [
                        'id' => $receiveId
                    ]);

                    if (!$detailResp->successful() || empty($detailResp->json()['d']['detailItem'])) {
                        continue;
                    }

                    $detailItems = $detailResp->json()['d']['detailItem'];

                    foreach ($detailItems as $accItem) {
                        $itemNo = $accItem['itemNo'] ?? null;
                        $serialNumbers = $accItem['detailSerialNumber'] ?? [];

                        if (empty($serialNumbers) || !$itemNo) {
                            continue;
                        }

                        // Cari baris item PO yang baru di database lokal
                        $localPoItem = $po->items->where('item_no', $itemNo)->first();

                        if (!$localPoItem) {
                            $this->warn("   ! Item {$itemNo} tidak ditemukan di purchase_order_items lokal.");
                            continue;
                        }

                        // Ambil semua IMEI dari response Accurate
                        $imeiList = array_column($serialNumbers, 'serialNumberNo');

                        if (empty($imeiList)) {
                            continue;
                        }

                        // Ambil daftar ID purchase_order_items yang masih aktif
                        $activePoItemIds = \App\Models\PurchaseOrderItem::pluck('id')->toArray();

                        // 3. Update inspectable_id di tabel device_inspections ke ID lokal yang baru HANYA jika yatim piatu
                        $updated = DeviceInspection::where('inspectable_type', 'like', '%PurchaseOrderItem%')
                            ->whereIn('imei', $imeiList)
                            ->whereNotIn('inspectable_id', $activePoItemIds)
                            ->update([
                                'inspectable_id' => $localPoItem->id,
                                'is_pushed'      => 1
                            ]);

                        // 4. Hitung ulang quantity_received & quantity_pushed di purchase_order_items
                        $qtyReceived = DeviceInspection::where('inspectable_id', $localPoItem->id)->count();
                        $qtyPushed   = DeviceInspection::where('inspectable_id', $localPoItem->id)->where('is_pushed', 1)->count();

                        $localPoItem->update([
                            'quantity_received' => $qtyReceived,
                            'quantity_pushed'   => $qtyPushed,
                        ]);

                        $totalMatchedImeis += $updated;
                        $this->info("   ✔ Berhasil menyambungkan {$updated} IMEI ke SKU {$localPoItem->item_no} (ID Baru: {$localPoItem->id} | Qty: {$qtyReceived}/{$localPoItem->quantity})");
                    }
                }
            } catch (\Exception $e) {
                $this->error(" -> Error pada PO {$po->po_number}: " . $e->getMessage());
                Log::error("Reconcile PO Error: " . $e->getMessage());
            }
        }

        $this->info("==================================================");
        $this->info("SELESAI! Total {$totalMatchedImeis} IMEI berhasil dipulihkan dan tersambung ke PO Item baru.");
    }
}
