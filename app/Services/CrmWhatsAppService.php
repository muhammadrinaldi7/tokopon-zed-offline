<?php

namespace App\Services;

use App\Models\MessageLog;
use App\Models\Order;
use App\Models\SellPhone;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrmWhatsAppService
{
    public const GATEWAY_CRM = 'crm';
    public const GATEWAY_QONTAK = 'qontak';
    public const GATEWAY_NONE = 'none';

    /**
     * Dapatkan gateway WhatsApp yang aktif saat ini ('crm', 'qontak', atau 'none').
     */
    public static function getActiveGateway(): string
    {
        $settingService = app(\App\Services\SettingService::class);
        return $settingService->get('whatsapp_active_gateway', config('services.crm_wa.default_gateway', self::GATEWAY_CRM));
    }

    /**
     * Set gateway WhatsApp yang aktif ('crm', 'qontak', atau 'none').
     */
    public static function setActiveGateway(string $gateway): void
    {
        $valid = in_array($gateway, [self::GATEWAY_CRM, self::GATEWAY_QONTAK, self::GATEWAY_NONE]) ? $gateway : self::GATEWAY_CRM;
        app(\App\Services\SettingService::class)->set('whatsapp_active_gateway', $valid, 'string');
    }

    public static function isWhatsAppEnabled(): bool
    {
        return self::getActiveGateway() !== self::GATEWAY_NONE;
    }

    public static function isCrmActive(): bool
    {
        return self::getActiveGateway() === self::GATEWAY_CRM;
    }

    public static function isQontakActive(): bool
    {
        return self::getActiveGateway() === self::GATEWAY_QONTAK;
    }

    public static function isAutoSendOrderEnabled(): bool
    {
        if (!self::isWhatsAppEnabled()) {
            return false;
        }
        return (bool) app(\App\Services\SettingService::class)->get('whatsapp_auto_send_order', config('services.crm_wa.auto_send_order', true));
    }

    public static function isAutoSendSellPhoneEnabled(): bool
    {
        if (!self::isWhatsAppEnabled()) {
            return false;
        }
        return (bool) app(\App\Services\SettingService::class)->get('whatsapp_auto_send_sellphone', config('services.crm_wa.auto_send_sellphone', true));
    }

    public static function isPaymentProofWaEnabled(): bool
    {
        if (!self::isWhatsAppEnabled()) {
            return false;
        }
        return (bool) app(\App\Services\SettingService::class)->get('whatsapp_enable_payment_proof', true);
    }

    /**
     * Cek status koneksi (Health Check) ke endpoint CRM WhatsApp Zed Group.
     */
    public function ping(): array
    {
        $apiUrl = config('services.crm_wa.api_url');
        $token = config('services.crm_wa.token');

        if (empty($apiUrl) || empty($token)) {
            return [
                'success' => false,
                'message' => 'Konfigurasi CRM_WA_API_URL atau CRM_WA_TOKEN belum diatur.',
            ];
        }

        // Endpoint ping: ambil base url lalu tambahkan /ping
        $pingUrl = rtrim($apiUrl, '/') . '/ping';

        try {
            $response = Http::withHeaders([
                'Authorization'             => 'Bearer ' . $token,
                'Accept'                    => 'application/json',
                'ngrok-skip-browser-warning' => '1',
                'User-Agent'                => 'ZedPOS/1.0',
            ])->timeout(10)->get($pingUrl);

            return [
                'success'     => $response->successful(),
                'status_code' => $response->status(),
                'response'    => $response->json() ?? $response->body(),
                'message'     => $response->successful() ? 'Koneksi ke CRM WhatsApp berhasil!' : 'CRM mengembalikan kode ' . $response->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal menghubungi CRM WhatsApp: ' . $e->getMessage(),
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Mengirim Struk Transaksi Penjualan (Order) via CRM WhatsApp Zed Group.
     */
    public function sendOrder(Order $order, string $pdfPublicUrl, string $filename, ?User $user = null): array
    {
        if (!config('services.crm_wa.enabled', true)) {
            return ['success' => false, 'message' => 'Integrasi CRM WhatsApp dinonaktifkan di konfigurasi.'];
        }

        $order->loadMissing(['user.profile', 'items.variant', 'branch', 'paymentMethod']);
        $user = $user ?: Auth::user();
        $phone = MessageDispatchService::formatPhoneNumber($order->user?->profile?->phone_number);
        $customerName = $order->user?->name ?: 'Customer';

        $dispatchService = app(MessageDispatchService::class);
        $contentSummary = $dispatchService->buildOrderContentSummary($order);

        if (!$phone) {
            $log = $this->createLog([
                'channel'          => 'whatsapp',
                'recipient'        => '-',
                'recipient_name'   => $customerName,
                'subject'          => 'Struk Penjualan #' . $order->order_number . ' (CRM WA)',
                'message_type'     => 'receipt_order',
                'content'          => $contentSummary,
                'attachment_url'   => $pdfPublicUrl,
                'attachment_name'  => $filename,
                'is_sent'          => false,
                'status'           => 'failed',
                'error_message'    => 'Nomor WhatsApp customer tidak ditemukan atau kosong.',
                'source'           => $order,
                'reference_number' => $order->order_number,
                'sent_by'          => $user?->id,
                'business_unit_id' => $order->business_unit_id ?: $user?->getActiveBusinessUnitId(),
                'branch_id'        => $order->branch_id ?: $user?->branch_id,
            ]);

            return ['success' => false, 'message' => 'Nomor HP customer tidak ditemukan.', 'log' => $log];
        }

        $templateId = config('services.crm_wa.template_id') ?: env('CRM_WA_TEMPLATE_ID', '380d1355-0a65-4dc5-be82-308ee7619910');
        $integrationId = config('services.crm_wa.channel_integration_id') ?: env('CRM_WA_CHANNEL_INTEGRATION_ID', '56b60c3c-0123-46af-958b-32f3ad12ee37');

        $payload = [
            'transaction_id'         => $order->order_number,
            'to_name'                => $customerName,
            'to_number'              => $phone,
            'message_template_id'    => $templateId,
            'channel_integration_id' => $integrationId,
            'language'               => ['code' => 'id'],
            'parameters'             => [
                'header' => [
                    'format' => 'DOCUMENT',
                    'params' => [
                        ['key' => 'url', 'value' => $pdfPublicUrl],
                        ['key' => 'filename', 'value' => $filename],
                    ],
                ],
                'buttons' => [],
                'body' => [
                    ['key' => '1', 'value' => 'nama', 'value_text' => $customerName],
                    ['key' => '2', 'value' => 'nota', 'value_text' => $order->order_number],
                    ['key' => '3', 'value' => 'total', 'value_text' => 'Rp ' . number_format($order->grand_total ?: $order->total_amount, 0, ',', '.')],
                ],
            ],
        ];

        $receiptPath = 'receipts/' . $filename;
        $localFilePath = \Illuminate\Support\Facades\Storage::disk('public')->exists($receiptPath)
            ? \Illuminate\Support\Facades\Storage::disk('public')->path($receiptPath)
            : null;

        $result = $this->dispatchCrmRequest($payload, $localFilePath, $filename);
        $isSent = $result['success'];

        $log = $this->createLog([
            'channel'          => 'whatsapp',
            'recipient'        => $phone,
            'recipient_name'   => $customerName,
            'subject'          => 'Struk Penjualan #' . $order->order_number . ' (CRM WA)',
            'message_type'     => 'receipt_order',
            'content'          => $contentSummary,
            'attachment_url'   => $pdfPublicUrl,
            'attachment_name'  => $filename,
            'payload'          => $payload,
            'response_payload' => $result['response'] ?? null,
            'is_sent'          => $isSent,
            'status'           => $isSent ? 'sent' : 'failed',
            'error_message'    => $result['error'] ?? null,
            'source'           => $order,
            'reference_number' => $order->order_number,
            'sent_by'          => $user?->id,
            'business_unit_id' => $order->business_unit_id ?: $user?->getActiveBusinessUnitId(),
            'branch_id'        => $order->branch_id ?: $user?->branch_id,
        ]);

        if ($isSent) {
            $order->update(['is_wa_sent' => true]);
        }

        return array_merge($result, ['log' => $log]);
    }

    /**
     * Mengirim Tanda Terima Transaksi Sell Phone (Buyback) via CRM WhatsApp Zed Group.
     */
    public function sendSellPhone(SellPhone $sellPhone, string $pdfPublicUrl, string $filename, ?User $user = null): array
    {
        if (!config('services.crm_wa.enabled', true)) {
            return ['success' => false, 'message' => 'Integrasi CRM WhatsApp dinonaktifkan di konfigurasi.'];
        }

        $sellPhone->loadMissing(['user.profile', 'branch', 'businessUnit']);
        $user = $user ?: Auth::user();
        $phone = MessageDispatchService::formatPhoneNumber($sellPhone->user?->profile?->phone_number);
        $customerName = $sellPhone->user?->name ?: 'Customer';
        $refNo = 'SPL-' . $sellPhone->id;

        $dispatchService = app(MessageDispatchService::class);
        $contentSummary = $dispatchService->buildSellPhoneContentSummary($sellPhone);

        if (!$phone) {
            $log = $this->createLog([
                'channel'          => 'whatsapp',
                'recipient'        => '-',
                'recipient_name'   => $customerName,
                'subject'          => 'Tanda Terima #' . $refNo . ' (CRM WA)',
                'message_type'     => 'receipt_sellphone',
                'content'          => $contentSummary,
                'attachment_url'   => $pdfPublicUrl,
                'attachment_name'  => $filename,
                'is_sent'          => false,
                'status'           => 'failed',
                'error_message'    => 'Nomor WhatsApp customer tidak ditemukan.',
                'source'           => $sellPhone,
                'reference_number' => $refNo,
                'sent_by'          => $user?->id,
                'business_unit_id' => $sellPhone->business_unit_id ?: $user?->getActiveBusinessUnitId(),
                'branch_id'        => $sellPhone->branch_id ?: $user?->branch_id,
            ]);

            return ['success' => false, 'message' => 'Nomor HP customer tidak ditemukan.', 'log' => $log];
        }

        $templateId = config('services.crm_wa.sellphone_template_id')
            ?: (config('services.crm_wa.template_id') ?: env('CRM_WA_TEMPLATE_ID', '380d1355-0a65-4dc5-be82-308ee7619910'));
        $integrationId = config('services.crm_wa.channel_integration_id')
            ?: env('CRM_WA_CHANNEL_INTEGRATION_ID', '56b60c3c-0123-46af-958b-32f3ad12ee37');

        $payload = [
            'transaction_id'         => $refNo,
            'to_name'                => $customerName,
            'to_number'              => $phone,
            'message_template_id'    => $templateId,
            'channel_integration_id' => $integrationId,
            'language'               => ['code' => 'id'],
            'parameters'             => [
                'header' => [
                    'format' => 'DOCUMENT',
                    'params' => [
                        ['key' => 'url', 'value' => $pdfPublicUrl],
                        ['key' => 'filename', 'value' => $filename],
                    ],
                ],
                'buttons' => [],
                'body' => [
                    ['key' => '1', 'value' => 'nama', 'value_text' => $customerName],
                    ['key' => '2', 'value' => 'nota', 'value_text' => $refNo],
                    ['key' => '3', 'value' => 'total', 'value_text' => 'Rp ' . number_format($sellPhone->appraised_value, 0, ',', '.')],
                ],
            ],
        ];

        $receiptPath = 'receipts_sellphone/' . $filename;
        $localFilePath = \Illuminate\Support\Facades\Storage::disk('public')->exists($receiptPath)
            ? \Illuminate\Support\Facades\Storage::disk('public')->path($receiptPath)
            : null;

        $result = $this->dispatchCrmRequest($payload, $localFilePath, $filename);
        $isSent = $result['success'];

        $log = $this->createLog([
            'channel'          => 'whatsapp',
            'recipient'        => $phone,
            'recipient_name'   => $customerName,
            'subject'          => 'Tanda Terima #' . $refNo . ' (CRM WA)',
            'message_type'     => 'receipt_sellphone',
            'content'          => $contentSummary,
            'attachment_url'   => $pdfPublicUrl,
            'attachment_name'  => $filename,
            'payload'          => $payload,
            'response_payload' => $result['response'] ?? null,
            'is_sent'          => $isSent,
            'status'           => $isSent ? 'sent' : 'failed',
            'error_message'    => $result['error'] ?? null,
            'source'           => $sellPhone,
            'reference_number' => $refNo,
            'sent_by'          => $user?->id,
            'business_unit_id' => $sellPhone->business_unit_id ?: $user?->getActiveBusinessUnitId(),
            'branch_id'        => $sellPhone->branch_id ?: $user?->branch_id,
        ]);

        if ($isSent) {
            $sellPhone->update(['is_wa_sent' => true]);
        }

        return array_merge($result, ['log' => $log]);
    }

    /**
     * Mengirim Bukti Pembayaran Sell Phone (Transfer) via CRM WhatsApp Zed Group.
     */
    public function sendSellPhonePaymentProof(SellPhone $sellPhone, string $documentUrl, string $filename, ?User $user = null): array
    {
        if (!config('services.crm_wa.enabled', true)) {
            return ['success' => false, 'message' => 'Integrasi CRM WhatsApp dinonaktifkan di konfigurasi.'];
        }

        $sellPhone->loadMissing(['user.profile', 'branch', 'businessUnit']);
        $user = $user ?: Auth::user();
        $phone = MessageDispatchService::formatPhoneNumber($sellPhone->user?->profile?->phone_number);
        $customerName = $sellPhone->user?->name ?: 'Customer';
        $refNo = 'SPL-' . $sellPhone->id;

        $contentSummary = "BUKTI PEMBAYARAN SELL PHONE\n" .
            "No. Transaksi: {$refNo}\n" .
            "Customer: {$customerName}\n" .
            "Perangkat: {$sellPhone->phone_brand} {$sellPhone->phone_model}\n" .
            "Total Transfer: Rp " . number_format($sellPhone->appraised_value, 0, ',', '.') . "\n" .
            "Rekening Tujuan: {$sellPhone->bank_name} - {$sellPhone->bank_account_number} a/n {$sellPhone->bank_account_name}";

        if (!$phone) {
            $log = $this->createLog([
                'channel'          => 'whatsapp',
                'recipient'        => '-',
                'recipient_name'   => $customerName,
                'subject'          => 'Bukti Pembayaran #' . $refNo . ' (CRM WA)',
                'message_type'     => 'payment_proof',
                'content'          => $contentSummary,
                'attachment_url'   => $documentUrl,
                'attachment_name'  => $filename,
                'is_sent'          => false,
                'status'           => 'failed',
                'error_message'    => 'Nomor WhatsApp customer tidak ditemukan.',
                'source'           => $sellPhone,
                'reference_number' => $refNo,
                'sent_by'          => $user?->id,
                'business_unit_id' => $sellPhone->business_unit_id ?: $user?->getActiveBusinessUnitId(),
                'branch_id'        => $sellPhone->branch_id ?: $user?->branch_id,
            ]);

            return ['success' => false, 'message' => 'Nomor HP customer tidak ditemukan.', 'log' => $log];
        }

        $templateId = config('services.crm_wa.sellphone_template_id')
            ?: (config('services.crm_wa.template_id') ?: env('CRM_WA_TEMPLATE_ID', '380d1355-0a65-4dc5-be82-308ee7619910'));
        $integrationId = config('services.crm_wa.channel_integration_id')
            ?: env('CRM_WA_CHANNEL_INTEGRATION_ID', '56b60c3c-0123-46af-958b-32f3ad12ee37');

        $payload = [
            'transaction_id'         => $refNo . '-PAY',
            'to_name'                => $customerName,
            'to_number'              => $phone,
            'message_template_id'    => $templateId,
            'channel_integration_id' => $integrationId,
            'language'               => ['code' => 'id'],
            'parameters'             => [
                'header' => [
                    'format' => 'DOCUMENT',
                    'params' => [
                        ['key' => 'url', 'value' => $documentUrl],
                        ['key' => 'filename', 'value' => $filename],
                    ],
                ],
                'buttons' => [],
                'body' => [
                    ['key' => '1', 'value' => 'nama', 'value_text' => $customerName],
                    ['key' => '2', 'value' => 'nota', 'value_text' => $refNo],
                    ['key' => '3', 'value' => 'total', 'value_text' => 'Rp ' . number_format($sellPhone->appraised_value, 0, ',', '.')],
                ],
            ],
        ];

        // Deteksi berkas lokal (PDF atau gambar bukti bayar) untuk multipart upload (menghindari error SSRF localhost)
        $localFilePath = null;
        $pdfPath = 'payment_receipts/pdf_bukti_' . $sellPhone->id . '.pdf';
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($pdfPath)) {
            $localFilePath = \Illuminate\Support\Facades\Storage::disk('public')->path($pdfPath);
        } elseif ($sellPhone->payment_receipt_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($sellPhone->payment_receipt_path)) {
            $localFilePath = \Illuminate\Support\Facades\Storage::disk('public')->path($sellPhone->payment_receipt_path);
        } elseif ($sellPhone->payment_receipt_path && file_exists(storage_path('app/public/' . $sellPhone->payment_receipt_path))) {
            $localFilePath = storage_path('app/public/' . $sellPhone->payment_receipt_path);
        }

        $result = $this->dispatchCrmRequest($payload, $localFilePath, $filename);
        $isSent = $result['success'];

        $log = $this->createLog([
            'channel'          => 'whatsapp',
            'recipient'        => $phone,
            'recipient_name'   => $customerName,
            'subject'          => 'Bukti Pembayaran #' . $refNo . ' (CRM WA)',
            'message_type'     => 'payment_proof',
            'content'          => $contentSummary,
            'attachment_url'   => $documentUrl,
            'attachment_name'  => $filename,
            'payload'          => $payload,
            'response_payload' => $result['response'] ?? null,
            'is_sent'          => $isSent,
            'status'           => $isSent ? 'sent' : 'failed',
            'error_message'    => $result['error'] ?? null,
            'source'           => $sellPhone,
            'reference_number' => $refNo,
            'sent_by'          => $user?->id,
            'business_unit_id' => $sellPhone->business_unit_id ?: $user?->getActiveBusinessUnitId(),
            'branch_id'        => $sellPhone->branch_id ?: $user?->branch_id,
        ]);

        return array_merge($result, ['log' => $log]);
    }

    /**
     * Eksekusi HTTP Request ke API CRM WhatsApp Zed Group.
     */
    protected function dispatchCrmRequest(array $payload, ?string $filePath = null, ?string $filename = null): array
    {
        $apiUrl = config('services.crm_wa.api_url');
        $token = config('services.crm_wa.token');

        if (empty($apiUrl)) {
            return [
                'success' => false,
                'message' => 'URL CRM WhatsApp tidak ditemukan di konfigurasi.',
                'error'   => 'Empty CRM_WA_API_URL',
            ];
        }

        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Token CRM WhatsApp tidak ditemukan di konfigurasi.',
                'error'   => 'Empty CRM_WA_TOKEN',
            ];
        }

        if (!preg_match("~^(?:f|ht)tps?://~i", $apiUrl)) {
            $apiUrl = "https://" . $apiUrl;
        }

        try {
            $request = Http::withHeaders([
                'Authorization'             => 'Bearer ' . $token,
                'Accept'                    => 'application/json',
                'ngrok-skip-browser-warning' => '1',
                'User-Agent'                => 'ZedPOS/1.0',
            ])->timeout(25);

            // Opsi B (Upload File Langsung via Multipart/form-data):
            // Jika file PDF fisik tersedia di disk lokal, upload langsung agar server CRM
            // tidak perlu mendownload ke alamat internal/localhost yang akan ditolak (Error 400).
            if ($filePath && file_exists($filePath)) {
                $response = $request->attach(
                    'file',
                    file_get_contents($filePath),
                    $filename ?: basename($filePath),
                    ['Content-Type' => 'application/pdf']
                )->post($apiUrl, [
                    'payload' => json_encode($payload),
                ]);
            } else {
                // Opsi A: Request JSON standar jika file lokal tidak ditemukan
                $response = $request->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($apiUrl, $payload);
            }

            $body = $response->json();

            // Status 200 OK
            if ($response->successful()) {
                // Tangani jika CRM mendeteksi pesan duplikat (idempotent)
                if (!empty($body['duplicate'])) {
                    return [
                        'success'      => true,
                        'is_duplicate' => true,
                        'message'      => 'Nota sudah pernah dikirim sebelumnya (Duplikasi dicegah).',
                        'response'     => $body,
                    ];
                }

                // Cek status internal dalam data[0] (pola Qontak: request valid tapi WA gagal)
                $firstItemStatus = $body['data'][0]['status'] ?? 'sent';
                if ($firstItemStatus === 'failed') {
                    $waErrMsg = $body['data'][0]['whatsapp_error_message'] ?? 'Pesan gagal dikirim oleh gateway WhatsApp.';
                    return [
                        'success'  => false,
                        'message'  => $waErrMsg,
                        'error'    => $waErrMsg,
                        'response' => $body,
                    ];
                }

                return [
                    'success'  => true,
                    'message'  => 'Nota berhasil terkirim ke WhatsApp CRM!',
                    'response' => $body,
                ];
            }

            // HTTP 4xx / 5xx
            $errorDetail = $body['error']['messages'][0] ?? ($body['error']['detail'] ?? $response->body());
            Log::channel('pos_accurate')->error('CRM WhatsApp API Error: ' . $response->status() . ' - ' . $response->body());

            return [
                'success'  => false,
                'message'  => 'Gagal API CRM (Code ' . $response->status() . '): ' . $errorDetail,
                'error'    => 'HTTP ' . $response->status() . ': ' . $response->body(),
                'response' => $body ?? ['status' => $response->status(), 'raw' => $response->body()],
            ];
        } catch (\Throwable $e) {
            Log::channel('pos_accurate')->error('CRM WhatsApp Request Exception: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Koneksi ke CRM WhatsApp gagal: ' . $e->getMessage(),
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Menyimpan riwayat pengiriman ke tabel message_logs.
     */
    protected function createLog(array $attributes): MessageLog
    {
        $source = $attributes['source'] ?? null;
        unset($attributes['source']);

        if ($source) {
            $attributes['source_type'] = get_class($source);
            $attributes['source_id'] = $source->id;
        }

        $attributes['sent_at'] = $attributes['sent_at'] ?? now();

        return MessageLog::create($attributes);
    }
}
