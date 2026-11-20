<?php

return [
    /*
     * VAPID keys for browser push to the admin panel.
     * Generate with: php artisan tinker --execute='print_r(Minishlink\WebPush\VAPID::createVapidKeys());'
     */
    'public_key' => env('VAPID_PUBLIC_KEY'),
    'private_key' => env('VAPID_PRIVATE_KEY'),
    'subject' => env('VAPID_SUBJECT'),
];
