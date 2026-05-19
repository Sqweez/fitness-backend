<?php

namespace App\Http\Resources\Client;

use App\Models\Service;
use App\Models\SessionService;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin SessionService */

class ClientVisitsHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $serviceName = $this->service_sale->service->name;

        return [
            'id' => $this->id,
            'date' => format_datetime($this->created_at),
            'service' => $serviceName,
            'club' => $this->session->club,
            'user' => $this->user,
            'trainer' => $this->trainer,
            'trinket' => $this->session->trinket->cabinet_number ?: '-',
            'minutes' => $this->minutes,
        ];
    }
}
