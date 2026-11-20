<?php

namespace App\Events\Chat;

use App\Models\ChatMessage;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A new chat message was saved. Every reaction (realtime, push, ...) is a listener in EventServiceProvider.
 */
class ChatMessageSent implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public ChatMessage $message;

    public function __construct(ChatMessage $message) {
        $this->message = $message;
    }
}
