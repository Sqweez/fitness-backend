<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Club\UpdateClubRequest;
use App\Http\Resources\Club\ClubResource;
use App\Http\Resources\Club\ClubListResource;
use App\Http\Services\ClubService;
use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClubController extends Controller
{
    private ClubService $service;

    public function __construct(ClubService $service) {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection {
        $clubs = Club::all();
        return ClubListResource::collection($clubs);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param Club $club
     * @return ClubResource
     */
    public function show(Club $club): ClubResource {
        return ClubResource::make($this->service->getClub($club));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateClubRequest $request
     * @param Club $club
     * @return ClubResource
     */
    public function update(UpdateClubRequest $request, Club $club): ClubResource {
        return ClubResource::make($this->service->updateClub($club, $request->validated()));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
