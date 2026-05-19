<?php

namespace App\Http\Controllers\mobile\v1;

use App\Http\Controllers\api\v1\ApiController;
use App\Http\Controllers\Controller;
use App\Http\Resources\Client\SingleClientResource;
use App\Models\Client;
use App\Repositories\Client\RetrieveSingleClient;
use Illuminate\Http\Request;

class ProfileController extends ApiController
{
    public function getMyProfile(Request $request): SingleClientResource {
        $client = $this->loginWithMobileToken($request);
        $client = RetrieveSingleClient::retrieve($client);
        return SingleClientResource::make($client);
    }
}
