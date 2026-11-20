<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
}, ['guards' => ['api']]);

Broadcast::channel('chat.client.{clientId}', function (Client $client, $clientId) {
    return $client->id === (int) $clientId;
}, ['guards' => ['mobile']]);

Broadcast::channel('chat.club.{clubId}', function (User $user, $clubId) {
    return Gate::forUser($user)->allows('manage-chats')
        && $user->clubs()->whereKey((int) $clubId)->exists();
}, ['guards' => ['api']]);
