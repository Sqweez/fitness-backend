<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\StopCard
 *
 * @property int $id
 * @property int $user_id
 * @property int $client_id
 * @property int $service_sale_id
 * @property int $is_active
 * @property string $active_until_prev
 * @property string|null $description
 * @property string|null $unstopped_at
 * @property int $remaining_days
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Client|null $client
 * @property-read \App\Models\ServiceSale|null $service
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|StopCard newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StopCard newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|StopCard query()
 * @method static \Illuminate\Database\Eloquent\Builder|StopCard whereActiveUntilPrev($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopCard whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopCard whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopCard whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopCard whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopCard whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopCard whereRemainingDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopCard whereServiceSaleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopCard whereUnstoppedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopCard whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|StopCard whereUserId($value)
 * @mixin \Eloquent
 */
class StopCard extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class)->select(['id', 'name'])->withTrashed();
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class)->select(['id', 'name'])->withTrashed();
    }

    public function service(): BelongsTo {
        return $this->belongsTo(ServiceSale::class);
    }
}
