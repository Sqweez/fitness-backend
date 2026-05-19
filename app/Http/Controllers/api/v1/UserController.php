<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\User\SingleUserResource;
use App\Http\Resources\User\UserListResource;
use App\Http\Services\UserService;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class UserController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection {
        $users =  User::query()
            ->with(['clubs', 'roles'])
            ->when(!$request->has('with-inactive'), function ($q) {
                return $q->where('is_active', true);
            })
            ->get();
        return UserListResource::collection($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateUserRequest $request
     * @param UserService $userService
     * @return JsonResponse
     * @throws \Throwable
     */
    public function store(CreateUserRequest $request, UserService $userService): JsonResponse {
        $validatedData = $request->validated();
        $user = $userService->createUser($validatedData);
        $userResource = UserListResource::make($user);
        return $this->respondSuccess(['user' => $userResource], 'Пользователь успешно создан');
    }

    /**
     * Display the specified resource.
     *
     * @param User $user
     * @return SingleUserResource
     */
    public function show(User $user): SingleUserResource {
        return SingleUserResource::make($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateUserRequest $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, User $user, UserService $userService)
    {
        $validatedData = $request->validated();
        $_user = $userService->updateUser($user, $validatedData);
        $userResource = UserListResource::make($_user);
        return $this->respondSuccess(['user' => $userResource], 'Пользователь успешно обновлен');
    }

    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public function uploadPhoto(Request $request, User $user): JsonResponse {
        $user->media()->forceDelete();
        $user
            ->addMedia($request->file('photo'))
            ->toMediaCollection(User::MEDIA_AVATAR);
        $userResource = SingleUserResource::make($user);
        return $this->respondSuccess(['user' => $userResource], 'Фото загружено!');
    }

    public function chooseWorkingClub(User $user, Request $request): JsonResponse {
        $club_id = $request->get('club_id');
        $user->update(['club_id' => $club_id]);
        return $this->respondSuccess([], 'Рабочее место успешно выбрано!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     * @param UserService $userService
     * @return JsonResponse
     */
    public function destroy(User $user, UserService $userService): JsonResponse {
        $userService->deleteUser($user);
        return $this->respondSuccess([], 'Сотрудник успешно удален!');
    }

    public function restoreUser(User $user) {
        $user->restore();
        $user->update(['is_active' => true]);
        return $this->respondSuccess([], 'Сотрудник успешно восстановлен!');
    }
}
