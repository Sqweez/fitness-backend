<?php

namespace App\Http\Controllers\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\ConfigResource;
use App\Http\Services\MobileConfigService;

class ConfigController extends Controller
{
    private MobileConfigService $service;

    public function __construct(MobileConfigService $service) {
        $this->service = $service;
    }

    public function show(): ConfigResource {
        return ConfigResource::make($this->service->getConfig());
    }
}
