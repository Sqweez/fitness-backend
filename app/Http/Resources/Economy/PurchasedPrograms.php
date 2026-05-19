<?php

namespace App\Http\Resources\Economy;

use App\Models\Sale;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Sale
 *
 */

class PurchasedPrograms extends JsonResource
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
            'name' => $this->salable->service ? $this->salable->service->name : 'Удаленная программа',
            'user' => $this->user,
            'club' => $this->club,
            'client' => $this->client,
            'amount' => optional($this->transaction)->amount * -1,
            'date' => format_datetime($this->created_at)
        ];
    }
}
