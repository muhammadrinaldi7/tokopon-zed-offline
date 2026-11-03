<?php

namespace App\Http\Controllers\Api\Executive;

use App\Http\Controllers\Controller;
use App\Services\ExecutiveAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExecutiveAiController extends Controller
{
    protected ExecutiveAiService $aiService;

    public function __construct(ExecutiveAiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Standard JSON chat completion with server-verified context & tool calling.
     */
    public function chat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:5000',
            'session_id' => 'nullable|string|max:120',
            'active_tab' => 'nullable|string|max:60',
            'active_filters' => 'nullable|array',
            'context_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Format permintaan tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $sessionId = $request->input('session_id') ?: ('exec-session-' . date('Ymd'));

        try {
            $data = $this->aiService->chat(
                $request->input('message'),
                $sessionId,
                $user->id,
                $request->input('context_data'),
                $user,
                $request->input('active_tab'),
                $request->input('active_filters')
            );

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::error('ExecutiveAiController chat error: ' . $e->getMessage(), [
                'user_id' => $user?->id,
                'session_id' => $sessionId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Layanan Asisten Eksekutif sedang sibuk atau mengalami gangguan koneksi sementara. Silakan coba sesaat lagi.',
            ], 503);
        }
    }

    /**
     * Real-time Server-Sent Events (SSE) streaming chat endpoint.
     */
    public function stream(Request $request): StreamedResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:5000',
            'session_id' => 'nullable|string|max:120',
            'active_tab' => 'nullable|string|max:60',
            'active_filters' => 'nullable|array',
            'context_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Format permintaan tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $sessionId = $request->input('session_id') ?: ('exec-session-' . date('Ymd'));
        $message = (string) $request->input('message');
        $activeTab = $request->input('active_tab');
        $activeFilters = $request->input('active_filters');
        $legacyContext = $request->input('context_data');

        return response()->stream(function () use ($message, $sessionId, $user, $activeTab, $activeFilters, $legacyContext) {
            @ini_set('zlib.output_compression', '0');
            @ini_set('implicit_flush', '1');
            while (ob_get_level() > 0) {
                @ob_end_flush();
            }

            $emit = function (array $payload): void {
                echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n";
                if (function_exists('ob_flush')) {
                    @ob_flush();
                }
                flush();
            };

            try {
                $this->aiService->streamChat(
                    $message,
                    $sessionId,
                    $user->id,
                    $emit,
                    $user,
                    $activeTab,
                    $activeFilters,
                    $legacyContext
                );
            } catch (\Throwable $e) {
                Log::error('ExecutiveAiController stream error: ' . $e->getMessage(), [
                    'user_id' => $user?->id,
                    'session_id' => $sessionId,
                ]);

                $emit([
                    'type' => 'error',
                    'message' => 'Layanan Asisten Eksekutif sedang sibuk atau mengalami gangguan koneksi sementara. Silakan coba sesaat lagi.',
                ]);
            }

            echo "data: [DONE]\n\n";
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Get chat history for given session.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->query('session_id');
        $limit = min(100, max(1, (int) $request->query('limit', 50)));

        $history = $this->aiService->getHistory($user->id, $sessionId, $limit);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * 1-Click Executive strategic summary generation grounded in server-verified data.
     */
    public function summarize(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'active_filters' => 'nullable|array',
            'context_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter ringkasan tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        try {
            $data = $this->aiService->generateExecutiveSummary(
                $request->input('context_data'),
                $user->id,
                $user,
                $request->input('active_filters')
            );

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::error('ExecutiveAiController summarize error: ' . $e->getMessage(), [
                'user_id' => $user?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyusun ringkasan eksekutif saat ini. Silakan coba sesaat lagi.',
            ], 503);
        }
    }

    /**
     * Clear chat history for specific session.
     */
    public function clearHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'session_id wajib diisi.',
            ], 422);
        }

        $user = $request->user();
        $this->aiService->clearHistory($user->id, $request->input('session_id'));

        return response()->json([
            'success' => true,
            'message' => 'Riwayat percakapan berhasil dibersihkan.',
        ]);
    }
}
