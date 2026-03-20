<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\TypingUpdated;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function index(): JsonResponse
    {
        $conversations = Conversation::query()
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get(['id', 'username', 'created_at', 'updated_at']);

        return response()->json($conversations);
    }

    public function storeConversation(): JsonResponse
    {
        $conversation = Conversation::create([
            'id' => (string) Str::uuid(),
            'username' => session('invite_username') ?? null,
        ]);

        return response()->json([
            'conversationId' => $conversation->id,
        ]);
    }

    public function messages(string $conversationId): JsonResponse
    {
        $messages = Message::query()
            ->where('conversation_id', $conversationId)
            ->orderBy('id')
            ->limit(200)
            ->get([
                'id',
                'conversation_id',
                'sender_role',
                'message_type',
                'text',
                'file_url',
                'file_name',
                'file_mime',
                'created_at',
            ]);

        return response()->json($messages);
    }

    public function storeMessage(Request $request, string $conversationId): JsonResponse
    {
        $data = $request->validate([
            'sender_role' => ['required', 'string', 'in:user,admin'],
            'message_type' => ['required', 'string', 'in:text,file,code_request,code_submission'],
            'text' => ['nullable', 'string'],
            'code' => ['nullable', 'string'],
            'file_url' => ['nullable', 'string'],
            'file_name' => ['nullable', 'string'],
            'file_mime' => ['nullable', 'string'],
        ]);

        if ($data['message_type'] === 'text' && empty($data['text'])) {
            return response()->json(['error' => 'Text is required for text messages.'], 422);
        }

        if ($data['message_type'] === 'file' && empty($data['file_url'])) {
            return response()->json(['error' => 'File URL is required for file messages.'], 422);
        }

        if ($data['message_type'] === 'code_request' && empty($data['text'])) {
            return response()->json(['error' => 'Code message is required.'], 422);
        }

        if ($data['message_type'] === 'code_submission' && empty($data['text'])) {
            return response()->json(['error' => 'Submitted code is required.'], 422);
        }

        $conversation = Conversation::firstOrCreate(['id' => $conversationId]);

        $message = $conversation->messages()->create([
            'sender_role' => $data['sender_role'],
            'message_type' => $data['message_type'],
            'text' => $data['text'] ?? null,
            'file_url' => $data['file_url'] ?? null,
            'file_name' => $data['file_name'] ?? null,
            'file_mime' => $data['file_mime'] ?? null,
        ]);

        $conversation->touch();

        try {
            broadcast(new MessageSent($message));
        } catch (\Throwable $exception) {
            Log::warning('Broadcast message failed', [
                'conversation_id' => $conversationId,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json($message);
    }

    public function typing(Request $request, string $conversationId): JsonResponse
    {
        $data = $request->validate([
            'sender_role' => ['required', 'string', 'in:user,admin'],
            'is_typing' => ['required', 'boolean'],
        ]);

        try {
            broadcast(new TypingUpdated($conversationId, $data['sender_role'], (bool) $data['is_typing']));
        } catch (\Throwable $exception) {
            Log::warning('Broadcast typing failed', [
                'conversation_id' => $conversationId,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function destroy(string $conversationId): JsonResponse
    {
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        // Delete all messages in the conversation first
        $conversation->messages()->delete();

        // Delete the conversation
        $conversation->delete();

        return response()->json(['ok' => true]);
    }
}
