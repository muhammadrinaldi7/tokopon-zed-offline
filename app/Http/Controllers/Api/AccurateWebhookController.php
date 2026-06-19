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

        // 3. Keamanan: Cek X-Accurate-Signature jika Accurate Secret Key diisi di Business Unit
        $signature = $request->header('X-Accurate-Signature');
        if ($signature && !empty($bu->accurate_secret_key)) {
            $expectedSignature = hash_hmac('sha256', $request->getContent(), $bu->accurate_secret_key);
            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning("Accurate Webhook Ditolak: Signature tidak valid untuk BU {$bu->name}.");
                return response()->json(['message' => 'Invalid Signature'], 401);
            }
        } elseif (!$signature && $request->query('token')) {
            // Fallback: Menggunakan ?token= di URL (biasanya AppKey atau custom token)
            // Mengecek ke kolom accurate_webhook_token milik Business Unit
            if ($request->query('token') !== $bu->accurate_webhook_token) {
                Log::warning("Accurate Webhook Ditolak: Token URL salah untuk BU {$bu->name}. URL Token: " . $request->query('token') . ' != DB: ' . $bu->accurate_webhook_token);
                return response()->json(['message' => 'Unauthorized Token'], 401);
            }
        }

        Log::info('webhook masuk dari BU: ' . $bu->name);

        // 4. Pecah bungkusannya (karena bisa jadi Accurate kirim banyak item sekaligus)
        foreach ($payloads as $payload) {
            $eventType = $payload['type'] ?? 'UNKNOWN';
            $uuid = $payload['uuid'] ?? md5(json_encode($payload) . time());

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
