<?php

namespace App\Events\Chat;

use App\Models\Chat;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The client or the club staff moved their read pointer forward.
 */
class ChatRead implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public Chat $chat;

    public function __construct(Chat $chat) {
        $this->chat = $chat;
    }
}
