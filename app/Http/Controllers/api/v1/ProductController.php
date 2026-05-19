<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\CreateProductBatchRequest;
use App\Http\Requests\Product\CreateProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\Product\ProductBatchesInformationResource;
use App\Http\Resources\Product\ProductsListResource;
use App\Models\Club;
use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection {
        $products = Product::query()
            ->when($request->has('store_id'), function ($query) {
                return $query->whereHas('batches', function ($q) {
                    return $q->where('store_id', \request('store_id'));
                });
            })
            ->with(['batches.club', 'category'])
            ->get();

        return ProductsListResource::collection($products);
    }

    public function search(Request $request): AnonymousResourceCollection {
        $search = $request->get('search');
        if (!$search) {
            return ProductsListResource::collection([]);
        }
        $products = Product::query()
            ->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', prepare_search_string($search))
                    ->orWhere('barcode', 'like', prepare_search_string($search));
            })
            ->where('product_type_id', $request->get('type', 1))
            ->when($request->has('store_id'), function ($query) {
                return $query->whereHas('batches', function ($q) {
                    return $q->where('store_id', \request('store_id'));
                });
            })
            ->with(['batches.club', 'category'])
            ->get();

        return ProductsListResource::collection($products);
    }

    public function store(CreateProductRequest $request): JsonResponse {
        $product = Product::create($request->validated())->refresh();
        $batches = $request->get('batches', []);
        foreach ($batches as $batch) {
            $product->batches()->create(array_merge_recursive($batch, [
                'user_id' => auth()->id(),
                'initial_quantity' => $batch['quantity'],
                'purchase_price' => 0
            ]));
        }
        return $this->respondSuccess([
            'product' => new ProductsListResource($product)
        ], 'Товар успешно создан!');
    }

    public function update(UpdateProductRequest $request, Product $product): ProductsListResource {
        $product->update($request->validated());
        $product->refresh();
        return new ProductsListResource($product);
    }

    public function destroy($id) {
        Product::whereKey($id)
            ->update(['barcode' => null]);
        Product::whereKey($id)->delete();
        return $this->respondSuccessNoReport([]);
    }

    public function createProductBatch(Product $product, CreateProductBatchRequest $request): JsonResponse {
        $validatedData = $request->validated();
        foreach ($validatedData['batches'] as $batch) {
            $product->batches()->create([
                'quantity' => $batch['quantity'],
                'initial_quantity' => $batch['quantity'],
                'purchase_price' => $validatedData['purchase_price'],
                'store_id' => $batch['id']
            ]);
        }
        //$product->batches()->create($request->validated());
        $product->refresh();
        $product->load('batches.club');
        return $this->respondSuccess([
            'quantities' => $product->collectQuantities()
        ], 'Количество товара успешно обновлено');
    }

    public function getProductBatchesInformation(Product $product): AnonymousResourceCollection {
        $batches = ProductBatch::query()
            ->where('product_id', $product->id)
            ->with(['user', 'club'])
            ->orderByDesc('created_at')
            ->get();

        return ProductBatchesInformationResource::collection($batches);
    }
}
