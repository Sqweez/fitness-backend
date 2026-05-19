<?php

namespace App\Actions\Dashboard;

use App\Http\Resources\Client\BirthdayClientListResource;
use App\Models\Client;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GetBirthdayClientsAction {

    public function handle(): AnonymousResourceCollection {
        $user = auth()->user();
        $clients = Client::query()
            ->when(
                (!$user->getIsBossAttribute() && $user->club_id !== __hardcoded(3)),
                function ($query) use ($user) {
                    return $query->where('club_id', $user->club_id);
                })
            // для детских клубов
            ->when(
                (!$user->getIsBossAttribute() && $user->club_id === __hardcoded(3)),
                function ($query) use ($user) {
                    return $query->whereDate('birth_date', '>=', now()->subYears(14));
                })
            ->whereDay('birth_date', today())
            ->whereMonth('birth_date', today())
            ->select(['id', 'name', 'phone', 'birth_date', 'club_id'])
            ->with('club')
            ->get();

        return BirthdayClientListResource::collection($clients);
    }
}
