<?php

namespace App\Events\Chat\Broadcast;

use App\Http\Resources\Mobile\Chat\ChatMessageResource;
use App\Http\Services\ChatService;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;

/**
 * Realtime update for the client's app: "chat.message" with a new message, "chat.read" otherwise.
 * Broadcast immediately: the database queue worker would delay delivery by its poll interval.
 * Rescued: if Reverb is down the error is reported and the request still succeeds.
 */
class ClientChatUpdated implements ShouldBroadcastNow, ShouldRescue
{
    public Chat $chat;
    public ?ChatMessage $message;

    public function __construct(Chat $chat, ?ChatMessage $message = null) {
        $this->chat = $chat;
        $this->message = $message;
    }

    public function broadcastOn(): PrivateChannel {
        return new PrivateChannel('chat.client.' . $this->chat->client_id);
    }

    public function broadcastAs(): string {
        return $this->message ? 'chat.message' : 'chat.read';
    }

    public function broadcastWith(): array {
        $payload = $this->message ? ['message' => ChatMessageResource::make($this->message->loadMissing('user'))->resolve()] : [];

        return $payload + [
            'unread_count' => app(ChatService::class)->getClientUnreadCount($this->chat),
            'staff_last_read_message_id' => $this->chat->staff_last_read_message_id === null ? null : (string) $this->chat->staff_last_read_message_id,
        ];
    }
}
