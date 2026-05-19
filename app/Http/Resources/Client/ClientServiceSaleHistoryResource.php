<?php

namespace App\Http\Resources\Client;

use App\Models\Sale;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin Sale */

class ClientServiceSaleHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'date' => format_datetime($this->created_at),
            'user' => $this->user,
            'club' => $this->club,
            'amount' => optional($this->transaction)->amount * -1,
            'service' => $this->salable->service,
            'minutes' => $this->salable->minutes ?: null,
        ];
    }
}
