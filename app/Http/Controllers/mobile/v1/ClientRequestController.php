<?php

namespace App\Http\Controllers\mobile\v1;

use App\Http\Controllers\api\v1\ApiController;
use App\Http\Controllers\Controller;
use App\Models\ClientRequest;
use Illuminate\Http\Request;

class ClientRequestController extends ApiController
{
    public function store(Request $request) {
        $client = $this->loginWithMobileToken($request);
        $payload = $request->all();
        if ($client) {
            $payload['client_id'] = $client->id;
        }
        $clientRequest = ClientRequest::create($payload);
        $message = '';
        if ($clientRequest->request_type_id === __hardcoded(1)) {
            $message = 'Менеджер TopStar свяжется с вами в ближайшее время';
        } else {
            $message = 'Менеджер TopStar свяжется с вами в ближайшее время';
        }

        return $this->respondSuccess([
            'message' => $message
        ]);
    }
}
