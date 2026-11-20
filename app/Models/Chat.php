<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $client_id
 * @property int|null $last_message_id
 * @property int|null $client_last_read_message_id
 * @property int|null $staff_last_read_message_id
 * @property-read Client $client
 * @property-read ChatMessage|null $lastMessage
 */
class Chat extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'client_id' => 'integer',
        'last_message_id' => 'integer',
        'client_last_read_message_id' => 'integer',
        'staff_last_read_message_id' => 'integer',
    ];

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class);
    }

    public function messages(): HasMany {
        return $this->hasMany(ChatMessage::class);
    }

    public function lastMessage(): BelongsTo {
        return $this->belongsTo(ChatMessage::class, 'last_message_id');
    }
}
