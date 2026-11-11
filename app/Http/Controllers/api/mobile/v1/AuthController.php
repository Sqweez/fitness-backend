<?php

namespace App\Http\Controllers\api\mobile\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\Auth\SendCodeRequest;
use App\Http\Requests\Mobile\Auth\VerifyCodeRequest;
use App\Http\Services\MobileAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function sendCode(SendCodeRequest $request, MobileAuthService $service): JsonResponse {
        $data = $request->validated();
        return response()->json($service->sendCode($data['phone']));
    }

    public function verify(VerifyCodeRequest $request, MobileAuthService $service): JsonResponse {
        $data = $request->validated();
        return response()->json(['token' => $service->verify($data['phone'], $data['code'])]);
    }

    public function logout(Request $request, MobileAuthService $service) {
        $service->logout($request->user('mobile'));
        return response()->noContent();
    }
}
