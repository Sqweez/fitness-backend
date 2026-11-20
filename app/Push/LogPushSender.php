<?php

namespace App\Push;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Placeholder driver until a real provider is connected: records which devices would receive what.
 */
class LogPushSender implements PushSender
{
    public function send(Collection $devices, PushMessage $message): void {
        Log::info('Push notification', [
            'device_ids' => $devices->pluck('id')->all(),
            // Body is not logged: it contains private chat text
            'data' => $message->data,
        ]);
    }
}
