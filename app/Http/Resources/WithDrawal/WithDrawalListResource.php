<?php

namespace App\Http\Resources\WithDrawal;

use App\Models\WithDrawal;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin WithDrawal */

class WithDrawalListResource extends JsonResource
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
            'client' => ['name' => 'Списание'],
            'club' => $this->club,
            'date' => format_datetime($this->created_at),
            'payment_type_text' => $this->payment_type_text,
            'payment_type' => $this->payment_type,
            'created_at' => $this->created_at,
            'type' => 'withdrawal'
        ];
    }
}
