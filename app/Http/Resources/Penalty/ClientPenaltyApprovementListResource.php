<?php

namespace App\Http\Resources\Penalty;

use App\Models\ClientServicePenalty;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClientServicePenalty
 * */

class ClientPenaltyApprovementListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'client' => $this->client,
            'club' => $this->client->club,
            'user' => $this->user,
            'trainer' => $this->trainer,
            'service' => $this->service->service->name,
            'created_at' => format_datetime($this->created_at),
            'penalty_date' => format_date($this->penalty_date),
            'is_accepted' => $this->is_accepted,
            'is_declined' => $this->is_declined,
            'description' => $this->description,
        ];
    }
}
