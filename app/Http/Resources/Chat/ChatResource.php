<?php

namespace App\Http\Resources\Chat;

use App\Models\Chat;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Chat */
class ChatResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'id' => (string) $this->id,
            'client' => ChatClientResource::make($this->client),
            'last_message' => ChatMessageResource::make($this->lastMessage),
            'unread_count' => (int) $this->unread_count,
        ];
    }
}
