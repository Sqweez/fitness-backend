<?php

namespace App\Http\Controllers\api\v1;

use App\Actions\Service\AcceptServiceRestorationAction;
use App\Actions\Service\CreateRestoredServiceAction;
use App\Actions\Service\DeclineServiceRestorationAction;
use App\Actions\Service\RestorePurchasedServiceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Service\RestoreServiceRequest;
use App\Http\Resources\Client\RestoredServicesApprovementListResource;
use App\Http\Resources\Client\SingleClientResource;
use App\Models\RestoredService;
use App\Models\ServiceSale;
use App\Repositories\Client\RetrieveSingleClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Throwable;

class RestoredServiceController extends ApiController
{
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection {
        $restoredServices = RestoredService::query()
            ->where('is_accepted', false)
            ->where('is_declined', false)
            ->with(['service.club', 'client:id,name', 'user:id,name'])
            ->get();

        return RestoredServicesApprovementListResource::collection(
            $restoredServices
        );
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig|Throwable
     */
    public function store(
        RestoreServiceRequest $request,
        ServiceSale $service,
        CreateRestoredServiceAction $action,
        RestorePurchasedServiceAction $restore
    ): JsonResponse {
        $restoredService = $action->handle($request, $service);
        $restore->handle($restoredService);
        if ($restoredService->is_accepted) {
            $message = 'Услуга успешно была восстановлена!';
        } else {
            $message = 'Запрос на восстановление услуги был отправлен!';
        }
        return $this->respondSuccess([
            'client' => SingleClientResource::make(RetrieveSingleClient::retrieve($restoredService->client))
        ], $message);
    }

    /**
     * @throws Throwable
     */
    public function update(
        Request $request,
        RestoredService $restored,
        DeclineServiceRestorationAction $declineAction,
        AcceptServiceRestorationAction $acceptAction,
        RestorePurchasedServiceAction $restoreAction
    ): JsonResponse {
        if ($request->has('is_accepted')) {
            $restored = $acceptAction->handle($restored);
            $restoreAction->handle($restored);
            return $this->respondSuccess();
        }
        if ($request->has('is_declined')) {
            $declineAction->handle($restored);
            return $this->respondSuccess();
        }
        return $this->respondSuccess();
    }
}
