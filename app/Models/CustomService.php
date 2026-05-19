<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\CustomService
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|CustomService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomService query()
 * @method static \Illuminate\Database\Eloquent\Builder|CustomService whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomService whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomService whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomService whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int|null $price
 * @method static \Illuminate\Database\Eloquent\Builder|CustomService wherePrice($value)
 */
class CustomService extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
}
