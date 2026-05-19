<?php

namespace App\Actions\Service;

use App\Http\Requests\Service\UpdateServiceRequest;
use App\Models\Service;

class UpdateServiceAction {

    public function handle(UpdateServiceRequest $request, Service $service): void {
        $service->update($request->validated());
    }
}
