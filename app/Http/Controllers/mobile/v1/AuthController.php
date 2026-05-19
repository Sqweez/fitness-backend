<?php

namespace App\Http\Controllers\mobile\v1;

use App\Http\Controllers\api\v1\ApiController;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class AuthController extends ApiController
{
    public function login(Request $request) {
        $phone = $request->get('phone', '');
        $password = $request->get('password', '');
        $client = Client::wherePhone('+' . $phone)->first();
        if (!$client) {
            return $this->respondError('Клиент с данным номером не зарегистрирован в нашей базе!', 404);
        }
        if ($password !== $client->mobile_password) {
            return $this->respondError('Неверный пароль, обратитесь к менеджеру TopStar');
        }

        $token = base64_encode($client->id);
        return $this->respondSuccess([
            'token' => $token
        ]);
    }

    public function me(Request $request) {
        $client = $this->loginWithMobileToken($request);
        if (!$client) {
            return $this->respondError('Данные авторизации устарели');
        }
        return $client;
    }
}
