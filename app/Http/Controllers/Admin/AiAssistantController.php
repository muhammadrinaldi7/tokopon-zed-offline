<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiChatHistory;
use App\Models\AiSetting;
use App\Ai\Agents\DatabaseAnalyzerAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Ai as LaravelAi;
use Laravel\Ai\Facades\Ai;

class AiAssistantController extends Controller
{
    public function index()
    {
        $adminId = Auth::id();
        $histories = AiChatHistory::where('admin_id', $adminId)
            ->orderBy('created_at', 'asc')
            ->get();

        return view('admin.ai-assistant.chat', compact('histories'));
    }

    public function settings()
    {
        $setting = AiSetting::first();
        if (!$setting) {
            $setting = new AiSetting([
                'provider' => 'gemini',
                'model' => 'gemini-1.5-flash',
                'system_prompt' => "Anda adalah Agen AI Analis Database profesional untuk sistem aplikasi Tokopon. Tugas Anda adalah menjawab pertanyaan user dengan cara menganalisis database secara akurat.",
            ]);
        }

        return view('admin.ai-assistant.settings', compact('setting'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'model' => 'required|string',
            'system_prompt' => 'required|string',
        ]);

        $setting = AiSetting::first();
        if (!$setting) {
            $setting = new AiSetting();
        }

        $setting->model = $request->model;
        $setting->system_prompt = $request->system_prompt;
        $setting->save();

        return redirect()->back()->with('success', 'Pengaturan AI berhasil diperbarui.');
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $adminId = Auth::id();
        $sessionId = $request->session()->getId();

        // 1. Simpan pesan user
        AiChatHistory::create([
            'admin_id' => $adminId,
            'session_id' => $sessionId,
            'role' => 'user',
            'message' => $request->message,
        ]);

        try {
            // 2. Inisiasi Agent dengan memberikan context adminId/sessionId
            // Di sini kita asumsikan Agent bisa menerima session ID / admin ID melalui setter atau constructor
            $agent = new DatabaseAnalyzerAgent($adminId);

            // 3. Panggil AI Facade (sesuaikan dengan cara eksekusi library Laravel AI Anda)
            // Misalnya jika menggunakan paket pihak ketiga:
            $response = LaravelAi::execute($agent, $request->message);

            $replyMessage = is_string($response) ? $response : (method_exists($response, 'content') ? $response->content() : 'Respon diterima');

            // 4. Simpan balasan AI
            AiChatHistory::create([
                'admin_id' => $adminId,
                'session_id' => $sessionId,
                'role' => 'assistant',
                'message' => $replyMessage,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => $replyMessage,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan pada AI: ' . $e->getMessage(),
            ], 500);
        }
    }
}
