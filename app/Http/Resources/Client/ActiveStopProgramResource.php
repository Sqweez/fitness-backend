<?php

namespace App\Http\Resources\Client;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ActiveStopProgramResource extends JsonResource
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
            'user' => $this->user,
            'remaining_days' => $this->remaining_days,
            'created_at_formatted' => format_datetime($this->created_at),
            'created_at' => $this->created_at,
            'active_until_prev' => $this->active_until_prev,
            'active_until_prev_formatted' => format_date($this->active_until_prev),
            'new_active_until' => format_date(Carbon::parse($this->created_at)->addDays($this->remaining_days)),
            'description' => $this->description,
        ];
    }
}
