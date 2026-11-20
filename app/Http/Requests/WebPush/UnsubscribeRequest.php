<?php

namespace App\Http\Requests\WebPush;

use Illuminate\Foundation\Http\FormRequest;

class UnsubscribeRequest extends FormRequest
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'endpoint' => ['required', 'string', 'max:2048'],
        ];
    }
}
