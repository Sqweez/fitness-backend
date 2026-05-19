<?php

namespace App\Http\Resources\Economy;

use App\Models\ClientReplenishment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClientReplenishment
 */

class AccountTopUp extends JsonResource
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
            'user' => $this->user,
            'amount' => $this->amount,
            'client' => $this->client,
            'club' => $this->club,
            'date' => format_datetime($this->created_at),
            'payment_type_text' => $this->payment_type_text,
            'payment_type' => $this->payment_type,
            'created_at' => $this->created_at,
            'type' => 'top-up'
        ];
    }
}
