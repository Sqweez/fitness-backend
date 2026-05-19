<?php

namespace App\Http\Resources\Stats;

use App\Models\Sale;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin Sale */
class PriceSaleHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $sale = $this->salable;

        return [
            'id' => $this->id,
            'user' => $this->user,
            'club' => $this->club,
            'service' => $sale->service,
            'amount' => $this->transaction ? $this->transaction->amount * -1 : 0,
            'client' => $this->client,
            'date' => format_datetime($this->created_at),
        ];
    }
}
