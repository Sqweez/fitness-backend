<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Resources\Json\JsonResource;

class ConfigResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'contacts' => $this->resource['contacts'],
            'app' => $this->resource['app'],
            'maintenance' => $this->resource['maintenance'],
            'links' => $this->resource['links'],
            'realtime' => $this->resource['realtime'],
        ];
    }

    public function withResponse($request, $response): void {
        $response->headers->set('Cache-Control', 'no-store, private');
    }
}
