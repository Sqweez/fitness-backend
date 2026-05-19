<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

trait ReturnsJsonResponse {


    /**
     * Отправка успешного ответа с сервера
     * @param array $data
     * @param null $message
     * @return JsonResponse
     */

    public function respondSuccess(array $data = [], $message = null): JsonResponse {
        $message = $message ?: __('messages.default_success');
        return response()->json(
            array_merge([
                'success' => true,
                'message' => $message
            ], $data)
        );
    }

    /**
     * Отправка ошибки
     * @param mixed $message
     * @param int $errorCode
     * @param array $data
     * @return JsonResponse
     */

    public function respondError($message = null, int $errorCode = 500, array $data = []): JsonResponse {
        return response()->json($data + [
            'success' => false,
            'message' => $message ?? __hardcoded('На сервере произошла ошибка')
        ], $errorCode);
    }

    public function respondErrorNoReport($message = null, int $errorCode = 500, array $data = []): JsonResponse {
        return $this->respondError($message, $errorCode, $data + ['unreportable' => true]);
    }

    public function respondSuccessNoReport(array $data): JsonResponse {
        return $this->respondSuccess($data + ['unreportable' => true]);
    }
}
