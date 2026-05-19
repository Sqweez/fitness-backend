<?php

namespace App\Http\Resources\UserReports;

use App\Models\WithDrawal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin WithDrawal */
class WithdrawalResource extends JsonResource
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
            'user' => $this->user,
            'club' => $this->club,
            'amount' => $this->amount,
            'date' => format_datetime($this->created_at),
            'description' => $this->description,
            'is_bar' => $this->description && \Str::contains(\Str::lower($this->description), 'бар'),
            'payment_type' => $this->payment_type,
        ];
    }
}
