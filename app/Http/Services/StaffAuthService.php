<?php

namespace App\Http\Services;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;

class StaffAuthService
{
    public function login(array $data): string {
        if (isset($data['pass'])) {
            $user = User::query()->pass($data['pass'])->active()->first();
            if (!$user) {
                $this->reject('Карта не привязана к пользователю!');
            }
        } else {
            $user = User::where('phone', unmask_phone($data['login']))->active()->first();
            if (!$user) {
                $this->reject('Учетная запись не найдена или неактивна');
            }
            if (!Hash::check($data['password'], $user->password)) {
                $this->reject('Неверные логин и пароль!');
            }
        }

        return $user->createToken('dashboard')->plainTextToken;
    }

    public function logout(User $user): void {
        $user->currentAccessToken()->delete();
    }

    private function reject(string $message): void {
        throw new HttpResponseException(response()->json(['success' => false, 'message' => $message], 403));
    }
}
