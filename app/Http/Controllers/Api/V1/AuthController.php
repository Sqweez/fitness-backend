<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\User\AuthUserResource;
use App\Http\Services\StaffAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    private StaffAuthService $service;

    public function __construct(StaffAuthService $service) {
        $this->service = $service;
    }

    public function login(LoginRequest $request): JsonResponse {
        return response()->json([
            'access_token' => $this->service->login($request->validated()),
            'token_type' => 'bearer',
            'expires_in' => null,
        ]);
    }

    public function logout(Request $request): JsonResponse {
        $this->service->logout($request->user('api'));
        return response()->json(['message' => 'Вы успешно вышли из системы!']);
    }

    public function me(Request $request): JsonResponse {
        return response()->json(new AuthUserResource($request->user('api')));
    }
}
