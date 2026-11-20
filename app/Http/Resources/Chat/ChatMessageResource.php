<?php

namespace App\Http\Resources\Chat;

use App\Models\ChatMessage;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChatMessage */
class ChatMessageResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'id' => (string) $this->id,
            'author_type' => $this->is_from_client ? 'client' : 'staff',
            'text' => $this->text,
            // Lets the admin panel replace its local "sending" copy with the saved message
            'client_message_id' => $this->client_message_id,
            'author_name' => $this->user?->name,
            'sent_at' => $this->created_at->toIso8601String(),
        ];
    }
}
