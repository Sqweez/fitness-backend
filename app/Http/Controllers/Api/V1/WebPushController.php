<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebPush\SubscribeRequest;
use App\Http\Requests\WebPush\UnsubscribeRequest;
use App\Http\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class WebPushController extends Controller
{
    private WebPushService $service;

    public function __construct(WebPushService $service) {
        $this->service = $service;
    }

    public function config(): JsonResponse {
        return response()->json(['data' => ['public_key' => config('webpush.public_key')]]);
    }

    public function subscribe(SubscribeRequest $request): Response {
        $data = $request->validated();
        $this->service->subscribe($request->user(), $data['endpoint'], $data['keys']['p256dh'], $data['keys']['auth'], $data['content_encoding']);
        return response()->noContent();
    }

    public function unsubscribe(UnsubscribeRequest $request): Response {
        $this->service->unsubscribe($request->user(), $request->validated()['endpoint']);
        return response()->noContent();
    }
}
