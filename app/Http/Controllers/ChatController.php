<?php

namespace App\Http\Controllers;

use App\Models\{Message, User, Group};
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function index() {
        return view('chat', [
            'users' => User::where('id', '!=', Auth::id())->get(),
            'groups' => Auth::user()->groups()->get()
        ]);
    }

    public function storeGroup(Request $request) {
        $request->validate(['name' => 'required|string|max:255']);

        // Menggunakan Database Transaction agar tidak ada data setengah jadi
        return DB::transaction(function () use ($request) {
            try {
                $group = Group::create([
                    'name' => $request->name,
                    'created_by' => Auth::id()
                ]);
                
                // Ambil semua ID user yang terdaftar
                $allUserIds = User::pluck('id')->toArray();
                
                // Masukkan semua user ke tabel pivot
                $group->users()->sync($allUserIds);
                
                return response()->json([
                    'status' => 'success', 
                    'group' => $group
                ]);
            } catch (\Exception $e) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
            }
        });
    }

    public function getGroupUsers(Group $group)
    {
        // Mengembalikan daftar user dengan relasi
        return response()->json($group->users()->get());
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
        $request->validate([
            'message' => 'required|string',
            'group_id' => 'nullable|exists:groups,id',
            'receiver_id' => 'nullable|exists:users,id'
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'group_id' => $request->group_id,
            'message' => $request->message,
        ]);
        
        $loadedMessage = $message->load('sender');
        broadcast(new MessageSent($loadedMessage))->toOthers();
        
        return response()->json(['message' => $loadedMessage]);
    }
}