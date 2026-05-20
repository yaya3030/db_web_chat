<?php

use Illuminate\Support\Facades\Broadcast;

/**
 * 1. PRESENCE CHANNEL: 'chat'
 * Tempat mengumpulkan radar user yang sedang online/offline.
 */
Broadcast::channel('chat', function ($user) {
    if (auth()->check()) {
        return [
            'id'   => $user->id,
            'name' => $user->name,
        ];
    }
    return false;
});

/**
 * 2. PRIVATE USER CHANNEL: 'user.{receiverId}'
 * Diperbaiki: Menggunakan receiverId agar sesuai dengan logika penerima pesan di Event.
 * User hanya bisa mendengarkan channel yang memiliki ID sama dengan ID dirinya sendiri.
 */
Broadcast::channel('user.{receiverId}', function ($user, $receiverId) {
    return (int) $user->id === (int) $receiverId;
});

/**
 * 3. PRIVATE GROUP CHANNEL: 'group.{groupId}'
 * Menggunakan exists() untuk pengecekan super cepat di database pivot.
 */
Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    return $user->groups()->where('groups.id', $groupId)->exists();
});