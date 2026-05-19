<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Requests\Client\AttachTrinketRequest;
use App\Http\Requests\Client\WriteOffSolariumRequest;
use App\Http\Requests\Client\WriteOffVisitRequest;
use App\Http\Resources\Client\SingleClientResource;
use App\Http\Services\ClientService;
use App\Models\Client;
use App\Models\Trinket;
use App\Repositories\Client\RetrieveSingleClient;
use Illuminate\Http\JsonResponse;

class SessionController extends ApiController
{
    public function writeOffVisit(WriteOffVisitRequest $request, ClientService $clientService): JsonResponse {
        $payload = $request->validated();
        $client = $clientService->writeOff($payload);
        $client = SingleClientResource::make($client);
        return $this->respondSuccess([
            'client' => $client
        ], 'Посещение успешно списано!');
    }

    public function writeOffSolarium(WriteOffSolariumRequest $request, ClientService $clientService): JsonResponse {
        $payload = $request->validated();
        $client = $clientService->writeOffSolarium($payload);
        $client = RetrieveSingleClient::retrieve($client);
        $client = SingleClientResource::make($client);
        return $this->respondSuccess([
            'client' => $client
        ], 'Посещение успешно списано!');
    }

    public function attach(Client $client, AttachTrinketRequest $request, ClientService $clientService): JsonResponse {
        if (!$client->trinket_can_given) {
            return $this->respondError('Невозможно прикрепить ключ!');
        }
        $trinket = Trinket::whereCode($request->get('code'))->first();
        if (!$trinket) {
            return $this->respondError('Ключ не найден!');
        }
        if ($trinket->active_session) {
            return $this->respondError('Ключ уже занят!');
        }
        $client->active_session->update([
            'trinket_id' => $trinket->id,
        ]);
        $client->update([
            'cached_trinket' => $request->get('code')
        ]);
        return $this->respondSuccess([
            'client' => SingleClientResource::make($client)
        ], 'Ключ успешно выдан!');
    }

    public function finish(Client $client, ClientService $clientService): JsonResponse {
        /*if (!$client->active_session) {
            return $this->respondError('Невозможно завершить сеанс, перезагрузите страницу!');
        }*/
        $clientService->finish($client);
        return $this->respondSuccess([
            'client' => SingleClientResource::make(Client::find($client->id))
        ], 'Посещение успешно завершено!');
    }
}
