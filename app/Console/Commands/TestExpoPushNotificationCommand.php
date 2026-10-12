<?php

namespace App\Console\Commands;

use App\Services\ExpoPushService;
use Illuminate\Console\Command;

class TestExpoPushNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:test {push_token : Expo push token tujuan (ExponentPushToken[...])}
                            {--title=Tes Notifikasi Tokopon : Judul notifikasi}
                            {--body=Halo! Ini adalah notifikasi uji coba dari Tokopon Backend. : Isi pesan notifikasi}
                            {--conversation_id=1 : ID percakapan chat opsional}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi uji coba ke perangkat mobile via Expo Push API';

    /**
     * Execute the console command.
     */
    public function handle(ExpoPushService $expoPushService): int
    {
        $pushToken = $this->argument('push_token');
        $title = $this->option('title');
        $body = $this->option('body');
        $conversationId = $this->option('conversation_id');

        $this->info("=== EXPO PUSH NOTIFICATION TEST ===");
        $this->line("Target Token    : <comment>{$pushToken}</comment>");
        $this->line("Title           : <comment>{$title}</comment>");
        $this->line("Body            : <comment>{$body}</comment>");
        $this->line("Channel ID      : <comment>chat-messages</comment>");
        $this->line("Conversation ID : <comment>{$conversationId}</comment>");
        $this->newLine();

        if (!$expoPushService->isValidExpoPushToken($pushToken)) {
            $this->error("ERROR: Format token tidak valid! Token harus berawalan 'ExponentPushToken[' atau 'ExpoPushToken['.");
            return self::FAILURE;
        }

        $this->info("Mengirim notifikasi ke server Expo (https://exp.host/--/api/v2/push/send)...");

        $result = $expoPushService->sendChatMessageNotification(
            $pushToken,
            $title,
            $body,
            $conversationId
        );

        $this->newLine();
        if ($result['success']) {
            $this->info("BERHASIL! Notifikasi sukses diterima oleh gateway Expo.");
            $this->line(json_encode($result['data'] ?? [], JSON_PRETTY_PRINT));
            return self::SUCCESS;
        } else {
            $this->error("GAGAL! " . ($result['message'] ?? 'Unknown error'));
            if (!empty($result['data']) || !empty($result['response'])) {
                $this->line(json_encode($result['data'] ?? ($result['response'] ?? []), JSON_PRETTY_PRINT));
            }
            return self::FAILURE;
        }
    }
}
