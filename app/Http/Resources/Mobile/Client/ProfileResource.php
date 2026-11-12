<?php

namespace App\Http\Resources\Mobile\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'first_name' => $this->resource['first_name'],
            'last_name' => $this->resource['last_name'],
        ];
    }
}
