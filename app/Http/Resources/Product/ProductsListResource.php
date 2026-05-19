<?php

namespace App\Http\Resources\Product;

use App\Models\Club;
use App\Models\Product;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin Product */

class ProductsListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $quantity = $this->collectQuantities();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'stock_price' => blank($this->stock_price) ? null : $this->stock_price,
            'full_name' => trim(sprintf('%s %s', $this->name, ($this->attribute ?: ''))),
            'category' => $this->category,
            'product_category_id' => $this->product_category_id,
            'product_type_id' => $this->product_type_id,
            'product_type' => $this->product_type,
            'quantity' => $quantity,
            'barcode' => $this->barcode,
            'not_in_stock' => collect($quantity)->reduce(function ($a, $c) {
                return $a + $c['quantity'];
            }, 0) === 0
            //'batches' => $this->batches,
        ];
    }
}
