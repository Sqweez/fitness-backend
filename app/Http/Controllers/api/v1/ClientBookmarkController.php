<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Client\ClientBookmarkResource;
use App\Models\Client;
use App\Models\ClientBookmark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class ClientBookmarkController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection {
        $clients =  ClientBookmark::query()
            ->where('user_id', auth()->id())
            ->with('client')
            ->with('client.avatar')
            ->get();

        return ClientBookmarkResource::collection($clients);
    }

    public function deleteBookmark($id): JsonResponse {
        ClientBookmark::query()
            ->where('client_id', $id)
            ->where('user_id', auth()->id())
            ->delete();
        return $this->respondSuccess([], 'Клиент удален из закладок');
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function store(Request $request): AnonymousResourceCollection {
        ClientBookmark::query()
            ->updateOrCreate([
                'client_id' => $request->get('client_id'),
                'user_id' => auth()->id()
            ], []);


        return $this->index();
    }
}
