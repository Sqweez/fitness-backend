<?php

namespace App\Http\Controllers\Mobile\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\TrainerResource;
use App\Http\Services\TrainerService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TrainerController extends Controller
{
    private TrainerService $service;

    public function __construct(TrainerService $service) {
        $this->service = $service;
    }

    public function index(): AnonymousResourceCollection {
        return TrainerResource::collection($this->service->getTrainers(true));
    }
}
