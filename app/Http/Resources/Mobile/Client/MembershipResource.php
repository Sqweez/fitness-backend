<?php

namespace App\Http\Resources\Mobile\Client;

use App\Models\ServiceSale;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceSale */
class MembershipResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'id' => (string) $this->id,
            'title' => $this->self_name ?: $this->service->name,
            'active' => $this->can_be_used,
            'limited' => !$this->is_unlimited,
            'visits_left' => $this->is_unlimited ? null : (int) $this->remaining_visits,
            'visits_total' => $this->is_unlimited ? null : (int) $this->entries_count,
            'valid_until' => $this->is_activated ? Carbon::parse($this->active_until)->endOfDay()->toIso8601String() : null,
        ];
    }
}
