<?php

namespace App\Http\Resources\Mobile\Client;

use Illuminate\Http\Resources\Json\JsonResource;

class MembershipsResource extends JsonResource
{
    public function toArray($request): array {
        return [
            'memberships' => MembershipResource::collection($this->resource),
            'message' => 'Поможем выбрать подходящий тариф. Напишите администратору или оформите абонемент на ресепшене клуба.',
        ];
    }
}
