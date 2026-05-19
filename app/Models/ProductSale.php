<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * App\Models\ProductSale
 *
 * @property int $id
 * @property int $product_id
 * @property int $purchase_price
 * @property int|null $product_batch_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\Sale|null $sale
 * @method static \Illuminate\Database\Eloquent\Builder|ProductSale newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductSale newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductSale query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductSale whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductSale whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductSale whereProductBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductSale whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductSale wherePurchasePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductSale whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProductSale extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function product(): BelongsTo {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function sale(): MorphOne {
        return $this->morphOne(Sale::class, 'salable');
    }
}
