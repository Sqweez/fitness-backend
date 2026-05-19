<?php

namespace App\Actions\Stats;
use App\Http\Resources\Stats\PriceSaleHistoryResource;
use App\Models\Sale;
use App\Models\ServiceSale;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GetPriceSaleHistoryAction {

    public function handle(Request $request) {
        $start = Carbon::parse($request->get('start'));
        $finish = Carbon::parse($request->get('finish'));
        $services = $request->get('services', null);
        if ($services) {
            $services = explode(',', $services);
        }
        $sales = Sale::query()
            ->hasMorph('salable', [ServiceSale::class])
            ->whereDate('created_at', '>=', $start->startOfDay())
            ->whereDate('created_at', '<=', $finish->endOfDay())
            ->when($services, function ($query) use ($services) {
                return $query->whereHasMorph('salable', [ServiceSale::class], function ($subQuery) use ($services) {
                    return $subQuery->whereIn('service_id', $services);
                });
            })
            ->with('salable.service:id,name')
            ->with('transaction:id,transactional_type,transactional_id,amount')
            ->with('user:id,name')
            ->with('club:id,name')
            ->with('client:id,name')
            ->get();

        return [
            'items' => PriceSaleHistoryResource::collection($sales),
            'total' => $this->getTotalServiceSales($sales),
        ];
    }

    private function getTotalServiceSales($sales) {
        return $sales
            ->groupBy(function ($sale) {
                return $sale->salable->service_id;
            })
            ->map(function ($items, $serviceId) {
                return [
                    'service_id' => $serviceId,
                    'service' => $items->first()->salable->service,
                    'amount' => $items->reduce(function ($a, $c) {
                        return $a + optional($c->transaction)->amount;
                    }, 0) * - 1,
                    'count' => $items->count()
                ];
            })
            ->values();
    }
}
