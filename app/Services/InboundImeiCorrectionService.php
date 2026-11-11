<?php

namespace App\Services;

use App\Livewire\Zoffline\Inbound\Scan;
use App\Models\AccurateDocumentAuditLog;
use App\Models\DeviceInspection;
use App\Models\OrderItem;
use App\Models\ProductSerialNumber;
use App\Models\PurchaseOrderItem;
use App\Models\Warranty;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InboundImeiCorrectionService
{
    protected AccurateService $accurateService;

    public function __construct(AccurateService $accurateService)
    {
        $this->accurateService = $accurateService;
    }

    /**
     * Koreksi IMEI / Serial Number pada DeviceInspection yang sudah ter-push ke Accurate.
     * Menyelaraskan dokumen Receive Item di Accurate Online dan seluruh database lokal secara atomik.
     *
     * @param DeviceInspection $inspection
     * @param string $newImei
     * @param string $reason
     * @param int $userId
     * @param bool $syncToAccurate
     * @return array
     * @throws Exception
     */
    public function correctPushedImei(
        DeviceInspection $inspection,
        string $newImei,
        string $reason,
        int $userId,
        bool $syncToAccurate = true
    ): array {
        $oldImei = trim($inspection->imei);

        // 1. Validasi Format IMEI baru (Luhn Checksum 15 digit GSM & karakter valid)
        $val = Scan::validateImeiOrSn($newImei);
        if (!$val['valid']) {
            throw new Exception($val['message']);
        }
        $cleanNewImei = $val['clean'];

        if ($cleanNewImei === $oldImei) {
            return [
                'success' => true,
                'message' => 'Nomor IMEI baru sama dengan nomor IMEI saat ini (tidak ada perubahan).',
                'old_imei' => $oldImei,
                'new_imei' => $cleanNewImei,
            ];
        }

        // 2. Cek Duplikasi IMEI baru di DeviceInspection lain
        $duplicateInspection = DeviceInspection::where('imei', $cleanNewImei)
            ->where('id', '!=', $inspection->id)
            ->first();

        if ($duplicateInspection) {
            throw new Exception("IMEI {$cleanNewImei} sudah terdaftar pada data inspeksi lain (ID: #{$duplicateInspection->id}).");
        }

        // 3. Safety Check: Pastikan unit dengan IMEI lama belum pernah terjual di POS
        $soldOrderItem = OrderItem::where('serial_number', $oldImei)->with('order')->first();
        if ($soldOrderItem) {
            $orderNum = $soldOrderItem->order?->order_number ?? $soldOrderItem->order_id;
            throw new Exception("Tidak dapat mengubah IMEI: Unit dengan IMEI {$oldImei} sudah tercatat TERJUAL pada Pesanan POS #{$orderNum}. Mohon koordinasikan dengan bagian Penjualan terlebih dahulu.");
        }

        // Ambil konteks PO
        $poItem = $inspection->inspectable;
        $po = ($poItem instanceof PurchaseOrderItem) ? $poItem->purchaseOrder : null;
        $dbSource = $po ? ($po->database_source ?? 'syihab') : 'syihab';

        $accurateResult = null;
        $targetRiNumber = null;

        // 4. Update di Accurate Online API jika item sudah di-push dan opsi sync aktif
        if ($syncToAccurate && $inspection->is_pushed) {
            $match = $this->findAccurateReceiveItemForInspection($inspection, $dbSource);

            if (!$match) {
                Log::warning("Koreksi IMEI: Dokumen Penerimaan Barang di Accurate tidak ditemukan untuk IMEI {$oldImei}. Mencoba melanjutkan koreksi lokal.");
            } else {
                $targetRi = $match['receiveItem'];
                $targetRiNumber = $targetRi['number'] ?? ($targetRi['no'] ?? null);

                // Eksekusi update nomor seri di Accurate Receive Item
                $updateRes = $this->updateAccurateReceiveItemSerialNumber(
                    $match,
                    $oldImei,
                    $cleanNewImei,
                    $dbSource
                );

                if (!$updateRes['success']) {
                    throw new Exception("Gagal mengupdate nomor seri di Accurate Online: " . ($updateRes['error'] ?? 'Terjadi kesalahan API.'));
                }

                $accurateResult = $updateRes;
            }
        }

        // 5. Update Database Lokal Secara Atomik
        $this->executeLocalUpdates(
            $inspection,
            $oldImei,
            $cleanNewImei,
            $reason,
            $userId,
            $targetRiNumber,
            $po?->business_unit_id,
            $accurateResult
        );

        return [
            'success' => true,
            'message' => "IMEI berhasil dikoreksi dari {$oldImei} menjadi {$cleanNewImei} baik di database lokal maupun dokumen Penerimaan Accurate.",
            'old_imei' => $oldImei,
            'new_imei' => $cleanNewImei,
            'accurate_doc' => $targetRiNumber,
        ];
    }

    /**
     * Cari dokumen Receive Item (RI) di Accurate yang memuat serial number lama
     */
    public function findAccurateReceiveItemForInspection(DeviceInspection $inspection, string $dbSource = 'syihab'): ?array
    {
        $oldImei = trim($inspection->imei);
        $poItem = $inspection->inspectable;
        $po = ($poItem instanceof PurchaseOrderItem) ? $poItem->purchaseOrder : null;

        // Kumpulkan kandidat Receive Item berdasarkan nomor PO
        $candidateKeywords = [];
        if ($po && !empty($po->po_number)) {
            $candidateKeywords[] = $po->po_number;
            $candidateKeywords[] = 'SJ-' . $po->po_number;
        }
        $candidateKeywords[] = $oldImei;

        $checkedRiIds = [];

        foreach (array_unique($candidateKeywords) as $kw) {
            try {
                $list = $this->accurateService->getRecentReceiveItemList($dbSource, 15, $kw);
                foreach ($list as $riSummary) {
                    $riId = $riSummary['id'] ?? null;
                    if (!$riId || in_array($riId, $checkedRiIds)) {
                        continue;
                    }
                    $checkedRiIds[] = $riId;

                    $riDetail = $this->accurateService->getReceiveItemDetail($riId, $dbSource);
                    if (!$riDetail) {
                        continue;
                    }

                    $detailItems = $riDetail['detailItem'] ?? [];
                    foreach ($detailItems as $dItem) {
                        $snList = $dItem['detailSerialNumber'] ?? [];
                        foreach ($snList as $snRow) {
                            $snVal = $snRow['serialNumberNo'] ?? ($snRow['serialNumber'] ?? '');
                            if (strcasecmp(trim($snVal), $oldImei) === 0) {
                                return [
                                    'receiveItem' => $riDetail,
                                    'detailItem'  => $dItem,
                                    'snRow'       => $snRow,
                                ];
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                Log::warning("Pencarian Receive Item Accurate keyword '{$kw}' gagal: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Kirim request update ke API Accurate Online /receive-item/save.do
     */
    protected function updateAccurateReceiveItemSerialNumber(
        array $match,
        string $oldImei,
        string $newImei,
        string $dbSource
    ): array {
        $riDetail   = $match['receiveItem'];
        $detailItem = $match['detailItem'];

        $riId = $riDetail['id'] ?? null;
        $detailItemId = $detailItem['id'] ?? null;

        if (!$riId || !$detailItemId) {
            return [
                'success' => false,
                'error' => 'ID dokumen Penerimaan Barang Accurate tidak lengkap.',
            ];
        }

        // Susun ulang detailSerialNumber dengan mengganti IMEI lama ke IMEI baru
        $allSnRows = $detailItem['detailSerialNumber'] ?? [];
        $updatedSnRows = [];

        foreach ($allSnRows as $snRow) {
            $curSn = $snRow['serialNumberNo'] ?? ($snRow['serialNumber'] ?? '');
            if (strcasecmp(trim($curSn), $oldImei) === 0) {
                $updatedSnRows[] = [
                    'id'             => $snRow['id'] ?? null,
                    'serialNumberNo' => $newImei,
                    'quantity'       => 1,
                ];
            } else {
                $updatedSnRows[] = [
                    'id'             => $snRow['id'] ?? null,
                    'serialNumberNo' => $curSn,
                    'quantity'       => 1,
                ];
            }
        }

        $payload = [
            'id'         => $riId,
            'detailItem' => [
                [
                    'id'                 => $detailItemId,
                    'detailSerialNumber' => $updatedSnRows,
                ]
            ],
        ];

        return $this->accurateService->saveReceiveItem($payload, $dbSource);
    }

    /**
     * Eksekusi update database lokal secara atomik
     */
    protected function executeLocalUpdates(
        DeviceInspection $inspection,
        string $oldImei,
        string $newImei,
        string $reason,
        int $userId,
        ?string $accurateDocNumber,
        ?int $businessUnitId,
        ?array $accurateResult
    ): void {
        DB::transaction(function () use (
            $inspection,
            $oldImei,
            $newImei,
            $reason,
            $userId,
            $accurateDocNumber,
            $businessUnitId,
            $accurateResult
        ) {
            // 1. Update tabel device_inspections
            $inspection->imei = $newImei;
            $inspection->save();

            // 2. Update tabel product_serial_numbers jika ada
            ProductSerialNumber::where('serial_number', $oldImei)
                ->update(['serial_number' => $newImei]);

            // 3. Update tabel warranties jika ada kartu garansi yang terbit
            Warranty::where('serial_number', $oldImei)
                ->update(['serial_number' => $newImei]);

            // 4. Catat jejak audit pada accurate_document_audit_logs
            AccurateDocumentAuditLog::create([
                'order_id'          => null,
                'business_unit_id'  => $businessUnitId,
                'document_type'     => 'RECEIVE_ITEM',
                'action_type'       => 'CORRECT_INBOUND_IMEI',
                'target_doc_number' => $accurateDocNumber ?: ('INSP-#' . $inspection->id),
                'old_values'        => ['imei' => $oldImei],
                'new_values'        => ['imei' => $newImei],
                'reason'            => $reason,
                'status'            => 'SUCCESS',
                'error_message'     => null,
                'accurate_response' => $accurateResult,
                'performed_by'      => $userId,
            ]);
        });
    }
}

