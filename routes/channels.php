<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{receiverId}', function ($user, $receiverId) {
    // User hanya bisa mendengarkan channel jika ID-nya cocok dengan receiverId
    return (int) $user->id === (int) $receiverId;
});
