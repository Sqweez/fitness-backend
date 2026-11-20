<?php

namespace App\Http\Resources\Mobile\Chat;

use App\Models\ChatMessage;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ChatMessage */
class ChatMessageResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'id' => (string) $this->id,
            'direction' => $this->is_from_client ? 'outgoing' : 'incoming',
            'text' => $this->text,
            'author_name' => $this->user?->name,
            'sent_at' => $this->created_at->toIso8601String(),
        ];
    }
}
