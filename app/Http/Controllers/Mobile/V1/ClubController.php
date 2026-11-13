<?php

namespace App\Http\Controllers\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Club\ClubResource;
use App\Http\Services\ClubService;
use Illuminate\Http\Request;

class ClubController extends Controller
{
    private ClubService $service;

    public function __construct(ClubService $service) {
        $this->service = $service;
    }

    public function show(Request $request): ClubResource {
        return ClubResource::make($this->service->getClientClub($request->user('mobile')));
    }
}
