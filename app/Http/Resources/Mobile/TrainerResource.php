<?php

namespace App\Http\Resources\Mobile;

use App\Models\Trainer;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Trainer */
class TrainerResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'id' => (string) $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'specialization' => $this->specialization,
            'photo_url' => $this->photo_url,
        ];
    }
}
