<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Models\Group;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    /**
     * 1. Menampilkan halaman chat utama
     */
    public function index() 
    {
        return view('chat', [
            // Mengambil semua user selain user yang sedang login
            'users' => User::where('id', '!=', Auth::id())->get(),
            // Mengambil semua grup tempat user tersebut bergabung
            'groups' => Auth::user()->groups()->get()
        ]);
    }

    /**
     * 2. Membuat grup baru
     */
    public function storeGroup(Request $request) 
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        return DB::transaction(function () use ($request) {
            try {
                $group = Group::create([
                    'name' => $request->name,
                    'created_by' => Auth::id()
                ]);
                
                // Otomatis masukkan semua user terdaftar ke dalam grup baru ini
                $allUserIds = User::pluck('id')->toArray();
                $group->users()->sync($allUserIds);
                
                return response()->json([
                    'status' => 'success', 
                    'group' => $group
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error', 
                    'message' => $e->getMessage()
                ], 500);
            }
        });
    }

    /**
     * 3. Mengambil daftar user di dalam grup
     */
    public function getGroupUsers(Group $group)
    {
        return response()->json($group->users()->get());
    }

    /**
     * 4. Mengambil riwayat chat pribadi (DM)
     */
    public function getMessages($userId) 
    {
        return Message::where(function($q) use ($userId) {
            $q->where('sender_id', Auth::id())->where('receiver_id', $userId);
        })->orWhere(function($q) use ($userId) {
            $q->where('sender_id', $userId)->where('receiver_id', Auth::id());
        })
        ->with('sender')
        ->orderBy('created_at', 'asc')
        ->get();
    }

    /**
     * 5. Mengambil riwayat chat grup
     */
    public function getGroupMessages($groupId) 
    {
        return Message::where('group_id', $groupId)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * 6. Mengirim pesan (Kunci Utama Aliran Real-time)
     */
    public function sendMessage(Request $request) 
    {
        $request->validate([
            'message' => 'required|string',
            'group_id' => 'nullable|exists:groups,id',
            'receiver_id' => 'nullable|exists:users,id'
        ]);

        // Menyimpan pesan ke database
        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'group_id' => $request->group_id,
            'message' => $request->message,
        ]);
        
        // Memuat data pengirim agar nama pembuat pesan muncul di websocket frontend
        $loadedMessage = $message->load('sender');
        
        // DIUBAH: Menggunakan broadcast murni tanpa toOthers() agar bypass pengecekan header 
        // Logika pencegahan duplikasi pesan sudah kita amankan di sisi JavaScript chat.blade.php
        broadcast(new MessageSent($loadedMessage));
        
        return response()->json([
            'status' => 'success',
            'message' => $loadedMessage
        ]);
    }
}