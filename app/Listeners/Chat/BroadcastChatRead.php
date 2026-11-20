<?php

namespace App\Listeners\Chat;

use App\Events\Chat\Broadcast\ClientChatUpdated;
use App\Events\Chat\Broadcast\StaffChatUpdated;
use App\Events\Chat\ChatRead;

/**
 * Both sides get it: counters for the reader's other devices, read receipts for the other side.
 */
class BroadcastChatRead
{
    public function handle(ChatRead $event): void {
        broadcast(new ClientChatUpdated($event->chat));
        broadcast(new StaffChatUpdated($event->chat));
    }
}
