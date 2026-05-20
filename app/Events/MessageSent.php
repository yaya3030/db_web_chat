<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // PASTIKAN MENGGUNAKAN ShouldBroadcastNow
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow // UBAH DI SINI
{
    use Dispatchable, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        // Memastikan relasi pengirim (sender) ikut terbawa ke dalam payload WebSocket
        $this->message = $message->load('sender');
    }

    public function broadcastOn(): Channel
    {
        // Jika pesan dikirim ke grup, siarkan ke channel group, jika personal siarkan ke channel user
        if ($this->message->group_id) {
            return new PrivateChannel('group.' . $this->message->group_id);
        }

        return new PrivateChannel('user.' . $this->message->receiver_id);
    }
}