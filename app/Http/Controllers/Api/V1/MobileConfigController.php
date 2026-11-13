<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MobileConfig\UpdateConfigRequest;
use App\Http\Resources\Mobile\ConfigResource;
use App\Http\Services\MobileConfigService;

class MobileConfigController extends Controller
{
    private MobileConfigService $service;

    public function __construct(MobileConfigService $service) {
        $this->service = $service;
    }

    public function show(): ConfigResource {
        return ConfigResource::make($this->service->getConfig());
    }

    public function update(UpdateConfigRequest $request): ConfigResource {
        return ConfigResource::make($this->service->updateConfig($request->validated()));
    }
}
