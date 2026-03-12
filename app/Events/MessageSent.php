<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message->load('sender'); // eager load sender relationship
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
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
