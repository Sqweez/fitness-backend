<?php

namespace App\Actions\Service;

use App\Models\Sale;
use App\Models\Service;
use App\Models\ServiceSale;
use Illuminate\Http\Request;

class ActivatePurchasedServiceAction {

    public function handle(Request $request, ServiceSale $serviceSale): ?Sale {
        $service = $serviceSale->service;
        $activeUntil = now()->addDays($service->validity_days);
        // Если солярий, то прибавляем 100 лет
        if ($service->service_type_id === Service::TYPE_SOLARIUM) {
            $activeUntil = now()->addYears(100);
        }
        $serviceSale->update([
            'active_until' => $activeUntil,
            'activated_at' => now()
        ]);
        return $serviceSale->sale;
    }
}
