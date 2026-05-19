<?php

namespace App\Http\Controllers;

use App\Models\{Message, User, Group};
use App\Events\MessageSent; // Pastikan memanggil Event jika kamu menggunakan broadcast pesan
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function index() {
        // Mengambil semua user selain diri sendiri, dan mengambil grup yang diikuti oleh user saat ini
        return view('chat', [
            'users' => User::where('id', '!=', Auth::id())->get(),
            'groups' => Auth::user()->groups ?? collect()
        ]);
    }

    public function storeGroup(Request $request) {
        // Validasi input nama grup
        $request->validate(['name' => 'required|string|max:255']);

        try {
            // FIX: Menyertakan 'created_by' agar MySQL tidak melempar error General Error 1364 lagi
            $group = Group::create([
                'name' => $request->name,
                'created_by' => Auth::id() // Otomatis mengisi id user pembuat grup
            ]);
            
            // Hubungkan user yang sedang login ke tabel pivot 'group_user'
            $group->users()->attach(Auth::id());
            
            return response()->json([
                'status' => 'success', 
                'group' => $group
            ]);
        } catch (\Exception $e) {
            // MENAMPILKAN ERROR ASLI DARI DATABASE
            return response()->json([
                'status' => 'error', 
                'message' => 'Database Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getMessages($userId) {
        return Message::where(function($q) use ($userId) {
            $q->where('sender_id', Auth::id())->where('receiver_id', $userId);
        })->orWhere(function($q) use ($userId) {
            $q->where('sender_id', $userId)->where('receiver_id', Auth::id());
        })->with('sender')->orderBy('created_at', 'asc')->get();
    }

    public function getGroupMessages($groupId) {
        return Message::where('group_id', $groupId)->with('sender')->orderBy('created_at', 'asc')->get();
    }

    public function sendMessage(Request $request) {
        // Membuat data pesan di database
        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'group_id' => $request->group_id,
            'message' => $request->message,
        ]);
        
        $loadedMessage = $message->load('sender');

        // Pemicu Realtime: Menyiarkan pesan ke user lain secara live lewat Reverb
        // (Aktifkan baris di bawah jika kamu sudah membuat file MessageSent Event)
        // broadcast(new MessageSent($loadedMessage))->toOthers();
        
        return response()->json(['message' => $loadedMessage]);
    }
}