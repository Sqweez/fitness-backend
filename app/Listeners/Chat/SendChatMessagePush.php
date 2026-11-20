<?php

namespace App\Listeners\Chat;

use App\Events\Chat\ChatMessageSent;
use App\Http\Services\PushService;
use App\Push\PushMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

class SendChatMessagePush implements ShouldQueue
{
    private PushService $push;

    public function __construct(PushService $push) {
        $this->push = $push;
    }

    public function handle(ChatMessageSent $event): void {
        $message = $event->message->load(['chat.client', 'user']);
        // Only staff replies are pushed to the client; staff work in the admin panel
        if ($message->is_from_client) {
            return;
        }

        $this->push->sendToClient($message->chat->client, new PushMessage(
            $message->user->name,
            Str::limit($message->text, 200),
            ['type' => 'chat.message', 'message_id' => (string) $message->id],
        ));
    }
}
