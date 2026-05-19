<?php

namespace App\Actions\Stats;

use App\Models\Client;

class GetActiveClientsAction {

    public function handle(): array {
        $clients = Client::query()
            ->where('has_active_programs', true)
            ->select(['id', 'name', 'has_active_programs', 'club_id', 'gender', 'phone'])
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
                ];
            })
        ];
    }
}
