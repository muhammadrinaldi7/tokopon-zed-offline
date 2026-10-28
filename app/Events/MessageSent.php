<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $user;
    public string $message;
    public string $time;
    public int $userId;
    public int $conversationId;
    public string $senderType;
    public ?int $id;

    public function __construct(
        string $user,
        string $message,
        string $time,
        int $userId,
        int $conversationId,
        string $senderType = 'customer',
        ?int $id = null
    ) {
        $this->user = $user;
        $this->message = $message;
        $this->time = $time;
        $this->userId = $userId;
        $this->conversationId = $conversationId;
        $this->senderType = $senderType;
        $this->id = $id;
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
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->id,
            'user' => $this->user,
            'message' => $this->message,
            'time' => $this->time,
            'userId' => $this->userId,
            'conversationId' => $this->conversationId,
            'senderType' => $this->senderType,
        ];
    }
}
