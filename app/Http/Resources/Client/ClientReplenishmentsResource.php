<?php

namespace App\Http\Resources\Client;

use App\Models\ClientReplenishment;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


/* @mixin ClientReplenishment */
class ClientReplenishmentsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'date' => format_datetime($this->created_at),
            'amount' => $this->amount,
            'user' => $this->user,
            'payment_type' => $this->payment_type_text,
        ];
    }
}
