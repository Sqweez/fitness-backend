<?php

namespace App\Http\Resources\Mobile\Client;

use App\Models\Pass;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Pass */
class PassResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'code' => (string) $this->code,
            'format' => 'CODE128',
        ];
    }
}
