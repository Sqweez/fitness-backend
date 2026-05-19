<?php

namespace App\Http\Controllers\api\v1;

use App\Actions\Stats\GetActiveClientsAction;
use App\Actions\Stats\GetClientsByClubAction;
use App\Actions\Stats\GetPriceSaleHistoryAction;
use App\Actions\Stats\GetUnlimitedEndingClientsAction;
use App\Actions\Stats\GetWrongBirthDateClients;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends ApiController
{
    public function getClientsByClub(GetClientsByClubAction $action): JsonResponse {
        return $this->respondSuccessNoReport($action->handle());
    }

    public function getActiveClients(GetActiveClientsAction $action): JsonResponse {
        return $this->respondSuccessNoReport($action->handle());
    }

    public function getPriceSaleHistory(Request $request, GetPriceSaleHistoryAction $action): JsonResponse {
        return $this->respondSuccessNoReport($action->handle($request));
    }

    public function getWrongBirthDateClients(GetWrongBirthDateClients $action): JsonResponse {
        return $this->respondSuccessNoReport($action->handle());
    }

    /* Клиенты с заканчивающимся безлимитом */
    public function getUnlimitedEndingClients(GetUnlimitedEndingClientsAction $action): JsonResponse {
        return $this->respondSuccessNoReport($action->handle());
    }
}
