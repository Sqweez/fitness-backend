<?php

namespace App\Http\Resources\UserReports;

use App\Models\ClientReplenishment;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin ClientReplenishment */
class ReplenishmentsResource extends JsonResource
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
            'date' => format_datetime($this->created_at),
            'client' => $this->client,
            'amount' => $this->amount,
            'description' => $this->description,
            'type' => $this->getPaymentTypeTextAttribute(),
            'club' => $this->club,
            'payment_type' => $this->payment_type,
        ];
    }
}
