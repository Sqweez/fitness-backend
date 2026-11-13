<?php

namespace App\Http\Resources\Club;

use App\Models\Club;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Club */
class ClubResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'cover_url' => $this->profile?->cover_url,
            'address' => $this->profile?->address,
            'map_url' => $this->profile?->map_url,
            'schedule' => $this->profile?->schedule ?? [],
            'phone' => $this->profile?->phone,
            'whatsapp' => $this->profile?->whatsapp,
            'instagram' => $this->profile?->instagram,
        ];
    }
}
