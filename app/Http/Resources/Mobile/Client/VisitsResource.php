<?php

namespace App\Http\Resources\Mobile\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class VisitsResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'items' => VisitResource::collection($this->resource['items']),
            'next_cursor' => $this->resource['next_cursor'],
        ];
    }
}
