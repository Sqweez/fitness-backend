<?php

namespace App\Events\Chat;

use App\Http\Services\ChatService;
use App\Models\Chat;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The client or the club staff moved their read pointer forward.
 * Both sides get it: counters for the reader's other devices, read receipts for the other side.
 */
class ChatRead implements ShouldBroadcastNow, ShouldRescue, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public Chat $chat;

    public function __construct(Chat $chat) {
        $this->chat = $chat;
    }

    public function broadcastOn(): array {
        $chat = $this->chat->loadMissing('client');

        return [
            new PrivateChannel('chat.client.' . $chat->client_id),
            new PrivateChannel('chat.club.' . $chat->client->club_id),
        ];
    }

    public function broadcastAs(): string {
        return 'chat.read';
    }

    public function broadcastWith(): array {
        return app(ChatService::class)->getReadState($this->chat);
    }
}
