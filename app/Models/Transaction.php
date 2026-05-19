<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Models\Transaction
 *
 * @property int $id
 * @property int $client_id
 * @property int $user_id
 * @property int $club_id
 * @property int $canceller_id
 * @property string $cancelled_at
 * @property int $amount
 * @property int $payment_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $canceller
 * @property-read \App\Models\Client|null $client
 * @property-read \App\Models\Club|null $club
 * @property-read mixed $payment_type_text
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCancellerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereClubId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction wherePaymentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereUserId($value)
 * @mixin \Eloquent
 * @property string|null $description
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereDescription($value)
 * @property string $transactional_type
 * @property int $transactional_id
 * @property-read Model|\Eloquent $transactional
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereTransactionalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereTransactionalType($value)
 */
class Transaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    const PAYMENT_TYPES = [
        [
            'id' => 1,
            'name' => 'Наличные'
        ],
        [
            'id' => 2,
            'name' => 'Безналичная оплата'
        ],
        [
            'id' => 3,
            'name' => 'Списание'
        ]
    ];

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function canceller(): BelongsTo {
        return $this->belongsTo(User::class, 'canceller_id');
    }

    public function club(): BelongsTo {
        return $this->belongsTo(Club::class);
    }

    public function transactional(): MorphTo {
        return $this->morphTo(__FUNCTION__, 'transactional_type', 'transactional_id', 'id');
    }

    public function getPaymentTypeTextAttribute() {
        $types = collect(self::PAYMENT_TYPES);
        return $types->where('id', $this->payment_type)->first();
    }
}
