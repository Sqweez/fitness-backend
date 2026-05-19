<?php

namespace App\Http\Services;

use App\Models\Client;
use App\Models\ClientCustomService;
use App\Models\CustomService;
use App\Models\SessionService;
use App\Models\Trinket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ClientService {

    public function createClient($payload = []): ?Client {
        return DB::transaction(function () use ($payload) {
            $client = Client::create(Arr::except($payload, ['pass', 'photo']));
            if (isset($payload['pass'])) {
                $pass = PassService::createPass($payload['pass']);
                $client->pass()->save($pass);
            }
            if (isset($payload['photo'])) {
                $client->addMedia($payload['photo'])->toMediaCollection(Client::MEDIA_AVATAR);
            }
            return $client;
        });
    }

    public function updateClient(Client $client, $payload = []): ?Client {
        return DB::transaction(function () use ($client, $payload) {
            \Log::info('aa', $payload);
            $client->update(Arr::except($payload, ['pass', 'photo']));
            if (isset($payload['pass'])) {
                $client->pass()->delete();
                $pass = PassService::createPass($payload['pass']);
                $client->pass()->save($pass);
            }
            if (isset($payload['photo'])) {
                $oldMedia = $client->getFirstMedia(Client::MEDIA_AVATAR);
                if ($oldMedia) {
                    $oldMedia->delete();
                }
                $client->addMedia($payload['photo'])->toMediaCollection(Client::MEDIA_AVATAR);
                ClientCustomService::create([
                    'client_id' => $client->id,
                    'user_id' => auth()->id(),
                    'club_id' => auth()->user()->club_id,
                    'custom_service_id' => __hardcoded(2),
                ]);
            }
            return $client;
        });
    }

    /**
     * @throws \Throwable
     */
    public function writeOff($payload = []) {
        return DB::transaction(function () use ($payload) {
            $client = Client::find($payload['client_id']);
            $session = $this->open($client, $payload);
            SessionService::create([
                'service_sale_id' => $payload['service_sale_id'],
                'user_id' => $payload['user_id'],
                'session_id' => $session->id,
                'trainer_id' => $payload['trainer_id']
            ]);
            return Client::find($client->id);
        });
    }

    public function writeOffSolarium(array $payload) {
        $client = Client::find($payload['client_id']);
        $minutes = $payload['minutes'];
        $activeSolarium = $client->solarium->sortBy('id');
        $activeSolarium->load('salable.visits');
        $session = $this->open($client, $payload);

        $client->update(['cached_solarium_total' => $client->cached_solarium_total - $minutes]);

        /*$activeSolarium = $activeSolarium->filter(function ($solarium) {
            return $solarium->salable->remaining_minutes > 0;
        });*/

        SessionService::create([
            'user_id' => $payload['user_id'],
            'service_sale_id' => $activeSolarium->first()->salable_id,
            'session_id' => optional($session)->id,
            'minutes' => $minutes
        ]);

        /*foreach ($activeSolarium as $item) {
            if ($minutes > 0) {
                $needleMinutes = min($minutes, $item->salable->remaining_minutes);
                $minutes -= $needleMinutes;

            }
        }*/

        return Client::find($client->id);
    }

    public function open(Client $client, $payload = []): Model {
        if ($client->active_session) {
            /*return $client->active_session;*/
            $session = $client->active_session;
            $this->finish($client);
            $client->update([
                'cached_trinket' => optional(Trinket::find($session->trinket_id))->code
            ]);
            return $client->sessions()->create([
                'start_user_id' => $payload['user_id'],
                'club_id' => auth()->user()->club_id,
                'trinket_id' => $session->trinket_id,
            ]);
        }
        return $client->sessions()->create([
            'start_user_id' => $payload['user_id'],
            'club_id' => auth()->user()->club_id,
            'trinket_id' => null,
        ]);
    }

    public function finish(Client $client) {
        $session = $client->active_session;
        if ($session) {
            $session->update([
                'finish_user_id' => auth()->id(),
                'finished_at' => now(),
                'is_system_finished' => app()->runningInConsole()
            ]);
        }
        $client->update(['cached_trinket' => null]);
    }
}
