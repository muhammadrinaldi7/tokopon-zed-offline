<?php

namespace App\Livewire\Admin\AiAssistant;

use App\Models\AiChatHistory;
use App\Models\AiSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Chat extends Component
{
    public $message = '';
    public $histories = [];
    public $chatSessionId;

    public function mount()
    {
        $this->chatSessionId = (string) Str::uuid();
    }

    #[Layout('layouts.admin')]
    public function render()
    {
        $this->histories = AiChatHistory::where('admin_id', Auth::id())
            ->orderBy('created_at', 'asc')
            ->get();

        return view('livewire.admin.ai-assistant.chat');
    }

    public function resetChat()
    {
        // Putus ingatan AI dengan membuat ID sesi baru
        $this->chatSessionId = (string) Str::uuid();

        // Hapus history lama di database agar layar benar-benar bersih
        AiChatHistory::where('admin_id', Auth::id())->delete();

        $this->histories = AiChatHistory::where('admin_id', Auth::id())
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function sendMessage()
    {
        $this->validate([
            'message' => 'required|string',
        ]);

        $adminId = Auth::id();
        $sessionId = $this->chatSessionId;
        $userMessage = $this->message;

        // Kosongkan input segera
        $this->message = '';

        // 1. Simpan pesan user
        AiChatHistory::create([
            'admin_id' => $adminId,
            'session_id' => $sessionId,
            'role' => 'user',
            'message' => $userMessage,
        ]);

        // Refresh UI untuk memunculkan bubble user seketika
        $this->histories = AiChatHistory::where('admin_id', Auth::id())
            ->orderBy('created_at', 'asc')
            ->get();

        // 2. Perintahkan browser untuk memanggil fungsi fetchAiResponse
        $this->dispatch('callFetchAi');
        $this->dispatch('messageSent');
    }

    public function fetchAiResponse()
    {
        $adminId = Auth::id();
        $sessionId = $this->chatSessionId;

        // Ambil pesan user terakhir untuk dikirim ke n8n
        $lastUserMessage = AiChatHistory::where('admin_id', $adminId)
            ->where('session_id', $sessionId)
            ->where('role', 'user')
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastUserMessage) {
            return;
        }

        $userMessage = $lastUserMessage->message;

        try {
            // 2. Ambil URL Webhook n8n dari tabel ai_settings
            $setting = AiSetting::where('provider', 'n8n')->first();
            $webhookUrl = $setting ? $setting->api_key : null;
            $webhookToken = $setting ? $setting->model : env('N8N_AGENT_TOKEN', 'zedpos-2026-banjarbaru');

            if (!$webhookUrl) {
                throw new \Exception('URL Webhook n8n belum dikonfigurasi di pengaturan AI.');
            }

            // 4. Siapkan payload JSON
            $payload = [
                'pertanyaan' => $userMessage,
                'session_id' => $sessionId,
                'admin_id' => $adminId,
            ];

            // 3. Lakukan HTTP POST Request ke n8n dengan timeout 60 detik
            $response = Http::withHeaders([
                'X-Zedpos-Agent-Token' => $webhookToken,
            ])->timeout(60)->post($webhookUrl, $payload);

            // Cek jika status HTTP 500 atau error lainnya
            if ($response->failed()) {
                throw new \Exception('Terjadi kesalahan pada server AI (n8n). Status: ' . $response->status());
            }

            // 5. Tangkap response JSON dari n8n
            $responseData = $response->json();

            if (!isset($responseData['jawaban_ai'])) {
                Log::warning('Format response n8n tidak sesuai:', ['response' => $responseData]);
                throw new \Exception('Format balasan dari n8n tidak memiliki properti "jawaban_ai".');
            }

            $replyMessage = $responseData['jawaban_ai'];

            // 1. Deteksi apakah AI meminta pembuatan file
            if (strpos($replyMessage, '[GENERATE_FILE]') !== false) {
                preg_match('/Tipe:\s*(.*)/', $replyMessage, $matchType);
                preg_match('/Query:\s*(.*)/', $replyMessage, $matchQuery);
                preg_match('/Pesan:\s*(.*)/', $replyMessage, $matchPesan);

                $fileType = isset($matchType[1]) ? trim(strtolower($matchType[1])) : 'excel';
                $sqlQuery = isset($matchQuery[1]) ? trim($matchQuery[1]) : '';
                $pesanAi  = isset($matchPesan[1]) ? trim($matchPesan[1]) : 'File Anda sedang disiapkan...';

                $replyMessage = $pesanAi . ' ⏳ (Memproses unduhan...)';

                session()->put('ai_export_data', [
                    'query' => $sqlQuery,
                    'type'  => $fileType
                ]);

                $this->dispatch('triggerDownload', url: route('ai.export.report'));
            }
            
            if (trim($replyMessage) === '') {
                $replyMessage = '⚠️ (n8n membalas dengan teks kosong. Silakan cek pemetaan output di workflow n8n Anda.)';
            }

            // 6. Simpan jawaban_ai ke dalam tabel ai_chat_histories
            AiChatHistory::create([
                'admin_id' => $adminId,
                'session_id' => $sessionId,
                'role' => 'assistant',
                'message' => $replyMessage,
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('n8n Webhook Timeout/Connection Error: ' . $e->getMessage());
            AiChatHistory::create([
                'admin_id' => $adminId,
                'session_id' => $sessionId,
                'role' => 'assistant',
                'message' => '⚠️ Mohon maaf, AI sedang sibuk atau mengalami timeout (melebihi 60 detik). Silakan coba lagi nanti.',
            ]);
        } catch (\Exception $e) {
            Log::error('n8n Webhook Error: ' . $e->getMessage());
            AiChatHistory::create([
                'admin_id' => $adminId,
                'session_id' => $sessionId,
                'role' => 'assistant',
                'message' => '⚠️ Oops, terjadi kesalahan: ' . $e->getMessage(),
            ]);
        }

        // Refresh UI
        $this->histories = AiChatHistory::where('admin_id', Auth::id())
            ->orderBy('created_at', 'asc')
            ->get();

        $this->dispatch('messageSent');
    }
}
