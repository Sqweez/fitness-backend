<?php

namespace App\Http\Resources\Mobile\Chat;

use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessagesResource extends JsonResource
{
    public function toArray($request): array {
        $chat = $this->resource['chat'];

        return [
            'items' => ChatMessageResource::collection($this->resource['items']),
            'next_cursor' => $this->resource['next_cursor'],
            'staff_last_read_message_id' => $chat?->staff_last_read_message_id === null ? null : (string) $chat->staff_last_read_message_id,
        ];
    }
}
