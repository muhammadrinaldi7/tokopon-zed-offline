<?php

namespace App\Services;

use App\Models\AccurateSyncIssue;
use App\Models\AccurateWebhookLog;
use App\Models\BusinessUnit;
use Illuminate\Support\Facades\Log;

class SyncIssueRecorder
{
    /**
     * Catat issue sinkronisasi Accurate secara pasif dan terisolasi.
     * Tidak akan melempar exception ke luar agar proses utama tetap aman.
     */
    public static function record(
        string $reasonCode,
        string $stage,
        ?AccurateWebhookLog $log = null,
        ?string $itemNo = null,
        ?string $warehouseName = null,
        ?string $serialNumber = null,
        string $severity = 'error',
        ?array $expected = null,
        ?array $actual = null,
        ?array $context = null,
        ?int $businessUnitId = null,
        ?string $databaseSource = null
    ): ?AccurateSyncIssue {
        try {
            // Tentukan Business Unit ID jika belum disediakan
            if (!$businessUnitId) {
                $source = $databaseSource ?? ($log?->database_source);
                if ($source) {
                    $businessUnitId = BusinessUnit::where('code', $source)->value('id');
                }
            }

            // Normalisasi parameter untuk fingerprint
            $cleanReason = trim($reasonCode);
            $cleanBuId = $businessUnitId ? (string) $businessUnitId : '0';
            $cleanItem = $itemNo ? trim((string) $itemNo) : '';
            $cleanWh = $warehouseName ? trim((string) $warehouseName) : '';
            $cleanSn = $serialNumber ? trim((string) $serialNumber) : '';

            $fingerprint = hash('sha256', "{$cleanReason}|{$cleanBuId}|{$cleanItem}|{$cleanWh}|{$cleanSn}");

            // Cari issue yang sudah ada dengan fingerprint ini
            $issue = AccurateSyncIssue::where('fingerprint', $fingerprint)->first();

            $eventType = $log?->event_type ?? ($context['event_type'] ?? null);

            if ($issue) {
                // Issue sudah pernah terjadi sebelumnya, update hitungan occurrences dan info terbaru
                $updateData = [
                    'occurrences'   => $issue->occurrences + 1,
                    'last_seen_at'  => now(),
                    'severity'      => $severity,
                ];

                if ($log) {
                    $updateData['webhook_log_id'] = $log->id;
                    $updateData['event_type'] = $log->event_type;
                }

                if ($expected !== null) {
                    $updateData['expected'] = $expected;
                }
                if ($actual !== null) {
                    $updateData['actual'] = $actual;
                }
                if ($context !== null) {
                    $updateData['context'] = array_merge($issue->context ?? [], $context);
                }

                // Jika sebelumnya sudah di-resolve tapi muncul lagi kejadian yang sama, buka kembali
                if ($issue->status === 'resolved') {
                    $updateData['status'] = 'open';
                    $updateData['resolution_note'] = ($issue->resolution_note ? $issue->resolution_note . "\n" : '') . 
                        '[' . now()->format('Y-m-d H:i') . '] Re-opened otomatis karena issue terdeteksi kembali.';
                }

                $issue->update($updateData);
                return $issue;
            }

            // Buat record issue baru
            return AccurateSyncIssue::create([
                'fingerprint'      => $fingerprint,
                'business_unit_id' => $businessUnitId,
                'webhook_log_id'   => $log?->id,
                'event_type'       => $eventType,
                'stage'            => $stage,
                'reason_code'      => $reasonCode,
                'severity'         => $severity,
                'item_no'          => $itemNo,
                'serial_number'    => $serialNumber,
                'warehouse_name'   => $warehouseName,
                'expected'         => $expected,
                'actual'           => $actual,
                'context'          => $context,
                'status'           => 'open',
                'occurrences'      => 1,
                'first_seen_at'    => now(),
                'last_seen_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            // Isolasi penuh: jangan biarkan error di recorder menggagalkan proses sinkronisasi utama
            Log::warning("SyncIssueRecorder: Gagal mencatat issue: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Helper cepat untuk gudang tidak terdaftar
     */
    public static function recordWarehouseNotFound(
        ?string $warehouseName,
        ?string $itemNo = null,
        ?string $databaseSource = null,
        ?AccurateWebhookLog $log = null,
        ?array $context = null
    ): ?AccurateSyncIssue {
        return self::record(
            reasonCode: 'WAREHOUSE_NOT_MAPPED',
            stage: 'resolve_warehouse',
            log: $log,
            itemNo: $itemNo,
            warehouseName: $warehouseName,
            severity: 'warning',
            expected: ['warehouse_must_exist' => true, 'name' => $warehouseName],
            actual: ['warehouse_found' => false],
            context: array_merge(['note' => "Gudang '{$warehouseName}' tidak ditemukan di tabel lokal"], $context ?? []),
            databaseSource: $databaseSource
        );
    }

    /**
     * Helper cepat untuk produk / SKU tidak ditemukan di ProductAccurate
     */
    public static function recordProductNotFound(
        ?string $itemNo,
        ?string $databaseSource = null,
        ?string $warehouseName = null,
        ?AccurateWebhookLog $log = null,
        ?array $context = null
    ): ?AccurateSyncIssue {
        return self::record(
            reasonCode: 'PRODUCT_NOT_FOUND',
            stage: 'resolve_product',
            log: $log,
            itemNo: $itemNo,
            warehouseName: $warehouseName,
            severity: 'warning',
            expected: ['product_must_exist' => true, 'item_no' => $itemNo],
            actual: ['product_found' => false],
            context: array_merge(['note' => "Produk SKU '{$itemNo}' belum terdaftar di ProductAccurate lokal ({$databaseSource})"], $context ?? []),
            databaseSource: $databaseSource
        );
    }

    /**
     * Helper cepat untuk kegagalan query database
     */
    public static function recordDbError(
        string $errorMsg,
        string $stage,
        ?string $itemNo = null,
        ?string $warehouseName = null,
        ?string $databaseSource = null,
        ?AccurateWebhookLog $log = null,
        ?array $context = null
    ): ?AccurateSyncIssue {
        return self::record(
            reasonCode: 'DB_WRITE_FAILED',
            stage: $stage,
            log: $log,
            itemNo: $itemNo,
            warehouseName: $warehouseName,
            severity: 'error',
            context: array_merge(['error' => $errorMsg], $context ?? []),
            databaseSource: $databaseSource
        );
    }

    /**
     * Helper cepat untuk format payload Accurate yang anomali / tidak ada item
     */
    public static function recordPayloadUnrecognized(
        array $payloadData,
        string $handlerName,
        ?AccurateWebhookLog $log = null
    ): ?AccurateSyncIssue {
        return self::record(
            reasonCode: 'PAYLOAD_UNRECOGNIZED',
            stage: 'parse_payload',
            log: $log,
            severity: 'warning',
            context: [
                'handler' => $handlerName,
                'sample_data' => array_slice($payloadData, 0, 5)
            ]
        );
    }

    /**
     * Helper cepat untuk event Accurate yang belum punya handler
     */
    public static function recordHandlerNotFound(string $eventType, AccurateWebhookLog $log): ?AccurateSyncIssue
    {
        return self::record(
            reasonCode: 'HANDLER_NOT_FOUND',
            stage: 'resolve_handler',
            log: $log,
            severity: 'info',
            context: ['event_type' => $eventType, 'note' => "Event '{$eventType}' belum dipetakan ke handler pemroses di sistem."]
        );
    }

    /**
     * Daftar label dan deskripsi human-readable untuk setiap reason code
     */
    public static function getReasonMeta(string $code): array
    {
        return match ($code) {
            'WAREHOUSE_NOT_MAPPED' => [
                'label' => 'Gudang Belum Terdaftar',
                'description' => 'Nama gudang dari Accurate tidak cocok dengan tabel gudang lokal.',
                'badge' => 'bg-amber-100 text-amber-800 border-amber-200',
            ],
            'PRODUCT_NOT_FOUND' => [
                'label' => 'SKU Belum Terdaftar',
                'description' => 'SKU/Item No dari Accurate belum ada di tabel Product Accurate lokal.',
                'badge' => 'bg-orange-100 text-orange-800 border-orange-200',
            ],
            'HANDLER_NOT_FOUND' => [
                'label' => 'Event Belum Diproses',
                'description' => 'Tipe webhook Accurate ini belum memiliki handler khusus untuk sinkronisasi.',
                'badge' => 'bg-blue-100 text-blue-800 border-blue-200',
            ],
            'DB_WRITE_FAILED' => [
                'label' => 'Gagal Tulis Database',
                'description' => 'Query penyimpanan stok atau nomor seri gagal dieksekusi di database.',
                'badge' => 'bg-rose-100 text-rose-800 border-rose-200',
            ],
            'API_ERROR' => [
                'label' => 'Koneksi API Accurate Gagal',
                'description' => 'Permintaan ke API Accurate mengalami timeout atau mengembalikan pesan error.',
                'badge' => 'bg-red-100 text-red-800 border-red-200',
            ],
            'SN_SYNC_FAILED' => [
                'label' => 'Sinkron SN Gagal',
                'description' => 'Proses penarikan / pembaruan nomor seri untuk SKU ini gagal.',
                'badge' => 'bg-red-100 text-red-800 border-red-200',
            ],
            'PAYLOAD_UNRECOGNIZED' => [
                'label' => 'Format Data Tidak Sesuai',
                'description' => 'Payload webhook tidak membawa detail nomor barang atau dokumen transaksi yang diharapkan.',
                'badge' => 'bg-purple-100 text-purple-800 border-purple-200',
            ],
            'JOB_EXCEPTION' => [
                'label' => 'Job Gagal / Crash',
                'description' => 'Terjadi error exception saat queue worker mengeksekusi job webhook.',
                'badge' => 'bg-red-100 text-red-800 border-red-200',
            ],
            default => [
                'label' => ucwords(str_replace('_', ' ', strtolower($code))),
                'description' => 'Kendala sinkronisasi data Accurate.',
                'badge' => 'bg-gray-100 text-gray-800 border-gray-200',
            ],
        };
    }
}
