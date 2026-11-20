<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Resources\Json\JsonResource;

class ChatsResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'items' => ChatResource::collection($this->resource['items']),
            'next_cursor' => $this->resource['next_cursor'],
        ];
    }
}
