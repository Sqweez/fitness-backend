<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * App\Models\RestoredService
 *
 * @property int $id
 * @property int $service_id
 * @property int $service_sale_id
 * @property int $user_id
 * @property int $client_id
 * @property int $restore_price
 * @property string $restore_until
 * @property bool $is_accepted
 * @property bool $is_declined
 * @property int|null $revisor_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Client|null $client
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection|\Spatie\MediaLibrary\MediaCollections\Models\Media[] $media
 * @property-read int|null $media_count
 * @property-read \App\Models\User|null $revisor
 * @property-read \App\Models\Service|null $service
 * @property-read \App\Models\ServiceSale|null $service_sale
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|RestoredService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RestoredService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RestoredService query()
 * @method static \Illuminate\Database\Eloquent\Builder|RestoredService whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RestoredService whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RestoredService whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RestoredService whereIsAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RestoredService whereIsDeclined($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RestoredService whereRestorePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RestoredService whereRestoreUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RestoredService whereRevisorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RestoredService whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RestoredService whereServiceSaleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RestoredService whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RestoredService whereUserId($value)
 * @mixin \Eloquent
 * @property string $previous_active_until
 * @method static \Illuminate\Database\Eloquent\Builder|RestoredService wherePreviousActiveUntil($value)
 * @property-read \App\Models\Transaction|null $transaction
 */
class RestoredService extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    const RESTORE_APPLICATION = 'restore_applications';

    protected $guarded = ['id'];

    protected $casts = [
        'is_accepted' => 'boolean',
        'is_declined' => 'boolean'
    ];

    public function service(): BelongsTo {
        return $this->belongsTo(Service::class);
    }

    public function service_sale(): BelongsTo {
        return $this->belongsTo(ServiceSale::class);
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function revisor(): BelongsTo {
        return $this->belongsTo(User::class, 'revisor_id');
    }

    public function transaction(): MorphOne {
        return $this->morphOne(Transaction::class, 'transactional');
    }
}
