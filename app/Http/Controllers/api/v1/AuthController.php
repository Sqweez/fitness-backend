<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Resources\User\AuthUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends ApiController
{

    public function username(): string {
        return 'phone';
    }

    public function login(Request $request) {
        if ($request->has('pass')) {
            return $this->loginViaCard($request->get('pass'));
        }

        if ($request->has('login') && $request->has('password')) {
            return $this->loginViaCredentials(unmask_phone($request->get('login')), $request->get('password'));
        }

        return $this->unauthorized();
    }

    public function logout(): JsonResponse {
        Auth::logout();
        return response()->json([
            'message' => 'Вы успешно вышли из системы!'
        ]);
    }

    public function refresh(): JsonResponse {
        return $this->respondWithToken(Auth::refresh());
    }

    public function me(): JsonResponse {
        return response()->json(AuthUserResource::make(Auth::user()));
    }

    private function loginViaCard($pass): JsonResponse {
        $user = User::query()
            ->pass($pass)
            ->active()
            ->first();

        if (!$user || !$token = Auth::login($user)) {
            return $this->respondError('Карта не привязана к пользователю!', 403);
        }
        return $this->respondWithToken($token);
    }

    private function loginViaCredentials($login, $password): JsonResponse {
        $user = User::where('phone', $login)->active()->first();
        if (!$user) {
            return $this->respondError('Учетная запись не найдена или неактивна', 403);
        }
        if (!$token = Auth::attempt(['phone' => $login, 'password' => $password, 'is_active' => true])) {
            return $this->respondError('Неверные логин и пароль!', 403);
        }
        return $this->respondWithToken($token);
    }

    protected function respondWithToken($token): JsonResponse {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60 * 60 * 60 * 1000
        ]);
    }
}
