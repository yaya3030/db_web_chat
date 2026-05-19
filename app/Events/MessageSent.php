<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        // Memastikan relasi sender dimuat agar data nama user terbawa
        $this->message = $message->load('sender');
    }

    /**
     * Nama event yang akan didengarkan di sisi JavaScript (Echo)
     */
    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    /**
     * Menentukan kemana pesan harus disiarkan
     */
    public function broadcastOn(): array
    {
        // 1. Jika pesan dikirim ke GRUP
        if ($this->message->group_id) {
            return [
                new PrivateChannel('group.' . $this->message->group_id),
            ];
        }

        // 2. Jika pesan dikirim secara PRIBADI (DM)
        // Kita siarkan ke channel pengirim dan penerima agar kedua layar update
        return [
            new PrivateChannel('user.' . $this->message->receiver_id),
            new PrivateChannel('user.' . $this->message->sender_id),
        ];
    }

    /**
     * Opsional: Menentukan data spesifik yang dikirim ke frontend
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
        ];
    }
}