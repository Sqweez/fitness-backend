<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Both client (mobile) and staff Sanctum tokens may authorize private channels
        Broadcast::routes(['prefix' => 'api', 'middleware' => ['auth:mobile,api']]);

        require base_path('routes/channels.php');
    }
}
