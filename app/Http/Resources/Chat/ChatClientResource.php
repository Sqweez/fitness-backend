<?php

namespace App\Http\Resources\Chat;

use App\Models\Client;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Client */
class ChatClientResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'club_id' => (string) $this->club_id,
        ];
    }
}
