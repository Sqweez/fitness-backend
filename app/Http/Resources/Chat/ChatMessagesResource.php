<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessagesResource extends JsonResource
{
    public function toArray($request): array {
        $chat = $this->resource['chat'];

        return [
            'items' => ChatMessageResource::collection($this->resource['items']),
            'next_cursor' => $this->resource['next_cursor'],
            'client_last_read_message_id' => $chat->client_last_read_message_id === null ? null : (string) $chat->client_last_read_message_id,
        ];
    }
}
