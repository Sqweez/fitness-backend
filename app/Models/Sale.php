<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Models\Sale
 *
 * @property int $id
 * @property int $client_id
 * @property int $club_id
 * @property int $user_id
 * @property int $transaction_id
 * @property string $salable_type
 * @property int $salable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Client|null $client
 * @property-read Model|\Eloquent $salable
 * @property-read \App\Models\Transaction|null $transaction
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Sale newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sale newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sale query()
 * @method static \Illuminate\Database\Eloquent\Builder|Sale whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sale whereClubId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sale whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sale whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sale whereSalableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sale whereSalableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sale whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sale whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sale whereUserId($value)
 * @mixin \Eloquent
 * @property-read \App\Models\Club|null $club
 * @method static \Illuminate\Database\Eloquent\Builder|Sale barSales()
 * @method static \Illuminate\Database\Eloquent\Builder|Sale shopSales()
 */
class Sale extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    const TYPE_SERVICE = 'service';
    const TYPE_PRODUCT = 'product';

    public function salable(): MorphTo {
        return $this->morphTo(__FUNCTION__, 'salable_type', 'salable_id', 'id');
    }

    public function transaction(): MorphOne {
        return $this->morphOne(Transaction::class, 'transactional');
    }

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class)->withDefault([
            'id' => null,
            'name' => 'Удаленный сотрудник'
        ]);
    }

    public function club(): BelongsTo {
        return $this->belongsTo(Club::class)->select(['id', 'name']);
    }

    public function scopeBarSales($query) {
        return $query
            ->whereHasMorph('salable', [ProductSale::class], function ($query) {
                $query->whereHas('product', function ($query) {
                    $query->where('product_type_id', __hardcoded(2));
                });
            });
    }

    public function scopeShopSales($query) {
        return $query
            ->whereHasMorph('salable', [ProductSale::class], function ($query) {
                $query->whereHas('product', function ($query) {
                    $query->where('product_type_id', __hardcoded(1));
                });
            });
    }
}
