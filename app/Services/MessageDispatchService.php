<?php

namespace App\Services;

use App\Mail\SalesReceiptMail;
use App\Mail\SellPhonePaymentReceiptMail;
use App\Mail\SellPhoneReceiptMail;
use App\Models\MessageLog;
use App\Models\Order;
use App\Models\SellPhone;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MessageDispatchService
{
    /**
     * Standardisasi nomor telepon (08xx / 8xx -> 628xx).
     */
    public static function formatPhoneNumber(?string $phone): ?string
    {
        if (!$phone) return null;
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            return '62' . $phone;
        }
        return $phone;
    }

    /**
     * Mengirim Struk Transaksi Penjualan (Order) via WhatsApp Qontak.
     */
    public function sendOrderWhatsApp(Order $order, string $pdfPublicUrl, string $filename, ?User $user = null): array
    {
        $order->loadMissing(['user.profile', 'items.variant', 'branch', 'paymentMethod']);
        $user = $user ?: Auth::user();
        $phone = self::formatPhoneNumber($order->user?->profile?->phone_number);
        $customerName = $order->user?->name ?: 'Customer';

        if (!$phone) {
            $this->createLog([
                'channel' => 'whatsapp',
                'recipient' => '-',
                'recipient_name' => $customerName,
                'subject' => 'Struk Pembelian (Qontak WA)',
                'message_type' => 'receipt_order',
                'content' => $this->buildOrderContentSummary($order),
                'attachment_url' => $pdfPublicUrl,
                'attachment_name' => $filename,
                'is_sent' => false,
                'status' => 'failed',
                'error_message' => 'Nomor WhatsApp customer tidak ditemukan atau kosong.',
                'source' => $order,
                'reference_number' => $order->order_number,
                'sent_by' => $user?->id,
                'business_unit_id' => $order->business_unit_id ?: $user?->getActiveBusinessUnitId(),
                'branch_id' => $order->branch_id ?: $user?->branch_id,
            ]);

            return ['success' => false, 'message' => 'Nomor HP customer tidak ditemukan.'];
        }

        $templateId = config('services.qontak.template_id') ?: env('QONTAK_TEMPLATE_ID');
        $integrationId = config('services.qontak.integration_id') ?: env('QONTAK_CHANNEL_INTEGRATION_ID');

        $payload = [
            'to_name' => $customerName,
            'to_number' => $phone,
            'channel_integration_id' => $integrationId,
            'message_template_id' => $templateId,
            'language' => ['code' => 'id'],
            'parameters' => [
                'header' => [
                    'format' => 'DOCUMENT',
                    'params' => [
                        ['key' => 'url', 'value' => $pdfPublicUrl],
                        ['key' => 'filename', 'value' => $filename]
                    ]
                ],
                'body' => [
                    ['key' => '1', 'value' => 'nama', 'value_text' => $customerName],
                    ['key' => '2', 'value' => 'no_invoice', 'value_text' => $order->order_number],
                    ['key' => '3', 'value' => 'total_tagihan', 'value_text' => 'Rp ' . number_format($order->grand_total ?: $order->total_amount, 0, ',', '.')]
                ]
            ]
        ];

        $contentSummary = $this->buildOrderContentSummary($order);
        $result = $this->dispatchQontakRequest($payload);

        $isSent = $result['success'];
        $log = $this->createLog([
            'channel' => 'whatsapp',
            'recipient' => $phone,
            'recipient_name' => $customerName,
            'subject' => 'Struk Penjualan #' . $order->order_number,
            'message_type' => 'receipt_order',
            'content' => $contentSummary,
            'attachment_url' => $pdfPublicUrl,
            'attachment_name' => $filename,
            'payload' => $payload,
            'response_payload' => $result['response'] ?? null,
            'is_sent' => $isSent,
            'status' => $isSent ? 'sent' : 'failed',
            'error_message' => $result['error'] ?? null,
            'source' => $order,
            'reference_number' => $order->order_number,
            'sent_by' => $user?->id,
            'business_unit_id' => $order->business_unit_id ?: $user?->getActiveBusinessUnitId(),
            'branch_id' => $order->branch_id ?: $user?->branch_id,
        ]);

        if ($isSent) {
            $order->update(['is_wa_sent' => true]);
        }

        return array_merge($result, ['log' => $log]);
    }

    /**
     * Mengirim Struk Transaksi Penjualan (Order) via Email.
     */
    public function sendOrderEmail(Order $order, string $pdfContent, string $filename, ?User $user = null): array
    {
        $order->loadMissing(['user', 'items.variant', 'branch', 'paymentMethod']);
        $user = $user ?: Auth::user();
        $email = $order->user?->email;
        $customerName = $order->user?->name ?: 'Customer';

        if (!$email || str_contains($email, '@pos.tokopun.com') || str_contains($email, '@tokopon.com')) {
            $this->createLog([
                'channel' => 'email',
                'recipient' => $email ?: '-',
                'recipient_name' => $customerName,
                'subject' => 'Struk Pembelian #' . $order->order_number,
                'message_type' => 'receipt_order',
                'content' => $this->buildOrderContentSummary($order),
                'attachment_name' => $filename,
                'is_sent' => false,
                'status' => 'failed',
                'error_message' => 'Alamat email tidak valid atau dummy sistem POS.',
                'source' => $order,
                'reference_number' => $order->order_number,
                'sent_by' => $user?->id,
                'business_unit_id' => $order->business_unit_id ?: $user?->getActiveBusinessUnitId(),
                'branch_id' => $order->branch_id ?: $user?->branch_id,
            ]);

            return ['success' => false, 'message' => 'Email customer tidak valid atau kosong.'];
        }

        $contentSummary = $this->buildOrderContentSummary($order);

        try {
            $mailer = config('mail.mailers.pos_sales.host') ? Mail::mailer('pos_sales') : Mail::mailer();
            $mailer->to($email)->send(new SalesReceiptMail($order, $pdfContent, $filename));

            $log = $this->createLog([
                'channel' => 'email',
                'recipient' => $email,
                'recipient_name' => $customerName,
                'subject' => 'Struk Pembelian #' . $order->order_number,
                'message_type' => 'receipt_order',
                'content' => $contentSummary,
                'attachment_name' => $filename,
                'is_sent' => true,
                'status' => 'sent',
                'source' => $order,
                'reference_number' => $order->order_number,
                'sent_by' => $user?->id,
                'business_unit_id' => $order->business_unit_id ?: $user?->getActiveBusinessUnitId(),
                'branch_id' => $order->branch_id ?: $user?->branch_id,
            ]);

            $order->update(['is_email_sent' => true]);

            return ['success' => true, 'message' => 'Struk digital telah dikirim ke ' . $email, 'log' => $log];
        } catch (\Exception $e) {
            Log::error('Order Email Send Error: ' . $e->getMessage());

            $log = $this->createLog([
                'channel' => 'email',
                'recipient' => $email,
                'recipient_name' => $customerName,
                'subject' => 'Struk Pembelian #' . $order->order_number,
                'message_type' => 'receipt_order',
                'content' => $contentSummary,
                'attachment_name' => $filename,
                'is_sent' => false,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'source' => $order,
                'reference_number' => $order->order_number,
                'sent_by' => $user?->id,
                'business_unit_id' => $order->business_unit_id ?: $user?->getActiveBusinessUnitId(),
                'branch_id' => $order->branch_id ?: $user?->branch_id,
            ]);

            return ['success' => false, 'message' => 'Koneksi SMTP bermasalah: ' . $e->getMessage(), 'log' => $log];
        }
    }

    /**
     * Mengirim Tanda Terima Sell Phone (Buyback) via WhatsApp Qontak.
     */
    public function sendSellPhoneWhatsApp(SellPhone $sellPhone, string $pdfPublicUrl, string $filename, ?User $user = null): array
    {
        $sellPhone->loadMissing(['user.profile', 'branch', 'businessUnit']);
        $user = $user ?: Auth::user();
        $phone = self::formatPhoneNumber($sellPhone->user?->profile?->phone_number);
        $customerName = $sellPhone->user?->name ?: 'Customer';
        $refNo = 'SPL-' . $sellPhone->id;

        if (!$phone) {
            $this->createLog([
                'channel' => 'whatsapp',
                'recipient' => '-',
                'recipient_name' => $customerName,
                'subject' => 'Tanda Terima Sell Phone (Qontak WA)',
                'message_type' => 'receipt_sellphone',
                'content' => $this->buildSellPhoneContentSummary($sellPhone),
                'attachment_url' => $pdfPublicUrl,
                'attachment_name' => $filename,
                'is_sent' => false,
                'status' => 'failed',
                'error_message' => 'Nomor WhatsApp customer tidak ditemukan.',
                'source' => $sellPhone,
                'reference_number' => $refNo,
                'sent_by' => $user?->id,
                'business_unit_id' => $sellPhone->business_unit_id ?: $user?->getActiveBusinessUnitId(),
                'branch_id' => $sellPhone->branch_id ?: $user?->branch_id,
            ]);

            return ['success' => false, 'message' => 'Nomor HP customer tidak ditemukan.'];
        }

        $templateId = config('services.qontak.sellphone_template_id') ?: (config('services.qontak.template_id') ?: env('QONTAK_SELLPHONE_TEMPLATE_ID'));
        $integrationId = config('services.qontak.integration_id') ?: env('QONTAK_CHANNEL_INTEGRATION_ID');

        $payload = [
            'to_name' => $customerName,
            'to_number' => $phone,
            'channel_integration_id' => $integrationId,
            'message_template_id' => $templateId,
            'language' => ['code' => 'id'],
            'parameters' => [
                'header' => [
                    'format' => 'DOCUMENT',
                    'params' => [
                        ['key' => 'url', 'value' => $pdfPublicUrl],
                        ['key' => 'filename', 'value' => $filename]
                    ]
                ],
                'body' => [
                    ['key' => '1', 'value' => 'nama', 'value_text' => $customerName],
                    ['key' => '2', 'value' => 'no_invoice', 'value_text' => $refNo],
                    ['key' => '3', 'value' => 'total_tagihan', 'value_text' => 'Rp ' . number_format($sellPhone->appraised_value, 0, ',', '.')]
                ]
            ]
        ];

        $contentSummary = $this->buildSellPhoneContentSummary($sellPhone);
        $result = $this->dispatchQontakRequest($payload);

        $isSent = $result['success'];
        $log = $this->createLog([
            'channel' => 'whatsapp',
            'recipient' => $phone,
            'recipient_name' => $customerName,
            'subject' => 'Tanda Terima #' . $refNo,
            'message_type' => 'receipt_sellphone',
            'content' => $contentSummary,
            'attachment_url' => $pdfPublicUrl,
            'attachment_name' => $filename,
            'payload' => $payload,
            'response_payload' => $result['response'] ?? null,
            'is_sent' => $isSent,
            'status' => $isSent ? 'sent' : 'failed',
            'error_message' => $result['error'] ?? null,
            'source' => $sellPhone,
            'reference_number' => $refNo,
            'sent_by' => $user?->id,
            'business_unit_id' => $sellPhone->business_unit_id ?: $user?->getActiveBusinessUnitId(),
            'branch_id' => $sellPhone->branch_id ?: $user?->branch_id,
        ]);

        if ($isSent) {
            $sellPhone->update(['is_wa_sent' => true]);
        }

        return array_merge($result, ['log' => $log]);
    }

    /**
     * Mengirim Tanda Terima Sell Phone via Email.
     */
    public function sendSellPhoneEmail(SellPhone $sellPhone, string $pdfContent, string $filename, ?User $user = null): array
    {
        $sellPhone->loadMissing(['user', 'branch', 'businessUnit']);
        $user = $user ?: Auth::user();
        $email = $sellPhone->user?->email;
        $customerName = $sellPhone->user?->name ?: 'Customer';
        $refNo = 'SPL-' . $sellPhone->id;

        if (!$email || str_contains($email, '@pos.tokopun.com') || str_contains($email, '@tokopon.com')) {
            $this->createLog([
                'channel' => 'email',
                'recipient' => $email ?: '-',
                'recipient_name' => $customerName,
                'subject' => 'Tanda Terima #' . $refNo,
                'message_type' => 'receipt_sellphone',
                'content' => $this->buildSellPhoneContentSummary($sellPhone),
                'attachment_name' => $filename,
                'is_sent' => false,
                'status' => 'failed',
                'error_message' => 'Email customer tidak valid.',
                'source' => $sellPhone,
                'reference_number' => $refNo,
                'sent_by' => $user?->id,
                'business_unit_id' => $sellPhone->business_unit_id ?: $user?->getActiveBusinessUnitId(),
                'branch_id' => $sellPhone->branch_id ?: $user?->branch_id,
            ]);

            return ['success' => false, 'message' => 'Email customer tidak valid.'];
        }

        $contentSummary = $this->buildSellPhoneContentSummary($sellPhone);

        try {
            $mailer = config('mail.mailers.pos_sales.host') ? Mail::mailer('pos_sales') : Mail::mailer();
            $mailer->to($email)->send(new SellPhoneReceiptMail($sellPhone, $pdfContent, $filename));

            $log = $this->createLog([
                'channel' => 'email',
                'recipient' => $email,
                'recipient_name' => $customerName,
                'subject' => 'Tanda Terima #' . $refNo,
                'message_type' => 'receipt_sellphone',
                'content' => $contentSummary,
                'attachment_name' => $filename,
                'is_sent' => true,
                'status' => 'sent',
                'source' => $sellPhone,
                'reference_number' => $refNo,
                'sent_by' => $user?->id,
                'business_unit_id' => $sellPhone->business_unit_id ?: $user?->getActiveBusinessUnitId(),
                'branch_id' => $sellPhone->branch_id ?: $user?->branch_id,
            ]);

            $sellPhone->update(['is_email_sent' => true]);

            return ['success' => true, 'message' => 'Struk digital telah dikirim ke ' . $email, 'log' => $log];
        } catch (\Exception $e) {
            Log::error('SellPhone Email Error: ' . $e->getMessage());

            $log = $this->createLog([
                'channel' => 'email',
                'recipient' => $email,
                'recipient_name' => $customerName,
                'subject' => 'Tanda Terima #' . $refNo,
                'message_type' => 'receipt_sellphone',
                'content' => $contentSummary,
                'attachment_name' => $filename,
                'is_sent' => false,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'source' => $sellPhone,
                'reference_number' => $refNo,
                'sent_by' => $user?->id,
                'business_unit_id' => $sellPhone->business_unit_id ?: $user?->getActiveBusinessUnitId(),
                'branch_id' => $sellPhone->branch_id ?: $user?->branch_id,
            ]);

            return ['success' => false, 'message' => 'Koneksi SMTP bermasalah: ' . $e->getMessage(), 'log' => $log];
        }
    }

    /**
     * Mengirim Bukti Pembayaran Sell Phone via WhatsApp.
     */
    public function sendSellPhonePaymentProofWhatsApp(SellPhone $sellPhone, string $documentUrl, string $filename, ?User $user = null): array
    {
        $sellPhone->loadMissing(['user.profile', 'branch', 'businessUnit']);
        $user = $user ?: Auth::user();
        $phone = self::formatPhoneNumber($sellPhone->user?->profile?->phone_number);
        $customerName = $sellPhone->user?->name ?: 'Customer';
        $refNo = 'SPL-' . $sellPhone->id;

        if (!$phone) {
            return ['success' => false, 'message' => 'Nomor HP customer tidak ditemukan.'];
        }

        $templateId = config('services.qontak.template_id') ?: env('QONTAK_TEMPLATE_ID');
        $integrationId = config('services.qontak.integration_id') ?: env('QONTAK_CHANNEL_INTEGRATION_ID');

        $payload = [
            'to_name' => $customerName,
            'to_number' => $phone,
            'channel_integration_id' => $integrationId,
            'message_template_id' => $templateId,
            'language' => ['code' => 'id'],
            'parameters' => [
                'header' => [
                    'format' => 'DOCUMENT',
                    'params' => [
                        ['key' => 'url', 'value' => $documentUrl],
                        ['key' => 'filename', 'value' => $filename]
                    ]
                ],
                'body' => [
                    ['key' => '1', 'value' => 'nama', 'value_text' => $customerName],
                    ['key' => '2', 'value' => 'no_invoice', 'value_text' => $refNo],
                    ['key' => '3', 'value' => 'total_tagihan', 'value_text' => 'Rp ' . number_format($sellPhone->appraised_value, 0, ',', '.')]
                ]
            ]
        ];

        $contentSummary = "BUKTI PEMBAYARAN SELL PHONE\n" .
            "No. Transaksi: {$refNo}\n" .
            "Customer: {$customerName}\n" .
            "Perangkat: {$sellPhone->phone_brand} {$sellPhone->phone_model}\n" .
            "Total Transfer: Rp " . number_format($sellPhone->appraised_value, 0, ',', '.') . "\n" .
            "Rekening Tujuan: {$sellPhone->bank_name} - {$sellPhone->bank_account_number} a/n {$sellPhone->bank_account_name}";

        $result = $this->dispatchQontakRequest($payload);
        $isSent = $result['success'];

        $log = $this->createLog([
            'channel' => 'whatsapp',
            'recipient' => $phone,
            'recipient_name' => $customerName,
            'subject' => 'Bukti Pembayaran #' . $refNo,
            'message_type' => 'payment_proof',
            'content' => $contentSummary,
            'attachment_url' => $documentUrl,
            'attachment_name' => $filename,
            'payload' => $payload,
            'response_payload' => $result['response'] ?? null,
            'is_sent' => $isSent,
            'status' => $isSent ? 'sent' : 'failed',
            'error_message' => $result['error'] ?? null,
            'source' => $sellPhone,
            'reference_number' => $refNo,
            'sent_by' => $user?->id,
            'business_unit_id' => $sellPhone->business_unit_id ?: $user?->getActiveBusinessUnitId(),
            'branch_id' => $sellPhone->branch_id ?: $user?->branch_id,
        ]);

        return array_merge($result, ['log' => $log]);
    }

    /**
     * Mengirim Bukti Pembayaran Sell Phone via Email.
     */
    public function sendSellPhonePaymentProofEmail(SellPhone $sellPhone, string $recipientEmail, ?User $user = null): array
    {
        $sellPhone->loadMissing(['user', 'branch', 'businessUnit']);
        $user = $user ?: Auth::user();
        $customerName = $sellPhone->user?->name ?: 'Customer';
        $refNo = 'SPL-' . $sellPhone->id;

        $contentSummary = "BUKTI PEMBAYARAN SELL PHONE\n" .
            "No. Transaksi: {$refNo}\n" .
            "Customer: {$customerName}\n" .
            "Perangkat: {$sellPhone->phone_brand} {$sellPhone->phone_model}\n" .
            "Total Transfer: Rp " . number_format($sellPhone->appraised_value, 0, ',', '.') . "\n" .
            "Rekening Tujuan: {$sellPhone->bank_name} - {$sellPhone->bank_account_number} a/n {$sellPhone->bank_account_name}";

        try {
            $mailer = config('mail.mailers.pos_sales.host') ? Mail::mailer('pos_sales') : Mail::mailer();
            $mailer->to($recipientEmail)->send(new SellPhonePaymentReceiptMail($sellPhone));

            $log = $this->createLog([
                'channel' => 'email',
                'recipient' => $recipientEmail,
                'recipient_name' => $customerName,
                'subject' => 'Bukti Pembayaran #' . $refNo,
                'message_type' => 'payment_proof',
                'content' => $contentSummary,
                'attachment_url' => $sellPhone->payment_receipt_path ? asset('storage/' . $sellPhone->payment_receipt_path) : null,
                'attachment_name' => basename($sellPhone->payment_receipt_path ?: 'bukti_bayar.jpg'),
                'is_sent' => true,
                'status' => 'sent',
                'source' => $sellPhone,
                'reference_number' => $refNo,
                'sent_by' => $user?->id,
                'business_unit_id' => $sellPhone->business_unit_id ?: $user?->getActiveBusinessUnitId(),
                'branch_id' => $sellPhone->branch_id ?: $user?->branch_id,
            ]);

            $sellPhone->update(['is_email_sent' => true]);

            return ['success' => true, 'message' => 'Bukti pembayaran berhasil dikirim ke ' . $recipientEmail, 'log' => $log];
        } catch (\Exception $e) {
            Log::error('SellPhone Payment Email Error: ' . $e->getMessage());

            $log = $this->createLog([
                'channel' => 'email',
                'recipient' => $recipientEmail,
                'recipient_name' => $customerName,
                'subject' => 'Bukti Pembayaran #' . $refNo,
                'message_type' => 'payment_proof',
                'content' => $contentSummary,
                'attachment_url' => $sellPhone->payment_receipt_path ? asset('storage/' . $sellPhone->payment_receipt_path) : null,
                'attachment_name' => basename($sellPhone->payment_receipt_path ?: 'bukti_bayar.jpg'),
                'is_sent' => false,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'source' => $sellPhone,
                'reference_number' => $refNo,
                'sent_by' => $user?->id,
                'business_unit_id' => $sellPhone->business_unit_id ?: $user?->getActiveBusinessUnitId(),
                'branch_id' => $sellPhone->branch_id ?: $user?->branch_id,
            ]);

            return ['success' => false, 'message' => 'SMTP Error: ' . $e->getMessage(), 'log' => $log];
        }
    }

    /**
     * Eksekusi HTTP Request ke API Mekari Qontak dengan HMAC-SHA256.
     */
    protected function dispatchQontakRequest(array $payload): array
    {
        $fullUrl = config('services.qontak.api_url') ?: env('QONTAK_API_URL', 'https://api.mekari.com/qontak/chat/v1/broadcasts/whatsapp/direct');
        if (empty($fullUrl)) {
            return ['success' => false, 'message' => 'URL Qontak tidak ditemukan di konfigurasi.', 'error' => 'Empty QONTAK_API_URL'];
        }

        if (!preg_match("~^(?:f|ht)tps?://~i", $fullUrl)) {
            $fullUrl = "https://" . $fullUrl;
        }

        $method = 'POST';
        $parsedUrl = parse_url($fullUrl);
        $endpoint = $parsedUrl['path'] ?? '';
        $clientId = config('services.qontak.client_id') ?: env('QONTAK_CLIENT_ID');
        $clientSecret = config('services.qontak.client_secret') ?: env('QONTAK_CLIENT_SECRET');

        $dateString = gmdate('D, d M Y H:i:s') . ' GMT';
        $requestLine = "{$method} {$endpoint} HTTP/1.1";
        $stringToSign = "date: {$dateString}\n{$requestLine}";
        $digest = hash_hmac('sha256', $stringToSign, $clientSecret, true);
        $signature = base64_encode($digest);
        $hmacHeader = "hmac username=\"{$clientId}\", algorithm=\"hmac-sha256\", headers=\"date request-line\", signature=\"{$signature}\"";
        $idempotencyKey = (string) Str::uuid();

        try {
            $response = Http::withHeaders([
                'Authorization'     => $hmacHeader,
                'Date'              => $dateString,
                'X-Idempotency-Key' => $idempotencyKey,
                'Content-Type'      => 'application/json',
                'Accept'            => 'application/json',
            ])->post($fullUrl, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Pesan WhatsApp berhasil dikirim!',
                    'response' => $response->json() ?? ['status' => $response->status()],
                ];
            } else {
                Log::error('Mekari Qontak API Error: ' . $response->status() . ' - ' . $response->body());
                return [
                    'success' => false,
                    'message' => 'Gagal API Qontak: Code ' . $response->status(),
                    'error' => 'HTTP ' . $response->status() . ': ' . $response->body(),
                    'response' => $response->json() ?? ['status' => $response->status(), 'raw' => $response->body()],
                ];
            }
        } catch (\Exception $e) {
            Log::error('Mekari Qontak Crash: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Crash: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Membangun teks rincian transaksi Order ("Apa yang dikirim") secara detail.
     */
    public function buildOrderContentSummary(Order $order): string
    {
        $customerName = $order->user?->name ?: 'Customer';
        $customerPhone = $order->user?->profile?->phone_number ?: '-';
        $customerEmail = $order->user?->email ?: '-';
        $branchName = $order->branch?->name ?: 'Toko Offline';
        $paymentName = $order->paymentMethod?->name ?: 'Tunai / Non-Tunai';
        $orderDate = $order->order_date ?: $order->created_at?->format('Y-m-d H:i:s');

        $lines = [];
        $lines[] = "----------------------------------------";
        $lines[] = "STRUK PEMBELIAN - #" . $order->order_number;
        $lines[] = "----------------------------------------";
        $lines[] = "Waktu      : " . $orderDate;
        $lines[] = "Cabang     : " . $branchName;
        $lines[] = "Pelanggan  : " . $customerName;
        $lines[] = "No. HP     : " . $customerPhone;
        $lines[] = "Email      : " . $customerEmail;
        $lines[] = "Pembayaran : " . $paymentName;
        $lines[] = "----------------------------------------";
        $lines[] = "RINCIAN ITEM:";

        if ($order->items && $order->items->isNotEmpty()) {
            foreach ($order->items as $idx => $item) {
                $itemName = $item->product_name ?: ($item->variant?->name ?? ($item->variant?->product?->name ?? 'Produk'));
                $itemQty = $item->qty ?: 1;
                $itemPrice = number_format($item->price_at_checkout ?: 0, 0, ',', '.');
                $itemSubtotal = number_format($item->subtotal ?: 0, 0, ',', '.');
                $sn = $item->serial_number ? " [SN: {$item->serial_number}]" : '';

                $lines[] = sprintf("%d. %s%s", $idx + 1, $itemName, $sn);
                $lines[] = sprintf("   %d x Rp %s = Rp %s", $itemQty, $itemPrice, $itemSubtotal);
            }
        } else {
            $lines[] = "- Data item tidak tercatat.";
        }

        $lines[] = "----------------------------------------";
        $subtotal = $order->total_amount ?: 0;
        $discount = $order->discount_amount ?: 0;
        $grandTotal = $order->grand_total ?: $subtotal;

        $lines[] = "Subtotal       : Rp " . number_format($subtotal, 0, ',', '.');
        if ($discount > 0) {
            $lines[] = "Diskon/Potongan: -Rp " . number_format($discount, 0, ',', '.');
        }
        $lines[] = "TOTAL AKHIR    : Rp " . number_format($grandTotal, 0, ',', '.');
        $lines[] = "----------------------------------------";
        $lines[] = "Lampiran Dokumen: PDF Struk Resmi Digital";

        return implode("\n", $lines);
    }

    /**
     * Membangun teks rincian transaksi Sell Phone ("Apa yang dikirim").
     */
    public function buildSellPhoneContentSummary(SellPhone $sellPhone): string
    {
        $customerName = $sellPhone->user?->name ?: 'Customer';
        $customerPhone = $sellPhone->user?->profile?->phone_number ?: '-';
        $customerEmail = $sellPhone->user?->email ?: '-';
        $branchName = $sellPhone->branch?->name ?: 'Cabang';
        $refNo = 'SPL-' . $sellPhone->id;

        $lines = [];
        $lines[] = "----------------------------------------";
        $lines[] = "TANDA TERIMA TRANSAKSI SELL PHONE (BUYBACK)";
        $lines[] = "No. Transaksi : " . $refNo;
        $lines[] = "----------------------------------------";
        $lines[] = "Waktu       : " . ($sellPhone->created_at?->format('Y-m-d H:i:s') ?: '-');
        $lines[] = "Cabang      : " . $branchName;
        $lines[] = "Customer    : " . $customerName;
        $lines[] = "No. HP      : " . $customerPhone;
        $lines[] = "Email       : " . $customerEmail;
        $lines[] = "----------------------------------------";
        $lines[] = "DETAIL PERANGKAT:";
        $lines[] = "Brand & Tipe: " . $sellPhone->phone_brand . " " . $sellPhone->phone_model;
        if ($sellPhone->phone_ram || $sellPhone->phone_storage) {
            $lines[] = "RAM/Storage : " . ($sellPhone->phone_ram ?: '-') . " / " . ($sellPhone->phone_storage ?: '-');
        }
        if ($sellPhone->imei) {
            $lines[] = "IMEI/SN     : " . $sellPhone->imei;
        }
        if ($sellPhone->minus_desc) {
            $lines[] = "Catatan Minus: " . $sellPhone->minus_desc;
        }
        $lines[] = "----------------------------------------";
        $lines[] = "NILAI TAKSIRAN : Rp " . number_format($sellPhone->appraised_value, 0, ',', '.');
        if ($sellPhone->bank_name || $sellPhone->bank_account_number) {
            $lines[] = "Rek. Transfer : " . $sellPhone->bank_name . " - " . $sellPhone->bank_account_number . " a/n " . $sellPhone->bank_account_name;
        }
        $lines[] = "----------------------------------------";
        $lines[] = "Lampiran Dokumen: PDF Tanda Terima Resmi";

        return implode("\n", $lines);
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

    /**
     * Sinkronisasi data historis Order dan SellPhone yang sudah terkirim (is_wa_sent / is_email_sent)
     * ke dalam tabel message_logs secara otomatis.
     */
    public function backfillHistoricalLogs(): array
    {
        $createdCount = 0;
        $skippedCount = 0;

        // 1. Sinkronisasi Order lama
        $orders = Order::where(function ($q) {
            $q->where('is_wa_sent', true)->orWhere('is_email_sent', true);
        })->with(['user.profile', 'items.variant', 'branch', 'paymentMethod'])->get();

        foreach ($orders as $order) {
            $customerName = $order->user?->name ?: 'Customer';
            $phone = self::formatPhoneNumber($order->user?->profile?->phone_number);
            $email = $order->user?->email;
            $filename = 'Struk_' . $order->order_number . '.pdf';
            $receiptPath = 'receipts/' . $filename;
            $attachmentUrl = \Illuminate\Support\Facades\Storage::disk('public')->exists($receiptPath)
                ? asset('storage/' . $receiptPath)
                : null;

            $contentSummary = $this->buildOrderContentSummary($order);

            // Backfill WhatsApp
            if ($order->is_wa_sent) {
                $exists = MessageLog::where('source_type', Order::class)
                    ->where('source_id', $order->id)
                    ->where('channel', 'whatsapp')
                    ->exists();

                if (!$exists) {
                    $this->createLog([
                        'channel' => 'whatsapp',
                        'recipient' => $phone ?: '-',
                        'recipient_name' => $customerName,
                        'subject' => 'Struk Penjualan #' . $order->order_number,
                        'message_type' => 'receipt_order',
                        'content' => $contentSummary,
                        'attachment_url' => $attachmentUrl,
                        'attachment_name' => $filename,
                        'is_sent' => true,
                        'status' => 'sent',
                        'source' => $order,
                        'reference_number' => $order->order_number,
                        'sent_by' => $order->handled_by,
                        'business_unit_id' => $order->business_unit_id,
                        'branch_id' => $order->branch_id,
                        'sent_at' => $order->updated_at ?: $order->created_at,
                    ]);
                    $createdCount++;
                } else {
                    $skippedCount++;
                }
            }

            // Backfill Email
            if ($order->is_email_sent) {
                $exists = MessageLog::where('source_type', Order::class)
                    ->where('source_id', $order->id)
                    ->where('channel', 'email')
                    ->exists();

                if (!$exists) {
                    $this->createLog([
                        'channel' => 'email',
                        'recipient' => $email ?: '-',
                        'recipient_name' => $customerName,
                        'subject' => 'Struk Pembelian #' . $order->order_number,
                        'message_type' => 'receipt_order',
                        'content' => $contentSummary,
                        'attachment_url' => $attachmentUrl,
                        'attachment_name' => $filename,
                        'is_sent' => true,
                        'status' => 'sent',
                        'source' => $order,
                        'reference_number' => $order->order_number,
                        'sent_by' => $order->handled_by,
                        'business_unit_id' => $order->business_unit_id,
                        'branch_id' => $order->branch_id,
                        'sent_at' => $order->updated_at ?: $order->created_at,
                    ]);
                    $createdCount++;
                } else {
                    $skippedCount++;
                }
            }
        }

        // 2. Sinkronisasi SellPhone lama
        $sellPhones = SellPhone::where(function ($q) {
            $q->where('is_wa_sent', true)->orWhere('is_email_sent', true);
        })->with(['user.profile', 'branch', 'businessUnit'])->get();

        foreach ($sellPhones as $sellPhone) {
            $customerName = $sellPhone->user?->name ?: 'Customer';
            $phone = self::formatPhoneNumber($sellPhone->user?->profile?->phone_number);
            $email = $sellPhone->user?->email;
            $refNo = 'SPL-' . $sellPhone->id;
            $filename = 'Tanda_Terima_SPL-' . $sellPhone->id . '.pdf';
            $receiptPath = 'receipts_sellphone/' . $filename;
            $attachmentUrl = \Illuminate\Support\Facades\Storage::disk('public')->exists($receiptPath)
                ? asset('storage/' . $receiptPath)
                : null;

            $contentSummary = $this->buildSellPhoneContentSummary($sellPhone);

            // Backfill WhatsApp
            if ($sellPhone->is_wa_sent) {
                $exists = MessageLog::where('source_type', SellPhone::class)
                    ->where('source_id', $sellPhone->id)
                    ->where('channel', 'whatsapp')
                    ->exists();

                if (!$exists) {
                    $this->createLog([
                        'channel' => 'whatsapp',
                        'recipient' => $phone ?: '-',
                        'recipient_name' => $customerName,
                        'subject' => 'Tanda Terima #' . $refNo,
                        'message_type' => 'receipt_sellphone',
                        'content' => $contentSummary,
                        'attachment_url' => $attachmentUrl,
                        'attachment_name' => $filename,
                        'is_sent' => true,
                        'status' => 'sent',
                        'source' => $sellPhone,
                        'reference_number' => $refNo,
                        'sent_by' => $sellPhone->handled_by,
                        'business_unit_id' => $sellPhone->business_unit_id,
                        'branch_id' => $sellPhone->branch_id,
                        'sent_at' => $sellPhone->updated_at ?: $sellPhone->created_at,
                    ]);
                    $createdCount++;
                } else {
                    $skippedCount++;
                }
            }

            // Backfill Email
            if ($sellPhone->is_email_sent) {
                $exists = MessageLog::where('source_type', SellPhone::class)
                    ->where('source_id', $sellPhone->id)
                    ->where('channel', 'email')
                    ->exists();

                if (!$exists) {
                    $this->createLog([
                        'channel' => 'email',
                        'recipient' => $email ?: '-',
                        'recipient_name' => $customerName,
                        'subject' => 'Tanda Terima #' . $refNo,
                        'message_type' => 'receipt_sellphone',
                        'content' => $contentSummary,
                        'attachment_url' => $attachmentUrl,
                        'attachment_name' => $filename,
                        'is_sent' => true,
                        'status' => 'sent',
                        'source' => $sellPhone,
                        'reference_number' => $refNo,
                        'sent_by' => $sellPhone->handled_by,
                        'business_unit_id' => $sellPhone->business_unit_id,
                        'branch_id' => $sellPhone->branch_id,
                        'sent_at' => $sellPhone->updated_at ?: $sellPhone->created_at,
                    ]);
                    $createdCount++;
                } else {
                    $skippedCount++;
                }
            }
        }

        return [
            'created' => $createdCount,
            'skipped' => $skippedCount,
            'total' => $createdCount + $skippedCount,
        ];
    }
}
