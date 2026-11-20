<?php

namespace App\Events\Chat;

use App\Http\Resources\Chat\ChatClientResource;
use App\Http\Resources\Chat\ChatMessageResource;
use App\Http\Services\ChatService;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A new chat message was saved. Broadcast to the client and the club staff;
 * other reactions (push, ...) are listeners in EventServiceProvider.
 * Broadcast immediately: the database queue worker would delay delivery by its poll interval.
 * Rescued: if Reverb is down the error is reported and the request still succeeds.
 */
class ChatMessageSent implements ShouldBroadcastNow, ShouldRescue, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public ChatMessage $message;

    public function __construct(ChatMessage $message) {
        $this->message = $message;
    }

    public function broadcastOn(): array {
        $chat = $this->message->loadMissing('chat.client')->chat;

        return [
            new PrivateChannel('chat.client.' . $chat->client_id),
            new PrivateChannel('chat.club.' . $chat->client->club_id),
        ];
    }

    public function broadcastAs(): string {
        return 'chat.message';
    }

    public function broadcastWith(): array {
        $message = $this->message->loadMissing(['chat.client', 'user']);

        return [
            'client' => ChatClientResource::make($message->chat->client)->resolve(),
            'message' => ChatMessageResource::make($message)->resolve() + [
                'direction' => $message->is_from_client ? 'outgoing' : 'incoming',
            ],
        ] + app(ChatService::class)->getReadState($message->chat);
    }
}
