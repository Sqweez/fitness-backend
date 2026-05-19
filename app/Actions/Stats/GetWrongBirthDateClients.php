<?php

namespace App\Actions\Stats;

use App\Models\Client;

class GetWrongBirthDateClients {

    public function handle(): array {
        $clients = Client::query()
            ->whereDate('birth_date', '>=', today()->subYear())
            ->orWhereDate('birth_date', '<=', today()->subYears(90))
            ->select(['id', 'name', 'has_active_programs', 'club_id', 'gender', 'phone', 'birth_date'])
            ->with('club')
            ->get();

        return [
            'clients' => $clients->map(function (Client $client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'gender_display' => $client->gender_display,
                    'gender' => $client->gender,
                    'club' => $client->club,
                    'phone' => $client->phone,
                    'birth_date' => format_date($client->birth_date)
                ];
            })
        ];
    }
}
