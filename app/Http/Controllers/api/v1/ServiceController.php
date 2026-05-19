<?php

namespace App\Http\Controllers\api\v1;

use App\Actions\Service\ActivatePurchasedServiceAction;
use App\Actions\Service\CreateServiceAction;
use App\Actions\Service\CreateRestoredServiceAction;
use App\Actions\Service\RestorePurchasedServiceAction;
use App\Actions\Service\StopPurchasedProgramAction;
use App\Actions\Service\UnstopPurchasedProgramAction;
use App\Actions\Service\UpdatePurchaseServiceAction;
use App\Actions\Service\UpdateServiceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StopCardRequest;
use App\Http\Requests\Service\CreateServiceRequest;
use App\Http\Requests\Service\RestoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Http\Resources\Client\ClientPurchasedServices;
use App\Http\Resources\Client\SingleClientResource;
use App\Http\Resources\Service\ServicesListResource;
use App\Http\Resources\Service\SingleServiceResource;
use App\Models\Sale;
use App\Models\Service;
use App\Models\ServiceSale;
use App\Repositories\Client\RetrieveSingleClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class ServiceController extends ApiController
{
    public function getServiceTypes(): array {
        return Service::TYPES;
    }

    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index()
    {
        $services = Service::query()
            ->where('is_active', true)
            ->where('price', '!=', 5)
            ->with('club')
            ->get();
        return ServicesListResource::collection($services);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateServiceRequest $request
     * @param CreateServiceAction $action
     * @return JsonResponse
     */
    public function store(CreateServiceRequest $request, CreateServiceAction $action): JsonResponse {
        $service = $action->handle($request);
        return $this->respondSuccess([
            'service' => ServicesListResource::make($service)
        ], 'Услуга успешно создана!');
    }

    /**
     * Display the specified resource.
     *
     * @param Service $service
     * @return SingleServiceResource
     */
    public function show(Service $service): SingleServiceResource {
        return SingleServiceResource::make($service);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateServiceRequest $request
     * @param Service $service
     * @param UpdateServiceAction $action
     * @return JsonResponse
     */
    public function update(UpdateServiceRequest $request, Service $service, UpdateServiceAction $action): JsonResponse {
        $action->handle($request, $service);
        return $this->respondSuccess([
            'service' => ServicesListResource::make(Service::find($service->id))
        ], 'Услуга успешно обновлена!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        //Service::whereKey($id)->delete();
        Service::whereKey($id)->update(['is_active' => false]);
        return $this->respondSuccess([], 'Услуга успешно удалена!');
    }

    /*
     * Активирует купленную ранее тренировку
     * */
    public function activateService(Request $request, ServiceSale $service, ActivatePurchasedServiceAction $action): JsonResponse {
        $sale = $action->handle($request, $service);
        return $this->respondSuccess([
            'program' => ClientPurchasedServices::make($sale)
        ], 'Программа успешно активирована!');
    }

    /*
     * Редактирование купленной программы (только для суперадмина)
     * */
    public function updatePurchaseService(Request $request, ServiceSale $service, UpdatePurchaseServiceAction $action): JsonResponse {
        $sale = $action->handle($request, $service);
        return $this->respondSuccess([
            'program' => ClientPurchasedServices::make($sale)
        ], 'Программа успешно отредактирована!');
    }

    public function stopPurchasedService(StopCardRequest $request, ServiceSale $service, StopPurchasedProgramAction $action): JsonResponse {
        $action->handle($request->validated());
        return $this->respondSuccess([], 'Программа успешно приостановлена!');
    }

    public function unstopPurchasedService(ServiceSale $service, UnstopPurchasedProgramAction $action): JsonResponse {
        $action->handle($service);
        return $this->respondSuccess([], 'Программа успешно разморожена!');
    }
}
