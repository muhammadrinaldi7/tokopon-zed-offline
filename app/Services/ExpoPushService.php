<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushService
{
    protected const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * Memeriksa apakah token memiliki format resmi Expo Push Token.
     */
    public function isValidExpoPushToken(?string $token): bool
    {
        if (empty($token) || !is_string($token)) {
            return false;
        }

        return str_starts_with($token, 'ExponentPushToken[') || str_starts_with($token, 'ExpoPushToken[');
    }

    /**
     * Mengirimkan satu push notification ke perangkat pengguna melalui Expo Push API.
     *
     * @param string $pushToken
     * @param string $title
     * @param string $body
     * @param array $data
     * @param string|null $channelId
     * @return array
     */
    public function sendNotification(
        string $pushToken,
        string $title,
        string $body,
        array $data = [],
        ?string $channelId = 'chat-messages'
    ): array {
        if (!$this->isValidExpoPushToken($pushToken)) {
            Log::warning("ExpoPushService: Token push tidak valid atau kosong: '{$pushToken}'");
            return [
                'success' => false,
                'message' => 'Token push tidak valid atau kosong.',
            ];
        }

        $payload = [
            'to' => $pushToken,
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
            'channelId' => $channelId ?: 'chat-messages',
            'priority' => 'high',
            'data' => $data,
        ];

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->timeout(10)->post(self::EXPO_PUSH_URL, $payload);

            $result = $response->json();

            if ($response->successful()) {
                // Expo API mengembalikan data: [{"status": "ok", "id": "..."}] atau data: {"status": "ok", "id": "..."}
                $ticket = isset($result['data'][0]) ? $result['data'][0] : ($result['data'] ?? []);
                $ticketStatus = $ticket['status'] ?? null;

                if ($ticketStatus === 'ok') {
                    Log::info("ExpoPushService: Berhasil mengirim notifikasi ke {$pushToken}");
                    return [
                        'success' => true,
                        'message' => 'Notifikasi berhasil dikirim.',
                        'data' => $result,
                    ];
                }

                $ticketError = $ticket['message'] ?? ($ticket['details']['error'] ?? 'Unknown ticket error');
                Log::warning("ExpoPushService: Expo mengembalikan error status tiket: {$ticketError}", ['payload' => $payload, 'result' => $result]);

                return [
                    'success' => false,
                    'message' => "Expo ticket error: {$ticketError}",
                    'data' => $result,
                ];
            }

            Log::error("ExpoPushService: Gagal request HTTP ({$response->status()}): " . $response->body());
            return [
                'success' => false,
                'message' => "HTTP request error status: {$response->status()}",
                'response' => $result,
            ];
        } catch (\Throwable $e) {
            Log::error("ExpoPushService Crash: " . $e->getMessage(), [
                'push_token' => $pushToken,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Mengirimkan notifikasi pesan chat khusus untuk Customer Service.
     */
    public function sendChatMessageNotification(
        string $pushToken,
        string $senderName,
        string $messageText,
        int|string $conversationId,
        array $extraData = []
    ): array {
        $title = $senderName ?: 'Admin CS Tokopon';
        $body = $messageText;

        $data = array_merge([
            'url' => '/(tabs)/chat',
            'conversation_id' => (string) $conversationId,
        ], $extraData);

        return $this->sendNotification($pushToken, $title, $body, $data, 'chat-messages');
    }
}
