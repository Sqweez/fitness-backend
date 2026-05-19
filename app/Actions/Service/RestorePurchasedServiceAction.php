<?php

namespace App\Actions\Service;

use App\Models\RestoredService;
use App\Models\Transaction;

class RestorePurchasedServiceAction {

    /**
     * @throws \Throwable
     */
    public function handle(RestoredService $restoredService): void {
        if (!$restoredService->is_accepted) {
            return ;
        }
        \DB::transaction(function () use ($restoredService) {
            $restoredService->service_sale->update([
                'active_until' => $restoredService->restore_until
            ]);
        });
    }
}
