<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Models\Pass
 *
 * @property int $id
 * @property string $code
 * @property int|null $passable_id
 * @property string|null $passable_type
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $passable
 * @method static \Database\Factories\PassFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Pass newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Pass newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Pass query()
 * @method static \Illuminate\Database\Eloquent\Builder|Pass whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pass whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pass whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pass whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pass wherePassableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pass wherePassableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Pass whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int|null $user_id
 * @method static \Illuminate\Database\Eloquent\Builder|Pass whereUserId($value)
 */
class Pass extends Model
{
    use HasFactory;

    protected $guarded = ['id'];


    public function passable(): MorphTo {
        return $this->morphTo();
    }
}
