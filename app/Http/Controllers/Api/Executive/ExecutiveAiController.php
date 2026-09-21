<?php

namespace App\Http\Controllers\Api\Executive;

use App\Http\Controllers\Controller;
use App\Services\ExecutiveAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExecutiveAiController extends Controller
{
    protected ExecutiveAiService $aiService;

    public function __construct(ExecutiveAiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Send chat prompt to 9router AI with live context.
     */
    public function chat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:5000',
            'session_id' => 'nullable|string',
            'context_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $sessionId = $request->input('session_id') ?: ('session-' . date('Ymd'));
        $contextData = $request->input('context_data');

        try {
            $data = $this->aiService->chat(
                $request->message,
                $sessionId,
                $user->id,
                $contextData,
                $user
            );

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal berkomunikasi dengan AI 9router: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get chat history for given session.
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->query('session_id');
        $limit = (int) $request->query('limit', 50);

        $history = $this->aiService->getHistory($user->id, $sessionId, $limit);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * 1-Click Executive strategic summary generation.
     */
    public function summarize(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'context_data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: context_data wajib disertakan.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $contextData = $request->input('context_data');

        try {
            $data = $this->aiService->generateExecutiveSummary($contextData, $user->id, $user);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghasilkan ringkasan AI: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear chat history for specific session.
     */
    public function clearHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'session_id wajib diisi.',
            ], 422);
        }

        $user = $request->user();
        $this->aiService->clearHistory($user->id, $request->session_id);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat chat berhasil dibersihkan.',
        ]);
    }
}
