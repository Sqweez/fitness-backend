<?php

namespace App\Actions\Penalty;

use App\Models\ClientServicePenalty;

class CreateClientPenaltyWriteOffAction {

    public function handle($payload) {
        ClientServicePenalty::create($payload);
    }
}
