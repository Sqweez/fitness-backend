<?php

namespace App\Http\Resources\Service;

use App\Models\Service;
use Illuminate\Http\Resources\Json\JsonResource;

/**
* @mixin Service
 */

class ServicesListResource extends JsonResource
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
            'name' => $this->name,
            'price' => $this->price,
            'prolongation_price' => $this->prolongation_price,
            'restore_price' => $this->restore_price,
            'unlimited_price' => $this->unlimited_price,
            'club' => $this->club,
            'type' => $this->type,
            'validity_days' => $this->computed_validity_days,
            'validity_minutes' => $this->validity_minutes,
            'entries_count' => $this->entries_count,
        ];
    }
}
