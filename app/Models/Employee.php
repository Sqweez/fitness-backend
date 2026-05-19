<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * App\Models\Employee
 *
 * @property int $id
 * @property string $name
 * @property string|null $phone
 * @property string|null $password
 * @property int|null $club_id
 * @property string $gender
 * @property string|null $birth_date
 * @property string|null $description
 * @property string|null $comment
 * @property int $balance
 * @property int $is_client
 * @property int $is_employee
 * @property int|null $created_by_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Club|null $club
 * @property-read string|null $birth_date_formatted
 * @property-read bool $is_trainer
 * @property-read string $string_role
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection|\Spatie\MediaLibrary\MediaCollections\Models\Media[] $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Pass|null $pass
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Role[] $roles
 * @property-read int|null $roles_count
 * @method static Builder|Employee newModelQuery()
 * @method static Builder|Employee newQuery()
 * @method static \Illuminate\Database\Query\Builder|Employee onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|User pass($pass)
 * @method static Builder|Employee query()
 * @method static Builder|Employee whereBalance($value)
 * @method static Builder|Employee whereBirthDate($value)
 * @method static Builder|Employee whereClubId($value)
 * @method static Builder|Employee whereComment($value)
 * @method static Builder|Employee whereCreatedAt($value)
 * @method static Builder|Employee whereCreatedById($value)
 * @method static Builder|Employee whereDeletedAt($value)
 * @method static Builder|Employee whereDescription($value)
 * @method static Builder|Employee whereGender($value)
 * @method static Builder|Employee whereId($value)
 * @method static Builder|Employee whereIsClient($value)
 * @method static Builder|Employee whereIsEmployee($value)
 * @method static Builder|Employee whereName($value)
 * @method static Builder|Employee wherePassword($value)
 * @method static Builder|Employee wherePhone($value)
 * @method static Builder|Employee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Employee withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Employee withoutTrashed()
 * @mixin \Eloquent
 * @property-read bool $can_sale_service
 * @property-read bool $is_boss
 * @property-read bool $is_seller
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Club[] $clubs
 * @property-read int|null $clubs_count
 * @property-read bool $is_bartender
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\WithDrawal[] $withdrawals
 * @property-read int|null $withdrawals_count
 * @property int $is_active
 * @method static Builder|Employee whereIsActive($value)
 * @method static Builder|User active()
 */
class Employee extends User
{
    use HasFactory;

    protected $table = 'users';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('employee', function(Builder $builder) {
            $builder->where('is_employee', true);
        });
    }

    public function getIsTrainerAttribute(): bool {
        if (!$this->roles()->exists()) {
            return false;
        }
        return $this->roles->contains('id', 6);
    }
}
