<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

Route::get('/', function () { 
    return view('welcome'); 
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () { 
        return view('dashboard'); 
    })->name('dashboard');

    // --- FITUR CHAT UTAMA ---
    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
    Route::get('/messages/{userId}', [ChatController::class, 'getMessages']);
    Route::get('/messages/group/{groupId}', [ChatController::class, 'getGroupMessages']);
    Route::post('/messages', [ChatController::class, 'sendMessage']);
    
    // --- FITUR GRUP (FIXED) ---
    // Mengarah ke storeGroup sesuai struktur Controller kamu agar tidak error SQL lagi
    Route::post('/groups', [ChatController::class, 'storeGroup']);
    
    // BARU: API untuk mengambil daftar anggota di dalam grup (Mengatasi "Gagal memuat anggota")
    Route::get('/groups/{group}/users', [ChatController::class, 'getGroupUsers']);

    // --- FITUR USER PROFILE ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// WAJIB ADA: Mengaktifkan jalur otentikasi realtime Reverb / Pusher
Broadcast::routes();

require __DIR__.'/auth.php';