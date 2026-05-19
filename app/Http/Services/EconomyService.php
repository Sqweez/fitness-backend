<?php

namespace App\Http\Services;

use App\Http\Resources\Economy\AccountTopUp;
use App\Http\Resources\Economy\ClubGuests;
use App\Http\Resources\Economy\NewClients;
use App\Http\Resources\Economy\PurchasedPrograms;
use App\Models\Client;
use App\Models\ClientReplenishment;
use App\Models\Club;
use App\Models\Sale;
use App\Models\ServiceSale;
use App\Models\Session;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EconomyService {

    public function getReports($dates) {
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
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $finish)
            ->with('club:id,name')
            ->get()
            ->groupBy('club_id');

        $ranges = collect(get_dates_range($start, $finish));

        $clubs = $clubs->map(function ($club) use ($data) {
            $needleData = isset($data[$club->id]) ? collect($data[$club->id]) : collect([]);
            $needleData = $needleData
                ->groupBy(function ($i) {
                    return Carbon::parse($i['created_at'])->format('Y-m-d');
                })
                ->map(function ($i) {
                    return collect($i)->sum('amount');
                });

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
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $finish)
            ->with(['club:id,name', 'client:id,name', 'user:id,name'])
            ->get();
        return AccountTopUp::collection($transactions);
    }

    private function getClubGuestsReport($start, $finish): AnonymousResourceCollection {
        $sessions = Session::query()
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $finish)
            ->with(['client', 'start_user', 'finish_user', 'club'])
            ->whereNotNull('finished_at')
            ->get();
        return ClubGuests::collection($sessions);
    }

    private function getProgramPurchasedReport($start, $finish) {
        $sales = Sale::query()
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $finish)
            ->where('salable_type', ServiceSale::class)
            ->with(['client', 'user', 'club', 'salable.service', 'transaction'])
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
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $finish)
            ->with(['club', 'registrar'])
            ->get();
        return NewClients::collection($clients);
    }
}
