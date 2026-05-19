<?php

namespace App\Models;

use App\Models\Traits\HasPass;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $name
 * @property string $login
 * @property string $password
 * @property int|null $club_id
 * @property string|null $phone
 * @property string|null $birth_date
 * @property string|null $photo
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Club|null $club
 * @property-read string $string_role
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Pass|null $pass
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles
 * @property-read int|null $roles_count
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Query\Builder|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|User pass($pass)
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereBirthDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereClubId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|User withTrashed()
 * @method static \Illuminate\Database\Query\Builder|User withoutTrashed()
 * @mixin \Eloquent
 * @property-read string|null $birth_date_formatted
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection|Media[] $media
 * @property-read int|null $media_count
 * @property-read bool $is_trainer
 * @property string $gender
 * @property string|null $comment
 * @property int $balance
 * @property int $is_client
 * @property int $is_employee
 * @property int|null $created_by_id
 * @method static \Illuminate\Database\Eloquent\Builder|User whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedById($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIsClient($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIsEmployee($value)
 * @property-read bool $can_sale_service
 * @property-read bool $is_boss
 * @property-read bool $is_seller
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Club[] $clubs
 * @property-read int|null $clubs_count
 * @property-read bool $is_bartender
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\WithDrawal[] $withdrawals
 * @property-read int|null $withdrawals_count
 * @property int $is_active
 * @method static Builder|User whereIsActive($value)
 * @method static Builder|User active()
 */
class User extends Authenticatable implements JWTSubject, HasMedia
{
    use HasFactory, Notifiable, SoftDeletes, HasPass, InteractsWithMedia, HasPass;

    protected static function boot() {
        parent::boot();
        static::addGlobalScope('order', function ($builder) {
            $builder->orderBy('name', 'asc');
        });
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];

    const MEDIA_AVATAR = 'user_avatars';

    protected $hidden = [
        'password',
        'deleted_at',
        'pass_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'club_id' => 'integer',
        'is_active' => 'boolean'
    ];

    public function pass(): MorphOne {
        return $this->morphOne(Pass::class, 'passable');
    }

    public function club(): BelongsTo {
        return $this->belongsTo(Club::class)->withDefault([
            'id' => null,
            'name' => 'Неизвестно'
        ]);
    }

    public function roles(): BelongsToMany {
        return $this
            ->belongsToMany(Role::class, 'role_user', 'user_id')
            ->select(['id', 'name']);
    }

    public function clubs(): BelongsToMany {
        return $this
            ->belongsToMany(Club::class, 'club_user', 'user_id')
            ->select(['id', 'name']);
    }

    public function withdrawals(): HasMany {
        return $this
            ->hasMany(WithDrawal::class);
    }

    public function scopeActive($q) {
        $q->where('is_active', true);
    }

    public function hasRole(string $roleName): bool {
        return $this->roles->contains('name', $roleName);
    }

    public function getBirthDateFormattedAttribute(): ?string {
        return $this->birth_date
            ? Carbon::parse($this->birth_date)->format('d.m.Y')
            : null;
    }

    public function getStringRoleAttribute(): string {
        if (!$this->roles) {
            return 'Неизвестно';
        }
        return $this->roles->pluck('name')->join(' | ');
    }

    public function getIsTrainerAttribute(): bool {
        if (!$this->roles) {
            return false;
        }
        return $this->roles->contains('id', Role::ROLE_TRAINER);
    }

    public function getIsBossAttribute (): bool {
        if (!$this->roles) {
            return false;
        }
        return $this->roles->contains('id', Role::ROLE_BOSS);
    }

    public function getIsSellerAttribute(): bool {
        if (!$this->roles) {
            return false;
        }
        return $this->roles->contains('id', Role::ROLE_SELLER);
    }

    public function getCanSaleServiceAttribute(): bool {
        return $this->getIsBossAttribute() || $this->getIsSellerAttribute();
    }

    public function canChangeClub(): bool {
        return $this->clubs && $this->clubs->count() > 1;
    }

    public function mustSelectAClub(): bool {
        return $this->canChangeClub() && $this->club_id === null;
    }

    public function canTopUpAccount(): bool {
        return $this->getIsBossAttribute()
            || $this->roles->contains('id', Role::ROLE_SELLER)
            || $this->roles->contains('id', Role::ROLE_SENIOR_BARTENDER)
            || $this->roles->contains('id', Role::ROLE_BARTENDER);
    }

    public function canWriteOffServices(): bool {
        return $this->getIsBossAttribute() || $this->roles->contains('id', Role::ROLE_ADMIN);
    }

    public function canSeePurchasedServices(): bool {
        return $this->getIsBossAttribute()
            || $this->roles->contains('id', Role::ROLE_ADMIN)
            || $this->roles->contains('id', Role::ROLE_SELLER);
    }

    public function canSaleProducts(): bool {
        return $this->getIsBossAttribute() || $this->roles->contains('id', Role::ROLE_ADMIN);
    }

    public function canSaleBar(): bool {
        return $this->getIsBossAttribute()
            || $this->roles->contains('id', Role::ROLE_BARTENDER)
            || $this->roles->contains('id', Role::ROLE_SENIOR_BARTENDER);
    }

    public function getIsBartenderAttribute(): bool {
        return $this->roles->contains('id', Role::ROLE_BARTENDER)
            || $this->roles->contains('id', Role::ROLE_SENIOR_BARTENDER);
    }

    public function canOpenSession(): bool {
        return $this->getIsBossAttribute()
            || $this->roles->contains('id', Role::ROLE_ADMIN)
            || $this->roles->contains('id', Role::ROLE_SELLER);
    }

    public function canCreateClients(): bool {
        return $this->getIsBossAttribute()
            || $this->roles->contains('id', Role::ROLE_ADMIN)
            || $this->roles->contains('id', Role::ROLE_SELLER);
    }

    public function getJWTIdentifier() {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array {
        return [];
    }
}
