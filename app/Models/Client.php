<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * App\Models\Client
 *
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string $gender
 * @property string|null $description
 * @property int $balance
 * @property string $birth_date
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Client newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Client newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Client query()
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereBirthDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereUserId($value)
 * @mixin \Eloquent
 * @property-read string|null $birth_date_formatted
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection|Media[] $media
 * @property-read int|null $media_count
 * @property-read \App\Models\Pass|null $pass
 * @method static \Illuminate\Database\Eloquent\Builder|Client pass($pass)
 * @property-read \App\Models\User|null $registrar
 * @property int $club_id
 * @property-read \App\Models\Club|null $club
 * @method static \Illuminate\Database\Eloquent\Builder|Client whereClubId($value)
 * @property-read mixed $age
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Transaction[] $transactions
 * @property-read int|null $transactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Sale[] $programs
 * @property-read int|null $programs_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Sale[] $sales
 * @property-read int|null $sales_count
 * @property-read mixed $trinket
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Session[] $sessions
 * @property-read int|null $sessions_count
 * @property-read \App\Models\Session|null $active_session
 * @property-read bool $is_in_club
 * @property-read bool $trinket_can_given
 * @property-read bool $session_can_be_finished
 * @property-read bool $has_unlimited_discount
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Sale[] $solarium
 * @property-read int|null $solarium_count
 * @property string|null $password
 * @property string|null $comment
 * @property int $is_client
 * @property int $is_employee
 * @property int|null $created_by_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read bool $is_trainer
 * @property-read string $string_role
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles
 * @property-read int|null $roles_count
 * @method static \Illuminate\Database\Query\Builder|Client onlyTrashed()
 * @method static Builder|Client whereComment($value)
 * @method static Builder|Client whereCreatedById($value)
 * @method static Builder|Client whereDeletedAt($value)
 * @method static Builder|Client whereIsClient($value)
 * @method static Builder|Client whereIsEmployee($value)
 * @method static Builder|Client wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|Client withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Client withoutTrashed()
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ClientReplenishment[] $replenishments
 * @property-read int|null $replenishments_count
 * @property-read string $age_type
 * @property-read bool $is_birthday
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Sale[] $lastPrograms
 * @property-read int|null $last_programs_count
 * @property string|null $cached_pass
 * @property string|null $cached_trinket
 * @method static Builder|Client whereCachedPass($value)
 * @method static Builder|Client whereCachedTrinket($value)
 * @property int $cached_solarium_total
 * @property bool $has_active_programs
 * @property-read mixed $gender_display
 * @property-read \App\Models\Session|null $last_session
 * @method static Builder|Client whereCachedSolariumTotal($value)
 * @method static Builder|Client whereHasActivePrograms($value)
 * @property string|null $mobile_password
 * @method static Builder|Client whereMobilePassword($value)
 */
class Client extends Authenticatable implements HasMedia, JWTSubject
{
    use HasFactory, InteractsWithMedia;

    use SoftDeletes;

    const MEDIA_AVATAR = 'client_avatar';

    protected $guarded = ['id'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'club_id' => 'integer',
        'has_active_programs' => 'boolean'
    ];


    protected static function boot() {
        parent::boot();
        static::addGlobalScope('order', function ($builder) {
            $builder->orderBy('name', 'asc');
        });
    }

    public function avatar() {
        return $this->media()->where('collection_name', self::MEDIA_AVATAR);
    }

    public function pass(): MorphOne {
        return $this->morphOne(Pass::class, 'passable');
    }

