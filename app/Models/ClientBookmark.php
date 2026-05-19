<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ClientBookmark
 *
 * @property int $id
 * @property int $user_id
 * @property int $client_id
 * @property-read \App\Models\Client|null $client
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|ClientBookmark newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientBookmark newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientBookmark query()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientBookmark whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientBookmark whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientBookmark whereUserId($value)
 * @mixin \Eloquent
 */
class ClientBookmark extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
