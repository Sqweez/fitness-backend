<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\ClientCustomService
 *
 * @property int $id
 * @property int $client_id
 * @property int $custom_service_id
 * @property int $user_id
 * @property int $club_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ClientCustomService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientCustomService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientCustomService query()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientCustomService whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientCustomService whereClubId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientCustomService whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientCustomService whereCustomServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientCustomService whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientCustomService whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientCustomService whereUserId($value)
 * @mixin \Eloquent
 * @property-read \App\Models\Client|null $client
 * @property-read \App\Models\Club|null $club
 * @property-read \App\Models\CustomService|null $custom_service
 * @property-read \App\Models\User|null $user
 */
class ClientCustomService extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function custom_service(): BelongsTo {
        return $this->belongsTo(CustomService::class);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class);
    }

    public function club(): BelongsTo {
        return $this->belongsTo(Club::class);
    }
}
