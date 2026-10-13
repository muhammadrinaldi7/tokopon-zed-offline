<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    if (!$conversation) return false;

    // User pemilik conversation ATAU user dengan role CS / admin
    return (int) $user->id === (int) $conversation->user_id 
        || $user->hasRole(['admin', 'superadmin', 'cs', 'manager', 'kasir_sju'])
        || $user->can('access-cs-chat');
});

Broadcast::channel('cs-chats', function ($user) {
    return $user->hasRole(['admin', 'superadmin', 'cs', 'manager', 'kasir_sju'])
        || $user->can('access-cs-chat');
});

