<?php

namespace App\Http\Controllers\api\v1;

use App\Actions\Penalty\CreateClientPenaltyWriteOffAction;
use App\Http\Requests\Penalty\PenaltyWriteOffRequest;
use App\Http\Resources\Penalty\ClientPenaltyApprovementListResource;
use App\Models\ClientServicePenalty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PenaltyController extends ApiController
{
    public function index(): AnonymousResourceCollection {
        return ClientPenaltyApprovementListResource::collection(
            ClientServicePenalty::query()
                ->has('service')
                ->where('is_accepted', false)
                ->where('is_declined', false)
                ->with(['user', 'client:id,name,club_id', 'client.club:id,name', 'trainer', 'service.service'])
                ->get()
        );
    }

    public function store(PenaltyWriteOffRequest $request, CreateClientPenaltyWriteOffAction $action): JsonResponse {
        $action->handle($request->validated());
        return $this->respondSuccess([
            'message' => 'Запрос на штрафное списание услуги был успешно отправлен!'
        ]);
    }

    public function update(ClientServicePenalty $penalty, Request $request) {
        $penalty->update($request->all() + ['solver_id' => auth()->id()]);
        $message = $penalty->is_accepted ? 'подтверждено' : 'отклонено';
        return $this->respondSuccess(['message' => 'Списание успешно ' . $message]);
    }
}
