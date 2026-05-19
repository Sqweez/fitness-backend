<?php

namespace App\Actions\Client;

use App\Http\Resources\Client\ClientPurchasedServiceHistoryResource;
use App\Models\Client;
use App\Models\Sale;
use App\Models\Service;
use App\Models\ServiceSale;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GetClientServiceHistoryAction {

    public function handle(Client $client, $service_id = null): AnonymousResourceCollection {
        $programs = Sale::query()
            ->where('client_id', $client->id)
            ->whereHasMorph('salable', [ServiceSale::class], function ($query) {
                return $query->whereHas('service', function ($query) {
                    return $query->whereIn('service_type_id', [Service::TYPE_PROGRAM, Service::TYPE_UNLIMITED]);
                });
            })
            ->when($service_id !== null, function ($query) use ($service_id) {
                $query->where('salable_id', $service_id);
            })
            ->with('salable.service')
            ->with('salable.penalties.user')
            ->with('salable.penalties.trainer')
            ->with('salable.penalties.club')
            ->with('salable.acceptedRestores.user')
            ->with('salable.visits.trainer')
            ->with('salable.visits.user')
            ->with('salable.visits.session.club')
            ->with('user:id,name')
            ->with('transaction')
            ->get();

        return ClientPurchasedServiceHistoryResource::collection($programs);
    }
}
