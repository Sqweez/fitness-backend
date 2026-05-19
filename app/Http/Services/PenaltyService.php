<?php

namespace App\Http\Services;

use App\Models\Client;

class PenaltyService {

    public function createPenaltyWriteOff(array $payload = []) {
        // @TODO DTO pattern
        $client = Client::query()->find($payload['client_id']);

    }
}
