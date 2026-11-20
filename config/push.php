<?php

return [
    /*
     * Active push delivery driver. Add a provider class to "drivers"
     * and switch PUSH_DRIVER to start real delivery.
     */
    'driver' => env('PUSH_DRIVER', 'log'),

    'drivers' => [
        'log' => App\Push\LogPushSender::class,
    ],
];
