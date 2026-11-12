<?php

namespace App\Http\Controllers\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\Client\MembershipsResource;
use App\Http\Resources\Mobile\Client\ProfileResource;
use App\Http\Services\MobileClientService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    private MobileClientService $service;

    public function __construct(MobileClientService $service) {
        $this->service = $service;
    }

    public function me(Request $request): ProfileResource {
        return ProfileResource::make($this->service->getProfile($request->user('mobile')));
    }

    public function membership(Request $request): MembershipsResource {
        return MembershipsResource::make($this->service->getMemberships($request->user('mobile')));
    }
}
