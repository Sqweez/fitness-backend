<?php

namespace App\Providers;

use App\Events\Chat\ChatMessageSent;
use App\Events\Chat\ChatRead;
use App\Listeners\Chat\BroadcastChatMessage;
use App\Listeners\Chat\BroadcastChatRead;
use App\Listeners\Chat\SendChatMessagePush;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ChatMessageSent::class => [
            BroadcastChatMessage::class,
            SendChatMessagePush::class,
        ],
        ChatRead::class => [
            BroadcastChatRead::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
