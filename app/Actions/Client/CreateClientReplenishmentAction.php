<?php

namespace App\Actions\Client;

use App\Http\Requests\Client\TopUpClientAccountRequest;
use App\Models\Client;

class CreateClientReplenishmentAction {

    public function handle(TopUpClientAccountRequest $request, Client $client) {
        $replenishment = $client->replenishments()->create($request->validated());
        $replenishment->transaction()->create($request->validated());
        $client->increment('balance', $replenishment->amount);
    }
}
