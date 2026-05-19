<?php

namespace App\Http\Resources\Economy;

use App\Models\Session;
use App\Models\SessionService;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin SessionService
 */

class SolariumHistoryResource extends JsonResource
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
            'client' => $this->session->client,
            'user' => $this->user,
            'club' => $this->session->club,
            'minutes' => $this->minutes,
            'date' => format_datetime($this->created_at),
            'created_at' => $this->created_at,
        ];
    }
}
