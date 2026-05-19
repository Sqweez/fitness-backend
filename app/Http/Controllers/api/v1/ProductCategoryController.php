<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends ApiController
{
    public function index(): \Illuminate\Http\JsonResponse {
        return $this->respondSuccessNoReport([
            'categories' => ProductCategory::query()->get()
        ]);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse {
        $name = $request->get('name');
        $category = ProductCategory::query()->create([
            'name' => $name
        ])->refresh();

        return $this->respondSuccess([
            'category' => $category
        ], 'Категория успешно добавлена!');
    }
}
