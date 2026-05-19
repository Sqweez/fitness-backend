<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Models\Session
 *
 * @property int $id
 * @property int $client_id
 * @property int $start_user_id
 * @property int|null $finish_user_id
 * @property int $club_id
 * @property string|null $finished_at
 * @property string|null $trinket
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Session newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Session newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Session query()
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereClubId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereFinishUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereFinishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereStartUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereTrinket($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \App\Models\Client|null $client
 * @property-read \App\Models\Club|null $club
 * @property-read \App\Models\User|null $finish_user
 * @property-read \App\Models\User|null $start_user
 * @property int|null $trinket_id
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereTrinketId($value)
 * @property-read mixed $time_duration
 * @method static \Illuminate\Database\Eloquent\Builder|Session today()
 * @property int $is_system_finished
 * @property-read \App\Models\SessionService|null $session_service
 * @method static \Illuminate\Database\Eloquent\Builder|Session whereIsSystemFinished($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SessionService[] $session_services
 * @property-read int|null $session_services_count
 */
class Session extends Model
{
    use HasFactory;

    protected $guarded = [/*'id'*/];

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function session_service(): HasOne {
        return $this->hasOne(SessionService::class, 'session_id');
    }

    public function session_services(): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $this->hasMany(SessionService::class, 'session_id');
    }

    public function start_user(): BelongsTo {
        return $this->belongsTo(User::class, 'start_user_id');
    }

    public function finish_user(): BelongsTo {
        return $this->belongsTo(User::class, 'finish_user_id');
    }

    public function club(): BelongsTo {
        return $this->belongsTo(Club::class);
    }

    public function trinket(): BelongsTo {
        return $this->belongsTo(Trinket::class)->withDefault([
            'code' => null,
            'cabinet_number' => 'Неизвестно'
        ]);
    }

    public function scopeToday($q) {
        $q->where('created_at', '>=', today()->startOfDay())
            ->where('created_at', '<=', today()->endOfDay());
    }

    public function getTimeDurationAttribute(): string {
        if (!$this->finished_at) {
            return 'Еще в клубе!';
        }
        $diff = Carbon::parse($this->finished_at)->diff($this->created_at);
        $time = sprintf('%sм %sc', $diff->i , $diff->s);
        if ($diff->h > 0) {
            $time = $diff->h . 'ч ' . $time;
        }
        return $time;
    }
}
