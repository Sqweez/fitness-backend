<?php

namespace App\Http\Resources\Mobile\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class MembershipHistoryResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'items' => MembershipResource::collection($this->resource['items']),
            'next_cursor' => $this->resource['next_cursor'],
        ];
    }
}
