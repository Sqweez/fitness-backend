<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Browser push subscription of a staff member (admin panel).
 *
 * @property int $id
 * @property int $user_id
 * @property string $endpoint
 * @property string $endpoint_hash
 * @property string $public_key
 * @property string $auth_token
 * @property string $content_encoding
 */
class WebPushSubscription extends Model
{
    const CONTENT_ENCODINGS = ['aesgcm', 'aes128gcm'];

    protected $guarded = ['id'];

    public static function hashEndpoint(string $endpoint): string {
        return hash('sha256', $endpoint);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
