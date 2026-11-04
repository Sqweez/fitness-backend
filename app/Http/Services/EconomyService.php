<?php

namespace App\Http\Services;

use App\Http\Resources\Economy\AccountTopUp;
use App\Http\Resources\Economy\ClubGuests;
use App\Http\Resources\Economy\NewClients;
use App\Models\Client;
use App\Models\ClientReplenishment;
use App\Models\Club;
use App\Models\Sale;
use App\Models\ServiceSale;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EconomyService {

    public function getReports($dates): array
    {
        $start = $dates['start'];
        $finish = $dates['finish'];
        return [
            'accounts_top_ups' => $this->getBalancesTopUpReport($start, $finish),
            'programs_purchased' => $this->getProgramPurchasedReport($start, $finish),
            'new_clients' => $this->getNewClientsReport($start, $finish),
            'club_guests' => $this->getClubGuestsReport($start, $finish),
        ];
    }

    public function getGraphReports($dates) {
        $start = $dates['start'];
        $finish = $dates['finish'];
        $clubs = Club::all();
        $data = ClientReplenishment::query()
            ->whereBetween('created_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($finish)->endOfDay()])
            ->selectRaw('club_id, DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('club_id', 'day')
            ->get()
            ->groupBy('club_id');

        $ranges = collect(get_dates_range($start, $finish));

        $clubs = $clubs->map(function ($club) use ($data) {
            $needleData = isset($data[$club->id])
                ? $data[$club->id]->pluck('total', 'day')->map(fn ($total) => (int) $total)
                : collect([]);

            return [
                'id' => $club->id,
                'name' => $club->name,
                'data' => $needleData,
                'color' => $club->club_color,
            ];
        });

        $mappedReports = $clubs->map(function ($data) use ($ranges) {
            return [
                'label' => $data['name'],
                'color' => $data['color'],
                'data' => collect($ranges)->map(function ($range) use ($data) {
                    return $data['data'][$range] ?? 0;
                }),
            ];
        });

        $totalReports = collect([[
            'label' => 'Итого',
            'color' => '#43A047',
            'data' => collect($ranges)->map(function ($range, $key) use ($mappedReports) {
                return $mappedReports->reduce(function ($a, $c) use ($key) {
                    return $a + $c['data'][$key];
                }, 0);
            })
        ]]);

        $reports = $mappedReports->mergeRecursive($totalReports);

        return [
            'dates' => $ranges->map(function ($range) {
                return Carbon::parse($range)->format('d.m.Y');
            }),
            'reports' => $reports,
        ];
    }

    public function getClientsBalance() {
        return
            Club::query()
                ->select(['id', 'name'])
                ->withSum('clients', 'balance')
                ->get()
                ->map(function ($club) {
                    $club['amount'] = $club['clients_sum_balance'] ? intval($club['clients_sum_balance']) : 0;
                    return $club;
                });
    }

    private function getBalancesTopUpReport($start, $finish): AnonymousResourceCollection {
        $transactions = ClientReplenishment::query()
            ->whereBetween('created_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($finish)->endOfDay()])
            ->with(['club:id,name', 'client:id,name', 'user:id,name'])
            ->get();
        return AccountTopUp::collection($transactions);
    }

    private function getClubGuestsReport($start, $finish): AnonymousResourceCollection {
        $sessions = Session::query()
            ->whereBetween('created_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($finish)->endOfDay()])
            ->with(['client:id,name', 'start_user:id,name', 'finish_user:id,name', 'club:id,name'])
            ->whereNotNull('finished_at')
            ->get();
        return ClubGuests::collection($sessions);
    }

    private function getProgramPurchasedReport($start, $finish) {
        $sales = Sale::query()
            ->whereBetween('created_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($finish)->endOfDay()])
            ->where('salable_type', ServiceSale::class)
            ->with(['client:id,name', 'user:id,name', 'club:id,name', 'salable.service', 'transaction'])
            ->get()
            ->groupBy(function (Sale $sale) {
                return $sale->created_at->format('Y-m-d H:i:s');
            })
            ->map(function ($sales) {
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
            });
        return $sales->values();
    }

    private function getNewClientsReport($start, $finish): AnonymousResourceCollection {
        $clients = Client::query()
            ->whereBetween('created_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($finish)->endOfDay()])
            ->with(['club:id,name', 'registrar:id,name'])
            ->get();
        return NewClients::collection($clients);
    }
}
