<?php

namespace App\Http\Controllers\api\v1;

use App\Actions\Sale\ProductSaleAction;
use App\Actions\Sale\ServiceSaleAction;
use App\Http\Requests\Sale\CreateProductSaleRequest;
use App\Http\Requests\Sale\CreateServiceSaleRequest;
use App\Http\Resources\Client\SingleClientResource;
use App\Http\Resources\Product\ProductsListResource;
use App\Models\Client;
use App\Models\Product;
use App\Repositories\Client\RetrieveSingleClient;
use Illuminate\Http\JsonResponse;

class SaleController extends ApiController
{
    public function createServiceSale(CreateServiceSaleRequest $request, ServiceSaleAction $action): JsonResponse {
        $validatedData = $request->validated();
        $client = $action->handle($validatedData);
        return $this->respondSuccess(
            [
                'client' => SingleClientResource::make($client)
            ],
            $this->getSuccessMessage($validatedData)
        );
    }

    public function createProductSale(CreateProductSaleRequest $request, ProductSaleAction $action): JsonResponse {
        $validatedData = $request->validated();
        $action->handle($validatedData);
        $client = Client::find($validatedData['client_id']);
        $client = RetrieveSingleClient::retrieve($client);
        return $this->respondSuccess(
            [
                'client' => SingleClientResource::make($client),
                'product' => ProductsListResource::make(Product::find($validatedData['product_id'])),
            ],
            'Товар успешно продан!');
    }

    private function getSuccessMessage(array $data) {
        switch ($data) {
            case isset($data['service_id']) && empty($data['is_prolongation']):
                return __('messages.service_sale_success');
            case isset($data['is_prolongation']):
                return __('messages.service_prolong_success');
            default:
                return __('messages.default_success');
        }
    }
}
