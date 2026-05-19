<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\WithDrawal
 *
 * @property int $id
 * @property int $user_id
 * @property int $club_id
 * @property int $amount
 * @property int $payment_type
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Club $club
 * @property-read mixed $payment_type_text
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|WithDrawal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WithDrawal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WithDrawal query()
 * @method static \Illuminate\Database\Eloquent\Builder|WithDrawal whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WithDrawal whereClubId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WithDrawal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WithDrawal whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WithDrawal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WithDrawal wherePaymentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WithDrawal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WithDrawal whereUserId($value)
 * @mixin \Eloquent
 */
class WithDrawal extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class)->select(['id', 'name']);
    }

    public function club(): BelongsTo {
        return $this->belongsTo(Club::class)->select(['id', 'name']);
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
