<?php

namespace App\Actions\Service;

use App\Http\Requests\Service\RestoreServiceRequest;
use App\Models\RestoredService;
use App\Models\ServiceSale;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Throwable;

class CreateRestoredServiceAction {

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws Exception
     * @throws Throwable
     */
    public function handle(RestoreServiceRequest $request, ServiceSale $serviceSale): Model {
        return \DB::transaction(function () use ($request, $serviceSale) {
            $restoredService = $serviceSale->restores()->create($request->validated());
            if ($request->has('document')) {
                $restoredService
                    ->addMedia($request->file('document'))
                    ->toMediaCollection(RestoredService::RESTORE_APPLICATION);
            }
            $transaction = $restoredService->transaction()->create([
                'client_id' => $restoredService->client_id,
                'user_id' => auth()->id(),
                'club_id' => $restoredService->service->club_id,
                'amount' => $restoredService->restore_price * -1,
                'description' => 'Списание средств за восстановление услуги ' . $restoredService->service->name,
            ]);
            $restoredService->client->increment('balance', $transaction->amount);
            return $restoredService;
        });

    }
}
