<?php

namespace App\Http\Resources\Client;

use App\Models\Client;
use App\Models\ClientBookmark;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin ClientBookmark */
class ClientBookmarkResource extends JsonResource
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
            'photo' => $this->client->getFirstMediaUrl(Client::MEDIA_AVATAR),
        ];
    }
}
