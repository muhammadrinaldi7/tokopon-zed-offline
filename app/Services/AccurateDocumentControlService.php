<?php

namespace App\Services;

use App\Models\AccurateDocumentAuditLog;
use App\Models\Order;
use App\Models\OrderAccurateDoc;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AccurateDocumentControlService
{
    protected AccurateService $accurateService;
    protected OrderAccurateSyncService $syncService;

    public function __construct(AccurateService $accurateService, OrderAccurateSyncService $syncService)
    {
        $this->accurateService = $accurateService;
        $this->syncService = $syncService;
    }

    /**
     * Mengambil perbandingan live antara data lokal sistem vs data di Accurate API
     */
    public function getLiveDocumentDiff(Order $order): array
    {
        $dbSource = $order->businessUnit?->accurate_database_source ?? 'syihab';

        // 1. Data Lokal
        $localData = [
            'order_number'        => $order->order_number,
            'order_date'          => $order->order_date ? Carbon::parse($order->order_date)->format('Y-m-d') : Carbon::parse($order->created_at)->format('Y-m-d'),
            'grand_total'         => (float) $order->grand_total,
            'customer_name'       => $order->user?->name ?? 'Pelanggan Umum',
            'branch'              => $order->branch?->name ?? ($order->shipping_address_snapshot['store'] ?? '-'),
            'accurate_invoice_no' => $order->accurate_invoice_no,
            'accurate_receipt_no' => $order->accurate_receipt_no,
            'accurate_sync_status'=> $order->accurate_sync_status,
        ];

        // 2. Data Live Accurate
        $liveInvoice = null;
        $liveReceipt = null;
        $discrepancies = [];

        // Ambil Faktur (Sales Invoice)
        $invoiceDoc = $order->accurateDocs()->where('doc_type', 'SALES_INVOICE')->first();
        $targetInvoiceLookup = $invoiceDoc?->accurate_id ?: $order->accurate_invoice_no;

        if ($targetInvoiceLookup) {
            try {
                $liveInvoice = $this->accurateService->getSalesInvoiceDetail($targetInvoiceLookup, $dbSource);
            } catch (\Throwable $e) {
                Log::warning("Gagal fetch live invoice untuk Order #{$order->order_number}: " . $e->getMessage());
            }
        }

        // Ambil Pelunasan (Sales Receipt)
        $receiptDoc = $order->accurateDocs()->where('doc_type', 'SALES_RECEIPT')->first();
        $targetReceiptLookup = $receiptDoc?->accurate_id ?: $order->accurate_receipt_no;

        if ($targetReceiptLookup) {
            try {
                $liveReceipt = $this->accurateService->getDetailSalesReceipt($targetReceiptLookup, $dbSource);
            } catch (\Throwable $e) {
                Log::warning("Gagal fetch live receipt untuk Order #{$order->order_number}: " . $e->getMessage());
            }
        }

        // 3. Evaluasi Perbedaan (Discrepancy)
        if ($liveInvoice) {
            // Bandingkan Tanggal Faktur
            // Format transDate di Accurate: d/m/Y
            $accDateFormatted = null;
            if (!empty($liveInvoice['transDate'])) {
                try {
                    $accDateFormatted = Carbon::createFromFormat('d/m/Y', $liveInvoice['transDate'])->format('Y-m-d');
                } catch (\Throwable $e) {
                    $accDateFormatted = $liveInvoice['transDate'];
                }
            }

            if ($accDateFormatted && $accDateFormatted !== $localData['order_date']) {
                $discrepancies['date'] = [
                    'label'    => 'Tanggal Transaksi',
                    'local'    => $localData['order_date'],
                    'accurate' => $accDateFormatted,
                ];
            }

            // Bandingkan Nominal Faktur
            $accTotal = (float) ($liveInvoice['totalAmount'] ?? 0);
            if (abs($accTotal - $localData['grand_total']) > 1) { // Toleransi pembulatan < 1 rupiah
                $discrepancies['amount'] = [
                    'label'    => 'Total Tagihan',
                    'local'    => $localData['grand_total'],
                    'accurate' => $accTotal,
                ];
            }

            // Bandingkan Nomor Faktur
            $accNumber = $liveInvoice['number'] ?? null;
            if ($accNumber && $order->accurate_invoice_no && $accNumber !== $order->accurate_invoice_no) {
                $discrepancies['invoice_number'] = [
                    'label'    => 'Nomor Faktur',
                    'local'    => $order->accurate_invoice_no,
                    'accurate' => $accNumber,
                ];
            }
        }

        return [
            'has_discrepancy'  => !empty($discrepancies),
            'discrepancies'    => $discrepancies,
            'local'            => $localData,
            'accurate_invoice' => $liveInvoice,
            'accurate_receipt' => $liveReceipt,
        ];
    }

    /**
     * Memperbarui tanggal transaksi di sistem dan otomatis sinkron ke Accurate
     */
    public function updateTransactionDate(Order $order, string $newDate, string $reason, int $userId): array
    {
        $dbSource = $order->businessUnit?->accurate_database_source ?? 'syihab';
        $oldDate = $order->order_date ? Carbon::parse($order->order_date)->format('Y-m-d') : Carbon::parse($order->created_at)->format('Y-m-d');
        $newCarbon = Carbon::parse($newDate);
        $newDateFormattedAccurate = $newCarbon->format('d/m/Y');

        if (empty(trim($reason))) {
            throw new Exception("Alasan perubahan tanggal wajib diisi untuk kebutuhan audit.");
        }

        $invoiceDoc = $order->accurateDocs()->where('doc_type', 'SALES_INVOICE')->first();
        $receiptDoc = $order->accurateDocs()->where('doc_type', 'SALES_RECEIPT')->first();

        $invoiceId = $invoiceDoc?->accurate_id;
        $receiptId = $receiptDoc?->accurate_id;

        $auditLogData = [
            'order_id'          => $order->id,
            'business_unit_id'  => $order->business_unit_id,
            'document_type'     => 'SALES_INVOICE',
            'action_type'       => 'UPDATE_DATE',
            'target_doc_number' => $order->accurate_invoice_no,
            'old_values'        => ['order_date' => $oldDate, 'invoice_no' => $order->accurate_invoice_no, 'receipt_no' => $order->accurate_receipt_no],
            'new_values'        => ['order_date' => $newDate],
            'reason'            => $reason,
            'performed_by'      => $userId,
        ];

        DB::beginTransaction();
        try {
            // JIKA FAKTUR SUDAH ADA DI ACCURATE:
            if ($order->accurate_invoice_no || $invoiceId) {
                // Skenario 1: Ada Sales Receipt (Pelunasan) di Accurate
                // Di Accurate, Faktur yang sudah lunas tidak bisa diubah tanggalnya sebelum pelunasan dihapus.
                $savedReceiptDetail = null;
                if ($receiptId || $order->accurate_receipt_no) {
                    $receiptLookup = $receiptId ?: $order->accurate_receipt_no;
                    try {
                        $savedReceiptDetail = $this->accurateService->getDetailSalesReceipt($receiptLookup, $dbSource);
                    } catch (\Throwable $e) {
                        Log::warning("Gagal ambil detail receipt lama saat update tanggal: " . $e->getMessage());
                    }

                    // Hapus receipt lama di Accurate
                    if (!empty($savedReceiptDetail['id'])) {
                        try {
                            $this->accurateService->deleteSalesReceipt($savedReceiptDetail['id'], $dbSource);
                        } catch (\Throwable $e) {
                            throw new Exception("Gagal menghapus pelunasan Accurate sebelum ubah tanggal faktur: " . $e->getMessage());
                        }
                    }
                }

                // Skenario 2: Update tanggal pada Sales Invoice di Accurate
                // Ambil detail Sales Invoice untuk memastikan ID dan nomor dokumen
                $invoiceLookup = $invoiceId ?: $order->accurate_invoice_no;
                $liveInvoice = $this->accurateService->getSalesInvoiceDetail($invoiceLookup, $dbSource);

                if (!$liveInvoice || empty($liveInvoice['id'])) {
                    throw new Exception("Faktur #{$order->accurate_invoice_no} tidak ditemukan di Accurate.");
                }

                $invoicePayload = [
                    'id'        => $liveInvoice['id'],
                    'transDate' => $newDateFormattedAccurate,
                ];

                list($host, $token, $secretKey) = $this->accurateService->getCredentials($dbSource);
                $timestamp = now()->toIso8601String();
                $signature = hash_hmac('sha256', $timestamp, $secretKey);

                $invoiceResponse = Http::timeout(30)->retry(2, 500)->withHeaders([
                    'Authorization'   => 'Bearer ' . $token,
                    'X-Api-Timestamp' => $timestamp,
                    'X-Api-Signature' => $signature,
                    'Content-Type'    => 'application/json',
                ])->post($host . '/sales-invoice/save.do', $invoicePayload);

                if (!$invoiceResponse->successful() || (isset($invoiceResponse['s']) && $invoiceResponse['s'] === false)) {
                    $err = $invoiceResponse['d'] ?? $invoiceResponse->body();
                    $msg = is_array($err) ? implode(', ', $err) : $err;
                    throw new Exception("Gagal memperbarui tanggal faktur di Accurate: {$msg}");
                }

                // Skenario 3: Terbitkan ulang Sales Receipt jika sebelumnya ada
                if ($savedReceiptDetail) {
                    $bankNo = $savedReceiptDetail['bank']['no'] ?? null;
                    if (!$bankNo && !empty($savedReceiptDetail['bankNo'])) {
                        $bankNo = $savedReceiptDetail['bankNo'];
                    }

                    if ($bankNo) {
                        $receiptPayload = [
                            'transDate'    => $newDateFormattedAccurate,
                            'customerNo'   => $liveInvoice['customer']['customerNo'] ?? $order->user?->accurate_customer_id,
                            'bankNo'       => $bankNo,
                            'branchName'   => $liveInvoice['branch']['name'] ?? ($order->branch?->name ?? 'Cabang Utama'),
                            'description'  => "Pelunasan Faktur {$liveInvoice['number']} (Koreksi Tanggal)",
                            'detailInvoice'=> [
                                [
                                    'invoiceNo'   => $liveInvoice['number'],
                                    'paymentAmount' => (float) ($savedReceiptDetail['totalPaymentAmount'] ?? $order->grand_total),
                                ]
                            ]
                        ];

                        $receiptRes = $this->accurateService->postSalesReceipt($receiptPayload, $dbSource);
                        if (isset($receiptRes['r']) && !empty($receiptRes['r']['id'])) {
                            $newReceiptId = $receiptRes['r']['id'];
                            $newReceiptNo = $receiptRes['r']['number'] ?? $order->accurate_receipt_no;

                            $order->accurate_receipt_no = $newReceiptNo;

                            if ($receiptDoc) {
                                $receiptDoc->update([
                                    'accurate_id' => $newReceiptId,
                                    'doc_number'  => $newReceiptNo,
                                    'status'      => 'SUCCESS',
                                ]);
                            }
                        }
                    }
                }
            }

            // 4. Update data di database lokal
            $newDateTime = $newCarbon->setTimeFrom(Carbon::parse($order->created_at))->format('Y-m-d H:i:s');
            $order->order_date = $newDateTime;
            $order->created_at = $newDateTime;
            $order->save();

            // 5. Catat ke Audit Log
            $auditLogData['status'] = 'SUCCESS';
            AccurateDocumentAuditLog::create($auditLogData);

            DB::commit();

            return [
                'success' => true,
                'message' => "Tanggal transaksi berhasil diubah menjadi {$newDate} di sistem dan Accurate.",
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            $auditLogData['status'] = 'FAILED';
            $auditLogData['error_message'] = $e->getMessage();
            AccurateDocumentAuditLog::create($auditLogData);

            Log::error("Gagal update tanggal transaksi Order #{$order->order_number}: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Menautkan ulang nomor dokumen Accurate yang valid ke Order lokal (Re-Link)
     */
    public function relinkDocument(Order $order, string $docType, string $newDocNumber, string $reason, int $userId): array
    {
        $dbSource = $order->businessUnit?->accurate_database_source ?? 'syihab';
        $newDocNumber = trim($newDocNumber);

        if (empty($newDocNumber)) {
            throw new Exception("Nomor dokumen tidak boleh kosong.");
        }
        if (empty(trim($reason))) {
            throw new Exception("Alasan penautan ulang wajib diisi.");
        }

        $oldValues = [
            'doc_type'            => $docType,
            'accurate_invoice_no' => $order->accurate_invoice_no,
            'accurate_receipt_no' => $order->accurate_receipt_no,
        ];

        // 1. Verifikasi keberadaan dokumen di Accurate API
        $accurateId = null;
        $accurateNumber = $newDocNumber;
        $amount = (float) $order->grand_total;

        if ($docType === 'SALES_INVOICE') {
            $invoice = $this->accurateService->getSalesInvoiceDetail($newDocNumber, $dbSource);
            if (!$invoice || empty($invoice['id'])) {
                throw new Exception("Faktur #{$newDocNumber} tidak ditemukan di database Accurate ({$dbSource}). Pastikan nomor faktur sudah benar.");
            }
            $accurateId = $invoice['id'];
            $accurateNumber = $invoice['number'] ?? $newDocNumber;
            $amount = (float) ($invoice['totalAmount'] ?? $order->grand_total);
        } elseif ($docType === 'SALES_RECEIPT') {
            $receipt = $this->accurateService->getDetailSalesReceipt($newDocNumber, $dbSource);
            if (!$receipt || empty($receipt['id'])) {
                throw new Exception("Pelunasan #{$newDocNumber} tidak ditemukan di database Accurate ({$dbSource}). Pastikan nomor bukti sudah benar.");
            }
            $accurateId = $receipt['id'];
            $accurateNumber = $receipt['number'] ?? $newDocNumber;
            $amount = (float) ($receipt['totalPaymentAmount'] ?? $order->grand_total);
        } else {
            throw new Exception("Tipe dokumen {$docType} tidak didukung untuk re-link manual.");
        }

        DB::beginTransaction();
        try {
            // 2. Update kolom di tabel orders
            if ($docType === 'SALES_INVOICE') {
                $order->accurate_invoice_no = $accurateNumber;
                $order->accurate_sync_status = 'SYNCED';
                $order->accurate_synced_at = now();
            } elseif ($docType === 'SALES_RECEIPT') {
                $order->accurate_receipt_no = $accurateNumber;
            }
            $order->save();

            // 3. Update atau buat record di order_accurate_docs
            OrderAccurateDoc::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'doc_type' => $docType,
                ],
                [
                    'doc_number'  => $accurateNumber,
                    'accurate_id' => $accurateId,
                    'amount'      => $amount,
                    'status'      => 'SUCCESS',
                ]
            );

            // 4. Catat Audit Log
            AccurateDocumentAuditLog::create([
                'order_id'          => $order->id,
                'business_unit_id'  => $order->business_unit_id,
                'document_type'     => $docType,
                'action_type'       => 'RELINK_DOC',
                'target_doc_number' => $accurateNumber,
                'old_values'        => $oldValues,
                'new_values'        => ['doc_number' => $accurateNumber, 'accurate_id' => $accurateId],
                'reason'            => $reason,
                'status'            => 'SUCCESS',
                'performed_by'      => $userId,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => "Dokumen {$docType} berhasil ditautkan ke {$accurateNumber} (Accurate ID: {$accurateId}).",
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Gagal Re-Link Dokumen Order #{$order->order_number}: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Memutus tautan dokumen Accurate dari Order lokal (Unlink)
     */
    public function unlinkDocument(Order $order, string $docType, string $reason, int $userId): array
    {
        if (empty(trim($reason))) {
            throw new Exception("Alasan pemutusan tautan wajib diisi.");
        }

        $oldValues = [
            'doc_type'            => $docType,
            'accurate_invoice_no' => $order->accurate_invoice_no,
            'accurate_receipt_no' => $order->accurate_receipt_no,
        ];

        DB::beginTransaction();
        try {
            if ($docType === 'SALES_INVOICE') {
                $order->accurate_invoice_no = null;
                $order->accurate_sync_status = 'PENDING';
            } elseif ($docType === 'SALES_RECEIPT') {
                $order->accurate_receipt_no = null;
            }
            $order->save();

            // Update status di order_accurate_docs
            $order->accurateDocs()->where('doc_type', $docType)->update([
                'status' => 'UNLINKED',
            ]);

            AccurateDocumentAuditLog::create([
                'order_id'          => $order->id,
                'business_unit_id'  => $order->business_unit_id,
                'document_type'     => $docType,
                'action_type'       => 'UNLINK_DOC',
                'target_doc_number' => $oldValues['accurate_invoice_no'] ?? null,
                'old_values'        => $oldValues,
                'new_values'        => ['status' => 'UNLINKED'],
                'reason'            => $reason,
                'status'            => 'SUCCESS',
                'performed_by'      => $userId,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => "Tautan dokumen {$docType} berhasil diputus.",
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Melakukan rollback penuh dokumen di Accurate dan langsung sinkronisasi ulang bersih
     */
    public function rollbackAndResync(Order $order, string $reason, int $userId): array
    {
        if (empty(trim($reason))) {
            throw new Exception("Alasan rollback dan re-sync wajib diisi.");
        }

        $oldValues = [
            'invoice_no' => $order->accurate_invoice_no,
            'receipt_no' => $order->accurate_receipt_no,
        ];

        try {
            // 1. Eksekusi rollback di Accurate melalui AccurateService
            $this->accurateService->rollbackOrderDocuments($order);

            // 2. Reset nomor dokumen lokal
            $order->update([
                'accurate_invoice_no' => null,
                'accurate_receipt_no' => null,
                'accurate_sync_status'=> 'PENDING',
            ]);

            // 3. Picu sinkronisasi ulang
            $syncResult = $this->syncService->syncOrder($order, 'MANUAL_ADMIN', $userId);

            // 4. Catat Audit Log
            AccurateDocumentAuditLog::create([
                'order_id'          => $order->id,
                'business_unit_id'  => $order->business_unit_id,
                'document_type'     => 'ALL',
                'action_type'       => 'ROLLBACK_AND_RESYNC',
                'target_doc_number' => $syncResult['invoice_no'] ?? null,
                'old_values'        => $oldValues,
                'new_values'        => $syncResult,
                'reason'            => $reason,
                'status'            => $syncResult['success'] ? 'SUCCESS' : 'FAILED',
                'error_message'     => $syncResult['error'] ?? null,
                'performed_by'      => $userId,
            ]);

            if ($syncResult['success']) {
                return [
                    'success' => true,
                    'message' => "Rollback & Sinkronisasi Ulang Berhasil! Nomor Faktur Baru: {$syncResult['invoice_no']}",
                ];
            } else {
                return [
                    'success' => false,
                    'error'   => "Rollback berhasil, namun sinkronisasi ulang gagal: " . ($syncResult['error'] ?? 'Terjadi kesalahan.'),
                ];
            }
        } catch (\Throwable $e) {
            Log::error("Rollback & Resync Gagal untuk Order #{$order->order_number}: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }
}

