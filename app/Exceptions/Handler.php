<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e) {
        if ($request->is('api/mobile/v1/*')) {
            if ($e instanceof ValidationException) {
                return response()->json(['error' => ['code' => 'VALIDATION_ERROR', 'message' => $e->validator->errors()->first()]], 422);
            }
            if ($e instanceof AuthenticationException) {
                return response()->json(['error' => ['code' => 'UNAUTHORIZED', 'message' => 'Необходима авторизация']], 401);
            }
            if ($e instanceof ThrottleRequestsException || $e instanceof LockTimeoutException) {
                $retryAfter = $e instanceof ThrottleRequestsException ? (int) $e->getHeaders()['Retry-After'] : 1;
                return response()->json(['error' => [
                    'code' => 'RATE_LIMITED',
                    'message' => 'Слишком много запросов. Попробуйте позже',
                    'retry_after_seconds' => $retryAfter,
                ]], 429)->header('Retry-After', $retryAfter);
            }
        }
        if ($e instanceof ValidationException)
            return response()->json([
                'message' => __hardcoded('Переданы неверные данные!'),
                'errors' => $e->validator->getMessageBag(),
                'success' => false,
                'invalid' => true,
            ], 422);
        if ($e instanceof AuthenticationException && $request->expectsJson())
            return response()->json([
                'message' => __hardcoded('Необходима авторизация'),
                'success' => false,
            ], 401);
        return parent::render($request, $e);
    }
}
