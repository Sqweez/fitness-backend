<?php

namespace App\Http\Resources\Economy;

use App\Models\Client;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Client
 * */

class NewClients extends JsonResource
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
            'registrar' => $this->registrar,
            'balance' => $this->balance,
            'date' => format_datetime($this->created_at),
            'club' => $this->club,
        ];
    }
}
