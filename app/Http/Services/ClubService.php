<?php

namespace App\Http\Services;

use App\Models\Client;
use App\Models\Club;
use App\Models\ClubProfile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ClubService
{
    public function getClientClub(Client $client): Club {
        return Club::query()->with('profile.media')->findOrFail($client->club_id);
    }

    public function getClub(Club $club): Club {
        return $club->load('profile.media');
    }

    public function updateClub(Club $club, array $data): Club {
        return DB::transaction(function () use ($club, $data) {
            $profile = $club->profile()->updateOrCreate([], Arr::except($data, ['cover']));
            if (array_key_exists('cover', $data)) {
                if ($data['cover'] === null) {
                    $profile->clearMediaCollection(ClubProfile::MEDIA_COVER);
                } else {
                    $profile->addMedia($data['cover'])->toMediaCollection(ClubProfile::MEDIA_COVER);
                }
            }
            return $club->refresh()->load('profile.media');
        });
    }
}
