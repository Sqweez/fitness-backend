<?php

namespace App\Actions\Client;

use App\Http\Resources\Client\ClientCustomServiceResource;
use App\Http\Resources\Client\ClientProductSaleHistoryResource;
use App\Http\Resources\Client\ClientReplenishmentsResource;
use App\Http\Resources\Client\ClientServiceSaleHistoryResource;
use App\Http\Resources\Client\ClientVisitsHistoryResource;
use App\Models\Client;
use App\Models\ClientCustomService;
use App\Models\ClientReplenishment;
use App\Models\ProductSale;
use App\Models\Sale;
use App\Models\Service;
use App\Models\ServiceSale;
use App\Models\Session;
use App\Models\SessionService;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GetClientHistoryAction {

    private Client $client;
    private $start;
    private $finish;

    public function handle(Client $client, $start, $finish): array {
        $this->client = $client;
        $this->start = Carbon::parse($start);
        $this->finish = Carbon::parse($finish);
        return [
            'replenishments' => $this->collectClientReplenishments(),
            'product_purchases' => $this->collectClientPurchases(),
            'bar_purchases' => $this->collectClientBarPurchases(),
            'service_purchases' => $this->collectServicePurchases(),
            'solarium_purchases' => $this->collectSolariumPurchases(),
            'visits' => $this->collectVisits(),
            'solarium_visits' => $this->collectSolariumVisits(),
            'custom_services' => $this->collectCustomServices(),
        ];
    }

    private function collectCustomServices(): AnonymousResourceCollection {
        $clientCustomActions = ClientCustomService::query()
            ->whereDate('created_at', '>=', $this->start)
            ->whereDate('created_at', '<=', $this->finish)
            ->where('client_id', $this->client->id)
            ->with(['user:id,name', 'club:id,name', 'custom_service:id,name'])
            ->latest()
            ->get();

        return ClientCustomServiceResource::collection($clientCustomActions);
    }

    private function collectClientReplenishments(): AnonymousResourceCollection {
        $replenishments = ClientReplenishment::query()
            ->whereDate('created_at', '>=', $this->start)
            ->whereDate('created_at', '<=', $this->finish)
            ->where('client_id', $this->client->id)
            ->with(['user', 'club', 'client'])
            ->latest()
            ->get();

        return ClientReplenishmentsResource::collection($replenishments);
    }

    private function collectClientPurchases(): AnonymousResourceCollection {
        $productSales = Sale::query()
            ->where('client_id', $this->client->id)
            ->whereDate('created_at', '>=', $this->start)
            ->whereDate('created_at', '<=', $this->finish)
            ->shopSales()
            ->with(['client', 'user', 'transaction', 'salable.product', 'club'])
            ->latest()
            ->get();

        return ClientProductSaleHistoryResource::collection($productSales);
    }

    public function collectClientBarPurchases(): AnonymousResourceCollection {
        $productSales = Sale::query()
            ->where('client_id', $this->client->id)
            ->whereDate('created_at', '>=', $this->start)
            ->whereDate('created_at', '<=', $this->finish)
            ->barSales()
            ->with(['client', 'user', 'transaction', 'salable.product', 'club'])
            ->latest()
            ->get();

        return ClientProductSaleHistoryResource::collection($productSales);
    }

    private function collectServicePurchases(): AnonymousResourceCollection {
        $serviceSales = Sale::query()
            ->where('client_id', $this->client->id)
            ->whereDate('created_at', '>=', $this->start)
            ->whereDate('created_at', '<=', $this->finish)
            ->whereHasMorph('salable', [ServiceSale::class], function ($query) {
                return $query->whereHas('service', function ($query) {
                    return $query->whereIn('service_type_id', [Service::TYPE_PROGRAM, Service::TYPE_UNLIMITED]);
                });
            })
            ->with(['client', 'user', 'transaction', 'salable.service', 'club'])
            ->latest()
            ->get();

        return ClientServiceSaleHistoryResource::collection($serviceSales);
    }

    public function collectSolariumPurchases () {
        $serviceSales = Sale::query()
            ->where('client_id', $this->client->id)
            ->whereDate('created_at', '>=', $this->start)
            ->whereDate('created_at', '<=', $this->finish)
            ->whereHasMorph('salable', [ServiceSale::class], function ($query) {
                return $query->whereHas('service', function ($query) {
                    return $query->whereIn('service_type_id', [Service::TYPE_SOLARIUM]);
                })->where('minutes_remaining', '>', 0);
            })
            ->with(['client', 'user', 'transaction', 'salable.service', 'club'])
            ->latest()
            ->get();

        return ClientServiceSaleHistoryResource::collection($serviceSales);
    }

    private function collectVisits(): AnonymousResourceCollection {
        $visits = SessionService::query()
            ->whereHas('session', function ($query) {
                return $query->where('client_id', $this->client->id);
            })
            ->whereHas('service_sale', function ($query) {
                return $query->whereHas('service', function ($subQuery) {
                    return $subQuery->whereIn('service_type_id', [Service::TYPE_UNLIMITED, Service::TYPE_PROGRAM]);
                });
            })
            ->whereDate('created_at', '>=', $this->start)
            ->whereDate('created_at', '<=', $this->finish)
            ->with(['user', 'trainer', 'session.trinket', 'service_sale.service', 'session.client'])
            ->latest()
            ->get();

        return ClientVisitsHistoryResource::collection($visits);
    }

    private function collectSolariumVisits(): AnonymousResourceCollection {
        $visits = SessionService::query()
            ->whereHas('session', function ($query) {
                return $query->where('client_id', $this->client->id);
            })
            ->whereHas('service_sale', function ($query) {
                return $query->whereHas('service', function ($subQuery) {
                    return $subQuery->whereIn('service_type_id', [Service::TYPE_SOLARIUM]);
                });
            })
            ->whereDate('created_at', '>=', $this->start)
            ->whereDate('created_at', '<=', $this->finish)
            ->with(['user', 'trainer', 'session.trinket', 'service_sale.service', 'session.client'])
            ->latest()
            ->get();
        return ClientVisitsHistoryResource::collection($visits);
    }
}
