<?php

namespace App\Actions\Service;

use App\Models\StopCard;

class StopPurchasedProgramAction {

    public function handle(array $payload): bool {
        StopCard::create($payload);
        return true;
    }
}
