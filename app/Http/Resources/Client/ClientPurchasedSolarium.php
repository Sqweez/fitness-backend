<?php

namespace App\Http\Resources\Client;

use App\Models\Sale;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin Sale */

class ClientPurchasedSolarium extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $service = $this->salable;

        return [
            'id' => $this->id,
            'name' => $service->service->name,
            'service_id' => $service->service->id,
            'minutes_remaining' => $service->remaining_minutes,
        ];
    }
}
