<?php

namespace App\Http\Resources\Mobile\Client;

use App\Models\Trinket;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Trinket */
class TrinketResource extends JsonResource
{
    public function toArray($request): array {
        if ($this->resource === null) {
            return ['data' => null];
        }

        return ['number' => (string) $this->cabinet_number];
    }
}
