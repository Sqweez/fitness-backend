<?php

namespace App\Actions\Stats;

use App\Models\Client;
use App\Models\Club;

class GetClientsByClubAction {

    private $clubs;

    public function handle() {
        $this->clubs = Club::query()->get();
        $clients = Client::query()
            ->select(['id', 'club_id', 'gender', 'has_active_programs'])
            //->with('programs')
            ->get();

        $totalClub = (object)
        [ 'id' => -1, 'name' => 'Всего'];

        $this->clubs->push($totalClub);

        return [
            'report' => $this->clubs->map(function ($club) use ($clients) {

                $clubClients = $club->id === - 1 ? $clients : $clients->where('club_id', $club->id);
                $totalClients = $clubClients->count();
                $totalManClients = $clubClients->where('gender', 'M')->count();
                $totalManPercentage = number_format(
                    calculate_percentage($totalManClients, $totalClients),
                    2);
                $totalWomanClients = $clubClients->where('gender', 'F')->count();
                $totalWomanPercentage = number_format(
                    calculate_percentage($totalWomanClients, $totalClients),
                    2);
                $totalActiveClients = $clubClients->where('has_active_programs', true)->count();
                $totalActivePercentage = number_format(
                    calculate_percentage($totalActiveClients, $totalClients),
                    2);

                return [
                    'id' => $club->id,
                    'name' => $club->name,
                    'total_clients' => $totalClients,
                    'total_man_clients' => $totalManClients,
                    'total_man_percentage' => $totalManPercentage,
                    'total_woman_clients' => $totalWomanClients,
                    'total_woman_percentage' => $totalWomanPercentage,
                    'total_active_clients' => $totalActiveClients,
                    'total_active_percentage' => $totalActivePercentage,
                ];
            })
        ];
    }
}
