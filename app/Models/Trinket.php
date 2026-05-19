<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * App\Models\Trinket
 *
 * @property int $id
 * @property string $code
 * @property int $cabinet_number
 * @property int $club_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Trinket newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Trinket newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Trinket query()
 * @method static \Illuminate\Database\Eloquent\Builder|Trinket whereCabinetNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Trinket whereClubId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Trinket whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Trinket whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Trinket whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Trinket whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \App\Models\Session|null $active_session
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Session[] $sessions
 * @property-read int|null $sessions_count
 * @property-read \App\Models\Club $club
 */
class Trinket extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function sessions(): HasMany {
        return $this->hasMany(Session::class);
    }

    public function active_session(): HasOne {
        return $this
            ->hasOne(Session::class)
            ->where('finished_at', null);
    }

    public function club() {
        return $this->belongsTo(Club::class);
    }
}
