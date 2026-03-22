<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('conversation.' . $this->message->conversation_id),
            new Channel('admin'), // Also broadcast to admin channel
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversationId' => $this->message->conversation_id,
            'senderRole' => $this->message->sender_role,
            'messageType' => $this->message->message_type,
            'text' => $this->message->text,
            'fileUrl' => $this->message->file_url,
            'fileName' => $this->message->file_name,
            'fileMime' => $this->message->file_mime,
            'createdAt' => $this->message->created_at?->toIso8601String(),
        ];
    }
}
