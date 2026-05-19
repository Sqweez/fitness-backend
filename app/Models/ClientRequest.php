<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ClientRequest
 *
 * @property int $id
 * @property int|null $client_id
 * @property int $request_type_id
 * @property string|null $name
 * @property string|null $phone
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ClientRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder|ClientRequest whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientRequest whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientRequest whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientRequest wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientRequest whereRequestTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientRequest whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int $is_answered
 * @property int|null $manager_id
 * @method static \Illuminate\Database\Eloquent\Builder|ClientRequest whereIsAnswered($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ClientRequest whereManagerId($value)
 */
class ClientRequest extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    const REQUEST_TYPES = [
        1 => 'Запрос на регистрацию',
        2 => 'Запрос на чат с тренером',
        3 => 'Запрос на тренировку'
    ];
}
