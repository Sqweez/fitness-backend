<?php

namespace App\Http\Controllers\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\Client\MembershipHistoryRequest;
use App\Http\Requests\Mobile\Client\VisitsRequest;
use App\Http\Resources\Mobile\Client\MembershipsResource;
use App\Http\Resources\Mobile\Client\MembershipHistoryResource;
use App\Http\Resources\Mobile\Client\PassResource;
use App\Http\Resources\Mobile\Client\ProfileResource;
use App\Http\Resources\Mobile\Client\TrinketResource;
use App\Http\Resources\Mobile\Client\VisitsResource;
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

    public function membershipHistory(MembershipHistoryRequest $request): MembershipHistoryResource {
        $data = $request->validated();
        return MembershipHistoryResource::make($this->service->getMembershipHistory($request->user('mobile'), (int) $data['limit'], (int) $data['cursor']));
    }

    public function trinket(Request $request): TrinketResource {
        return TrinketResource::make($this->service->getTrinket($request->user('mobile')));
    }

    public function pass(Request $request): PassResource {
        return PassResource::make($this->service->getPass($request->user('mobile')));
    }

    public function visits(VisitsRequest $request): VisitsResource {
        $data = $request->validated();
        return VisitsResource::make($this->service->getVisits($request->user('mobile'), (int) $data['limit'], (int) $data['cursor']));
    }
}
