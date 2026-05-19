<?php

namespace App\Http\Controllers\api\v1;

use App\Actions\Client\CreateClientReplenishmentAction;
use App\Actions\Client\GetClientHistoryAction;
use App\Actions\Client\GetClientServiceHistoryAction;
use App\Http\Requests\Client\CreateClientRequest;
use App\Http\Requests\Client\TopUpClientAccountRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\Client\ClientListResource;
use App\Http\Resources\Client\SingleClientResource;
use App\Http\Services\ClientService;
use App\Http\Services\TransactionService;
use App\Models\Client;
use App\Models\ClientCustomService;
use App\Models\Pass;
use App\Models\Transaction;
use App\Repositories\Client\RetrieveSingleClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClientController extends ApiController {
    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection {
        $clients = Client::query()->with(['club', 'pass', 'active_session.trinket', 'registrar'])->get();
        return ClientListResource::collection($clients);
    }

    public function search(Request $request) {
        $search = $request->get('search');
        if (!$search) {
            return ClientListResource::collection([]);
        }
        $clients = Client::query()
            ->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', prepare_search_string($search))
                    ->orWhere('phone', 'like', prepare_search_string($search))
                    ->orWhere('cached_pass', $search)
                    ->orWhere('cached_trinket', $search);
            })
            ->with(['club', /*'pass', 'active_session.trinket'*/])
            ->get();
        return ClientListResource::collection($clients);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateClientRequest $request
     * @param ClientService $clientService
     * @return JsonResponse
     */
    public function store(CreateClientRequest $request, ClientService $clientService): JsonResponse {
        $validatedData = $request->validated();
        $client = $clientService->createClient($validatedData);
        $client = SingleClientResource::make($client);
        return $this->respondSuccess(['client' => $client], 'Клиент создан успешно!');
    }

    /**
     * Display the specified resource.
     *
     * @param Client $client
     * @return SingleClientResource
     */
    public function show(Client $client) {
        $client = RetrieveSingleClient::retrieve($client);
        return SingleClientResource::make($client);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateClientRequest $request
     * @param Client $client
     * @param ClientService $clientService
     * @return JsonResponse
     */
    public function update(UpdateClientRequest $request, Client $client, ClientService $clientService): JsonResponse {
        $validatedData = $request->validated();
        $client = $clientService->updateClient($client, $validatedData);
        $client = RetrieveSingleClient::retrieve($client);
        $client = SingleClientResource::make($client);
        return $this->respondSuccess(['client' => $client], 'Данные о клиенте успешно обновлены!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse {
        $client = Client::find($id);
        Pass::whereCode($client->id)->update([
            'passable_id' => null,
            'passable_type' => null,
        ]);
        $client->update(['cached_pass' => null]);
        Client::whereKey($id)->delete();
        return $this->respondSuccess([], 'Клиент успешно удален');
    }

    public function topUpClientAccount(TopUpClientAccountRequest $request, Client $client, CreateClientReplenishmentAction $action): JsonResponse {
        $action->handle($request, $client);
        return $this->respondSuccess(['balance' => $client->balance], 'Баланс клиента успешно пополнен');
    }

    public function getServiceHistory(Client $client, Request $request, GetClientServiceHistoryAction $action): JsonResponse {
        $report = $action->handle($client, $request->get('service_id'));
        return $this->respondSuccessNoReport([
            'report' => $report
        ]);
    }

    public function getClientHistory(Client $client, Request $request, GetClientHistoryAction $action): JsonResponse {
        $history = $action->handle($client, $request->get('start'), $request->get('finish'));
        return $this->respondSuccessNoReport([
            'history' => $history
        ]);
    }

    public function remakePass(Client $client, Request $request) {
        $code = $request->get('pass');
        $pass = Pass::whereCode($code)->first();
        if ($pass) {
            return $this->respondError('Данная карта уже привязана к другому клиенту или сотруднику!');
        }
        $client->pass()->delete();
        $client->pass()->create(['code' => $code]);
        $clientCustomService = ClientCustomService::create([
            'client_id' => $client->id,
            'user_id' => auth()->id(),
            'club_id' => auth()->user()->club_id,
            'custom_service_id' => __hardcoded(1),
        ]);
        Transaction::create([
            'transactional_type' => ClientCustomService::class,
            'transactional_id' => $clientCustomService->id,
            'client_id' => $client->id,
            'user_id' => auth()->id(),
            'club_id' => auth()->user()->club_id,
            'amount' => __hardcoded(1000) * -1,
            'description' => 'Списание средств за переоформление карты'
        ]);
        $client->update([
            'balance' => $client->balance - __hardcoded(1000),
            'cached_pass' => $code
        ]);
        return $this->respondSuccess([], 'Карта успешно переоформлена!');
    }
}
