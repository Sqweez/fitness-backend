<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\RestoredServicesApprovementListResource;
use App\Models\RestoredService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RequestController extends Controller
{
    public function getPenaltiesRequests(PenaltyController $controller): AnonymousResourceCollection {
        return $controller->index();
    }

    public function getRestoredServiceRequests(RestoredServiceController $controller): AnonymousResourceCollection {
        return $controller->index();
    }
}
