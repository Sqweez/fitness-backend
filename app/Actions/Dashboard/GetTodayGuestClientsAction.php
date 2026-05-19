<?php

namespace App\Actions\Dashboard;

use App\Models\Client;
use App\Models\Session;
use App\Models\User;

class GetTodayGuestClientsAction {

    public function handle() {
        /* @var User $user */
        $user = auth()->user();
        return Session::query()
            ->today()
            ->when(!$user->is_boss, function ($query) use ($user) {
                return $query->where('club_id', $user->club_id);
            })
            ->with(['club', 'client'])
            ->get()
            ->unique(function ($item) {
                return sprintf("%s_%s", $item->client_id, $item->club_id);
            });
    }
}
