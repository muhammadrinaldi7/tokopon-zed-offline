<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccurateWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Tangkap data bungkusan Array dari Accurate
        $payloads = $request->json()->all();

        // Jaga-jaga kalau testing dari postman bukan array
        if (!is_array($payloads) || !isset($payloads[0])) {
            $payloads = [$request->all()];
        }

        // 2. Ambil databaseId dari payload pertama untuk mencari Business Unit
        $databaseId = $payloads[0]['databaseId'] ?? null;

        $bu = \App\Models\BusinessUnit::where('accurate_database_id', $databaseId)->first();

        // Tolak jika database tidak terdaftar di sistem kita
        if (!$bu) {
            Log::warning("Accurate Webhook Ditolak: Database ID {$databaseId} tidak terdaftar di pengaturan Business Unit.");
            // Kita kembalikan 200 OK agar Accurate tidak menganggap server kita mati dan terus mengulang (retry) pengiriman
            return response()->json(['message' => 'Database tidak dikenali, harap setup di Admin'], 200);
        }

        // 3. Keamanan Wajib: Jika BU memiliki Secret Key / Webhook Token, WAJIB lolos salah satu verifikasi!
        $signature = $request->header('X-Accurate-Signature');
        $urlToken = (string) $request->query('token', '');
        $hasSecret = !empty($bu->accurate_secret_key);
        $hasWebhookToken = !empty($bu->accurate_webhook_token);

        if ($hasSecret || $hasWebhookToken) {
            $verified = false;

            if ($signature && $hasSecret) {
                $expectedSignature = hash_hmac('sha256', $request->getContent(), $bu->accurate_secret_key);
                if (hash_equals($expectedSignature, (string) $signature)) {
                    $verified = true;
                }
            }

            if (!$verified && $urlToken !== '' && $hasWebhookToken) {
                if (hash_equals((string) $bu->accurate_webhook_token, $urlToken)) {
                    $verified = true;
                }
            }

            if (!$verified) {
                Log::warning("Accurate Webhook Ditolak: Signature/Token tidak valid atau kosong untuk BU {$bu->name}.");
                return response()->json(['message' => 'Unauthorized Webhook'], 401);
            }
        }

        Log::info('webhook masuk dari BU: ' . $bu->name);

        // 4. Pecah bungkusannya (karena bisa jadi Accurate kirim banyak item sekaligus)
        foreach ($payloads as $payload) {
            $eventType = $payload['type'] ?? 'UNKNOWN';
            $uuid = $payload['uuid'] ?? md5(json_encode($payload) . time());

            // Pengecekan Idempotency: Abaikan jika webhook dengan UUID yang sama sudah pernah masuk
            $existingLog = \App\Models\AccurateWebhookLog::where('event_id', $uuid)->first();
            if ($existingLog) {
                Log::info("Accurate Webhook Ditolak (Duplikat): Event ID {$uuid} sudah pernah diproses.");
                continue; // Skip payload ini, lanjut ke payload berikutnya
            }

            // 5. Simpan ke Database
            $log = \App\Models\AccurateWebhookLog::create([
                'event_type' => $eventType,
                // Sangat Penting: Simpan CODE milik Business Unit (misal 'gsk'/'syihab'), bukan ID angkanya.
                // Karena Job Handler di belakang layar menggunakan parameter string nama dbSource.
                'database_source' => $bu->code,
                'event_id' => $uuid,
                'payload' => $payload,
                'status' => 'pending'
            ]);

            // 6. Lempar ke Antrian
            \App\Jobs\ProcessAccurateWebhookJob::dispatch($log->id);
            Log::info("Accurate Webhook queued: [Event: {$eventType}] [Log ID: {$log->id}] [BU: {$bu->code}]");
        }

        // 7. Jawab 200 OK ke Accurate
        return response()->json(['status' => 'success', 'message' => 'Terekam di log']);
    }
}
