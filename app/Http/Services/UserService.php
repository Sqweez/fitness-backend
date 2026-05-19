<?php

namespace App\Http\Services;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserService {

    /**
     * @throws Throwable
     */
    public function createUser($payload = []): ?User {
        return DB::transaction(function () use ($payload) {
            $user = User::create(Arr::except($payload, ['pass']));
            if (isset($payload['pass'])) {
                $pass = PassService::createPass($payload['pass']);
                $user->pass()->save($pass);
            }
            if (isset($payload['photo'])) {
                $user->addMedia($payload['photo'])->toMediaCollection(User::MEDIA_AVATAR);
            }
            $user->roles()->sync($payload['roles']);
            $user->clubs()->sync($payload['clubs']);
            return $user;
        });
    }

    public function updateUser(User $user, $payload = []): User {
        return DB::transaction(function () use ($payload, $user) {
            if (isset($payload['password'])) {
                $payload['password'] = \Hash::make($payload['password']);
            }
            $user->update($payload);
            if (isset($payload['pass'])) {
                $user->pass()->delete();
                $pass = PassService::createPass($payload['pass']);
                $user->pass()->save($pass);
            }
            if (isset($payload['photo'])) {
                $oldMedia = $user->getFirstMedia(User::MEDIA_AVATAR);
                if ($oldMedia) {
                    try {
                        $oldMedia->delete();
                    } catch (\Exception $exception) {
                        \Log::error($exception->getMessage());
                    }
                }
                $user->addMedia($payload['photo'])->toMediaCollection(User::MEDIA_AVATAR);
            }
            $user->roles()->sync($payload['roles']);
            $user->clubs()->sync($payload['clubs_id']);
            return $user;
        });
    }

    public function deleteUser(User $user) {
        $user->pass()->delete();
        $user->delete();
    }
}
