<?php

namespace App\Http\Resources\Client;

use App\Models\ClientCustomService;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin ClientCustomService */
class ClientCustomServiceResource extends JsonResource
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
            'user' => $this->user,
            'club' => $this->club,
            'service' => $this->custom_service,
            'date' => format_datetime($this->created_at),
        ];
    }
}
