<?php

namespace App\Http\Resources\Economy;

use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Session
 * */

class ClubGuests extends JsonResource
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
            'client' => $this->client,
            'start_user' => $this->start_user,
            'finish_user' => $this->finish_user,
            'club' => $this->club,
            'created_at' => format_datetime($this->created_at),
            'finished_at' => format_datetime($this->finished_at),
            'time_duration' => $this->time_duration,
            'money_spent' => __hardcoded(0)
        ];
    }
}
