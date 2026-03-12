<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Private channel for conversation messages
 * Only authenticated users can listen to their conversation
 * In a real app, add additional logic to verify user is a participant
 */
Broadcast::channel('conversation.{id}', function (User $user, string $id) {
    // Verify conversation exists
    $conversation = Conversation::findOrFail($id);

    // Allow authenticated user to listen
    // TODO: Add logic to check if user is actual conversation participant
    // e.g., check user session, IP, or add user_id to Conversation table
    return true;
});
