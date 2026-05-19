<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

Route::get('/', function () { return view('welcome'); });

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () { return view('dashboard'); })->name('dashboard');

    // Chat Utama
    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
    Route::get('/messages/{userId}', [ChatController::class, 'getMessages']);
    Route::get('/messages/group/{groupId}', [ChatController::class, 'getGroupMessages']);
    Route::post('/messages', [ChatController::class, 'sendMessage']);
    
    // API Grup - Pastikan mengarah ke storeGroup
    Route::post('/groups', [ChatController::class, 'storeGroup']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
});

// WAJIB ADA: Mengaktifkan jalur realtime
Broadcast::routes();

require __DIR__.'/auth.php';