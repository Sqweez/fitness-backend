<?php

namespace App\Listeners\Chat;

use App\Events\Chat\Broadcast\ClientChatUpdated;
use App\Events\Chat\Broadcast\StaffChatUpdated;
use App\Events\Chat\ChatMessageSent;

class BroadcastChatMessage
{
    public function handle(ChatMessageSent $event): void {
        $chat = $event->message->chat()->first();

        broadcast(new ClientChatUpdated($chat, $event->message));
        broadcast(new StaffChatUpdated($chat, $event->message));
    }
}
