<?php

namespace App\Actions\Service;

use App\Models\ServiceSale;
use App\Models\StopCard;

class UnstopPurchasedProgramAction {

    public function handle(ServiceSale $service) {
        StopCard::query()
            ->where('service_sale_id', $service->id)
            ->update([
                'is_active' => false,
                'unstopped_at' => now()
            ]);
    }

}
