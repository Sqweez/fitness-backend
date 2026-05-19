<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WithDrawal\CreateWithDrawalRequest;
use App\Models\WithDrawal;
use Illuminate\Http\Request;

class WithDrawalController extends ApiController
{
    public function store(CreateWithDrawalRequest $request): \Illuminate\Http\JsonResponse {
        WithDrawal::query()
            ->create($request->validated());
        return $this->respondSuccess([], 'Списание успешно создано!');
    }
}
