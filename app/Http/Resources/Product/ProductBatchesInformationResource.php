<?php

namespace App\Http\Resources\Product;

use App\Models\ProductBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin ProductBatch */
class ProductBatchesInformationResource extends JsonResource
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
            'club' => $this->club,
            'user' => $this->user,
            'quantity' => $this->initial_quantity,
            'date' => format_datetime($this->created_at)
        ];
    }
}
