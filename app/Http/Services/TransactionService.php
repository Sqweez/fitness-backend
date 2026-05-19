<?php

namespace App\Http\Services;

use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;

class TransactionService {

    public static function create(Client $client, $payload = []): Model {
        $transaction = $client->transactions()->create($payload);
        $client->update([
            'balance' => $client->balance + $transaction->amount
        ]);
        return $transaction;
    }
}
