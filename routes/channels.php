<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Group;
use App\Models\User;

/**
 * 1. PRESENCE CHANNEL: 'chat'
 * Menggunakan model User untuk memastikan data terverifikasi.
 */
Broadcast::channel('chat', function ($user) {
    return [
        'id'   => $user->id,
        'name' => $user->name,
    ];
});

/**
 * 2. PRIVATE GROUP CHANNEL: 'group.{groupId}'
 * Menggunakan relasi model yang lebih efisien.
 */
Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    // Memastikan user adalah anggota grup yang valid
    return $user->groups()->where('groups.id', $groupId)->exists();
});

/**
 * 3. PRIVATE USER CHANNEL: 'user.{userId}'
 * Penambahan pengecekan tipe data (casting) untuk keamanan.
 */
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});