    public function registrar(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id')->select(['id', 'name'])->withDefault([
            'id' => null,
            'name' => 'Система'
        ]);
    }

    public function club(): BelongsTo {
        return $this->belongsTo(Club::class)->withDefault([
            'id' => -1,
            'name' => 'Неизвестно'
        ]);
    }

    public function transactions(): HasMany {
        return $this->hasMany(Transaction::class)
            ->orderByDesc('id');
    }

    public function replenishments(): HasMany {
        return $this->hasMany(ClientReplenishment::class, 'client_id');
    }

    public function sales(): HasMany {
        return $this->hasMany(Sale::class)
            ->latest();
    }

    public function programs(): HasMany {
        return $this
            ->hasMany(Sale::class)
            ->whereHasMorph('salable', [ServiceSale::class], function ($query) {
                return $query->whereHas('service', function ($query) {
                    return $query
                        ->whereIn('service_type_id',
                            [Service::TYPE_PROGRAM, Service::TYPE_UNLIMITED]);
                });
            })
            ->latest('created_at');
    }

    public function lastPrograms(): HasMany {
        return $this->programs()
            ->limit(10)
            ->with([
                'salable.service', 'salable.restores',
                'salable.visits', 'club', 'salable.penalties',
                'salable.visits.trainer', 'salable.active_session', 'salable.active_stop.user'
            ]);
    }

    public function solarium(): HasMany {
        return $this
            ->sales()
            ->whereHasMorph('salable', [ServiceSale::class], function ($query) {
                return $query->whereHas('service', function ($query) {
                    return $query->whereIn('service_type_id', [Service::TYPE_SOLARIUM]);
                });
            })
            ->latest();
    }

    public function activeSolariumMinutes() {
        return $this
            ->sales()
            ->whereHasMorph('salable', [ServiceSale::class], function ($query) {
                return $query->whereHas('service', function ($query) {
                    return $query->whereIn('service_type_id', [Service::TYPE_SOLARIUM]);
                })->where('minutes_remaining', '>', 0);
            })
            ->with(['salable' => function ($query) {
                return $query->whereHas('service', function ($query) {
                    return $query->whereIn('service_type_id', [Service::TYPE_SOLARIUM]);
                })->where('minutes_remaining', '>', 0);
            }])
            ->latest();
    }

    public function sessions(): HasMany {
        return $this->hasMany(Session::class)->orderByDesc('id');
    }

    public function active_session(): HasOne {
        return $this
            ->hasOne(Session::class)
            ->where('finished_at', null);
    }

    public function last_session() {
        return $this
            ->hasOne(Session::class)
            ->latest('created_at');
    }

    public function getBirthDateFormattedAttribute(): ?string {
        return $this->birth_date
            ? Carbon::parse($this->birth_date)->format('d.m.Y')
            : 'Неизвестно';
    }

    public function getTrinketAttribute() {
        return $this->active_session ? $this->active_session->trinket : null;
    }

    public function getIsInClubAttribute(): bool {
        return !!$this->cached_trinket;
    }

    public function getAgeAttribute() {
        if (!$this->birth_date) {
            return 'Неизвестно';
        }
        return now()->diffInYears($this->birth_date);
    }

    public function getAgeTypeAttribute(): string {
        if (!$this->birth_date) {
            return 'Взрослый';
        }
        if ($this->getAgeAttribute() < 14) {
            return 'Ребенок';
        }
        if ($this->getAgeAttribute() >= 60) {
            return 'Элегант';
        }
        return 'Взрослый';
    }

    public function getGenderDisplayAttribute() {
        return $this->gender === 'F' ? 'Женский' : 'Мужской';
    }

    public function getIsBirthdayAttribute(): bool {
        if (!$this->birth_date) {
            return false;
        }
        return Carbon::parse($this->birth_date)->isBirthday();
    }

    public function getTrinketCanGivenAttribute(): bool {
        return $this->active_session && $this->active_session->trinket_id === null;
    }

    public function getSessionCanBeFinishedAttribute(): bool {
        return !!$this->active_session /*&& $this->active_session->trinket_id !== null */;
    }

    public function getHasUnlimitedDiscountAttribute(): bool {
        return $this->lastPrograms
            ->where('salable.service.service_type_id', 1)
            ->filter(function ($sale) {
                return $sale['salable']['can_be_used'] === true &&
                    Carbon::parse($sale['salable']['active_until'])->diffInDays(now()) >= Club::UNLIMITED_DAYS_BEFORE_DISCOUNT;
            })
            ->count() > 0;
    }

    public function getJWTIdentifier() {
        // TODO: Implement getJWTIdentifier() method.
    }

    public function getJWTCustomClaims() {
        // TODO: Implement getJWTCustomClaims() method.
    }
}
