<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Concerns\ReturnsJsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    use ReturnsJsonResponse;

    public function loginWithMobileToken(Request $request) {
        $token = $request->header('T-Authorization', null);
        if (!$token) {
            return null;
        }
        $userId = base64_decode($token);
        return Client::whereKey($userId)->first();
    }
}
