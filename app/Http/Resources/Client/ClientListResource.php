<?php

namespace App\Http\Resources\Client;

use App\Models\Client;
use Illuminate\Http\Resources\Json\JsonResource;

/**
* @mixin Client
 */

class ClientListResource extends JsonResource
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
            'phone' => mask_phone($this->phone),
            'registrar' => $this->whenLoaded($this->registrar->name),
            'club' => $this->club,
            'balance' => $this->balance,
            'pass' => $this->cached_pass,
            'trinket' => $this->cached_trinket,
            'is_in_club' => $this->is_in_club,
        ];
    }
}
