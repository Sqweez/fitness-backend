<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * App\Models\ClientReplenishment
 *
 * @property int $id
 * @property int $client_id
 * @property int $user_id
 * @property int $payment_type
 * @property int $amount
 * @property int $club_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Client|null $client
 * @property-read \App\Models\Club|null $club
 * @property-read \App\Models\Transaction|null $transaction
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|ClientReplenishment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientReplenishment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientReplenishment query()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientReplenishment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientReplenishment whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientReplenishment whereClubId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientReplenishment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientReplenishment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientReplenishment wherePaymentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientReplenishment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientReplenishment whereUserId($value)
 * @mixin \Eloquent
 * @property string $description
 * @method static \Illuminate\Database\Eloquent\Builder|ClientReplenishment whereDescription($value)
 * @property-read string $payment_type_text
 */
class ClientReplenishment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function club(): BelongsTo {
        return $this->belongsTo(Club::class);
    }

    public function transaction(): MorphOne {
        return $this->morphOne(Transaction::class, 'transactional');
    }

    public function getPaymentTypeTextAttribute(): string {
        switch ($this->payment_type) {
            case 1:
                return 'Наличные';
            case 2:
                return 'Безналичная оплата';
            default:
                return 'Неизвестный тип оплаты';
        }
    }
}
