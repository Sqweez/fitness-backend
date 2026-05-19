<?php

namespace App\Http\Resources\Client;

use App\Models\RestoredService;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin RestoredService */

class RestoredServicesApprovementListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'service' => $this->service,
            'user' => $this->user,
            'client' => $this->client,
            'club' => $this->service->club,
            'document' => [
                'name' => $this->getFirstMedia(RestoredService::RESTORE_APPLICATION)->name,
                'link' => $this->getFirstMediaUrl(RestoredService::RESTORE_APPLICATION)
            ],
            'restore_price' => $this->restore_price,
            'restore_until' => format_date($this->restore_until),
            'created_at' => format_datetime($this->created_at),
        ];
    }
}
