<?php

namespace App\Actions\Service;

use App\Http\Requests\Service\CreateServiceRequest;
use App\Http\Resources\Service\ServicesListResource;
use App\Models\Service;

class CreateServiceAction {

    public function handle(CreateServiceRequest $request) {
        $validatedData = $request->validated();
        return Service::create($validatedData);
    }
}
