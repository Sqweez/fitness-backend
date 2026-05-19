<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Service
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $price
 * @property int $unlimited_price
 * @property int $validity_days
 * @property int $validity_minutes
 * @property int $club_id
 * @property int $service_type_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Service newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Service newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Service query()
 * @method static \Illuminate\Database\Eloquent\Builder|Service whereClubId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Service whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Service whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Service whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Service whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Service wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Service whereServiceTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Service whereUnlimitedPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Service whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Service whereValidityDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Service whereValidityMinutes($value)
 * @mixin \Eloquent
 * @property int $entries_count
 * @method static \Illuminate\Database\Eloquent\Builder|Service whereEntriesCount($value)
 * @property-read \App\Models\Club|null $club
 * @property-read mixed $type
 * @property int $prolongation_price
 * @method static \Illuminate\Database\Eloquent\Builder|Service whereProlongationPrice($value)
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Query\Builder|Service onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Service whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Service withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Service withoutTrashed()
 * @property int $restore_price
 * @method static \Illuminate\Database\Eloquent\Builder|Service whereRestorePrice($value)
 * @property-read int|null $computed_validity_days
 * @property int $is_active
 * @method static \Illuminate\Database\Eloquent\Builder|Service whereIsActive($value)
 */
class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected static function boot() {
        parent::boot();
        static::addGlobalScope('order', function ($builder) {
            $builder->orderBy('name', 'asc');
        });
    }

    const TYPE_UNLIMITED = 1;
    const TYPE_SOLARIUM = 2;
    const TYPE_PROGRAM = 3;

    const TYPES = [
        [
            'name' => 'Безлимит',
            'id' => 1,
        ],
        [
            'name' => 'Солярий',
            'id' => 2,
        ],
        [
            'name' => 'Программа',
            'id' => 3,
        ],
    ];

    public function club(): BelongsTo {
        return $this->belongsTo(Club::class);
    }

    public function getTypeAttribute() {
        $types = collect(self::TYPES);
        return $types->where('id', $this->service_type_id)->first();
    }

    public function getComputedValidityDaysAttribute(): ?int {
        return in_array($this->service_type_id, [self::TYPE_PROGRAM, self::TYPE_UNLIMITED]) ? $this->validity_days : null;
    }
}
