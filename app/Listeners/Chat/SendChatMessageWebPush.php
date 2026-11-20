<?php

namespace App\Listeners\Chat;

use App\Events\Chat\ChatMessageSent;
use App\Http\Services\WebPushService;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Browser push to the club's admins and bosses about a new client message.
 */
class SendChatMessageWebPush implements ShouldQueue
{
    private WebPushService $webPush;

    public function __construct(WebPushService $webPush) {
        $this->webPush = $webPush;
    }

    public function handle(ChatMessageSent $event): void {
        $message = $event->message->load('chat.client');
        if (!$message->is_from_client) {
            return;
        }
        $chat = $message->chat;

        // Same audience as the chat.club.{clubId} channel; inactive staff must not keep receiving client messages
        $userIds = User::query()->active()
            ->whereHas('roles', function (Builder $query) {
                $query->whereIn('roles.id', [Role::ROLE_BOSS, Role::ROLE_ADMIN]);
            })
            ->whereHas('clubs', function (Builder $query) use ($chat) {
                $query->whereKey($chat->client->club_id);
            })
            ->pluck('id');

        $this->webPush->sendToUsers($userIds, [
            'title' => $chat->client->name,
            'body' => Str::limit($message->text, 200),
            'tag' => 'chat-' . $chat->id,
            'data' => [
                'type' => 'chat.message',
                'chat_id' => (string) $chat->id,
                'message_id' => (string) $message->id,
                'url' => '/chats?chat=' . $chat->id,
            ],
        ]);
    }
}
