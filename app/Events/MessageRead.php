<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $conversationId;
    public string $readAt;
    public string $readerType;

    public function __construct(int $conversationId, string $readerType = 'cs')
    {
        $this->conversationId = $conversationId;
        $this->readAt = now()->toIso8601String();
        $this->readerType = $readerType;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->conversationId),
            new PrivateChannel('cs-chats'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }

    public function broadcastWith(): array
    {
        return [
            'conversationId' => $this->conversationId,
            'readAt' => $this->readAt,
            'readerType' => $this->readerType,
        ];
    }
}
