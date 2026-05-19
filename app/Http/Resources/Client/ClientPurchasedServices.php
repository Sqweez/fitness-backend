<?php

namespace App\Http\Resources\Client;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
* @mixin Sale
 */

class ClientPurchasedServices extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array {

        $service = $this->salable;

        return [
            'id' => $service->id,
            'name' => $service->self_name ?: $service->service->name,
            'club' => $this->club,
            'service_id' => $service->service->id,
            'price' => $service->service->price,
            'unlimited_price' => $service->service->unlimited_price,
            'can_be_used' => $service->can_be_used,
            'initial_entries_count' => $service->entries_count,
            'type' => $service->service->type,
            'active_until' => $service->active_until_formatted,
            'active_until_restored' => format_date(today()->addDays(7)),
            'can_be_prolonged' => $service->can_be_prolonged,
            'is_activated' => $service->is_activated,
            'visits_count' => $service->visits_count,
            'penalties' => $service->penalties,
            'penalties_count' => $service->penalties_count,
            'is_unlimited' => $service->is_unlimited,
            'remaining_visits' => $service->remaining_visits,
            'can_be_restored' => $service->can_be_restored,
            'restore_price' => $service->service->restore_price,
            'prolongation_price' => $service->service->prolongation_price,
            'has_unconfirmed_restore_requests' => $service->has_unconfirmed_restore_requests,
            'days_remaining' => $service->days_remaining,
            'already_written_off' => $service->already_written_off,
            'last_trainer' => $service->last_trainer,
            'is_stopped' => $service->is_stopped,
            'active_stop' => $service->active_stop ? new ActiveStopProgramResource($service->active_stop) : null,
           # ,
           # 'last_trainer' => $service->last_trainer,
        ];
    }
}
