<?php

namespace App\Http\Resources\Service;

use App\Models\Service;
use Illuminate\Http\Resources\Json\JsonResource;

/**
* @mixin Service
 */

class SingleServiceResource extends JsonResource
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
            'description' => $this->description,
            'club' => $this->club,
            'club_id' => $this->club_id,
            'service_type_id' => $this->service_type_id,
            'type' => $this->type,
            'price' => $this->price,
            'prolongation_price' => $this->prolongation_price,
            'restore_price' => $this->restore_price,
            'unlimited_price' => $this->unlimited_price,
            'validity_days' => $this->validity_days,
            'validity_minutes' => $this->validity_minutes,
            'entries_count' => $this->entries_count,
        ];
    }
}
