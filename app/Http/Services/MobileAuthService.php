<?php

namespace App\Http\Services;

use App\Models\Client;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class MobileAuthService
{
    public function sendCode(string $phone): array {
        return Cache::lock('mobile:auth:lock:' . $phone, 10)->block(3, function () use ($phone) {
            $client = $this->findClient($phone);
            $limits = [[1, 60], [3, 3600], [5, 86400]];
            $retryAfter = 0;
            foreach ($limits as [$attempts, $seconds]) {
                $key = 'mobile:auth:send:' . $phone . ':' . $seconds;
                if (RateLimiter::tooManyAttempts($key, $attempts)) {
                    $retryAfter = max($retryAfter, RateLimiter::availableIn($key));
                }
            }
            if ($retryAfter > 0) {
                $this->rateLimited($retryAfter);
            }

            $key = 'mobile:auth:code:' . $phone;
            $challenge = Cache::get($key);
            if (!$challenge || $challenge['client_id'] !== $client->id) {
                $challenge = ['id' => Str::random(32), 'client_id' => $client->id, 'attempts' => 0, 'expires_at' => now()->addMinutes(5)->timestamp];
                Cache::put($key, $challenge, 300);
            }
            foreach ($limits as [$attempts, $seconds]) {
                RateLimiter::hit('mobile:auth:send:' . $phone . ':' . $seconds, $seconds);
            }

            return ['code_length' => strlen(__hardcoded('111111')), 'retry_after_seconds' => 60];
        });
    }

    public function verify(string $phone, string $code): string {
        return Cache::lock('mobile:auth:lock:' . $phone, 10)->block(3, function () use ($phone, $code) {
            $client = $this->findClient($phone);
            $key = 'mobile:auth:code:' . $phone;
            $challenge = Cache::get($key);
            if (!$challenge || $challenge['client_id'] !== $client->id || $challenge['expires_at'] <= now()->timestamp) {
                $this->reject('CODE_EXPIRED', 'Срок действия кода истёк', 422);
            }
            if ($challenge['attempts'] >= 5) {
                $this->rateLimited($challenge['expires_at'] - now()->timestamp);
            }
            if (!hash_equals(__hardcoded('111111'), $code)) {
                $challenge['attempts']++;
                Cache::put($key, $challenge, $challenge['expires_at'] - now()->timestamp);
                $this->reject('INVALID_CODE', 'Неверный код подтверждения', 422);
            }

            if (!Cache::add('mobile:auth:consumed:' . $challenge['id'], true, 300)) {
                $this->reject('CODE_EXPIRED', 'Срок действия кода истёк', 422);
            }
            Cache::forget($key);

            return $client->createToken('mobile')->plainTextToken;
        });
    }

    public function logout(Client $client): void {
        $client->currentAccessToken()->delete();
    }

    private function findClient(string $phone): Client {
        $digits = substr($phone, 1);
        $clients = Client::query()->whereRaw(
            "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), '(', ''), ')', '') IN (?, ?)",
            [$digits, '8' . substr($digits, 1)]
        )->limit(2)->get();

        if ($clients->isEmpty()) {
            $this->reject('PHONE_NOT_FOUND', 'Клиент с таким номером телефона не найден', 404);
        }
        if ($clients->count() > 1) {
            $this->reject('PHONE_AMBIGUOUS', 'Обратитесь к администратору для уточнения номера', 409);
        }

        return $clients->first();
    }

    private function reject(string $code, string $message, int $status): void {
        throw new HttpResponseException(response()->json(['error' => ['code' => $code, 'message' => $message]], $status));
    }

    private function rateLimited(int $retryAfter): void {
        throw new HttpResponseException(response()->json([
            'error' => [
                'code' => 'RATE_LIMITED',
                'message' => 'Слишком много попыток. Попробуйте позже',
                'retry_after_seconds' => $retryAfter,
            ]
        ], 429)->header('Retry-After', $retryAfter));
    }
}
