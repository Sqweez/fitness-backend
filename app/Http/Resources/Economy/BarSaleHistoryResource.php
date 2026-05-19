<?php

namespace App\Http\Resources\Economy;

use App\Models\Sale;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin Sale */

class BarSaleHistoryResource extends JsonResource
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
            'client' => $this->client,
            'user' => $this->user,
            'amount' => $this->transaction->amount * -1,
            'created_at' => $this->created_at,
            'date' => format_datetime($this->created_at),
            'product' => $this->salable->product->full_name,
            'club' => $this->club,
        ];
    }
}
