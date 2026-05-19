<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Economy\GetReportsRequest;
use App\Http\Resources\Client\ClientProductSaleHistoryResource;
use App\Http\Resources\Economy\AccountTopUp;
use App\Http\Resources\Economy\BarSaleHistoryResource;
use App\Http\Resources\Economy\SolariumHistoryResource;
use App\Http\Resources\WithDrawal\WithDrawalListResource;
use App\Http\Services\EconomyService;
use App\Models\ClientReplenishment;
use App\Models\ProductSale;
use App\Models\Sale;
use App\Models\Service;
use App\Models\Session;
use App\Models\SessionService;
use App\Models\Trinket;
use App\Models\User;
use App\Models\WithDrawal;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EconomyController extends ApiController
{
    public function index(GetReportsRequest $request, EconomyService $service) {
        $dates = $request->validated();
        $reports = $service->getReports($dates);
        return $this->respondSuccessNoReport([
            'reports' => $reports
        ]);
    }

    public function getClientsBalance(EconomyService $service): JsonResponse {
        return $this->respondSuccessNoReport([
            'reports' => $service->getClientsBalance()
        ]);
    }

    public function getGraphReports(GetReportsRequest $request, EconomyService $service): JsonResponse {
        return $this->respondSuccessNoReport([
            'reports' => $service->getGraphReports($request->validated())
        ]);
    }

    public function getMyTopUps(): \Illuminate\Support\Collection {
        $transactions = AccountTopUp::collection(
            ClientReplenishment::query()
                ->whereDate('created_at', '>=', now()->startOfDay())
                ->whereDate('created_at', '<=', now()->endOfDay())
                ->where('user_id', auth()->id())
                ->with(['club:id,name', 'client:id,name', 'user:id,name'])
                ->orderByDesc('created_at')
                ->get()
        );
        $withdrawals = WithDrawalListResource::collection(
            WithDrawal::query()
                ->whereDate('created_at', '>=', now()->startOfDay())
                ->whereDate('created_at', '<=', now()->endOfDay())
                ->where('user_id', auth()->id())
                ->with(['club:id,name'])
                ->orderByDesc('created_at')
                ->get()
        );

        return $transactions
            ->collection
            ->mergeRecursive(
                $withdrawals->collection
            )
            ->sortBy('created_at')
            ->values();
    }

    public function getMyWithDrawals(): AnonymousResourceCollection {
        return WithDrawalListResource::collection([]);
    }

    public function getMyBar(Request $request): AnonymousResourceCollection {
        $start = Carbon::parse($request->get('start'));
        $finish = Carbon::parse($request->get('finish'));
        $barProductSales = Sale::query()
            ->whereDate('created_at', '>=', $start->startOfDay())
            ->whereDate('created_at', '<=', $finish->endOfDay())
            ->barSales()
            ->when(!auth()->user()->getIsBossAttribute(), function ($query) {
                return $query->where('club_id', auth()->user()->club_id);
            })
            ->with(['client', 'user', 'transaction', 'salable.product', 'club'])
            ->latest()
            ->get();

        return BarSaleHistoryResource::collection($barProductSales);
    }

    public function getMySales(Request $request): AnonymousResourceCollection {
        $defaultDate = today()->format('Y-m-d');
        $start = Carbon::parse($request->get('start', $defaultDate));
        $finish = Carbon::parse($request->get('finish', $defaultDate));
        $barProductSales = Sale::query()
            ->whereDate('created_at', '>=', $start->startOfDay())
            ->whereDate('created_at', '<=', $finish->endOfDay())
            ->shopSales()
            ->when(!auth()->user()->getIsBossAttribute(), function ($query) {
                return $query->where('user_id', auth()->id());
            })
            ->with(['client', 'user', 'transaction', 'salable.product', 'club'])
            ->latest()
            ->get();

        return BarSaleHistoryResource::collection($barProductSales);
    }

    public function getMySolarium(): AnonymousResourceCollection {
        /* @var User $user */
        $user = auth()->user();
        /*$solariumHistory = SessionService::query()
            ->whereHas('service_sale', function ($query) {
                return $query->whereHas('service', function ($subQuery) {
                    return $subQuery->whereIn('service_type_id', [Service::TYPE_SOLARIUM]);
                });
            })
            ->today()
            ->when(!$user->is_boss, function ($query) use ($user) {
                return $query->whereHas('session', function ($subQuery) use ($user) {
                    return $subQuery->where('club_id', $user->club_id);
                });
            })
            ->with(['user', 'session.client'])
            ->latest()
            ->get();*/

        /*$solariumHistory = Session::query()
            ->whereHas('session_services', function ($query) {
                return $query->whereHas('service_sale', function ($query) {
                    return $query->whereHas('service', function ($subQuery) {
                        return $subQuery->whereIn('service_type_id', [Service::TYPE_SOLARIUM]);
                    });
                })->today();
            })
            ->when(!$user->is_boss, function ($query) use ($user) {
                return $query->where('club_id', $user->club_id);
            })
            ->with(['session_services' => function ($query) {
                return $query->whereHas('service_sale', function ($query) {
                    return $query->whereHas('service', function ($subQuery) {
                        return $subQuery->whereIn('service_type_id', [Service::TYPE_SOLARIUM]);
                    });
                })->today();
            }])
            ->with(['session_services.user', 'client'])
            ->latest()
            ->get();*/

        $solariumHistory = SessionService::query()
            ->whereHas('service_sale', function ($query) {
                return $query->whereHas('service', function ($subQuery) {
                    return $subQuery->whereIn('service_type_id', [Service::TYPE_SOLARIUM]);
                });
            })
            ->today()
            ->when(!$user->is_boss, function ($query) use ($user) {
                return $query->whereHas('session', function ($subQuery) use ($user) {
                    return $subQuery->where('club_id', $user->club_id);
                });
            })
            ->with(['user', 'session.client'])
            ->latest()
            ->get();

        return SolariumHistoryResource::collection($solariumHistory);
    }

    public function getKeys() {
        return Trinket::query()
            ->when(!auth()->user()->getIsBossAttribute(), function ($q) {
                return $q->where('club_id', auth()->user()->club_id);
            })
            ->has('active_session')
            ->with('active_session.start_user:id,name')
            ->with('active_session.client:id,name')
            ->with('club:id,name')
            ->get()
            ->map(function ($trinket) {
                $trinket->date = format_datetime($trinket->active_session->created_at);
                return $trinket;
            });
    }
}
