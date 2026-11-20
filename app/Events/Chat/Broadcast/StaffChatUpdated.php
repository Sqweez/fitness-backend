<?php

namespace App\Events\Chat\Broadcast;

use App\Http\Resources\Chat\ChatClientResource;
use App\Http\Resources\Chat\ChatMessageResource;
use App\Http\Services\ChatService;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;

/**
 * Realtime update for the club staff in the admin panel: "chat.message" with a new message, "chat.read" otherwise.
 * Broadcast immediately: the database queue worker would delay delivery by its poll interval.
 * Rescued: if Reverb is down the error is reported and the request still succeeds.
 */
class StaffChatUpdated implements ShouldBroadcastNow, ShouldRescue
{
    public Chat $chat;
    public ?ChatMessage $message;

    public function __construct(Chat $chat, ?ChatMessage $message = null) {
        $this->chat = $chat;
        $this->message = $message;
    }

    public function broadcastOn(): PrivateChannel {
        return new PrivateChannel('chat.club.' . $this->chat->loadMissing('client')->client->club_id);
    }

    public function broadcastAs(): string {
        return $this->message ? 'chat.message' : 'chat.read';
    }

    public function broadcastWith(): array {
        $payload = ['chat_id' => (string) $this->chat->id];
        if ($this->message) {
            $payload['client'] = ChatClientResource::make($this->chat->loadMissing('client')->client)->resolve();
            $payload['message'] = ChatMessageResource::make($this->message->loadMissing('user'))->resolve();
        }

        return $payload + [
            'unread_count' => app(ChatService::class)->getStaffUnreadCount($this->chat),
            'client_last_read_message_id' => $this->chat->client_last_read_message_id === null ? null : (string) $this->chat->client_last_read_message_id,
        ];
    }
}
