<?php

namespace App\Push;

use App\Models\PushDevice;
use Illuminate\Support\Collection;

/**
 * Delivery provider contract. To connect a provider, implement this interface
 * and register the class in config/push.php under its driver name.
 */
interface PushSender
{
    /**
     * @param Collection<int, PushDevice> $devices Never empty
     */
    public function send(Collection $devices, PushMessage $message): void;
}
