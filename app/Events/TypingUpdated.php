<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TypingUpdated implements ShouldBroadcastNow
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
            new Channel('chat.' . $this->conversationId),
            new Channel('admin'),
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
