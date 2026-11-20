<?php

namespace App\Http\Services;

use App\Models\Client;
use App\Models\PushDevice;
use App\Push\PushMessage;
use App\Push\PushSender;
use Laravel\Sanctum\PersonalAccessToken;

class PushService
{
    private PushSender $sender;

    public function __construct(PushSender $sender) {
        $this->sender = $sender;
    }

    public function registerDevice(Client $client, PersonalAccessToken $accessToken, string $token, string $platform): void {
        // The same device token moves to whoever is signed in on the device now
        PushDevice::query()->updateOrCreate(['token' => $token], [
            'client_id' => $client->id,
            'personal_access_token_id' => $accessToken->id,
            'platform' => $platform,
        ]);
    }

    public function sendToClient(Client $client, PushMessage $message): void {
        $devices = $client->pushDevices()->get();
        if ($devices->isEmpty()) {
            return;
        }

        $this->sender->send($devices, $message);
    }
}
