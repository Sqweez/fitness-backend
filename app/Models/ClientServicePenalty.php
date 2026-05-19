<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\ClientServicePenalty
 *
 * @property int $id
 * @property int $client_id
 * @property int $service_sale_id
 * @property string $description
 * @property int $trainer_id
 * @property string $penalty_date
 * @property bool $is_accepted
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ClientServicePenalty newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientServicePenalty newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientServicePenalty query()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientServicePenalty whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientServicePenalty whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientServicePenalty whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientServicePenalty whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientServicePenalty whereIsAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientServicePenalty wherePenaltyDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientServicePenalty whereServiceSaleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientServicePenalty whereTrainerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientServicePenalty whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int $user_id
 * @property-read \App\Models\Client|null $client
 * @property-read \App\Models\ServiceSale|null $service
 * @property-read \App\Models\User|null $trainer
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|ClientServicePenalty whereUserId($value)
 * @property int $is_declined
 * @property int|null $solver_id
 * @method static \Illuminate\Database\Eloquent\Builder|ClientServicePenalty whereIsDeclined($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientServicePenalty whereSolverId($value)
 * @property-read \App\Models\User|null $solver
 * @property int $club_id
 * @property-read \App\Models\Club|null $club
 * @method static \Illuminate\Database\Eloquent\Builder|ClientServicePenalty whereClubId($value)
 */
class ClientServicePenalty extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_accepted' => 'boolean',
        'is_declined' => 'boolean'
    ];

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class)
            ->select(['id', 'name'])
            ->withDefault([
                'id' => null,
                'name' => 'Неизвестно'
            ]);
    }

    public function trainer(): BelongsTo {
        return $this
            ->belongsTo(User::class, 'trainer_id')
            ->select(['id', 'name'])
            ->withDefault([
                'id' => null,
                'name' => 'Не установлен'
            ]);
    }

    public function solver(): BelongsTo {
        return $this->belongsTo(User::class, 'solver_id')->select(['id', 'name']);
    }

    public function service(): BelongsTo {
        return $this->belongsTo(ServiceSale::class, 'service_sale_id');
    }

    public function club(): BelongsTo {
        return $this->belongsTo(Club::class);
    }
}
