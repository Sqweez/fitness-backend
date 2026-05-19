<?php

namespace App\Actions\Stats;

use App\Models\Client;
use App\Models\Service;
use App\Models\ServiceSale;

class GetUnlimitedEndingClientsAction {

    public function handle() {
        $serviceSales = ServiceSale::query()
            ->whereDate('active_until', '>=', today()->addDays(6))
            ->whereDate('active_until', '<=', today()->addDays(10))
            ->whereHas('service', function ($query) {
                return $query->where('service_type_id', Service::TYPE_UNLIMITED);
            })
            ->with('sale.client:id,name,phone')
            ->with('sale.club')
            ->with('service:id,name')
            ->get();

        return [
            'items' => $serviceSales->map(function (ServiceSale $sale) {
                return [
                    'id' => $sale->id,
                    'client' => $sale->sale->client,
                    'active_until' => format_date($sale->active_until),
                    'service' => $sale->service,
                    'club' => $sale->sale->club,
                    'unmasked_phone' => $sale->sale->client->phone,
                    'client_id' => $sale->sale->client_id,
                ];
            })
        ];
    }
}
