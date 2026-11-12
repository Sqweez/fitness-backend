<?php

namespace App\Http\Resources\Mobile\Client;

use App\Models\Session;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Session */
class VisitResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'id' => (string) $this->id,
            'at' => $this->created_at->toIso8601String(),
        ];
    }
}
