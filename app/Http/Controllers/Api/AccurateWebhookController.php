<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccurateWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Keamanan: Cek Token dari Parameter URL
        $urlToken = $request->query('token');
        $expectedToken = env('ACCURATE_WEBHOOK_TOKEN');

        // Tolak jika URL tidak ada ?token= yang sesuai dengan .env
        if ($urlToken !== $expectedToken) {
            Log::warning('Accurate Webhook Ditolak: Token tidak cocok.');
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        Log::info('webhook masuk:', $request->all());

        // 2. Tangkap data bungkusan Array dari Accurate
        $payloads = $request->json()->all();

        // Jaga-jaga kalau testing dari postman bukan array
        if (!is_array($payloads) || !isset($payloads[0])) {
            $payloads = [$request->all()];
        }

        // 3. Pecah bungkusannya (karena bisa jadi Accurate kirim banyak item sekaligus)
        foreach ($payloads as $payload) {
            $eventType = $payload['type'] ?? 'UNKNOWN';
            $dbSource = $payload['databaseId'] ?? 'unknown_db';
            $uuid = $payload['uuid'] ?? md5(json_encode($payload) . time());

            // 4. Simpan ke Database persis seperti sebelumnya
            $log = \App\Models\AccurateWebhookLog::create([
                'event_type' => $eventType,       // Bakal berisi "ITEM"
                'database_source' => (string)$dbSource,   // Bakal berisi "2601686"
                'event_id' => $uuid,
                'payload' => $payload,
                'status' => 'pending'
            ]);

            // 5. Lempar ke Antrian
            \App\Jobs\ProcessAccurateWebhookJob::dispatch($log->id);
            Log::info("Accurate Webhook queued: [Event: {$eventType}] [Log ID: {$log->id}]");
        }

        // 6. Jawab 200 OK ke Accurate
        return response()->json(['status' => 'success', 'message' => 'Terekam di log']);
    }
}
