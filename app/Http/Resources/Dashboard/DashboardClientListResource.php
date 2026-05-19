<?php

namespace App\Http\Resources\Dashboard;

use App\Models\Session;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin Session */

class DashboardClientListResource extends JsonResource
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
            'id' => $this->client->id,
            'name' => $this->client->name,
            'phone' => mask_phone($this->client->phone),
            'club' => $this->club,
            'unmasked_phone' => $this->client->phone,
        ];
    }
}
