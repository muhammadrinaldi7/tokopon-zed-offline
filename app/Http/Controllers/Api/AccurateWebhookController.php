<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\ProductVariant;
use App\Models\SecondProductVariant;
use App\Models\WarehouseStock;
use App\Services\AccurateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AccurateWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Validasi Token Verifikasi
        $token = $request->header('X-Accurate-Notification-Token');
        if ($token && $token !== env('ACCURATE_WEBHOOK_TOKEN')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $eventType = $payload['event'] ?? 'UNKNOWN';
        
        // Accurate terkadang mengirimkan multi-DB source jika diatur
        $dbSource = $payload['database_source'] ?? 'syihab'; 

        // Generate a pseudo-event ID for idempotency (if needed later)
        $eventId = md5(json_encode($payload) . time());

        // 2. Simpan ke Database
        $log = \App\Models\AccurateWebhookLog::create([
            'event_type' => $eventType,
            'database_source' => $dbSource,
            'event_id' => $eventId,
            'payload' => $payload,
            'status' => 'pending'
        ]);

        // 3. Dispatch Background Job
        \App\Jobs\ProcessAccurateWebhookJob::dispatch($log->id);

        Log::info("Accurate Webhook queued: [Event: {$eventType}] [Log ID: {$log->id}]");

        // 4. Respond 200 OK secara instan ke Accurate
        return response()->json(['status' => 'queued', 'log_id' => $log->id]);
    }
}
