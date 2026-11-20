<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $chat_id
 * @property int|null $user_id
 * @property string $text
 * @property string $client_message_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read Chat $chat
 * @property-read User|null $user
 * @property-read bool $is_from_client
 */
class ChatMessage extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'chat_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function chat(): BelongsTo {
        return $this->belongsTo(Chat::class);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function getIsFromClientAttribute(): bool {
        return $this->user_id === null;
    }
}
