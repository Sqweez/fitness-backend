<?php

namespace App\Actions\UserReports;

use App\Http\Resources\Economy\BarSaleHistoryResource;
use App\Http\Resources\Economy\PurchasedPrograms;
use App\Http\Resources\Economy\SolariumHistoryResource;
use App\Http\Resources\UserReports\ReplenishmentsResource;
use App\Http\Resources\UserReports\WithdrawalResource;
use App\Models\ClientReplenishment;
use App\Models\Sale;
use App\Models\Service;
use App\Models\ServiceSale;
use App\Models\Session;
use App\Models\SessionService;
use App\Models\User;
use App\Models\WithDrawal;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RetrieveUserReportsAction {

    private $start;
    private $finish;
    private User $user;

    public function handle(User $user, $start = null, $finish = null) {
        $this->user = $user;
        $this->start = $start;
        $this->finish = $finish;

        return [
            'replenishments' => $this->getReplenishments(),
            'withdrawals' => $this->getWithdrawals(),
            'service_sales' => $this->getServiceSales(),
            'shop_sales' => $this->getShopSales(),
            'solarium_visits' => $this->getSolariumVisits(),
            'given_keys' => $this->getGivenKeys(),
        ];
    }

    private function getReplenishments(): AnonymousResourceCollection {
        $replenishments = ClientReplenishment::query()
            ->where('user_id', $this->user->id)
            ->when(($this->start && $this->finish), function ($query) {
                return $query
                    ->whereDate('created_at', '>=', $this->start)
                    ->whereDate('created_at', '<=', $this->finish);
            })
            ->with([
                'user:id,name',
                'club:id,name',
                'client:id,name',
            ])
            ->get();

        return ReplenishmentsResource::collection($replenishments);
    }

    private function getWithdrawals(): AnonymousResourceCollection {
        $items = WithDrawal::query()
            ->where('user_id', $this->user->id)
            ->when(($this->start && $this->finish), function ($query) {
                return $query
                    ->whereDate('created_at', '>=', $this->start)
                    ->whereDate('created_at', '<=', $this->finish);
            })
            ->with([
                'user:id,name',
                'club:id,name',
            ])
            ->get();

        return WithdrawalResource::collection($items);
    }

    private function getServiceSales() {
        return Sale::query()
            ->where('user_id', $this->user->id)
            ->whereDate('created_at', '>=', $this->start)
            ->whereDate('created_at', '<=', $this->finish)
            ->where('salable_type', ServiceSale::class)
            ->with([
                'client:id,name',
                'user:id,name',
                'club:id,name',
                'salable.service',
                'transaction'
            ])
            ->get()
            ->groupBy(function (Sale $sale) {
                return $sale->created_at->format('Y-m-d H:i:s');
            })
            ->map(function ($sales, $created_at) {
                $sale = $sales->first();
                return [
                    'id' => $sale->id,
                    'name' => $sale->salable->service ? $sale->salable->service->name : 'Удаленная программа',
                    'user' => $sale->user,
                    'club' => $sale->club,
                    'client' => $sale->client,
                    'amount' => $sales->reduce(function ($a, $c) {
                        if (isset($c['transaction'])) {
                            return $a + $c['transaction']['amount'];
                        } else {
                            return $a + 0;
                        }
                    }, 0) * -1,
                    'date' => format_datetime($sale->created_at)
                ];
            })
            ->values();
    }

    private function getShopSales(): AnonymousResourceCollection {
        $sales = Sale::query()
            ->whereDate('created_at', '>=', $this->start)
            ->whereDate('created_at', '<=', $this->finish)
            ->shopSales()
            ->with(['client:id,name', 'user:id,name', 'transaction', 'salable.product', 'club'])
            ->where('user_id', $this->user->id)
            ->get();

        return BarSaleHistoryResource::collection($sales);
    }

    private function getSolariumVisits(): AnonymousResourceCollection {
        $solariumHistory = SessionService::query()
            ->whereHas('service_sale', function ($query) {
                return $query->whereHas('service', function ($subQuery) {
                    return $subQuery->where('service_type_id', Service::TYPE_SOLARIUM);
                });
            })
            ->whereDate('created_at', '>=', $this->start)
            ->whereDate('created_at', '<=', $this->finish)
            ->where('user_id', $this->user->id)
            ->with(['user:id,name', 'session.client:id,name'])
            ->get();

        return SolariumHistoryResource::collection($solariumHistory);
    }

    private function getGivenKeys() {
        $keys = Session::query()
            ->whereDate('created_at', '>=', $this->start)
            ->whereDate('created_at', '<=', $this->finish)
            ->where('start_user_id', $this->user->id)
            ->with(['client:id,name', 'trinket', 'club:id,name'])
            ->get();

        return $keys->map(function (Session $session) {
            return [
                'id' => $session->id,
                'client' => $session->client,
                'trinket' => $session->trinket,
                'date' => format_datetime($session->created_at),
                'club' => $session->club,
            ];
        });
    }
}
