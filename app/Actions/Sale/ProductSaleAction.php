<?php

namespace App\Actions\Sale;

use App\Models\Client;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\Service;

class ProductSaleAction {

    public function handle(array $payload) {
        return \DB::transaction(function () use ($payload) {
            $product = Product::find($payload['product_id']);
            $client = Client::find($payload['client_id']);
            $needleBatch = $product->decrementBatch($payload['club_id']);
            $productSale = ProductSale::create([
                'product_id' => $product->id,
                'product_batch_id' => $needleBatch->id,
                'purchase_price' => $needleBatch->purchase_price
            ]);
            $salePayload = $this->_mapSalePayload($payload);
            $sale = $productSale->sale()->create($salePayload);
            $transactionPayload = $this->_mapTransactionPayload($product, $payload);
            $transaction = $sale->transaction()->create($transactionPayload);
            // Инкремент т.к. значение транзакции уже отрицательное
            $client->increment('balance', $transaction->amount);
        });
    }

    private function _mapSalePayload($payload): array {
        return [
            'client_id' => $payload['client_id'],
            'user_id' => $payload['user_id'],
            'club_id' => $payload['club_id'],
        ];
    }

    public function _mapTransactionPayload(Product $product, $payload): array {
        $description = sprintf("Списание средств за покупку товара %s", $product->fullname);
        return [
            'user_id' => $payload['user_id'],
            'client_id' => $payload['client_id'],
            'amount' => $payload['amount'] * -1,
            'club_id' => $payload['club_id'],
            'description' => $description,
        ];
    }
}
