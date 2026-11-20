<?php

namespace App\Http\Controllers\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\Push\RegisterPushTokenRequest;
use App\Http\Services\PushService;
use Illuminate\Http\Response;

class PushTokenController extends Controller
{
    public function store(RegisterPushTokenRequest $request, PushService $service): Response {
        $data = $request->validated();
        $client = $request->user('mobile');
        $service->registerDevice($client, $client->currentAccessToken(), $data['token'], $data['platform']);
        return response()->noContent();
    }
}
