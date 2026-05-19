<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Di sini kita mendaftarkan semua saluran siaran event yang didukung oleh
| aplikasi. Saluran 'chat' ini digunakan untuk melacak status online user.
|
 */

Broadcast::channel('chat', function ($user) {
    // KUNCI UTAMA: Mengizinkan semua user yang sudah login untuk bergabung ke radar online
    if (auth()->check()) {
        return [
            'id' => $user->id,
            'name' => $user->name
        ];
    }
    return false;
});