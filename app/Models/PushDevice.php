<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $client_id
 * @property int $personal_access_token_id
 * @property string $token
 * @property string $platform
 */
class PushDevice extends Model
{
    const PLATFORMS = ['ios', 'android'];

    protected $guarded = ['id'];

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class);
    }
}
