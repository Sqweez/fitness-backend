<?php

namespace App\Http\Controllers\api\v2;

use App\Http\Controllers\api\v1\ApiController;
use App\Http\Requests\Economy\GetEconomyReportRequest;
use App\Http\Services\EconomyReportService;
use Illuminate\Http\JsonResponse;

class EconomyReportController extends ApiController
{
    public function topUps(GetEconomyReportRequest $request, EconomyReportService $service): JsonResponse
    {
        return $this->respondSuccessNoReport($service->getTopUps($request->filters()));
    }

    public function programs(GetEconomyReportRequest $request, EconomyReportService $service): JsonResponse
    {
        return $this->respondSuccessNoReport($service->getPrograms($request->filters()));
    }

    public function guests(GetEconomyReportRequest $request, EconomyReportService $service): JsonResponse
    {
        return $this->respondSuccessNoReport($service->getGuests($request->filters()));
    }

    public function newClients(GetEconomyReportRequest $request, EconomyReportService $service): JsonResponse
    {
        return $this->respondSuccessNoReport($service->getNewClients($request->filters()));
    }
}
