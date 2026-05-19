<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\Product
 *
 * @property int $id
 * @property string $name
 * @property int $price
 * @property int $product_category_id
 * @property string|null $barcode
 * @property int $product_type_id
 * @property string|null $attribute
 * @property int|null $product_group_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereAttribute($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereBarcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereProductCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereProductGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereProductTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ProductBatch[] $batches
 * @property-read int|null $batches_count
 * @property-read \App\Models\ProductCategory|null $category
 * @property-read string $product_type
 * @property-read string $fullname
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Query\Builder|Product onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Product withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Product withoutTrashed()
 * @property int|null $stock_price
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereStockPrice($value)
 */
class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected static function boot() {
        parent::boot();
        static::addGlobalScope('order', function ($builder) {
            $builder->orderBy('id', 'desc');
        });
    }

    public function category(): BelongsTo {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function batches(): HasMany {
        return $this->hasMany(ProductBatch::class, 'product_id');
    }

    public function getProductTypeAttribute(): string {
        switch ($this->product_type_id) {
            case 1:
                return 'Магазин';
            case 2:
                return 'Бар';
            default:
                return 'Неизвестно';
        }
    }

    public function collectQuantities() {
        $totalQuantity = collect([[
            'name' => 'Всего',
            'club_id' => -1,
            'quantity' => $this->batches->reduce(function ($a, $c) {
                return $a + $c['quantity'];
            }, 0)
        ]]);

        $quantitiesByClub = $this->batches->groupBy('store_id');
        $quantitiesByClub = $quantitiesByClub
            ->map(function ($qnts, $store_id) {
                return [
                    'quantity' => collect($qnts)->reduce(function ($a, $c) {
                        return $a + $c['quantity'];
                        }, 0),
                    'club_id' => $store_id,
                    'name' => $qnts->first()['club']['name']
                ];
            })
            ->values()
           /* ->filter(function ($q) {
                return $q['quantity'] > 0;
            })*/
            ->values();

        if (auth()->user()->is_boss) {
            $quantitiesByClub = $quantitiesByClub->mergeRecursive($totalQuantity);
        }

        return $quantitiesByClub;
    }

    public function getFullnameAttribute(): string {
        return trim(sprintf('%s %s', $this->name, ($this->attribute ?: '')));
    }

    public function decrementBatch($store_id) {
        $batch = ProductBatch::query()
            ->where('product_id', $this->id)
            ->where('store_id', $store_id)
            ->where('quantity', '>', 0)
            ->oldest()
            ->first();

        $batch->decrement('quantity');

        return $batch;
    }
}
