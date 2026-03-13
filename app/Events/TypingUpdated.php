<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TypingUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $conversationId;
    public string $senderRole;
    public bool $isTyping;

    public function __construct(string $conversationId, string $senderRole, bool $isTyping)
    {
        $this->conversationId = $conversationId;
        $this->senderRole = $senderRole;
        $this->isTyping = $isTyping;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('conversation.' . $this->conversationId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'typing.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'conversationId' => $this->conversationId,
            'senderRole' => $this->senderRole,
            'isTyping' => $this->isTyping,
        ];
    }
}
