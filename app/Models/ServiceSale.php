<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * App\Models\ServiceSale
 *
 * @property int $id
 * @property int $service_id
 * @property int|null $entries_count
 * @property int|null $minutes_remaining
 * @property string|null $active_until
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Service|null $service
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceSale newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceSale newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceSale query()
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceSale whereActiveUntil($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceSale whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceSale whereEntriesCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceSale whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceSale whereMinutesRemaining($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceSale whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceSale whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property-read string|null $active_until_formatted
 * @property-read bool $can_be_prolonged
 * @property-read bool $can_be_used
 * @property-read bool $is_activated
 * @property-read bool $is_expired
 * @property-read bool $is_unlimited
 * @property-read int|null $remaining_visits
 * @property-read int|null $visits_count
 * @property-read \App\Models\Sale|null $sale
 * @property-read Collection|\App\Models\SessionService[] $visits
 * @property-read bool $can_be_restored
 * @property-read bool $already_written_off
 * @property-read mixed $last_trainer
 * @property-read Collection|\App\Models\ClientServicePenalty[] $penalties
 * @property-read int|null $penalties_count
 * @property int $is_prolongation
 * @property-read int $remaining_minutes
 * @property-read int $solarium_expired_minutes
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceSale whereIsProlongation($value)
 * @property-read Collection|\App\Models\RestoredService[] $restores
 * @property-read int|null $restores_count
 * @property-read bool $has_unconfirmed_restore_requests
 * @property-read Collection|\App\Models\RestoredService[] $acceptedRestores
 * @property-read int|null $accepted_restores_count
 * @property-read string|null $days_remaining
 * @property string|null $self_name
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceSale whereSelfName($value)
 * @property string|null $activated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceSale whereActivatedAt($value)
 * @property int|null $client_id
 * @method static \Illuminate\Database\Eloquent\Builder|ServiceSale whereClientId($value)
 * @property-read \App\Models\SessionService|null $active_session
 * @property-read \App\Models\StopCard|null $active_stop
 * @property-read bool $is_stopped
 * @property-read Collection|\App\Models\StopCard[] $stops
 * @property-read int|null $stops_count
 */
class ServiceSale extends Model
{
    use HasFactory;

    protected $table = 'service_sale';

    protected $guarded = ['id'];

    public function service(): BelongsTo {
        return $this->belongsTo(Service::class);
    }

    public function active_session(): HasOne {
        return $this->hasOne(SessionService::class,'service_sale_id')
            ->whereHas('session', function ($q) {
                return $q->where('finished_at', null);
            });
    }

    public function stops(): HasMany {
        return $this->hasMany(StopCard::class, 'service_sale_id');
    }

    public function active_stop(): HasOne {
        return $this->hasOne(StopCard::class, 'service_sale_id')
            ->where('is_active', true)
            ->where('unstopped_at', null);
    }

    public function sale(): MorphOne {
        return $this->morphOne(Sale::class, 'salable');
    }

    public function visits(): HasMany {
        return $this->hasMany(SessionService::class)->latest();
    }

    public function penalties(): HasMany {
        return $this->hasMany(ClientServicePenalty::class)
            ->where('is_accepted', true)
            ->where('is_declined', false);
    }

    public function restores(): HasMany {
        return $this->hasMany(RestoredService::class);
    }

    public function acceptedRestores(): HasMany {
        return $this->restores()->where('is_accepted', true);
    }

    public function getLastTrainerAttribute() {
        return $this->visits
            ->first()
            ->trainer ?? ['id' => null, 'name' => 'Не установлен'];
    }

    public function getActiveUntilFormattedAttribute(): ?string {
        return
            $this->active_until ?
                Carbon::parse($this->active_until)->format('d.m.Y') :
                null;
    }

    public function getIsActivatedAttribute(): bool {
        return !!$this->active_until;
    }

    public function getCanBeProlongedAttribute(): bool {
        return $this->getCanBeUsedAttribute() && $this->service->prolongation_price > 0;
    }

    public function getVisitsCountAttribute(): int {
        return $this->visits->count();
    }

    public function getPenaltiesCountAttribute(): int {
        return $this->penalties->count();
    }

    public function getIsUnlimitedAttribute(): bool {
        return is_null($this->entries_count);
    }

    public function getRemainingVisitsAttribute() {
        if ($this->getIsUnlimitedAttribute()) {
            return true;
        }
        return $this->entries_count - $this->getVisitsCountAttribute() - $this->getPenaltiesCountAttribute();
    }

    public function getSolariumExpiredMinutesAttribute(): int {
        return $this->visits->sum('minutes');
    }

    public function getRemainingMinutesAttribute(): int {
        return $this->minutes_remaining - $this->getSolariumExpiredMinutesAttribute();
    }

    public function getIsExpiredAttribute(): bool {
        if (!$this->getIsActivatedAttribute()) {
            return false;
        }
        return !Carbon::parse($this->active_until)->greaterThanOrEqualTo(today());
    }

    public function getCanBeUsedAttribute(): bool {
        return $this->getIsActivatedAttribute()
            && $this->getRemainingVisitsAttribute()
            && !$this->getIsExpiredAttribute()
            && !$this->getIsStoppedAttribute();
    }

    public function getCanBeRestoredAttribute(): bool {
        return !$this->getCanBeUsedAttribute()
            && $this->getIsActivatedAttribute()
            && $this->getRemainingVisitsAttribute() > 0
            && $this->service->restore_price > 0
            && $this->service->is_active
            && !$this->getIsStoppedAttribute();
    }

    public function getAlreadyWrittenOffAttribute(): bool {
        return !!$this->active_session;
    }

    public function getHasUnconfirmedRestoreRequestsAttribute(): bool {
        return $this->restores
                ->where('is_accepted', false)
                ->where('is_declined', false)
                ->count() > 0;
    }

    public function getDaysRemainingAttribute(): ?string {
        if (!$this->active_until || !$this->getCanBeUsedAttribute()) {
            return null;
        }
        $diff = Carbon::parse($this->active_until)->diff(now());
        return $diff->days;
    }

    public function getIsStoppedAttribute(): bool {
        return !!$this->active_stop;
    }
}
