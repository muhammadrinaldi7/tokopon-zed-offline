<?php

namespace App\Jobs;

use App\Services\ExpoPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendExpoPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 20;

    public string $pushToken;
    public string $title;
    public string $body;
    public array $data;
    public ?string $channelId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $pushToken,
        string $title,
        string $body,
        array $data = [],
        ?string $channelId = 'chat-messages'
    ) {
        $this->pushToken = $pushToken;
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
        $this->channelId = $channelId;
    }

    /**
     * Execute the job.
     */
    public function handle(ExpoPushService $expoPushService): void
    {
        $result = $expoPushService->sendNotification(
            $this->pushToken,
            $this->title,
            $this->body,
            $this->data,
            $this->channelId
        );

        if (!$result['success']) {
            Log::warning("SendExpoPushNotificationJob: Notifikasi gagal dikirim: " . ($result['message'] ?? '-'));
        }
    }
}
