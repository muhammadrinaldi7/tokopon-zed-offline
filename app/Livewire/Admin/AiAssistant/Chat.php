<?php

namespace App\Livewire\Admin\AiAssistant;

use App\Models\AiChatHistory;
use App\Models\AiSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Chat extends Component
{
    public $message = '';
    public $histories = [];

    #[Layout('layouts.admin')]
    public function render()
    {
        $this->histories = AiChatHistory::where('admin_id', Auth::id())
            ->orderBy('created_at', 'asc')
            ->get();

        return view('livewire.admin.ai-assistant.chat');
    }

    public function sendMessage()
    {
        $this->validate([
            'message' => 'required|string',
        ]);

        $adminId = Auth::id();
        $sessionId = request()->session()->getId();
        $userMessage = $this->message;

        // Kosongkan input
        $this->message = '';

        // 1. Simpan pesan user
        AiChatHistory::create([
            'admin_id' => $adminId,
            'session_id' => $sessionId,
            'role' => 'user',
            'message' => $userMessage,
        ]);

        try {
            // 2. Ambil URL Webhook n8n dari tabel ai_settings
            // Asumsi: provider diset ke 'n8n' dan url disimpan di kolom api_key
            $setting = AiSetting::where('provider', 'n8n')->first();
            $webhookUrl = $setting ? $setting->api_key : null;

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
            $response = Http::timeout(60)->post($webhookUrl, $payload);

            // Cek jika status HTTP 500 atau error lainnya
            if ($response->failed()) {
                throw new \Exception('Terjadi kesalahan pada server AI (n8n). Status: ' . $response->status());
            }

            // 5. Tangkap response JSON dari n8n
            $responseData = $response->json();

            if (!isset($responseData['jawaban_ai'])) {
                // Fallback jika format JSON dari n8n tidak memiliki key jawaban_ai
                Log::warning('Format response n8n tidak sesuai:', ['response' => $responseData]);
                throw new \Exception('Format balasan dari n8n tidak memiliki properti "jawaban_ai". Pastikan node terakhir di n8n mengeluarkan output JSON dengan key tersebut.');
            }

            $replyMessage = $responseData['jawaban_ai'];

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
            // Penanganan error timeout
            Log::error('n8n Webhook Timeout/Connection Error: ' . $e->getMessage());
            AiChatHistory::create([
                'admin_id' => $adminId,
                'session_id' => $sessionId,
                'role' => 'assistant',
                'message' => '⚠️ Mohon maaf, AI sedang sibuk atau mengalami timeout (melebihi 60 detik). Silakan coba lagi nanti.',
            ]);
        } catch (\Exception $e) {
            // 7. Penanganan error umum (ramah untuk UI)
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
