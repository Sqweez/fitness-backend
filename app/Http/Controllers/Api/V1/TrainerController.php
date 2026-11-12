<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trainer\StoreTrainerRequest;
use App\Http\Requests\Trainer\UpdateTrainerRequest;
use App\Http\Resources\Trainer\TrainerResource;
use App\Http\Services\TrainerService;
use App\Models\Trainer;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TrainerController extends Controller
{
    private TrainerService $service;

    public function __construct(TrainerService $service) {
        $this->service = $service;
    }

    public function index(): AnonymousResourceCollection {
        return TrainerResource::collection($this->service->getTrainers(false));
    }

    public function store(StoreTrainerRequest $request): TrainerResource {
        return TrainerResource::make($this->service->createTrainer($request->validated()));
    }

    public function show(Trainer $trainer): TrainerResource {
        return TrainerResource::make($this->service->getTrainer($trainer));
    }

    public function update(UpdateTrainerRequest $request, Trainer $trainer): TrainerResource {
        return TrainerResource::make($this->service->updateTrainer($trainer, $request->validated()));
    }

    public function destroy(Trainer $trainer): Response {
        $this->service->deleteTrainer($trainer);
        return response()->noContent();
    }
}
